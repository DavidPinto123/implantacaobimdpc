<?php

namespace App\Services;

use App\Models\ControleNotaFiscalNota;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FinanceiroNotasFiscaisPdfService
{
    /**
     * Limite máximo de notas para evitar OOM em export sem filtro.
     */
    public const LIMITE_REGISTROS = 5000;

    /**
     * Gera o PDF do relatório de notas fiscais com base na query já filtrada.
     *
     * @param  array<string, string>  $filtrosResumo  Resumo legível dos filtros aplicados (label => valor)
     */
    public function gerar(Builder $query, array $filtrosResumo = []): DomPDF
    {
        ini_set('memory_limit', '1024M');

        $totalRegistros = (clone $query)->count();
        $truncado = $totalRegistros > self::LIMITE_REGISTROS;

        $notas = $this->carregarNotas($query);

        $totalizadores = $this->totalizadores($notas);

        $linhas = $notas->map(fn (ControleNotaFiscalNota $nota): array => $this->montarLinha($nota))->all();

        return Pdf::loadView('pdf.financeiro-notas-fiscais', [
            'linhas' => $linhas,
            'filtrosResumo' => $filtrosResumo,
            'totalizadores' => $totalizadores,
            'emitidoEm' => now(),
            'truncado' => $truncado,
            'totalSemTruncamento' => $totalRegistros,
            'limiteRegistros' => self::LIMITE_REGISTROS,
        ])
            ->setPaper('a4', 'landscape')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);
    }

    /**
     * @return Collection<int, ControleNotaFiscalNota>
     */
    protected function carregarNotas(Builder $query): Collection
    {
        return $query
            ->with([
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra:id,codigo,unidade,projeto_id',
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto:id,resp_eng',
                'autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal.obra.projeto.responsavelEng:id,name',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra:id,codigo,unidade,projeto_id',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto:id,resp_eng',
                'asa.controleNotaFiscalAuxiliar.controleNotaFiscal.obra.projeto.responsavelEng:id,name',
                'baixadoPor:id,name',
            ])
            ->limit(self::LIMITE_REGISTROS)
            ->get();
    }

    /**
     * @return array{quantidade: int, valor_total: float, valor_total_formatado: string}
     */
    protected function totalizadores(Collection $notas): array
    {
        $valorTotal = (float) $notas->sum(fn (ControleNotaFiscalNota $nota): float => (float) $nota->valor_acumulado_medido_nf);

        return [
            'quantidade' => $notas->count(),
            'valor_total' => $valorTotal,
            'valor_total_formatado' => $this->formatBrl($valorTotal),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function montarLinha(ControleNotaFiscalNota $nota): array
    {
        $obra = $nota->obra;

        return [
            'numero_nf' => (string) ($nota->numero_nf ?? ''),
            'empresa' => (string) ($nota->empresa ?? ''),
            'cnpj' => (string) ($nota->cnpj_fornecedor ?? ''),
            'tipo' => $this->labelTipo($nota->tipo_medicao),
            'valor' => $this->formatBrl($nota->valor_acumulado_medido_nf),
            'emissao' => $nota->emissao?->format('d/m/Y') ?? '',
            'baixa' => $nota->baixado ? 'Baixado' : 'Pendente',
            'baixado_por' => (string) ($nota->baixadoPor?->name ?? ''),
            'baixado_em' => $nota->baixado_em?->format('d/m/Y H:i') ?? '',
            'codigo' => (string) ($obra?->codigo ?? ''),
            'unidade' => (string) ($obra?->unidade ?? ''),
            'gestor' => (string) ($obra?->projeto?->responsavelEng?->name ?? ''),
        ];
    }

    protected function labelTipo(?string $tipo): string
    {
        return match ($tipo) {
            ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA => 'Mão de obra',
            ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL => 'Material',
            ControleNotaFiscalNota::TIPO_MEDICAO_TRANSPORTE => 'Transporte',
            default => '',
        };
    }

    protected function formatBrl(mixed $valor): string
    {
        return 'R$ '.number_format((float) ($valor ?? 0), 2, ',', '.');
    }
}
