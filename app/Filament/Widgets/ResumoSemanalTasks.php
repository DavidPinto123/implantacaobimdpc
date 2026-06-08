<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumoSemanalTasks extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $inicioSemana = now()->startOfWeek();
        $fimSemana = now()->endOfWeek();

        $user = auth()->user();

        $base = Task::query();

        // colaborador vê só as próprias tarefas
        if (! $user?->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])) {
            $base->where('assigned_to', $user->id);
        }

        $pendentes = (clone $base)
            ->where('status', 'pendente')
            ->count();

        $emAndamento = (clone $base)
            ->where('status', 'em_andamento')
            ->count();

        $atrasadas = (clone $base)
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->whereDate('termino_programado', '<', now()->toDateString())
            ->count();

        $concluidas = (clone $base)
            ->where('status', 'concluida')
            ->whereBetween('data_entrega', [$inicioSemana, $fimSemana])
            ->count();

        $canceladas = (clone $base)
            ->where('status', 'cancelada')
            ->whereBetween('updated_at', [$inicioSemana, $fimSemana])
            ->count();

        return [
            Stat::make('Pendentes', $pendentes)
                ->description('Tarefas pendentes no momento'),

            Stat::make('Em andamento', $emAndamento)
                ->description('Tarefas em andamento no momento'),

            Stat::make('Atrasadas', $atrasadas)
                ->description('Tarefas vencidas e não finalizadas'),

            Stat::make('Concluídas na semana', $concluidas)
                ->description('Finalizadas nesta semana'),

            Stat::make('Canceladas na semana', $canceladas)
                ->description('Canceladas nesta semana'),
        ];
    }
}
