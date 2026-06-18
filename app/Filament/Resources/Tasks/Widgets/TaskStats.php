<?php

namespace App\Filament\Resources\Tasks\Widgets;

use App\Filament\Resources\Tasks\Pages\ListTasks;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;
use App\Filament\Resources\Tasks\TaskResource;

class TaskStats extends BaseWidget
{
    use InteractsWithPageTable;

    public array $activeTableFilters = [];

    #[On('task-table-filters-updated')]
    public function updateActiveTableFilters(array $filters): void
    {
        $this->activeTableFilters = $filters;
    }

    protected function getTablePage(): string
    {
        return ListTasks::class;
    }

    protected function getColumns(): int
    {
        return 6;
    }

    protected function getStats(): array
    {
        

        $base = TaskResource::getEloquentQuery();

        $assignedTo = $this->activeTableFilters['assigned_to']['value'] ?? null;

        if ($assignedTo) {
            $base->where('assigned_to', $assignedTo);
        }

        $pendentes = (clone $base)
            ->where('status', 'pendente')
            ->count();

        $emAndamento = (clone $base)
            ->where('status', 'em_andamento')
            ->count();

        $concluidas = (clone $base)
            ->where('status', 'concluida')
            ->count();

        $canceladas = (clone $base)
            ->where('status', 'cancelada')
            ->count();

        $atrasadas = (clone $base)
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->whereNotNull('termino_programado')
            ->whereDate('termino_programado', '<', today())
            ->count();

        $futuras = (clone $base)
            ->where('status', 'pendente')
            ->whereDate('inicio', '>', today())
            ->count();

        return [
            Stat::make('Não iniciadas', $pendentes)
                ->color('warning')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer stat-pendentes',
                    'x-on:click' => "\$wire.dispatch('task-status-card-selected', { status: 'pendente' })",
                ]),

            Stat::make('Em andamento', $emAndamento)
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer stat-em-andamento',
                    'x-on:click' => "\$wire.dispatch('task-status-card-selected', { status: 'em_andamento' })",
                ]),

            Stat::make('Atrasadas', $atrasadas)
                ->color('danger')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer stat-atrasadas',
                    'x-on:click' => "\$wire.dispatch('task-status-card-selected', { status: 'atrasadas' })",
                ]),

            Stat::make('Concluídas', $concluidas)
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer stat-concluidas',
                    'x-on:click' => "\$wire.dispatch('task-status-card-selected', { status: 'concluida' })",
                ]),

            Stat::make('Canceladas', $canceladas)
                ->color('gray')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer stat-canceladas',
                    'x-on:click' => "\$wire.dispatch('task-status-card-selected', { status: 'cancelada' })",
                ]),

            Stat::make('Previstas', $futuras)
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer stat-previstas',
                    'x-on:click' => "\$wire.dispatch('task-status-card-selected', { status: 'previstas' })",
                ]),
        ];
    }
}
