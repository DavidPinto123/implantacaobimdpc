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

    protected string $view = 'filament.resources.tasks.pages.list-tasks';

    #[Url]
    public ?string $status_card = null;

    #[Url]
    public ?int $filtroProjetoId = null;

    public string $visualizacao = 'tabela';

    public string $kanbanAgrupamento = 'status';

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
        'previstas',
    ];

    public function getFiltroProjetosOptions(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $taskQuery = \App\Models\Task::query()->whereNotNull('projeto_id');

        if (! $user->hasAnyRole(['super_admin', 'admin', 'PMO', 'Planejamento Estratégico'])) {
            $setorIds = $user->setores()->pluck('setores.id')->toArray();
            if ($user->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])) {
                $taskQuery->where(function ($q) use ($setorIds, $user) {
                    $q->whereIn('setor_id', $setorIds)
                      ->orWhere(fn ($s) => $s->whereNull('setor_id')->where('assigned_to', $user->id));
                });
            } else {
                $taskQuery->where('assigned_to', $user->id);
            }
        }

        $projetoIds = $taskQuery->pluck('projeto_id')->unique();

        return \App\Models\Projeto::whereIn('id', $projetoIds)
            ->orderBy('nome')
            ->pluck('nome', 'id')
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('limpar_filtros')
                ->label('Limpar filtros')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->url(ListTasks::getUrl())
                ->visible(function () {
                    return filled($this->status_card)
                        || filled($this->filtroProjetoId)
                        || filled($this->tableFilters['assigned_to']['value'] ?? null);
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
                $this->filtroProjetoId,
                fn (Builder $query) => $query->where('projeto_id', $this->filtroProjetoId)
            )
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

                        'previstas'
                            => $query->where('status', 'pendente')->whereDate('inicio', '>', today()),

                        default => null,
                    };
                }
            );
    }

    public function updatedTableFilters(): void
    {
        $this->dispatch('task-table-filters-updated', filters: $this->tableFilters);
    }

    public function moverTarefaKanban(int $id, string $novoStatus): void
    {
        if (! in_array($novoStatus, ['pendente', 'em_andamento', 'concluida', 'cancelada'], true)) {
            return;
        }
        $task = \App\Models\Task::find($id);
        if (! $task) {
            return;
        }
        $task->status = $novoStatus;
        $task->save();
    }

    public function moverTarefaResponsavel(int $id, ?string $userId): void
    {
        $task = \App\Models\Task::find($id);
        if (! $task) {
            return;
        }
        $task->assigned_to = $userId ? (int) $userId : null;
        $task->save();
    }

    public function getKanbanTarefas(): \Illuminate\Support\Collection
    {
        $tasks = $this->getTableQuery()
            ->with(['responsavel', 'projeto', 'cronogramaFaseItem.fase'])
            ->get();

        if ($this->kanbanAgrupamento === 'profissional') {
            return $tasks->groupBy(fn ($t) => $t->assigned_to ?? 0);
        }

        return $tasks->groupBy(function ($t) {
            $hoje = today();
            if ($t->termino_programado
                && $t->termino_programado->lt($hoje)
                && ! in_array($t->status, ['concluida', 'cancelada'], true)) {
                return 'atrasadas';
            }
            if ($t->inicio && $t->inicio->gt($hoje) && $t->status === 'pendente') {
                return 'previstas';
            }
            return $t->status;
        });
    }

    public function getKanbanUsuarios(): \Illuminate\Support\Collection
    {
        if ($this->kanbanAgrupamento !== 'profissional') {
            return collect();
        }

        return $this->getTableQuery()
            ->with('responsavel')
            ->get()
            ->map->responsavel
            ->filter()
            ->unique('id')
            ->sortBy('name');
    }
}
