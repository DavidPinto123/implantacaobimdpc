<?php

namespace App\Services;

use App\Enums\TipoUnidade;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\Obras;
use App\Services\ControleNotaFiscal\PreencheEscoposPadraoControleNotaFiscal;
use Illuminate\Support\Str;

class AutorizacaoServicoService
{
    public function gerarNumeroAsEstruturado(
        ?Obras $obra,
        ?AsEscopo $escopo,
        ?Construtora $construtora,
    ): string {
        $sigla = $this->normalizeReadableSegment($obra?->sigla, 'SEM SIGLA');
        $numeroAsReferencia = $this->normalizeNumeroAs($escopo?->numero_as);
        $nomeUnidade = $this->normalizeReadableSegment($obra?->unidade, 'SEM UNIDADE');
        $escopoSegment = $this->normalizeReadableSegment($escopo?->escopo, 'SEM ESCOPO');
        $fornecedor = $this->normalizeReadableSegment($construtora?->nome, 'SEM FORNECEDOR');

        return implode('-', [
            $sigla,
            'SF',
            'EXP',
            $numeroAsReferencia,
            $nomeUnidade,
            $escopoSegment,
            $fornecedor,
        ]);
    }

    protected function normalizeReadableSegment(?string $value, string $fallback): string
    {
        if (blank($value)) {
            return $fallback;
        }

        $normalized = (string) Str::of($value)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return $normalized !== '' ? $normalized : $fallback;
    }

    protected function normalizeNumeroAs(?string $value): string
    {
        if (blank($value)) {
            return 'SEM AS';
        }

        $normalized = (string) Str::of($value)
            ->replaceMatches('/[^0-9.]+/', '')
            ->trim()
            ->value();

        if ($normalized !== '') {
            return $normalized;
        }

        return (string) Str::of($value)->replaceMatches('/\s+/', ' ')->trim()->value();
    }

