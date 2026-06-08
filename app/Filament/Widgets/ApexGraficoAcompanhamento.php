<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Dashboard\Concerns\AppliesHomeFilters;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ApexGraficoAcompanhamento extends ApexChartWidget
{
    use AppliesHomeFilters;
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    /**
     * Chart Id
     */
    protected static ?string $chartId = 'apexGraficoAcompanhamento';

    /**
     * Widget Title
     *
     * @var string|null
     */
    // protected static ?string $heading = 'Gráfico de Acompanhamento';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $data = $this->getData();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'stacked' => true,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'dataLabels' => [
                        'total' => [
                            'enabled' => true, // ← mostra o total no topo da pilha
                            'style' => [
                                'fontSize' => '14px',
                                'fontWeight' => 'bold',
                                'color' => 'red',
                            ],
                        ],
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'series' => $data['datasets'],
            'xaxis' => [
                'categories' => $data['labels'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'center',
            ],
        ];
    }

    protected function getData(): array
    {
        $queryBase = $this->getFilteredProjetosQuery();

        $inauguradas = $this->getMonthlySeries((clone $queryBase), ['Inaugurada']);
        $implantacao = $this->getMonthlySeries((clone $queryBase), ['Implantação', 'Implantacao']);
        $obras = $this->getMonthlySeries((clone $queryBase), ['Obras', 'Em obra', 'Em Obra']);
        $emProcesso = $this->getMonthlySeries((clone $queryBase), ['Em processo', 'Fase de projeto', 'Fase de Projeto']);

        return [
            'datasets' => [
                [
                    'name' => 'Inaugurada',
                    'data' => $inauguradas,
                    'color' => '#a1d89a',
                ],
                [
                    'name' => 'Implantação',
                    'data' => $implantacao,
                    'color' => '#f3e39f',
                ],
                [
                    'name' => 'Em obra',
                    'data' => $obras,
                    'color' => '#ffb000',
                ],
                [
                    'name' => 'Fase de projeto',
                    'data' => $emProcesso,
                    'color' => '#d05757',
                ],
            ],
            'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        ];
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<int, int>
     */
    protected function getMonthlySeries(Builder $query, array $statuses): array
    {
        $rows = $query
            ->select(DB::raw('MONTH(imp_fim) as mes'), DB::raw('COUNT(*) as total'))
            ->whereIn('status', $statuses)
            ->whereNotNull('imp_fim')
            ->groupBy(DB::raw('MONTH(imp_fim)'))
            ->orderBy('mes')
            ->pluck('total', 'mes')
            ->toArray();

        $series = array_fill(0, 12, 0);

        foreach ($rows as $mes => $total) {
            $series[(int) $mes - 1] = (int) $total;
        }

        return $series;
    }
}
