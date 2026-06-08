<?php

namespace App\Filament\Pages\PosObra;

use App\Enums\PosObra\StatusPendencia;
use App\Enums\PosObra\UrgenciaPendencia;
use App\Filament\Resources\PosObra\PendenciaResource;
use App\Models\PosObra\AtualizacaoStatus;
use App\Models\PosObra\Pendencia;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class DashboardPosObra extends Page
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static UnitEnum|string|null $navigationGroup = 'Pós Obra';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard Pós Obra';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.pos-obra.dashboard-pos-obra';

    public bool $modoAtrasadas = false;

    public array $todasAtrasadas = [];

    public array $statsAtrasadas = [];

    public array $kpis = [];

    public array $sla = [];

    public array $charts = [];

    public array $urgentes = [];

    public array $atrasadas = [];

    public array $ultimasAtualizacoes = [];

    public array $pendenciasRecentes = [];

    private const TERMINAL = [
        StatusPendencia::CONCLUIDA->value,
        StatusPendencia::AS_ORCAMENTOS->value,
        StatusPendencia::GARANTIA_SOLICITADA->value,
        StatusPendencia::PROJ_COMPLEMENTAR->value,
        StatusPendencia::CANCELADA->value,
    ];

    public function mount(): void
    {
        $this->loadData();
    }

    public function verAtrasadas(): void
    {
        $this->modoAtrasadas = true;
        $this->todasAtrasadas = $this->buildTodasAtrasadas();
        $this->statsAtrasadas = $this->buildStatsAtrasadas();
    }

    public function voltarDashboard(): void
    {
        $this->modoAtrasadas = false;
        $this->todasAtrasadas = [];
        $this->statsAtrasadas = [];
    }

    private function buildTodasAtrasadas(): array
    {
        return Pendencia::query()
            ->with(['obra:id,projeto_id,unidade', 'obra.projeto:id,sigla', 'gestor:id,name', 'construtora:id,nome', 'disciplina:id,label'])
            ->whereNotIn('status', self::TERMINAL)
            ->whereNotNull('data_termino')
            ->where('data_termino', '<', now()->toDateString())
            ->orderBy('data_termino')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'descricao' => $p->descricao,
                'obra' => $p->obra?->sigla ?? $p->obra?->unidade ?? '—',
                'gestor' => $p->gestor?->name ?? '—',
                'construtora' => $p->construtora?->nome ?? '—',
                'disciplina' => $p->disciplina?->label ?? '—',
                'status' => $p->status->label(),
                'urgencia' => $p->urgencia?->label() ?? '—',
                'urgencia_val' => $p->urgencia?->value ?? '',
                'data_termino' => $p->data_termino?->format('d/m/Y') ?? '—',
                'dias_atraso' => $p->data_termino ? (int) $p->data_termino->diffInDays(now()) : 0,
                'url' => PendenciaResource::getUrl('view', ['record' => $p->id]),
            ])
            ->toArray();
    }

    private function buildStatsAtrasadas(): array
    {
        $items = collect($this->todasAtrasadas);
        $total = $items->count();
        $mediaAtraso = $total > 0 ? round($items->avg('dias_atraso'), 1) : 0;
        $maxAtraso = $items->max('dias_atraso') ?? 0;

        $porFornecedor = $items->groupBy('construtora')->map->count()->sortDesc()->toArray();
        $porGestor = $items->groupBy('gestor')->map->count()->sortDesc()->toArray();
        $porDisciplina = $items->groupBy('disciplina')->map->count()->sortDesc()->toArray();
        $porUrgencia = $items->groupBy('urgencia')->map->count()->sortDesc()->toArray();

        return compact('total', 'mediaAtraso', 'maxAtraso', 'porConstrutora', 'porGestor', 'porDisciplina', 'porUrgencia');
    }

    public function loadData(): void
    {
        $base = Pendencia::query();

        $this->kpis = $this->buildKpis(clone $base);
        $this->sla = $this->buildSla(clone $base);
        $this->urgentes = $this->buildUrgentes();
        $this->atrasadas = $this->buildAtrasadas();
        $this->ultimasAtualizacoes = $this->buildUltimasAtualizacoes();
        $this->pendenciasRecentes = $this->buildPendenciasRecentes();

        $this->charts = array_values(array_filter([
            $this->chartStatus(clone $base),
            $this->chartUrgencia(clone $base),
            $this->chartGestor(clone $base),
            $this->chartTaxaGestor(clone $base),
            $this->chartConstrutora(clone $base),
            $this->chartDisciplina(clone $base),
            $this->chartRanking(clone $base),
            $this->chartMensal(clone $base),
        ]));
    }

    private function buildKpis(Builder $query): array
    {
        $total = $query->count();
        $terminal = (clone $query)->whereIn('status', self::TERMINAL)->count();
        $ativas = $total - $terminal;
        $atrasadas = (clone $query)
            ->whereNotIn('status', self::TERMINAL)
            ->whereNotNull('data_termino')
            ->where('data_termino', '<', now()->toDateString())
            ->count();
        $urgentes = (clone $query)->where('urgencia', UrgenciaPendencia::P3->value)->count();
        $taxa = $total > 0 ? round(($terminal / $total) * 100, 1) : 0;

        return compact('total', 'terminal', 'ativas', 'atrasadas', 'urgentes', 'taxa');
    }

    private function buildSla(Builder $query): array
    {
        $ativos = (clone $query)->whereNotIn('status', self::TERMINAL)->whereNotNull('data_termino');
        $total_ativos = $ativos->count();
        $fora_prazo = (clone $ativos)->where('data_termino', '<', now()->toDateString())->count();
        $dentro_prazo = $total_ativos - $fora_prazo;
        $pct = $total_ativos > 0 ? round(($dentro_prazo / $total_ativos) * 100) : 0;

        return compact('total_ativos', 'dentro_prazo', 'fora_prazo') + ['percentual_cumprimento' => $pct];
    }

    private function buildUrgentes(): array
    {
        return Pendencia::query()
            ->with(['obra:id,unidade', 'disciplina:id,label'])
            ->whereNotIn('status', self::TERMINAL)
            ->where('urgencia', UrgenciaPendencia::P3->value)
            ->orderBy('data_inicio')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'descricao' => $p->descricao,
                'obra' => $p->obra?->unidade ?? '—',
                'status' => $p->status->label(),
                'disciplina' => $p->disciplina?->label ?? '—',
            ])
            ->toArray();
    }

    private function buildAtrasadas(): array
    {
        return Pendencia::query()
            ->with(['obra:id,unidade', 'gestor:id,name'])
            ->whereNotIn('status', self::TERMINAL)
            ->whereNotNull('data_termino')
            ->where('data_termino', '<', now()->toDateString())
            ->orderBy('data_termino')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'descricao' => $p->descricao,
                'obra' => $p->obra?->unidade ?? '—',
                'gestor' => $p->gestor?->name ?? '—',
                'data_termino' => $p->data_termino?->format('d/m/Y') ?? '—',
                'status' => $p->status->label(),
            ])
            ->toArray();
    }

    private function buildUltimasAtualizacoes(): array
    {
        return AtualizacaoStatus::query()
            ->with(['pendencia:id,codigo,obras_id', 'pendencia.obra:id,unidade'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'codigo' => $a->pendencia?->codigo ?? '—',
                'pendencia_id' => $a->pendencia_id,
                'obra' => $a->pendencia?->obra?->unidade ?? '—',
                'status_anterior' => $a->status_anterior instanceof StatusPendencia
                    ? $a->status_anterior->label()
                    : StatusPendencia::tryFrom((string) $a->status_anterior)?->label() ?? (string) $a->status_anterior,
                'status_novo' => $a->status_novo instanceof StatusPendencia
                    ? $a->status_novo->label()
                    : StatusPendencia::tryFrom((string) $a->status_novo)?->label() ?? (string) $a->status_novo,
                'usuario' => $a->atualizado_por ?? '—',
                'data' => $a->created_at?->format('d/m H:i') ?? '—',
                'comentario' => $a->comentario ?? null,
            ])
            ->toArray();
    }

    private function buildPendenciasRecentes(): array
    {
        return Pendencia::query()
            ->with(['obra:id,unidade', 'disciplina:id,label'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'descricao' => $p->descricao,
                'obra' => $p->obra?->unidade ?? '—',
                'status' => $p->status->label(),
                'urgencia' => $p->urgencia?->label() ?? '—',
                'urgencia_val' => $p->urgencia?->value ?? '',
                'data' => $p->created_at?->format('d/m H:i') ?? '—',
            ])
            ->toArray();
    }

    private function chartStatus(Builder $query): ?array
    {
        $rows = $query
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->toBase()->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'por-status',
            'type' => 'donut',
            'title' => 'Por Status',
            'labels' => $rows->map(fn ($r) => StatusPendencia::from($r->status)->label())->all(),
            'series' => $rows->pluck('total')->all(),
        ];
    }

    private function chartUrgencia(Builder $query): ?array
    {
        $rows = $query
            ->selectRaw('urgencia, COUNT(*) as total')
            ->whereNotNull('urgencia')
            ->groupBy('urgencia')
            ->toBase()->get()
            ->keyBy('urgencia');

        if ($rows->isEmpty()) {
            return null;
        }

        $cases = UrgenciaPendencia::cases();

        return [
            'id' => 'por-urgencia',
            'type' => 'donut',
            'title' => 'Por Urgência',
            'labels' => array_map(fn ($e) => $e->label(), $cases),
            'series' => array_map(fn ($e) => (int) ($rows[$e->value]->total ?? 0), $cases),
        ];
    }

    private function chartGestor(Builder $query): ?array
    {
        $terminal = implode("','", self::TERMINAL);
        $rows = $query
            ->join('users', 'users.id', '=', 'po_pendencias.gestor_id')
            ->selectRaw("users.name as gestor, COUNT(po_pendencias.id) as total,
                SUM(CASE WHEN po_pendencias.status IN ('{$terminal}') THEN 1 ELSE 0 END) as concluidas,
                SUM(CASE WHEN po_pendencias.status NOT IN ('{$terminal}') THEN 1 ELSE 0 END) as ativas")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->toBase()->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'por-gestor',
            'type' => 'bar',
            'title' => 'Pendências por Gestor',
            'labels' => $rows->pluck('gestor')->all(),
            'series' => [
                ['name' => 'Concluídas', 'data' => $rows->pluck('concluidas')->map(fn ($v) => (int) $v)->all()],
                ['name' => 'Ativas',     'data' => $rows->pluck('ativas')->map(fn ($v) => (int) $v)->all()],
            ],
        ];
    }

    private function chartTaxaGestor(Builder $query): ?array
    {
        $terminal = implode("','", self::TERMINAL);
        $rows = $query
            ->join('users', 'users.id', '=', 'po_pendencias.gestor_id')
            ->selectRaw("users.name as gestor,
                ROUND(SUM(CASE WHEN po_pendencias.status IN ('{$terminal}') THEN 1 ELSE 0 END) * 100.0 / COUNT(po_pendencias.id), 1) as taxa")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('taxa')
            ->toBase()->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'taxa-gestor',
            'type' => 'bar',
            'title' => '% Resolvido por Gestor',
            'labels' => $rows->pluck('gestor')->all(),
            'series' => [
                ['name' => '% Resolvido', 'data' => $rows->pluck('taxa')->map(fn ($v) => (float) $v)->all()],
            ],
            'percent' => true,
        ];
    }

    private function chartConstrutora(Builder $query): ?array
    {
        $terminal = implode("','", self::TERMINAL);
        $rows = $query
            ->join('construtoras', 'construtoras.id', '=', 'po_pendencias.construtora_id')
            ->selectRaw("construtoras.nome as construtora, COUNT(po_pendencias.id) as total,
                SUM(CASE WHEN po_pendencias.status IN ('{$terminal}') THEN 1 ELSE 0 END) as concluidas,
                SUM(CASE WHEN po_pendencias.status NOT IN ('{$terminal}') THEN 1 ELSE 0 END) as ativas")
            ->groupBy('construtoras.id', 'construtoras.nome')
            ->orderByDesc('total')
            ->limit(15)
            ->toBase()->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'por-construtora',
            'type' => 'bar',
            'title' => 'Top 15 Construtoras / Fornecedores',
            'labels' => $rows->pluck('construtora')->all(),
            'series' => [
                ['name' => 'Concluídas', 'data' => $rows->pluck('concluidas')->map(fn ($v) => (int) $v)->all()],
                ['name' => 'Ativas',     'data' => $rows->pluck('ativas')->map(fn ($v) => (int) $v)->all()],
            ],
            'horizontal' => true,
        ];
    }

    private function chartDisciplina(Builder $query): ?array
    {
        $terminal = implode("','", self::TERMINAL);
        $rows = $query
            ->join('po_disciplinas_config', 'po_disciplinas_config.id', '=', 'po_pendencias.disciplina_config_id')
            ->selectRaw("po_disciplinas_config.label as disciplina, COUNT(po_pendencias.id) as total,
                SUM(CASE WHEN po_pendencias.status IN ('{$terminal}') THEN 1 ELSE 0 END) as concluidas,
                SUM(CASE WHEN po_pendencias.status NOT IN ('{$terminal}') THEN 1 ELSE 0 END) as ativas")
            ->groupBy('po_disciplinas_config.id', 'po_disciplinas_config.label')
            ->orderByDesc('total')
            ->toBase()->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'por-disciplina',
            'type' => 'bar',
            'title' => 'Por Disciplina',
            'labels' => $rows->pluck('disciplina')->all(),
            'series' => [
                ['name' => 'Concluídas', 'data' => $rows->pluck('concluidas')->map(fn ($v) => (int) $v)->all()],
                ['name' => 'Ativas',     'data' => $rows->pluck('ativas')->map(fn ($v) => (int) $v)->all()],
            ],
            'horizontal' => true,
        ];
    }

    private function chartRanking(Builder $query): ?array
    {
        $rows = $query
            ->join('construtoras', 'construtoras.id', '=', 'po_pendencias.construtora_id')
            ->selectRaw('construtoras.nome as construtora,
                COUNT(DISTINCT po_pendencias.obras_id) as total_obras,
                COUNT(po_pendencias.id) as total_pendencias,
                ROUND(CAST(COUNT(po_pendencias.id) AS FLOAT) / NULLIF(COUNT(DISTINCT po_pendencias.obras_id), 0), 2) as nota')
            ->whereNotNull('po_pendencias.obras_id')
            ->groupBy('construtoras.id', 'construtoras.nome')
            ->having(DB::raw('COUNT(DISTINCT po_pendencias.obras_id)'), '>', 0)
            ->orderBy('nota')
            ->limit(20)
            ->toBase()->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'ranking-fornecedores',
            'type' => 'bar',
            'title' => 'Ranking Fornecedores (pendências / obra)',
            'labels' => $rows->pluck('construtora')->all(),
            'series' => [
                ['name' => 'Nota', 'data' => $rows->pluck('nota')->map(fn ($v) => (float) $v)->all()],
            ],
            'horizontal' => true,
        ];
    }

    private function chartMensal(Builder $query): ?array
    {
        $driver = DB::getDriverName();
        $dateFn = $driver === 'sqlite'
            ? "strftime('%Y-%m', data_inicio)"
            : "DATE_FORMAT(data_inicio, '%Y-%m')";

        $rows = $query
            ->selectRaw("{$dateFn} as ym, COUNT(*) as total")
            ->whereNotNull('data_inicio')
            ->where('data_inicio', '>=', now()->subMonths(12)->startOfMonth()->toDateString())
            ->groupBy('ym')
            ->orderBy('ym')
            ->toBase()->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'evolucao-mensal',
            'type' => 'line',
            'title' => 'Evolução Mensal (12 meses)',
            'labels' => $rows->pluck('ym')->all(),
            'series' => [
                ['name' => 'Pendências abertas', 'data' => $rows->pluck('total')->all()],
            ],
        ];
    }
}
