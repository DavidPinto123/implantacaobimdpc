<?php

namespace App\Services;

use App\Enums\AsStatus;
use App\Enums\TipoUnidade;
use App\Models\AsEscopo;
use App\Models\CapexSimulacao;
use App\Models\CapexSimulacaoItem;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\Obras;
use Illuminate\Support\Collection;

class SincronizarSimuladorOiControleAsService
{
    public function __construct(
        protected AutorizacaoServicoComplementoService $complementoService,
    ) {}

    /**
     * @return array{preenchidos: int, criados: int, ignorados_edicao_manual: int, conflitos: array<int, string>}
     */
    public function sincronizar(Obras $obra, CapexSimulacao $simulacao): array
    {
        $resultado = [
            'preenchidos' => 0,
            'criados' => 0,
            'ignorados_edicao_manual' => 0,
            'conflitos' => [],
        ];

        $controle = ControleNotaFiscal::query()
            ->where('obra_id', $obra->id)
            ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
            ->latest('id')
            ->first();

        if (! $controle) {
            $resultado['conflitos'][] = 'Obra sem controle de nota fiscal.';

            return $resultado;
        }

        $simulacao->loadMissing('itens.escopo');

        $itensPorChave = $simulacao->itens
            ->filter(fn ($item): bool => $this->itemImportavel($item))
            ->groupBy(fn ($item): string => $this->chaveItemSimulador($item));

        foreach ($itensPorChave as $chave => $itensSimulador) {
            if ($itensSimulador->count() > 1) {
                $resultado['conflitos'][] = "Simulador OI possui itens duplicados para {$chave}.";

                continue;
            }

            $itemSimulador = $itensSimulador->first();
            $destinos = $this->destinosParaItemSimulador($controle, $itemSimulador);

            if ($destinos->count() > 1) {
                $resultado['conflitos'][] = "Controle de AS possui linhas duplicadas para {$chave}.";

                continue;
            }

            $destino = $destinos->first();

            if (! $destino) {
                $escopo = $this->escopoParaItemSimulador($itemSimulador, $controle);

                $destino = $controle->itens()->create([
                    'as_escopo_id' => $escopo?->id,
                    'grupo' => $escopo?->grupo,
                    'numero_as' => $escopo?->numero_as,
                    'numero_complemento' => $this->complementoService->normalizar($itemSimulador->numero_complemento),
                    'escopo' => $escopo?->escopo ?? $itemSimulador->nome_escopo,
                    'percentual_total' => 100,
                    'percentual_faturamento_mao_obra' => $escopo?->percentual_faturamento_mao_obra_default ?? 60,
                    'percentual_faturamento_material' => $escopo?->percentual_faturamento_material_default ?? 40,
                    'valor_global_a' => 0,
                    'total_medicao_a_menos_b' => 0,
                    'valor_acumulado_medido' => 0,
                    'saldo' => 0,
                    'sort_order' => ((int) $controle->itens()->max('sort_order')) + 1,
                ]);
                $resultado['criados']++;
            }

            if ($mensagem = $this->mensagemLinhaVinculadaAsImutavel($destino, $chave)) {
                $resultado['conflitos'][] = $mensagem;

                continue;
            }

            $valorEstimado = round((float) $itemSimulador->custo_estimado, 2);

            $destino->update([
                'capex_simulacao_item_id' => $itemSimulador->id,
                'valor_estimado_as' => $valorEstimado,
                'valor_estimado_as_simulador' => $valorEstimado,
                'valor_estimado_as_editado_manualmente' => false,
            ]);
            $this->atualizarValorEstimadoAsVinculada($destino, $valorEstimado);

            $resultado['preenchidos']++;
        }

        return $resultado;
    }

    /**
     * @return array{preenchidos: int, criados: int, ignorados_edicao_manual: int, conflitos: array<int, string>}
     */
    public function sincronizarItem(ControleNotaFiscalItem $destino, CapexSimulacao $simulacao): array
    {
        $resultado = [
            'preenchidos' => 0,
            'criados' => 0,
            'ignorados_edicao_manual' => 0,
            'conflitos' => [],
        ];

        if (blank($destino->as_escopo_id) && blank($destino->capex_simulacao_item_id)) {
            $resultado['conflitos'][] = 'Linha sem escopo AS vinculado.';

            return $resultado;
        }

        $simulacao->loadMissing('itens.escopo');

        $chave = $this->chaveItemDestino($destino);
        $itensSimulador = $simulacao->itens
            ->filter(fn ($item): bool => $this->itemImportavel($item))
            ->filter(fn ($item): bool => $this->chaveItemSimulador($item) === $chave)
            ->values();

        if ($itensSimulador->count() === 0) {
            $resultado['conflitos'][] = 'Nenhum item compatível encontrado no Simulador OI para esta linha.';

            return $resultado;
        }

        if ($itensSimulador->count() > 1) {
            $resultado['conflitos'][] = "Simulador OI possui itens duplicados para {$chave}.";

            return $resultado;
        }

        if ($mensagem = $this->mensagemLinhaVinculadaAsImutavel($destino, $chave)) {
            $resultado['conflitos'][] = $mensagem;

            return $resultado;
        }

        $itemSimulador = $itensSimulador->first();
        $valorEstimado = round((float) $itemSimulador->custo_estimado, 2);

        $destino->update([
            'capex_simulacao_item_id' => $itemSimulador->id,
            'valor_estimado_as' => $valorEstimado,
            'valor_estimado_as_simulador' => $valorEstimado,
            'valor_estimado_as_editado_manualmente' => false,
        ]);
        $this->atualizarValorEstimadoAsVinculada($destino, $valorEstimado);

        $resultado['preenchidos']++;

        return $resultado;
    }

