<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjetoResource;
use App\Models\Projeto;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class DashboardComercialCoordenacao extends Page
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Dashboard comercial';

    protected static ?string $title = 'Dashboard coordenação comercial';

    protected static ?string $slug = 'dashboard-coordenacao-comercial';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.dashboard-comercial-coordenacao';

    public array $kpis = [];

    public array $responsaveisResumo = [];

    public array $marcasResumo = [];

    public array $shellAtrasadoDetalhes = [];

    public array $entradasRecentes = [];

    public array $pontosSolicitadosSemAgendamento = [];

    public array $charts = [];

    public function mount(): void
    {
        $this->loadData();
    }

    protected function baseQuery(): Builder
    {
        return Projeto::query();
    }

    public function loadData(): void
    {
        $this->kpis = Cache::remember('dashboard-comercial-coordenacao-kpis', now()->addMinutes(3), function (): array {
            $base = $this->baseQuery();
            $statusExpr = $this->statusComiteSqlExpression();

            $total = (clone $base)->count();
            $aprovados = (clone $base)->whereRaw("{$statusExpr} = 'aprovado'")->count();
            $emValidacao = (clone $base)->whereRaw("{$statusExpr} = 'em validacao'")->count();
            $reprovados = (clone $base)->whereRaw("{$statusExpr} = 'reprovado'")->count();
            $semResponsavel = (clone $base)->whereNull('resp_com')->count();
            $imovelPronto = (clone $base)->where('imovel_pronto', true)->count();
            $shell30 = (clone $base)
                ->whereNotNull('data_entrega_shell')
                ->whereBetween('data_entrega_shell', [now()->startOfDay(), now()->copy()->addDays(30)->endOfDay()])
                ->count();
            $shellAtrasado = (clone $base)
                ->whereNotNull('data_entrega_shell')
                ->whereDate('data_entrega_shell', '<', now()->startOfDay())
                ->count();

            return [
                'total' => $total,
                'aprovados' => $aprovados,
                'em_validacao' => $emValidacao,
                'reprovados' => $reprovados,
                'sem_responsavel' => $semResponsavel,
                'imovel_pronto' => $imovelPronto,
                'shell_30' => $shell30,
                'shell_atrasado' => $shellAtrasado,
            ];
        });

        $this->responsaveisResumo = Cache::remember('dashboard-comercial-coordenacao-responsaveis', now()->addMinutes(3), function (): array {
            return $this->baseQuery()
                ->leftJoin('users', 'projetos.resp_com', '=', 'users.id')
                ->selectRaw("COALESCE(users.name, 'Sem responsavel') as nome, COUNT(projetos.id) as total")
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($item) => [
                    'nome' => (string) $item->nome,
                    'total' => (int) $item->total,
                ])
                ->toArray();
        });

        $this->marcasResumo = Cache::remember(
            'dashboard-comercial-coordenacao-marcas',
            now()->addMinutes(3),
            function (): array {
                $marcaExpr = "COALESCE(NULLIF(marca, ''), 'Sem marca')";

                $subquery = $this->baseQuery()
                    ->selectRaw("{$marcaExpr} as marca");

                return DB::query()
                    ->fromSub($subquery, 't')
                    ->selectRaw('marca, COUNT(*) as total')
                    ->groupBy('marca')
                    ->orderByDesc('total')
                    ->limit(6)
                    ->get()
                    ->map(fn ($item) => [
                        'marca' => (string) $item->marca,
                        'total' => (int) $item->total,
                    ])
                    ->toArray();
            }
        );

        $this->shellAtrasadoDetalhes = Cache::remember('dashboard-comercial-coordenacao-shell-atrasado-lista', now()->addMinutes(3), function (): array {
            return $this->baseQuery()
                ->with(['responsavelCom:id,name'])
                ->whereNotNull('data_entrega_shell')
                ->whereDate('data_entrega_shell', '<', now()->startOfDay())
                ->orderBy('data_entrega_shell')
                ->limit(8)
                ->get()
                ->map(function (Projeto $projeto): array {
                    $dataEntrega = $projeto->data_entrega_shell ? Carbon::parse($projeto->data_entrega_shell) : null;
                    $diasAtraso = $dataEntrega ? $dataEntrega->startOfDay()->diffInDays(now()->startOfDay()) : 0;

                    return [
                        'id' => $projeto->id,
                        'codigo' => (string) ($projeto->codigo ?: '-'),
                        'nome' => (string) ($projeto->nome ?: 'Ponto sem nome'),
                        'vencimento' => $dataEntrega?->format('d/m/Y') ?: '-',
                        'dias_atraso' => (int) $diasAtraso,
                        'responsavel' => (string) ($projeto->responsavelCom?->name ?: 'Sem responsavel'),
                        'url' => ProjetoResource::getUrl('visualizar-ponto', ['record' => $projeto->id]),
                    ];
                })
                ->values()
                ->all();
        });

        $this->entradasRecentes = Cache::remember('dashboard-comercial-coordenacao-entradas-recentes', now()->addMinutes(3), function (): array {
            return $this->baseQuery()
                ->with(['responsavelCom:id,name'])
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(function (Projeto $projeto): array {
                    $statusNormalizado = $this->normalizarStatusComite($projeto->status_comite);

                    return [
                        'id' => $projeto->id,
                        'codigo' => (string) ($projeto->codigo ?: '-'),
                        'nome' => (string) ($projeto->nome ?: 'Ponto sem nome'),
                        'criado_em' => optional($projeto->created_at)?->format('d/m H:i') ?: '-',
                        'responsavel' => (string) ($projeto->responsavelCom?->name ?: 'Sem responsavel'),
                        'status_label' => $this->rotuloStatusComite($statusNormalizado),
                        'status_tone' => $this->corStatusComite($statusNormalizado),
                        'url' => ProjetoResource::getUrl('visualizar-ponto', ['record' => $projeto->id]),
                    ];
                })
                ->values()
                ->all();
        });

        $this->pontosSolicitadosSemAgendamento = Cache::remember('dashboard-comercial-coordenacao-vt-solicitados-sem-agendamento', now()->addMinutes(3), function (): array {
            $visStatusExpr = $this->visStatusSqlExpression('vis_status');

            return $this->baseQuery()
                ->with(['responsavelCom:id,name', 'relatorioVisitaTecnica:id,projeto_id,agendado_em'])
                ->whereRaw("{$visStatusExpr} = 'sim'")
                ->where(function (Builder $query): void {
                    $query
                        ->whereDoesntHave('relatorioVisitaTecnica')
                        ->orWhereHas('relatorioVisitaTecnica', fn (Builder $q) => $q->whereNull('agendado_em'));
                })
                ->latest('updated_at')
                ->limit(8)
                ->get()
                ->map(function (Projeto $projeto): array {
                    return [
                        'id' => $projeto->id,
                        'codigo' => (string) ($projeto->codigo ?: '-'),
                        'nome' => (string) ($projeto->nome ?: 'Ponto sem nome'),
                        'criado_em' => optional($projeto->updated_at)?->format('d/m H:i') ?: '-',
                        'responsavel' => (string) ($projeto->responsavelCom?->name ?: 'Sem responsavel'),
                        'status_label' => 'Sem agendamento',
                        'status_tone' => 'warning',
                        'url' => ProjetoResource::getUrl('visualizar-ponto', ['record' => $projeto->id]),
                    ];
                })
                ->values()
                ->all();
        });

        $this->charts = array_values(array_filter([
            $this->chartStatusComite(),
            $this->chartResponsavel(),
            $this->chartMarcas(),
            $this->chartEvolucaoMensal(),
        ]));
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('novoPonto')
                ->label('Novo ponto')
                ->icon('heroicon-o-plus')
                ->url('/admin/cadastrar-ponto'),
            Action::make('minhaGestao')
                ->label('Gestão comercial')
                ->icon('heroicon-o-chart-bar-square')
                ->url('/admin/dashboard-comercial'),
            Action::make('agendaVt')
                ->label('Agenda VT')
                ->icon('heroicon-o-calendar-days')
                ->url('/admin/agenda-vt-comercial'),
        ];
    }

    private function chartStatusComite(): ?array
    {
        $series = [
            (int) ($this->kpis['aprovados'] ?? 0),
            (int) ($this->kpis['em_validacao'] ?? 0),
            (int) ($this->kpis['reprovados'] ?? 0),
        ];

        if (array_sum($series) === 0) {
            return null;
        }

        return [
            'id' => 'status-comite',
            'type' => 'donut',
            'title' => 'Status do Comite',
            'labels' => ['Aprovado', 'Em validacao', 'Reprovado'],
            'series' => $series,
        ];
    }

    private function chartResponsavel(): ?array
    {
        $statusExpr = $this->statusComiteSqlExpression('projetos.status_comite');

        $rows = $this->baseQuery()
            ->leftJoin('users', 'projetos.resp_com', '=', 'users.id')
            ->selectRaw("
                COALESCE(users.name, 'Sem responsavel') as nome,
                COUNT(projetos.id) as total,
                SUM(CASE WHEN {$statusExpr} = 'aprovado' THEN 1 ELSE 0 END) as aprovados
            ")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'por-responsavel',
            'type' => 'bar',
            'title' => 'Top Responsaveis Comerciais',
            'labels' => $rows->pluck('nome')->all(),
            'series' => [
                ['name' => 'Total', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()],
                ['name' => 'Aprovados', 'data' => $rows->pluck('aprovados')->map(fn ($v) => (int) $v)->all()],
            ],
            'horizontal' => true,
        ];
    }

    private function chartMarcas(): ?array
    {
        $marcaExpr = "COALESCE(NULLIF(marca, ''), 'Sem marca')";

        $subquery = $this->baseQuery()
            ->selectRaw("{$marcaExpr} as marca");

        $rows = DB::query()
            ->fromSub($subquery, 't')
            ->selectRaw('marca, COUNT(*) as total')
            ->groupBy('marca')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'por-marca',
            'type' => 'bar',
            'title' => 'Distribuicao por Marca',
            'labels' => $rows->pluck('marca')->all(),
            'series' => [
                ['name' => 'Pontos', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()],
            ],
            'horizontal' => true,
        ];
    }

    private function chartEvolucaoMensal(): ?array
    {
        $driver = DB::getDriverName();
        $dateFn = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $rows = $this->baseQuery()
            ->selectRaw("{$dateFn} as ym, COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'evolucao-mensal',
            'type' => 'line',
            'title' => 'Evolucao de Cadastros (12 meses)',
            'labels' => $rows->pluck('ym')->all(),
            'series' => [
                ['name' => 'Pontos cadastrados', 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()],
            ],
        ];
    }

    private function statusComiteSqlExpression(string $column = 'status_comite'): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), '_', ' '), 'ç', 'c'), 'ã', 'a'), 'á', 'a'), 'â', 'a'))";
    }

    private function visStatusSqlExpression(string $column = 'vis_status'): string
    {
        return "LOWER(TRIM(COALESCE({$column}, '')))";
    }

    private function normalizarStatusComite(?string $status): string
    {
        $normalizado = Str::of((string) ($status ?? ''))
            ->ascii()
            ->lower()
            ->replace('_', ' ')
            ->squish()
            ->toString();

        return match (true) {
            $normalizado === 'aprovado' => 'aprovado',
            $normalizado === 'reprovado' => 'reprovado',
            str_contains($normalizado, 'valida') => 'em_validacao',
            default => 'sem_status',
        };
    }

    private function rotuloStatusComite(string $status): string
    {
        return match ($status) {
            'aprovado' => 'Aprovado',
            'em_validacao' => 'Em validacao',
            'reprovado' => 'Reprovado',
            default => 'Sem status',
        };
    }

    private function corStatusComite(string $status): string
    {
        return match ($status) {
            'aprovado' => 'success',
            'em_validacao' => 'warning',
            'reprovado' => 'danger',
            default => 'gray',
        };
    }

    protected static function isAllowedCommercialUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $hasSetorComercial = $user->setores()
            ->whereRaw('LOWER(setor) = ?', ['comercial'])
            ->exists();

        if (! $hasSetorComercial) {
            return false;
        }

        return $user->hasRole('Gestor');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        if (! $user->can('View:DashboardComercialCoordenacao')) {
            return false;
        }

        return static::isAllowedCommercialUser($user);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
