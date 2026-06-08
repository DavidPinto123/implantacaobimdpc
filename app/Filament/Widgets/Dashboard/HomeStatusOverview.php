<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\AppliesHomeFilters;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HomeStatusOverview extends StatsOverviewWidget
{
    use AppliesHomeFilters;
    use InteractsWithPageFilters;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $query = $this->getFilteredProjetosQuery();

        $inaugurada = (clone $query)->where('status', 'Inaugurada')->count();
        $obras = (clone $query)->where('status', 'Obras')->count();
        $emProcesso = (clone $query)->where('status', 'Em processo')->count();
        $meta = (int) env('DASHBOARD_META_PROJETOS', 0);

        return [
            Stat::make('Inaugurada', number_format($inaugurada, 0, ',', '.'))
                ->color('success'),
            Stat::make('Obras', number_format($obras, 0, ',', '.'))
                ->color('warning'),
            Stat::make('Em Processo', number_format($emProcesso, 0, ',', '.'))
                ->color('info'),
            Stat::make('Meta', number_format($meta, 0, ',', '.'))
                ->color('gray'),
        ];
    }
}
