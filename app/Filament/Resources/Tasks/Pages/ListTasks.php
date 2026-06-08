<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\Tasks\Widgets\TaskStats;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListTasks extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TaskResource::class;

    #[Url]
    public ?string $status_card = null;

    public bool $isStatsQuery = false;

    #[On('task-status-card-selected')]
    public function setStatusCard(string $status): void
    {
        if (! in_array($status, $this->allowedFilters, true)) {
            return;
        }

        $this->status_card = $status;

        $this->resetPage(); // opcional
    }

    protected array $allowedFilters = [
        'pendente',
        'em_andamento',
        'concluida',
        'cancelada',
        'atrasadas',
        'futuras',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('limpar_filtros')
                ->label('Limpar filtros')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url(ListTasks::getUrl())
                ->visible(function () {
                    $hasStatusCard = filled($this->status_card);

                    $hasAssignedTo = filled($this->tableFilters['assigned_to']['value'] ?? null);

                    return $hasStatusCard || $hasAssignedTo;
                }),
            Actions\CreateAction::make()
                ->label('Criar tarefa')
                ->modalHeading('Criar tarefa')
                ->modalSubmitActionLabel('Salvar')
                ->modalWidth('7xl'), // opcional
            
        ];
    }

    protected function getHeaderWidgetsData(): array
    {
        return [
            'activeTableFilters' => $this->tableFilters,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TaskStats::class,
        ];
    }   


    public function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->when(
                $this->status_card && ! $this->isStatsQuery,
                function (Builder $query) {
                    match ($this->status_card) {
                        'pendente',
                        'em_andamento',
                        'concluida',
                        'cancelada'
                            => $query->where('status', $this->status_card),

                        'atrasadas'
                            => $query
                                ->whereNotIn('status', ['concluida', 'cancelada'])
                                ->whereNotNull('termino_programado')
                                ->whereDate('termino_programado', '<', today()),

                        'futuras'
                            => $query->whereDate('inicio', '>', today()),

                        default => null,
                    };
                }
            );
    }

    public function updatedTableFilters(): void
    {
        $this->dispatch('task-table-filters-updated', filters: $this->tableFilters);
    }
}
