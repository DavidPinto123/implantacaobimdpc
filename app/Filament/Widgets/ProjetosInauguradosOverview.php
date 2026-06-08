<?php

namespace App\Filament\Widgets;

use App\Models\Projeto;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjetosInauguradosOverview extends BaseWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected function getStats(): array
    {
        $ano = Carbon::now()->year;
        $meta = 0; // Pode vir de config/tabela

        $total = Projeto::where('status', 'Inaugurada')
            ->whereYear('imp_fim', $ano)
            ->count();

        $mesAtual = Carbon::now()->month;
        $projecao = ($total / max(1, $mesAtual)) * 12;
        $atingirMeta = $projecao >= $meta;

        // Dados reais por mês
        $dadosMes = Projeto::selectRaw('MONTH(imp_fim) as mes, COUNT(*) as total')
            ->where('pipeline', 'PIPE 2025')
            ->where('status', 'Inaugurada')
            ->whereYear('imp_fim', $ano)
            ->groupBy('mes')
            ->pluck('total', 'mes')
            ->toArray();

        // Garante 12 meses
        $chartInaugurados = array_fill(1, 12, 0);
        foreach ($dadosMes as $mes => $totalMes) {
            $chartInaugurados[$mes] = $totalMes;
        }

        // Chart da projeção (simples, proporcional)
        $chartProjecao = array_map(fn ($val) => round($val * ($projecao / max(1, $total))), $chartInaugurados);

        $totalEmObras = Projeto::where('status', 'Obras')
            ->where('pipeline', 'PIPE 2025')
            ->whereYear('imp_fim', 2025)
            ->whereNotNull('imp_fim')
            ->count();

        $totalFaseProjeto = Projeto::where('status', 'Em processo')
            ->where('pipeline', 'PIPE 2025')
            ->whereYear('imp_fim', 2025)
            ->whereNotNull('imp_fim')
            ->count();

        $totalRisco = Projeto::where('risco_obra', 1)->count();
        $totalSemRisco = Projeto::where('risco_obra', 0)->count();

        return [
            Stat::make("Meta {$ano}", $meta)
                ->description('Projetos previstos para inaugurar')
                ->icon('heroicon-o-flag'),

            Stat::make('Inaugurados até agora', $total)
                ->description("Ano $ano")
                ->descriptionIcon($total >= $meta ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                ->chart(array_values($chartInaugurados))
                ->color($total >= $meta ? 'success' : 'blue'),

            Stat::make('Projeção', number_format($projecao, 0))
                ->description($atingirMeta ? 'No ritmo para alcançar' : 'Abaixo do ritmo')
                ->descriptionIcon($atingirMeta ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->chart(array_values($chartProjecao))
                ->color($atingirMeta ? 'success' : 'blue'),

            Stat::make('Em obras', $totalEmObras)
                ->description('Projetos atualmente em obra')
                ->icon('heroicon-o-building-office')
                ->color('info'),

            Stat::make('Fase de Projeto', $totalFaseProjeto)
                ->description('Projetos em fase de projeto')
                ->icon('heroicon-o-document-text')
                ->color('gray'),

            Stat::make('Projetos com Risco', $totalRisco)
                ->description('Possuem risco de obra')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Projetos sem Risco', $totalSemRisco)
                ->description('Sem risco registrado')
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}
