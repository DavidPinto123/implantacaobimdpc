
<?php
/*
namespace App\Filament\Widgets\Dashboard;

use Filament\Widgets\ChartWidget;
use App\Models\Acompanhamento;
use Illuminate\Support\Facades\DB;

class GraficoAcompanhamento extends ChartWidget
{
    protected static ?string $heading = 'Gráfico de Acompanhamento';

    protected int|string|array $columnSpan = 2;

    protected int|string|array $rowSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $dadosInauguradas = Acompanhamento::select(DB::raw('MONTH(inauguracao) as mes'), DB::raw('COUNT(*) as total'))
            ->where('status', 'INAUGURADA')
            ->whereYear('inauguracao', 2025)
            ->where('pipeline', 'PIPE 2025')
            ->groupBy(DB::raw('MONTH(inauguracao)'))
            ->orderBy('mes')
            ->get();

        $dadosObras = Acompanhamento::select(DB::raw('MONTH(entrega_obra) as mes'), DB::raw('COUNT(*) as total'))
            ->where('status', 'OBRAS')
            ->whereYear('entrega_obra', 2025)
            ->where('pipeline', 'PIPE 2025')
            ->groupBy(DB::raw('MONTH(entrega_obra)'))
            ->orderBy('mes')
            ->get();

        $dadosProcesso = Acompanhamento::select(DB::raw('MONTH(inicio_projeto) as mes'), DB::raw('COUNT(*) as total'))
            ->where('status', 'EM PROCESSO')
            ->whereYear('inicio_projeto', 2025)
            ->where('pipeline', 'PIPE 2025')
            ->groupBy(DB::raw('MONTH(inicio_projeto)'))
            ->orderBy('mes')
            ->get();

        $inauguradas = array_fill(0, 12, 0);
        $obras = array_fill(0, 12, 0);
        $emProcesso = array_fill(0, 12, 0);

        foreach ($dadosInauguradas as $dado) {
            $inauguradas[$dado->mes - 1] = $dado->total;
        }

        foreach ($dadosObras as $dado) {
            $obras[$dado->mes - 1] = $dado->total;
        }

        foreach ($dadosProcesso as $dado) {
            $emProcesso[$dado->mes - 1] = $dado->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Inaugurada',
                    'data' => $inauguradas,
                    'backgroundColor' => '#a1d89a',
                    'stack' => 'stack1',
                  	'borderWidth' => 0,
                ],
                [
                    'label' => 'Em obra',
                    'data' => $obras,
                    'backgroundColor' => '#ffb000',
                    'stack' => 'stack1',
                  	'borderWidth' => 0,
                ],
                [
                    'label' => 'Fase de projeto',
                    'data' => $emProcesso,
                    'backgroundColor' => '#d05757',
                    'stack' => 'stack1',
                  	'borderWidth' => 0,
                ],
            ],
            'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        ];
    }

    protected function getOptions(): array
{
    return [
        'plugins' => [
    'datalabels' => [
        'display' => true,
        'anchor' => 'end',
        'align' => 'top',
        'color' => '#000',
        'font' => [
            'weight' => 'bold',
            'size' => 14,
        ],
        'formatter' => <<<JS
            function(value) {
                return value;
            }
        JS,
    ],
],
        'scales' => [
            'y' => [
                'beginAtZero' => true,
            ],
        ],
    ];
}


    protected function getScripts(): array
{
    return [
        '/js/chartjs-plugin-datalabels.min.js', // plugin baixado
        '/js/chart-plugins.js',                 // script de registro
    ];
}
}
*/
