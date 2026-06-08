<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DashboardTarefas extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Dashboard de Tarefas';

    protected static ?string $title = 'Dashboard de Tarefas';

    protected static string|null|\UnitEnum $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dashboard-tarefas';

    public ?array $data = [];

    public array $charts = [];

    public $chartImages = [];

    protected $listeners = ['setCharts'];

    public function setCharts($data)
    {
        $this->chartImages = $data['charts'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimir_pdf')
                ->label('Imprimir PDF')
                ->icon('heroicon-o-printer')
                // ->color('#DDA518')

                ->extraAttributes([
                    'style' => 'background-color:#DDA518;',
                    'onclick' => 'enviarGraficosParaLivewire()',
                ])
                ->action('gerarPdf'),
        ];
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->loadCharts();
    }

    public function updated($name, $value): void
    {
        if (str_starts_with($name, 'data.')) {
            $this->resetTable();
            $this->loadCharts();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaForm::make([
                    Grid::make(3)->schema([

                        DatePicker::make('data_inicial')
                            ->label('Data inicial')
                            ->live(),

                        DatePicker::make('data_final')
                            ->label('Data final')
                            ->live(),

                        Select::make('assigned_to')
                            ->label('Responsável')
                            ->options(function () {
                                $user = auth()->user();

                                if (! $user) {
                                    return [];
                                }

                                $setorIds = $user->setores()->pluck('setores.id')->toArray();

                                return User::query()
                                    ->whereHas('setores', function ($query) use ($setorIds) {
                                        $query->whereIn('setores.id', $setorIds);
                                    })
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Todos')
                            ->live()
                            ->visible(fn () => auth()->user()?->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])),

                    ]),
                ]),
            ])
            ->statePath('data');
    }

    public function getCards(): array
    {
        $query = $this->applyDashboardFilters(Task::query());

        $pendentes = (clone $query)
            ->where('status', 'pendente')
            ->count();

        $emAndamento = (clone $query)
            ->where('status', 'em_andamento')
            ->count();

        $atrasadas = (clone $query)
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->whereDate('termino_programado', '<', now()->toDateString())
            ->count();

        $concluidas = (clone $query)
            ->where('status', 'concluida')
            ->count();

        $canceladas = (clone $query)
            ->where('status', 'cancelada')
            ->count();

        $futuras = (clone $query)
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->whereDate('inicio', '>', today())
            ->count();

        return [
            [
                'label' => 'Pendentes',
                'value' => $pendentes,
            ],
            [
                'label' => 'Em andamento',
                'value' => $emAndamento,
            ],
            [
                'label' => 'Atrasadas',
                'value' => $atrasadas,
            ],
            [
                'label' => 'Concluídas',
                'value' => $concluidas,
            ],
            [
                'label' => 'Canceladas',
                'value' => $canceladas,
            ],
            [
                'label' => 'Futuras',
                'value' => $futuras,
            ],
        ];
    }

    protected function loadCharts(): void
    {
        $query = $this->getBaseFilteredTasksQuery();

        $this->charts = [];

        if ($c = $this->chartStatus(clone $query)) {
            $this->charts[] = $c;
        }

        if ($c = $this->chartAtrasadas(clone $query)) {
            $this->charts[] = $c;
        }

        if ($c = $this->chartTarefasPorUsuario()) {
            $this->charts[] = $c;
        }
    }

    protected function chartStatus($query): ?array
    {
        $statuses = [
            'pendente' => 'Pendente',
            'em_andamento' => 'Em andamento',
            'concluida' => 'Concluída',
            'cancelada' => 'Cancelada',
        ];

        $rows = $query
            ->reorder()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $labels = [];
        $series = [];

        foreach ($statuses as $key => $label) {
            $labels[] = $label;
            $series[] = (int) ($rows[$key] ?? 0);
        }

        return [
            'id' => 'tarefas-por-status',
            'type' => 'donut',
            'title' => 'Tarefas por Status',
            'labels' => $labels,
            'series' => $series,
        ];
    }

    protected function chartAtrasadas($query): ?array
    {
        $statuses = [
            'pendente' => 'Pendentes Atrasadas',
            'em_andamento' => 'Em andamento Atrasadas',
        ];

        $rows = $query
            ->reorder()
            ->whereNotIn('status', ['concluida', 'cancelada'])
            ->whereNotNull('termino_programado')
            ->whereDate('termino_programado', '<', now()->toDateString())
            ->whereIn('status', ['pendente', 'em_andamento'])
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $labels = [];
        $series = [];

        foreach ($statuses as $key => $label) {
            $labels[] = $label;
            $series[] = (int) ($rows[$key] ?? 0);
        }

        // se não houver nenhuma atrasada, não renderiza o gráfico
        // if (array_sum($series) === 0) {
        // return null;
        // }

        return [
            'id' => 'tarefas-atrasadas-por-status',
            'type' => 'donut',
            'title' => 'Tarefas Atrasadas',
            'labels' => $labels,
            'series' => $series,
        ];
    }

    protected function chartTarefasPorUsuario(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $filtros = $this->data ?? [];

        $dataInicial = $filtros['data_inicial'] ?? null;
        $dataFinal = $filtros['data_final'] ?? null;
        $assignedTo = $filtros['assigned_to'] ?? null;

        $setorIds = $user->setores()->pluck('setores.id')->toArray();

        $query = User::query()
            ->whereHas('setores', function ($query) use ($setorIds) {
                $query->whereIn('setores.id', $setorIds);
            })
            ->leftJoin('tasks', function ($join) use ($dataInicial, $dataFinal, $setorIds) {
                $join->on('tasks.assigned_to', '=', 'users.id');

                if (! empty($setorIds)) {
                    $join->whereIn('tasks.setor_id', $setorIds);
                }

                if (! empty($dataInicial)) {
                    $join->whereDate('tasks.created_at', '>=', $dataInicial);
                }

                if (! empty($dataFinal)) {
                    $join->whereDate('tasks.created_at', '<=', $dataFinal);
                }
            });

        // Se selecionou um responsável, conta só as tarefas dele,
        // mas mantém todos os usuários do setor no gráfico.
        if (! empty($assignedTo)) {
            $query->selectRaw(
                'users.name as responsavel_nome, SUM(CASE WHEN tasks.assigned_to = ? THEN 1 ELSE 0 END) as total',
                [$assignedTo]
            );
        } else {
            $query->selectRaw('users.name as responsavel_nome, COUNT(tasks.id) as total');
        }

        $rows = $query
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->orderBy('users.name')
            // ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'tarefas-por-usuario',
            'type' => 'bar',
            'title' => 'Tarefas por Usuário',
            'labels' => $rows->pluck('responsavel_nome')
                ->map(fn ($nome) => Str::limit((string) $nome, 18))
                ->all(),
            'series' => [
                [
                    'name' => 'Tarefas',
                    'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
                ],
            ],
        ];
    }

    public function getBaseFilteredTasksQuery()
    {
        $query = Task::query()
            ->with(['category', 'solicitante', 'responsavel', 'marca', 'setor']);

        return $this->applyDashboardFilters($query);
    }

    public function getFilteredTasksQuery()
    {
        return $this->getBaseFilteredTasksQuery()
            ->orderByDesc('created_at');
    }

    protected function applyDashboardFilters($query)
    {
        $filtros = $this->data ?? [];
        $user = auth()->user();

        $dataInicial = $filtros['data_inicial'] ?? null;
        $dataFinal = $filtros['data_final'] ?? null;
        $assignedTo = $filtros['assigned_to'] ?? null;

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $setorIds = $user->setores()->pluck('setores.id')->toArray();

        if ($user->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])) {
            $query->whereIn('tasks.setor_id', $setorIds);

            if (! empty($assignedTo)) {
                $query->where('tasks.assigned_to', $assignedTo);
            }
        } else {
            $query->where('tasks.assigned_to', $user->id);
        }

        if (! empty($dataInicial)) {
            $query->whereDate('tasks.created_at', '>=', $dataInicial);
        }

        if (! empty($dataFinal)) {
            $query->whereDate('tasks.created_at', '<=', $dataFinal);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        $query = $this->getFilteredTasksQuery();

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('id')->label('ID')->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        $isOverdue =
                            $record->termino_programado
                            && $record->status !== 'concluida'
                            && $record->status !== 'cancelada'
                            && $record->termino_programado->lt(today());

                        if ($isOverdue) {
                            return 'Atrasada';
                        }

                        return match ($state) {
                            'pendente' => 'Pendente',
                            'em_andamento' => 'Em andamento',
                            'concluida' => 'Concluída',
                            'cancelada' => 'Cancelada',
                            default => (string) $state,
                        };
                    })
                    ->color(function ($state, $record) {
                        $isOverdue =
                            $record->termino_programado
                            && $record->status !== 'concluida'
                            && $record->status !== 'cancelada'
                            && $record->termino_programado->lt(today());

                        if ($isOverdue) {
                            return 'danger';
                        }

                        return match ($state) {
                            'pendente' => 'warning',
                            'em_andamento' => 'info',
                            'concluida' => 'success',
                            'cancelada' => 'gray',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('setor.setor')
                    ->label('Setor'),

                TextColumn::make('title')
                    ->label('Tarefa')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description)
                    ->extraAttributes([
                        'style' => 'min-width: 350px;',
                    ]),

                TextColumn::make('category.name')->label('Categoria'),
                TextColumn::make('sigla')->label('Sigla'),
                TextColumn::make('marca.nome')->label('Unidade')->searchable(),
                TextColumn::make('solicitante.name')->label('Solicitante'),
                TextColumn::make('responsavel.name')->label('Responsável'),
                TextColumn::make('prazo')->label('Prazo (dias)'),
                TextColumn::make('inicio')->label('Início')->date('d/m/Y'),
                TextColumn::make('termino_programado')->label('Término Programado')->date('d/m/Y'),
                TextColumn::make('data_entrega')->label('Data de Entrega')->date('d/m/Y'),
                TextColumn::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function gerarPdf()
    {
        $tarefas = $this->getFilteredTasksQuery()->get();

        $cards = $this->getCards();

        $filtros = $this->data ?? [];

        $dataInicial = $filtros['data_inicial'] ?? null;
        $dataFinal = $filtros['data_final'] ?? null;
        $assignedTo = $filtros['assigned_to'] ?? null;

        $responsavel = null;

        if ($assignedTo) {
            $responsavel = User::find($assignedTo)?->name;
        }

        $pdf = Pdf::loadView('pdf.dashboard-tarefas', [
            'tarefas' => $tarefas,
            'cards' => $cards,
            'charts' => $this->chartImages,
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'responsavel' => $responsavel,
            'total' => $tarefas->count(),
            'emitidoEm' => now(),
        ])->setPaper('a3', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'dashboard-tarefas.pdf'
        );
    }
}
