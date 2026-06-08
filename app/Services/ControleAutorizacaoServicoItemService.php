<?php

namespace App\Services;

use App\Enums\StatusControleNotaFiscalNota;
use App\Models\AsEscopo;
use App\Models\Construtora;
use App\Models\ControleNotaFiscalItem;

class ControleAutorizacaoServicoItemService
{
    public function __construct(
        protected AutorizacaoServicoService $autorizacaoServicoService,
    ) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function persistir(ControleNotaFiscalItem $item, array $dados): void
    {
        $item->loadMissing(['asEscopo', 'autorizacaoServico.construtora', 'controleNotaFiscal', 'notasFiscais']);

        $escopoBloqueado = $item->autorizacaoServico !== null;
        $asEscopoOriginalId = $item->as_escopo_id;
        $asEscopoInformado = array_key_exists('as_escopo_id', $dados);
        $asEscopoId = $escopoBloqueado
            ? $item->as_escopo_id
            : ($asEscopoInformado
                ? (filled($dados['as_escopo_id']) ? (int) $dados['as_escopo_id'] : null)
                : $item->as_escopo_id);
        $asEscopo = $asEscopoId ? AsEscopo::query()->find($asEscopoId) : null;
        $numeroComplemento = $item->numero_complemento;
        $escopoAlterado = ! $escopoBloqueado
            && (int) ($asEscopoOriginalId ?? 0) !== (int) ($asEscopo?->id ?? 0);

        if ($asEscopo?->id !== null && $escopoAlterado) {
            $numeroComplemento = $this->autorizacaoServicoService
                ->gerarProximoComplementoParaEscopo(
                    (int) $item->controleNotaFiscal?->obra_id,
                    (int) $asEscopo->id,
                    $item->id,
                ) ?? null;
        }

        if (! $escopoBloqueado && $asEscopo === null) {
            $numeroComplemento = null;
        }

        $construtora = $this->construtoraDaLinha($item, $dados);
        $valorEstimado = $this->parseMoedaBr($dados['valor_estimado'] ?? null)
            ?? (float) ($item->autorizacaoServico?->valor_estimado ?? $item->valor_estimado_as ?? 0);
        $valorFechadoAtual = (float) ($item->autorizacaoServico?->valor ?? $item->valor_global_a ?? 0);
        $valorFechado = array_key_exists('_valor_fechado_calculado_as', $dados)
            ? ($this->parseMoedaBr($dados['_valor_fechado_calculado_as'] ?? null) ?? $valorFechadoAtual)
            : $valorFechadoAtual;
        [$percentualMaoObra, $percentualMaterial] = $this->normalizarPercentuaisFaturamento($item, $dados, $asEscopo);
        $valorEstimadoEditadoManualmente = (bool) $item->valor_estimado_as_editado_manualmente;

        if ($item->valor_estimado_as_simulador !== null) {
            $valorEstimadoEditadoManualmente = round($valorEstimado, 2) !== round((float) $item->valor_estimado_as_simulador, 2);
        }

        $faturado = $this->totalNotasAprovadas($item);

        $limparEscopoComplementar = $escopoAlterado
            && (filled($asEscopoOriginalId) || blank($numeroComplemento));

        $payload = [
            'escopo_complementar' => $limparEscopoComplementar
                ? null
                : ($dados['escopo_complementar'] ?? $item->escopo_complementar),
            'valor_estimado_as' => $valorEstimado,
            'valor_estimado_as_editado_manualmente' => $valorEstimadoEditadoManualmente,
            'percentual_faturamento_mao_obra' => $percentualMaoObra,
            'percentual_faturamento_material' => $percentualMaterial,
            'valor_global_a' => $valorFechado,
            'total_medicao_a_menos_b' => max($valorFechado - $faturado, 0),
            'valor_acumulado_medido' => $faturado,
            'saldo' => max($valorFechado - $faturado, 0),
            'empresa' => $construtora?->nome ?? $item->empresa,
        ];

        if (! $escopoBloqueado) {
            $payload += [
                'as_escopo_id' => $asEscopo?->id,
                'grupo' => $asEscopo?->grupo,
                'numero_as' => $asEscopo?->numero_as,
                'numero_complemento' => $numeroComplemento,
                'escopo' => $asEscopo?->escopo,
            ];
        }

        $item->update($payload);

        if ($item->autorizacaoServico) {
            $item->autorizacaoServico->update([
                'construtora_id' => $construtora?->id ?? $item->autorizacaoServico->construtora_id,
                'valor_estimado' => $valorEstimado,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    protected function construtoraDaLinha(ControleNotaFiscalItem $item, array $dados): ?Construtora
    {
        if (array_key_exists('construtora_id', $dados)) {
            return filled($dados['construtora_id'] ?? null)
                ? Construtora::query()->find((int) $dados['construtora_id'])
                : null;
        }

        if (filled($item->empresa)) {
            return Construtora::query()
                ->where('nome', $item->empresa)
                ->first();
        }

        return $item->controleNotaFiscal?->construtora_id
            ? Construtora::query()->find($item->controleNotaFiscal->construtora_id)
            : null;
    }

    public function parseMoedaBr(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $normalizado = str_replace(['R$', ' ', '.'], '', (string) $valor);
        $normalizado = str_replace(',', '.', $normalizado);

        return is_numeric($normalizado) ? (float) $normalizado : null;
    }

    /**
     * @param  array<string, mixed>  $dados
     * @return array{0: float, 1: float}
     */
    protected function normalizarPercentuaisFaturamento(ControleNotaFiscalItem $item, array $dados, ?AsEscopo $asEscopo): array
    {
        $escopoAlterado = array_key_exists('as_escopo_id', $dados)
            && (int) ($item->as_escopo_id ?? 0) !== (int) ($asEscopo?->id ?? 0);

        $maoObraInformada = $this->parsePercentual($dados['percentual_faturamento_mao_obra'] ?? null);
        $materialInformado = $this->parsePercentual($dados['percentual_faturamento_material'] ?? null);
        $maoObraAtual = (float) ($item->percentual_faturamento_mao_obra ?? 60);
        $materialAtual = (float) ($item->percentual_faturamento_material ?? (100 - $maoObraAtual));

        if ($escopoAlterado && (
            $maoObraInformada === null
            || round($maoObraInformada, 2) === round($maoObraAtual, 2)
        )) {
            $maoObraInformada = (float) ($asEscopo?->percentual_faturamento_mao_obra_default ?? 60);
        }

        if ($escopoAlterado && (
            $materialInformado === null
            || round($materialInformado, 2) === round($materialAtual, 2)
        )) {
            $materialInformado = (float) ($asEscopo?->percentual_faturamento_material_default ?? (100 - $maoObraInformada));
        }

        $maoObra = $maoObraInformada ?? $maoObraAtual;
        $material = $materialInformado ?? (100 - $maoObra);

        $maoObra = round(max(0, min(100, $maoObra)), 2);
        $material = round(max(0, min(100, $material)), 2);

        if (round($maoObra + $material, 2) !== 100.0) {
            $material = round(100 - $maoObra, 2);
        }

        return [$maoObra, $material];
    }

    protected function parsePercentual(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $normalizado = str_replace(['%', ' '], '', (string) $valor);
        $normalizado = str_replace(',', '.', $normalizado);

        return is_numeric($normalizado) ? (float) $normalizado : null;
    }

    protected function totalNotasAprovadas(ControleNotaFiscalItem $item): float
    {
        if ($item->relationLoaded('notasFiscais')) {
            return (float) $item->notasFiscais
                ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
                ->sum('valor_acumulado_medido_nf');
        }

        return (float) $item->notasFiscais()
            ->where('status', StatusControleNotaFiscalNota::APROVADO->value)
            ->sum('valor_acumulado_medido_nf');
    }
}
