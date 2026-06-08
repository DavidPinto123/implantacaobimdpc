<?php

namespace App\Filament\Resources\AutorizacaoServicos\Pages;

use App\Filament\Resources\AutorizacaoServicos\AutorizacaoServicoResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditAutorizacaoServico extends EditRecord
{
    protected static string $resource = AutorizacaoServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return 'Visualizar AS';
    }

    public function getHeading(): string
    {
        return 'Visualizar AS';
    }

    protected function authorizeAccess(): void
    {
        abort_unless((bool) Auth::user()?->can('View:AutorizacaoServico'), 403);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function percentualMask(): RawJs
    {
        return RawJs::make(<<<'JS'
            (() => {
                let value = String($input ?? '')
                    .replace(/[^\d,]/g, '')
                    .replace(/,+/g, ',');

                const parts = value.split(',');
                let integer = parts[0] || '';
                const decimal = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;

                integer = integer.replace(/^0+(?=\d)/, '');

                if (integer === '') {
                    integer = decimal === null ? '' : '0';
                }

                return decimal === null ? integer : `${integer},${decimal}`;
            })()
        JS);
    }

    /**
     * @return array<int, array{parcela: string, percentual: string, valor: string, observacao: string}>
     */
    protected function parcelamentoPadrao(): array
    {
        $parcelamento = $this->record?->parcelamento_autorizacao_servico;

        if (is_array($parcelamento) && $parcelamento !== []) {
            return collect($parcelamento)
                ->map(fn (array $parcela): array => [
                    'parcela' => (string) ($parcela['parcela'] ?? ''),
                    'percentual' => number_format((float) ($parcela['percentual'] ?? 0), 2, ',', '.'),
                    'valor' => number_format((float) ($parcela['valor'] ?? 0), 2, ',', '.'),
                    'observacao' => (string) ($parcela['observacao'] ?? ''),
                ])
                ->all();
        }

        $valorLiquido = (float) ($this->record?->valor ?? 0);

        return [[
            'parcela' => $this->nomeParcelaPadrao(1),
            'percentual' => '100,00',
            'valor' => $this->formatMoeda($valorLiquido),
            'observacao' => '>> FATURAR SOMENTE COM AUTORIZAÇÃO DO(A) GESTOR(A) DPC',
        ]];
    }

    protected function dataPadrao(string $campo): ?string
    {
        $this->record?->loadMissing(['obra', 'itens']);

        $valor = match ($campo) {
            'data_inicio_servico' => $this->record?->data_inicio_servico,
            'data_termino_servico' => $this->record?->data_termino_servico,
            'data_entrega_material' => $this->record?->data_entrega_material,
            default => null,
        };

        return $valor instanceof \DateTimeInterface ? $valor->format('Y-m-d') : ($valor ? (string) $valor : null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{data_inicio_servico: ?string, data_termino_servico: ?string, data_entrega_material: ?string, desconto_autorizacao_servico: float, descricao_servico_pdf: ?string, anexos_autorizacao_servico: array<int, string>}
     */
    protected function normalizarDatasFormulario(array $data): array
    {
        return [
            'data_inicio_servico' => filled($data['data_inicio_servico'] ?? null) ? (string) $data['data_inicio_servico'] : null,
            'data_termino_servico' => filled($data['data_termino_servico'] ?? null) ? (string) $data['data_termino_servico'] : null,
            'data_entrega_material' => filled($data['data_entrega_material'] ?? null) ? (string) $data['data_entrega_material'] : null,
            'desconto_autorizacao_servico' => $this->parseNumeroBr($data['desconto_autorizacao_servico'] ?? null) ?? 0.0,
            'descricao_servico_pdf' => filled($data['descricao_servico_pdf'] ?? null)
                ? trim((string) $data['descricao_servico_pdf'])
                : null,
            'anexos_autorizacao_servico' => array_values(array_filter((array) ($data['anexos_autorizacao_servico'] ?? []))),
        ];
    }

    protected function descricaoPadraoPdf(): string
    {
        $this->record?->loadMissing(['asEscopo', 'itens']);

        $itemPrincipal = $this->record?->itens?->first();
        $descricao = $this->record?->descricao_servico_pdf;
        $descricao = filled($descricao) ? $descricao : $itemPrincipal?->escopo_complementar;
        $descricao = filled($descricao) ? $descricao : $itemPrincipal?->escopo;
        $descricao = filled($descricao) ? $descricao : $this->record?->asEscopo?->escopo;

        return (string) $descricao;
    }

    /**
     * @param  array<int, array<string, mixed>>  $parcelamento
     * @return array<int, array{parcela: string, percentual: float, valor: float, observacao: string}>
     */
    protected function normalizarParcelamentoFormulario(array $parcelamento, array $data = []): array
    {
        $desconto = $this->parseNumeroBr($data['desconto_autorizacao_servico'] ?? null)
            ?? (float) ($this->record?->desconto_autorizacao_servico ?? 0);
        $valorFechado = (float) ($this->record?->valor ?? 0);
        $parcelas = collect($parcelamento)
            ->map(fn (array $parcela, int $indice): array => [
                'parcela' => filled($parcela['parcela'] ?? null)
                    ? (string) $parcela['parcela']
                    : $this->nomeParcelaPadrao($indice + 1),
                'percentual' => $this->parseNumeroBr($parcela['percentual'] ?? null) ?? 0.0,
                'valor' => 0.0,
                'observacao' => (string) ($parcela['observacao'] ?? ''),
            ])
            ->values()
            ->all();

        $somaValores = 0.0;
        $ultimoIndiceComPercentual = null;

        foreach ($parcelas as $indice => $parcela) {
            $valor = round($valorFechado * ($parcela['percentual'] / 100), 2);
            $parcelas[$indice]['valor'] = $valor;
            $somaValores += $valor;

            if ($parcela['percentual'] > 0) {
                $ultimoIndiceComPercentual = $indice;
            }
        }

        if ($ultimoIndiceComPercentual !== null) {
            $parcelas[$ultimoIndiceComPercentual]['valor'] = round(
                $parcelas[$ultimoIndiceComPercentual]['valor'] + ($valorFechado - $somaValores),
                2,
            );
        }

        return $parcelas;
    }

    protected function valorLiquido(float $valor, float $desconto): float
    {
        return max(round($valor - max($desconto, 0), 2), 0.0);
    }

    protected function atualizarValorParcelaJs(): string
    {
        return <<<'JS'
            const parseNumero = (valor) => {
                if (valor === null || valor === undefined || valor === '') {
                    return 0;
                }

                if (typeof valor === 'number') {
                    return valor;
                }

                const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                const numero = Number(normalizado);

                return Number.isFinite(numero) ? numero : 0;
            };

            const formatMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const valorFechado = parseNumero($get('../../valor_total_autorizacao_servico'));
            const desconto = parseNumero($get('../../desconto_autorizacao_servico'));
            const valorLiquido = Math.max(valorFechado - Math.max(desconto, 0), 0);
            const percentual = parseNumero($state);

            $set('valor', formatMoeda(Math.round((valorLiquido * (percentual / 100)) * 100) / 100));
        JS;
    }

    protected function atualizarValoresParcelasJs(): string
    {
        return <<<'JS'
            const parseNumero = (valor) => {
                if (valor === null || valor === undefined || valor === '') {
                    return 0;
                }

                if (typeof valor === 'number') {
                    return valor;
                }

                const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                const numero = Number(normalizado);

                return Number.isFinite(numero) ? numero : 0;
            };

            const formatMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const valorFechado = parseNumero($get('valor_total_autorizacao_servico'));
            const desconto = parseNumero($state);
            const valorLiquido = Math.max(valorFechado - Math.max(desconto, 0), 0);

            $set('total_apos_desconto', formatMoeda(valorLiquido));

            Object.entries($get('parcelamento') || {}).forEach(([chave, parcela]) => {
                const percentual = parseNumero(parcela?.percentual);
                const valor = Math.round((valorLiquido * (percentual / 100)) * 100) / 100;

                $set(`parcelamento.${chave}.valor`, formatMoeda(valor));
            });
        JS;
    }

    protected function ocultarAvisoPercentualParcelamentoJs(): string
    {
        return <<<'JS'
            (() => {
                const parseNumero = (valor) => {
                    if (valor === null || valor === undefined || valor === '') {
                        return 0;
                    }

                    if (typeof valor === 'number') {
                        return valor;
                    }

                    const normalizado = String(valor).replace(/\./g, '').replace(',', '.');
                    const numero = Number(normalizado);

                    return Number.isFinite(numero) ? numero : 0;
                };

                const total = Object.values($get('parcelamento') || {}).reduce((soma, parcela) => {
                    return soma + parseNumero(parcela?.percentual);
                }, 0);

                return total <= 100;
            })()
        JS;
    }

    protected function formatMoeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    protected function nomeParcelaPadrao(int $indice): string
    {
        return 'Parcela '.$indice;
    }

    /**
     * @param  array<int, mixed>  $parcelas
     * @return array<int, array{parcela: string, percentual: string, valor: string, observacao: string}>
     */
    protected function normalizarEstadoParcelasFormulario(array $parcelas): array
    {
        return collect($parcelas)
            ->filter(fn (mixed $parcela): bool => is_array($parcela))
            ->values()
            ->map(fn (array $parcela, int $indice): array => [
                'parcela' => filled($parcela['parcela'] ?? null)
                    ? (string) $parcela['parcela']
                    : $this->nomeParcelaPadrao($indice + 1),
                'percentual' => filled($parcela['percentual'] ?? null)
                    ? (string) $parcela['percentual']
                    : '0,00',
                'valor' => filled($parcela['valor'] ?? null)
                    ? (string) $parcela['valor']
                    : '0,00',
                'observacao' => (string) ($parcela['observacao'] ?? ''),
            ])
            ->all();
    }

    /**
     * @param  array<int, array{parcela: string, percentual: string, valor: string, observacao: string}>  $parcelas
     */
    protected function nomeProximaParcelaDisponivel(array $parcelas, ?int $ignorarIndice = null): string
    {
        $numerosUsados = collect($parcelas)
            ->except($ignorarIndice === null ? [] : [$ignorarIndice])
            ->map(fn (array $parcela): ?int => $this->numeroDaParcela($parcela['parcela'] ?? ''))
            ->filter()
            ->values()
            ->all();

        $proximo = 1;

        while (in_array($proximo, $numerosUsados, true)) {
            $proximo++;
        }

        return $this->nomeParcelaPadrao($proximo);
    }

    protected function numeroDaParcela(string $parcela): ?int
    {
        return preg_match('/^Parcela\s+(\d+)$/i', trim($parcela), $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    protected function parseNumeroBr(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $normalizado = str_replace(['.', ','], ['', '.'], (string) $valor);

        return is_numeric($normalizado) ? (float) $normalizado : null;
    }
}
