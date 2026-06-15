<?php

namespace App\Filament\Pages;

use App\Models\Projeto;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class ProjetosDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $title = 'Dashboard de Projetos';

    protected static ?string $navigationLabel = 'Dashboard de Projetos';

    protected static null|string|UnitEnum $navigationGroup = 'Dashboard';

    protected string $view = 'filament.pages.projetos-dashboard';

    public array $charts = [];

    public array $formData = [];

    // filtro
    public array $pipeline = [];

    public function mount(): void
    {
        $this->form->fill([
            'pipeline' => [], // começa mostrando todos
        ]);

        $this->formData = $this->form->getState();
        $this->loadCharts();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('pipeline')
                ->label('PIPE/ LAND')
                ->options(
                    Projeto::query()
                        ->select('pipeline')
                        ->distinct()
                        ->pluck('pipeline', 'pipeline')
                        ->filter() // remmandaove null
                        ->mapWithKeys(fn ($p) => [(string) $p => (string) $p])
                        ->toArray()
                )
                ->multiple()
                ->placeholder('Todos')
                ->live()
                ->afterStateUpdated(fn ($state) => $this->loadCharts((array) $state)),
        ];
    }
    /*
    public function updatedPipeline(): void
    {
        $this->loadCharts();
    }
    */

    protected function loadCharts(?array $pipeline = null): void
    {
        // se não veio pipeline, tenta pegar do form ou do formData
        if ($pipeline === null) {
            $state = $this->form->getState() ?? $this->formData;
            $pipeline = $state['pipeline'] ?? [];
        }

        // atualiza cache local do formData pra manter tudo consistente
        $this->formData['pipeline'] = $pipeline;

        $query = Projeto::query()->whereNull('deleted_at');
        if (! empty($pipeline)) {
            $query->whereIn('pipeline', $pipeline);
        }

        $this->charts = [];
        if ($c = $this->chartStatus(clone $query)) {
            $this->charts[] = $c;
        }
        if ($c = $this->chartMensal(clone $query)) {
            $this->charts[] = $c;
        }
        if ($c = $this->chartOpexTop(clone $query)) {
            $this->charts[] = $c;
        }
        if ($c = $this->chartFlags(clone $query)) {
            $this->charts[] = $c;
        }
        if ($c = $this->chartRentM2(clone $query)) {
            $this->charts[] = $c;
        }
        if ($c = $this->chartDuracao(clone $query)) {
            $this->charts[] = $c;
        }
        if ($c = $this->chartCash(clone $query)) {
            $this->charts[] = $c;
        }
    }

    protected function chartStatus($query): ?array
    {
        $rows = $query
            ->selectRaw("COALESCE(status, 'Sem status') AS label, COUNT(*) AS total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'id' => 'por-status',
                'type' => 'donut',
                'title' => 'Projetos por Status',
                'labels' => [],
                'series' => [],
            ];
        }

        return [
            'id' => 'por-status',
            'type' => 'donut',
            'title' => 'Projetos por Status',
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->all(),
        ];
    }

    protected function chartMensal($query): ?array
    {
        $rows = $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return [
            'id' => 'criacao-mensal',
            'type' => 'line',
            'title' => 'Projetos criados por mês (12m)',
            'labels' => $rows->pluck('ym')->all(),
            'series' => [
                ['name' => 'Projetos', 'data' => $rows->pluck('total')->all()],
            ],
        ];
    }

    protected function chartOpexTop($query): ?array
    {
        $rows = $query
            ->select(
                'nome',
                DB::raw('COALESCE(aluguel_cto,0) AS aluguel_cto'),
                DB::raw('COALESCE(iptu,0)       AS iptu'),
                DB::raw('COALESCE(condominio,0) AS condominio')
            )
            ->orderByDesc(DB::raw('COALESCE(aluguel_cto,0)+COALESCE(iptu,0)+COALESCE(condominio,0)'))
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'id' => 'opex-top10',
                'type' => 'bar',
                'title' => 'Top 5 OPEX mensal por projeto',
                'labels' => [],
                'series' => [
                    ['name' => 'Aluguel',    'data' => []],
                    ['name' => 'IPTU',       'data' => []],
                    ['name' => 'Condomínio', 'data' => []],
                ],
            ];
        }

        return [
            'id' => 'opex-top10',
            'type' => 'bar',
            'title' => 'Top 5 OPEX mensal por projeto',
            'labels' => $rows->pluck('nome')->map(fn ($n) => Str::limit((string) ($n ?? '—'), 16))->values()->all(),
            'series' => [
                ['name' => 'Aluguel',    'data' => $rows->pluck('aluguel_cto')->map(fn ($v) => (float) $v)->all()],
                ['name' => 'IPTU',       'data' => $rows->pluck('iptu')->map(fn ($v) => (float) $v)->all()],
                ['name' => 'Condomínio', 'data' => $rows->pluck('condominio')->map(fn ($v) => (float) $v)->all()],
            ],
            'currency' => 'BRL',
            'yAxisTitle' => 'R$',
        ];
    }

    protected function chartFlags($query): ?array
    {
        $row = $query->selectRaw('
            SUM(CASE WHEN relocation = 1 THEN 1 ELSE 0 END)     AS relocation,
            SUM(CASE WHEN imovel_pronto = 1 THEN 1 ELSE 0 END)  AS imovel_pronto
        ')->first();

        $rel = (int) ($row->relocation ?? 0);
        $imo = (int) ($row->imovel_pronto ?? 0);
        if (($rel + $imo) === 0) {
            return null;
        }

        return [
            'id' => 'flags',
            'type' => 'bar',
            'title' => 'Flags de projeto',
            'labels' => ['Relocation', 'Imóvel pronto'],
            'series' => [['name' => 'Qtd', 'data' => [$rel, $imo]]],
        ];
    }

    protected function chartRentM2($query): array
    {
        $rows = $query
            ->join('cidades', 'cidades.id', '=', 'projetos.cidade_id')
            ->select(
                'cidades.nome as cidade_nome',
                DB::raw('ROUND(AVG(CASE WHEN area_locada > 0 THEN aluguel_cto/area_locada END),2) as aluguel_m2')
            )
            ->groupBy('cidades.nome')
            ->havingRaw('aluguel_m2 IS NOT NULL')
            ->orderByDesc('aluguel_m2')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'id' => 'aluguel-m2-cidade',
                'type' => 'bar',
                'title' => 'Aluguel médio por m² (Top 10 cidades)',
                'labels' => [],
                'series' => [
                    ['name' => 'R$/m²', 'data' => []],
                ],
                'currency' => 'BRL',
                'yAxisTitle' => 'R$/m²',
            ];
        }

        return [
            'id' => 'aluguel-m2-cidade',
            'type' => 'bar',
            'title' => 'Aluguel médio por m² (Top 10 cidades)',
            'labels' => $rows->pluck('cidade_nome')->all(),
            'series' => [[
                'name' => 'R$/m²',
                'data' => $rows->pluck('aluguel_m2')->map(fn ($v) => (float) $v)->all(),
            ]],
            'currency' => 'BRL',
            'yAxisTitle' => 'R$/m²',
        ];
    }

    protected function chartDuracao($query): ?array
    {
        $dias = $query
            ->select(DB::raw('DATEDIFF(entrega_obra, inicio_obra) AS dias'))
            ->whereNotNull('inicio_obra')
            ->whereNotNull('entrega_obra')
            ->pluck('dias')
            ->filter(fn ($d) => is_numeric($d) && (int) $d > 0)
            ->map(fn ($d) => (int) $d)
            ->values();

        if ($dias->isEmpty()) {
            return null;
        }

        $bins = [60, 120, 180, 240, 300, 360, PHP_INT_MAX];
        $labels = ['0–60', '61–120', '121–180', '181–240', '241–300', '301–360', '>360'];
        $count = array_fill(0, count($labels), 0);

        foreach ($dias as $d) {
            foreach ($bins as $i => $max) {
                if ($d <= $max) {
                    $count[$i]++;
                    break;
                }
            }
        }

        return [
            'id' => 'duracao-obra',
            'type' => 'bar',
            'title' => 'Duração da obra (em dias)',
            'labels' => $labels,
            'series' => [['name' => 'Projetos', 'data' => $count]],
        ];
    }

    protected function chartCash($query): ?array
    {
        $vals = $query
            ->whereNotNull('cash_on_cash')
            ->pluck('cash_on_cash')
            ->map(fn ($v) => is_numeric($v) ? (float) $v : null)
            ->filter(fn ($v) => ! is_null($v) && is_finite($v))
            ->values();

        if ($vals->isEmpty()) {
            return null;
        }

        $bins = [5, 10, 15, 20, 25, PHP_INT_MAX];
        $labels = ['0–5%', '5–10%', '10–15%', '15–20%', '20–25%', '>25%'];
        $count = array_fill(0, count($labels), 0);

        foreach ($vals as $v) {
            foreach ($bins as $i => $max) {
                if ($v <= $max) {
                    $count[$i]++;
                    break;
                }
            }
        }

        return [
            'id' => 'cash-on-cash',
            'type' => 'bar',
            'title' => 'Distribuição Cash on Cash (%)',
            'labels' => $labels,
            'series' => [['name' => 'Projetos', 'data' => $count]],
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:ProjetosDashboard');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('View:ProjetosDashboard');
    }
}
