<?php

namespace App\Filament\Resources\Asas\Widgets;

use App\Models\Asa;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class ResumoContrato extends ChartWidget
{
    #[Reactive]
    public ?string $projetoFiltro = null;

    #[Reactive]
    public ?string $construtoraFiltro = null;

    protected ?string $heading = 'RESUMO CONTRATO';

    protected ?string $description = 'CONSIDERADOS ADICIONAIS APROVADOS E EM ANALISE';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $query = Asa::query();

        if (filled($this->projetoFiltro)) {
            $query->where('projeto_id', $this->projetoFiltro);
        }

        if (filled($this->construtoraFiltro)) {
            $query->where('solicitante', $this->construtoraFiltro);
        }

        $aditivosEmAnalise = (clone $query)
            ->where('status', 'em_analise')
            ->sum('valor_total');

        $aditivosAprovados = (clone $query)
            ->where('status', 'aprovado')
            ->sum('valor_total');

        $aditivosTotais = $aditivosEmAnalise + $aditivosAprovados;

        return [
            'datasets' => [
                [
                    'label' => 'Valores',
                    'data' => [
                        (float) $aditivosEmAnalise,
                        (float) $aditivosAprovados,
                        (float) $aditivosTotais,
                    ],
                ],
            ],
            'labels' => [
                'Em análise',
                'Aprovados',
                'Total',
            ],
        ];
    }
}