    public function encontrarAprovadaParaObra(Obras $obra): ?CapexSimulacao
    {
        return $this->listarAprovadasParaObra($obra)
            ->first();
    }

    /**
     * @return Collection<int, CapexSimulacao>
     */
    public function listarAprovadasParaObra(Obras $obra): Collection
    {
        if (blank($obra->projeto_id)) {
            return collect();
        }

        return CapexSimulacao::query()
            ->where('status', 1)
            ->where('projeto_id', $obra->projeto_id)
            ->latest('updated_at')
            ->get();
    }

    protected function chave(int $asEscopoId, mixed $numeroComplemento): string
    {
        return $asEscopoId.'|'.$this->complementoService->normalizar($numeroComplemento);
    }

    protected function mensagemLinhaVinculadaAsImutavel(ControleNotaFiscalItem $item, string $chave): ?string
    {
        $item->loadMissing('autorizacaoServico');

        if (! $item->autorizacaoServico) {
            return null;
        }

        if ($item->autorizacaoServico->status === AsStatus::CANCELADA) {
            return "Linha vinculada a AS cancelada ignorada para {$chave}.";
        }

        if ($item->autorizacaoServico->status === AsStatus::ENVIADA) {
            return "Linha vinculada a AS enviada ignorada para {$chave}.";
        }

        return null;
    }

    protected function atualizarValorEstimadoAsVinculada(ControleNotaFiscalItem $item, float $valorEstimado): void
    {
        $item->loadMissing('autorizacaoServico');

        $item->autorizacaoServico?->update([
            'valor_estimado' => $valorEstimado,
        ]);
    }

    protected function itemImportavel(CapexSimulacaoItem $item): bool
    {
        return (bool) $item->incluir
            && (filled($item->as_escopo_id) || $this->itemManualSemEscopo($item));
    }

    protected function itemManualSemEscopo(CapexSimulacaoItem $item): bool
    {
        return blank($item->as_escopo_id)
            && (string) $item->tipo === 'manual'
            && filled($item->nome_escopo);
    }

    protected function escopoParaItemSimulador(CapexSimulacaoItem $item, ControleNotaFiscal $controle): ?AsEscopo
    {
        if (! $this->itemManualSemEscopo($item)) {
            return $item->escopo;
        }

        $nomeEscopo = trim((string) $item->nome_escopo);

        if ($nomeEscopo === '') {
            return null;
        }

        return AsEscopo::query()->updateOrCreate(
            [
                'controle_nota_fiscal_id' => $controle->id,
                'capex_simulacao_item_id' => $item->id,
            ],
            [
                'escopo' => $nomeEscopo,
                'grupo' => 'Personalizado OI',
                'numero_as' => "OI-{$controle->id}-{$item->id}",
                'percentual_faturamento_mao_obra_default' => 60,
                'percentual_faturamento_material_default' => 40,
                'is_active' => true,
                'is_personalizado' => true,
            ],
        );
    }

    protected function chaveItemSimulador(CapexSimulacaoItem $item): string
    {
        if ($this->itemManualSemEscopo($item)) {
            return 'manual|'.$item->id;
        }

        return $this->chave((int) $item->as_escopo_id, $item->numero_complemento);
    }

    protected function chaveItemDestino(ControleNotaFiscalItem $item): string
    {
        if (blank($item->as_escopo_id) && filled($item->capex_simulacao_item_id)) {
            return 'manual|'.$item->capex_simulacao_item_id;
        }

        return $this->chave((int) $item->as_escopo_id, $item->numero_complemento);
    }

    /**
     * @return Collection<int, ControleNotaFiscalItem>
     */
    protected function destinosParaItemSimulador(ControleNotaFiscal $controle, CapexSimulacaoItem $item): Collection
    {
        if ($this->itemManualSemEscopo($item)) {
            return $controle->itens()
                ->where('capex_simulacao_item_id', $item->id)
                ->get();
        }

        return $this->destinos($controle, (int) $item->as_escopo_id, $item->numero_complemento);
    }

    /**
     * @return Collection<int, ControleNotaFiscalItem>
     */
    protected function destinos(ControleNotaFiscal $controle, int $asEscopoId, mixed $numeroComplemento): Collection
    {
        $complemento = $this->complementoService->normalizar($numeroComplemento);

        return $controle->itens()
            ->where('as_escopo_id', $asEscopoId)
            ->get()
            ->filter(fn (ControleNotaFiscalItem $item): bool => $this->complementoService->normalizar($item->numero_complemento) === $complemento)
            ->values();
    }
}