    public function sincronizarItensContratuais(
        AutorizacaoServico $autorizacaoServico,
        ?string $numeroAsAnterior = null,
    ): int {
        $autorizacaoServico->loadMissing(['asEscopo', 'construtora']);

        if (! $autorizacaoServico->obra_id || ! $autorizacaoServico->as_escopo_id) {
            return 0;
        }

        $escopo = $autorizacaoServico->asEscopo;

        if (! $escopo) {
            return 0;
        }

        $controlesQuery = $this->queryControlesPorAutorizacao($autorizacaoServico);

        if (! $controlesQuery->exists()) {
            $this->criarControleNotaFiscalInicial($autorizacaoServico);
            $controlesQuery = $this->queryControlesPorAutorizacao($autorizacaoServico);
        }

        $empresa = (string) ($autorizacaoServico->construtora?->nome ?? '');
        $controlesEncontrados = 0;

        $controlesQuery->chunkById(100, function ($controles) use (
            $autorizacaoServico,
            $escopo,
            $empresa,
            $numeroAsAnterior,
            &$controlesEncontrados
        ): void {
            foreach ($controles as $controle) {
                $controlesEncontrados++;

                $itemPorVinculo = ControleNotaFiscalItem::query()
                    ->where('controle_nota_fiscal_id', $controle->id)
                    ->whereKey($autorizacaoServico->controle_nota_fiscal_item_id)
                    ->first();

                $itemPorEscopo = ControleNotaFiscalItem::query()
                    ->where('controle_nota_fiscal_id', $controle->id)
                    ->where('as_escopo_id', $autorizacaoServico->as_escopo_id)
                    ->where(function ($query) use ($autorizacaoServico): void {
                        $numeroComplemento = (string) ($autorizacaoServico->numero_complemento ?? '');

                        if ($numeroComplemento !== '') {
                            $query->where('numero_complemento', $numeroComplemento);

                            return;
                        }

                        $query
                            ->whereNull('numero_complemento')
                            ->orWhere('numero_complemento', '');
                    })
                    ->first();

                $itemPorNumeroGerado = ControleNotaFiscalItem::query()
                    ->where('controle_nota_fiscal_id', $controle->id)
                    ->where('numero_as', $autorizacaoServico->numero_as)
                    ->where(function ($query) use ($autorizacaoServico): void {
                        $numeroComplemento = (string) ($autorizacaoServico->numero_complemento ?? '');

                        if ($numeroComplemento !== '') {
                            $query->where('numero_complemento', $numeroComplemento);

                            return;
                        }

                        $query
                            ->whereNull('numero_complemento')
                            ->orWhere('numero_complemento', '');
                    })
                    ->first();

                if (! $itemPorNumeroGerado && filled($numeroAsAnterior) && $numeroAsAnterior !== $autorizacaoServico->numero_as) {
                    $itemPorNumeroGerado = ControleNotaFiscalItem::query()
                        ->where('controle_nota_fiscal_id', $controle->id)
                        ->where('numero_as', $numeroAsAnterior)
                        ->first();
                }

                $item = $itemPorVinculo ?: $itemPorEscopo ?: $itemPorNumeroGerado;

                if ($item) {
                    $item->update([
                        'as_escopo_id' => $autorizacaoServico->as_escopo_id,
                        'grupo' => $escopo->grupo,
                        'numero_as' => $escopo->numero_as,
                        'numero_complemento' => (string) ($autorizacaoServico->numero_complemento ?? ''),
                        'escopo' => $escopo->escopo,
                        'empresa' => $empresa,
                        'valor_estimado_as' => (float) $autorizacaoServico->valor_estimado,
                        'valor_global_a' => (float) $autorizacaoServico->valor,
                        'saldo' => (float) $autorizacaoServico->valor,
                        'observacoes' => $autorizacaoServico->observacoes,
                    ]);

                    if ((int) $autorizacaoServico->controle_nota_fiscal_item_id !== (int) $item->id) {
                        $autorizacaoServico->forceFill([
                            'controle_nota_fiscal_item_id' => $item->id,
                        ])->saveQuietly();
                    }

                    if (
                        $itemPorEscopo
                        && $itemPorNumeroGerado
                        && $itemPorEscopo->id !== $itemPorNumeroGerado->id
                    ) {
                        $itemPorNumeroGerado->delete();
                    }

                    continue;
                }

                $nextSortOrder = (int) ControleNotaFiscalItem::query()
                    ->where('controle_nota_fiscal_id', $controle->id)
                    ->max('sort_order') + 1;

                $itemCriado = ControleNotaFiscalItem::query()->create([
                    'controle_nota_fiscal_id' => $controle->id,
                    'as_escopo_id' => $autorizacaoServico->as_escopo_id,
                    'grupo' => $escopo->grupo,
                    'numero_as' => $escopo->numero_as,
                    'numero_complemento' => (string) ($autorizacaoServico->numero_complemento ?? ''),
                    'escopo' => $escopo->escopo,
                    'empresa' => $empresa,
                    'percentual_total' => 100,
                    'percentual_faturamento_mao_obra' => $escopo->percentual_faturamento_mao_obra_default ?? 60,
                    'percentual_faturamento_material' => $escopo->percentual_faturamento_material_default ?? 40,
                    'valor_estimado_as' => (float) $autorizacaoServico->valor_estimado,
                    'valor_global_a' => (float) $autorizacaoServico->valor,
                    'total_medicao_a_menos_b' => 0,
                    'valor_acumulado_medido' => 0,
                    'saldo' => (float) $autorizacaoServico->valor,
                    'observacoes' => $autorizacaoServico->observacoes,
                    'sort_order' => $nextSortOrder,
                ]);

                $autorizacaoServico->forceFill([
                    'controle_nota_fiscal_item_id' => $itemCriado->id,
                ])->saveQuietly();
            }
        });

        return $controlesEncontrados;
    }

    public function gerarProximoComplementoParaEscopo(int $obraId, int $asEscopoId, ?int $ignorarItemId = null): ?string
    {
        $complemento = app(AutorizacaoServicoComplementoService::class)
            ->gerarProximo($obraId, $asEscopoId, $ignorarItemId);

        return $complemento === '' ? null : $complemento;
    }

    protected function queryControlesPorAutorizacao(AutorizacaoServico $autorizacaoServico)
    {
        $controlesRetrofit = ControleNotaFiscal::query()
            ->where('obra_id', $autorizacaoServico->obra_id)
            ->where('tipo_unidade', TipoUnidade::RETROFIT->value);

        if ($controlesRetrofit->exists()) {
            return $controlesRetrofit;
        }

        $controlesLegadosComItens = ControleNotaFiscal::query()
            ->where('obra_id', $autorizacaoServico->obra_id)
            ->whereHas('itens');

        if ($controlesLegadosComItens->exists()) {
            return $controlesLegadosComItens;
        }

        return $controlesRetrofit;
    }

    protected function criarControleNotaFiscalInicial(AutorizacaoServico $autorizacaoServico): void
    {
        $obra = Obras::query()->find($autorizacaoServico->obra_id);

        $controle = ControleNotaFiscal::query()->create([
            'obra_id' => $autorizacaoServico->obra_id,
            'tipo_unidade' => TipoUnidade::RETROFIT->value,
            'status' => ControleNotaFiscal::STATUS_ATIVO,
            'unidade' => $obra?->unidade,
            'sigla' => $obra?->sigla,
            'endereco' => $obra?->endereco,
        ]);

        app(PreencheEscoposPadraoControleNotaFiscal::class)->handle($controle);
    }
}
