<?php

namespace App\Filament\Widgets;

use App\Models\Projeto;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RiscosDonutChart extends ApexChartWidget
{
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'riscosDonutChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Status';

    protected static ?int $contentHeight = 400;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        // Base query com filtro PIPE 2025
        $query = Projeto::query()
            ->where('pipeline', 'PIPE 2025');

        // Contagem por status (Inaugurada / Obras)
        $totaisStatus = $query->clone()
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('status', ['Inaugurada', 'Obras'])
            ->groupBy('status')
            ->pluck('total', 'status');

        // Contagem por status_contrato (assinada)
        $totaisContrato = Projeto::withoutGlobalScopes()
            ->where('pipeline', 'PIPE 2025')
            ->whereRaw("TRIM(UPPER(status_contrato)) = 'ASSINADO'")
            ->count();

        // 🚨 Para LAND BANK não dá pra usar a mesma $base, senão vai filtrar fora
        $totaisPipeline = Projeto::query()
            ->selectRaw('pipeline, COUNT(*) as total')
            ->where('pipeline', 'LIKE', 'LAND BANK%')
            ->groupBy('pipeline')
            ->pluck('total', 'pipeline');

        $landBankTotal = $totaisPipeline->sum();

        $series = [
            (int) ($totaisStatus['Inaugurada'] ?? 0),
            (int) ($totaisStatus['Obras'] ?? 0),
            (int) $totaisContrato,
            (int) $landBankTotal,
        ];

        $labels = [
            'Inaugurada ('.$series[0].')',
            'Obras ('.$series[1].')',
            'Assinadas ('.$series[2].')',
            'Land Bank ('.$series[3].')',
        ];

        return [
            'chart' => [
                'type' => 'donut',
                'height' => static::$contentHeight,
            ],
            'series' => $series,
            'labels' => $labels,
            'colors' => ['#F5C000', '#FFE07A', '#D7D9DC', '#A6A9AE'],
            'legend' => [
                'position' => 'bottom',
                'horizontalAlign' => 'center',
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '70%',
                        'labels' => [
                            'show' => true,
                            'total' => [
                                'show' => true,
                                'label' => 'Total',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
    {
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        total: {
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val, opts) {
                    var total = opts.globals.seriesTotals.reduce((a, b) => a + b, 0);
                    var pct = total ? (val / total * 100).toFixed(1) : 0;
                    return val + ' (' + pct + '%)';
                }
            }
        }
    }
    JS);
    }
}
