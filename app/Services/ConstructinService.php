<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConstructinService
{
    private const BASE_URL = 'https://connect.constructin.com.br/v1';

    private const CACHE_IMGS = 3600;

    private const CACHE_VISI = 7200;

    private const CACHE_PROJECTS = 7200;

    private const CACHE_RDOS_LIST = 3600;

    private const CACHE_S_CURVE = 7200;

    private const CACHE_RDO_DETAIL = 1800;

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer '
                .config('services.constructin.key')
                .'.'
                .config('services.constructin.secret'),
            'Accept' => 'application/json',
        ];
    }

    public function getImages(int $projectId): array
    {
        return Cache::remember("cin_images_{$projectId}", self::CACHE_IMGS, function () use ($projectId) {
            try {
                $r = Http::withHeaders($this->headers())
                    ->timeout(15)->retry(2, 500)
                    ->get(self::BASE_URL."/project/{$projectId}/images", ['limit' => 500]);

                if ($r->failed()) {
                    return [];
                }

                $items = $r->json('images') ?? $r->json() ?? [];

                return collect($items)
                    ->map(fn ($img) => ['url' => $img['url'] ?? null, 'date' => $img['date'] ?? null])
                    ->filter(fn ($img) => filled($img['url']))
                    ->values()->all();
            } catch (\Throwable $e) {
                Log::error('Constructin images', ['msg' => $e->getMessage()]);

                return [];
            }
        });
    }

    public function getVisiCurve(int $projectId, Carbon $inicio, Carbon $fim): array
    {
        return Cache::remember("cin_visi_{$projectId}", self::CACHE_VISI, function () use ($projectId, $inicio, $fim) {
            try {
                $r = Http::withHeaders($this->headers())
                    ->timeout(20)->retry(2, 500)
                    ->get(self::BASE_URL."/project/{$projectId}/rdos", ['limit' => 100]);

                if ($r->failed()) {
                    return [];
                }

                $rdos = collect($r->json('rdos') ?? $r->json() ?? [])
                    ->filter(fn ($rdo) => filled($rdo['date'] ?? null));

                $rdosPorMes = $rdos
                    ->groupBy(fn ($rdo) => Carbon::parse($rdo['date'])->format('Y-m'))
                    ->map(fn ($g) => $g->sortByDesc('date')->first());

                $totalMeses = (int) $inicio->copy()->diffInMonths($fim) + 1;
                $mesesLabel = [];
                $ptsPrevistos = [];
                $ptsRealizados = [];

                for ($i = 0; $i < $totalMeses; $i++) {
                    $mes = $inicio->copy()->addMonths($i);
                    $chave = $mes->format('Y-m');

                    $mesesLabel[] = $mes->isoFormat('MMM');
                    $ptsPrevistos[] = round(($i / max($totalMeses - 1, 1)) * 100);

                    if (isset($rdosPorMes[$chave])) {
                        $detalhe = $this->getRdoDetail($projectId, $rdosPorMes[$chave]['id']);
                        $ptsRealizados[] = $this->pctMedio($detalhe);
                    } else {
                        $ptsRealizados[] = end($ptsRealizados) ?: 0;
                    }
                }

                return compact('mesesLabel', 'ptsPrevistos', 'ptsRealizados');
            } catch (\Throwable $e) {
                Log::error('Constructin visi', ['msg' => $e->getMessage()]);

                return [];
            }
        });
    }

    public function getRdoDetail(int $projectId, int $rdoId): array
    {
        return Cache::remember("cin_rdo_detail_{$projectId}_{$rdoId}", self::CACHE_RDO_DETAIL, function () use ($projectId, $rdoId) {
            try {
                $r = Http::withHeaders($this->headers())
                    ->timeout(15)
                    ->retry(2, 500)
                    ->get(self::BASE_URL."/project/{$projectId}/rdos/{$rdoId}");

                return $r->ok() ? ($r->json() ?? []) : [];
            } catch (\Throwable $e) {
                Log::warning('Constructin rdo detail', [
                    'project' => $projectId,
                    'rdo' => $rdoId,
                    'msg' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    private function pctMedio(array $rdo): float
    {
        $acts = collect($rdo['activities'] ?? [])->filter(fn ($a) => isset($a['percentage']));

        return $acts->isEmpty() ? 0.0 : round($acts->avg('percentage'), 1);
    }

    public function getProjects(): array
    {
        return Cache::remember('cin_projects_list', self::CACHE_PROJECTS, function () {
            try {
                $r = Http::withHeaders($this->headers())
                    ->timeout(20)->retry(2, 500)
                    ->get(self::BASE_URL.'/projects', ['limit' => 500]);

                if ($r->failed()) {
                    Log::warning('Constructin /projects endpoint indisponível', ['status' => $r->status()]);

                    return [];
                }

                $items = $r->json('projects') ?? $r->json() ?? [];

                return collect($items)
                    ->map(fn ($p) => ['id' => $p['id'] ?? null, 'name' => $p['name'] ?? ''])
                    ->filter(fn ($p) => filled($p['id']) && filled($p['name']))
                    ->values()->all();
            } catch (\Throwable $e) {
                Log::warning('Constructin projects', ['msg' => $e->getMessage()]);

                return [];
            }
        });
    }

    public function findProjectByNovaSigla(string $novaSigla): ?int
    {
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            if (Str::contains($project['name'], $novaSigla, ignoreCase: true)) {
                return (int) $project['id'];
            }
        }

        return null;
    }

    public function getSCurve(int $projectId): array
    {
        return Cache::remember("cin_scurve_{$projectId}", self::CACHE_S_CURVE, function () use ($projectId) {
            try {
                $r = Http::withHeaders($this->headers())
                    ->timeout(20)->retry(2, 500)
                    ->get(self::BASE_URL."/project/{$projectId}/timeline/s-curve");

                if ($r->failed()) {
                    Log::warning('Constructin s-curve indisponível', [
                        'status' => $r->status(),
                        'project' => $projectId,
                    ]);

                    return [];
                }

                $points = $r->json('points') ?? [];

                if (empty($points)) {
                    return [];
                }

                usort($points, fn ($a, $b) => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? '')));

                $toPct = fn ($v) => is_numeric($v) ? round(((float) $v) * 100, 2) : null;

                // Agrupa por semana (ISO year+week) e mantém o último valor não-nulo de cada série na semana.
                // Isso reduz o ruído (de pontos diários para ~35 pontos semanais) e bate com a granularidade
                // que o Construct-IN mostra na curva S do produto deles.
                $porSemana = [];
                foreach ($points as $p) {
                    if (empty($p['date'])) {
                        continue;
                    }
                    $date = Carbon::parse($p['date']);
                    $chave = $date->format('o-W'); // ISO year-week (ex: 2025-35)
                    $bucket = $porSemana[$chave] ?? [
                        'date' => $date,
                        'planned' => null,
                        'actual' => null,
                        'original' => null,
                    ];
                    // Último ponto da semana define a data de referência para o eixo X.
                    $bucket['date'] = $date;
                    foreach (['planned' => 'planned_progress', 'actual' => 'actual_progress', 'original' => 'original_progress'] as $key => $apiKey) {
                        $val = $toPct($p[$apiKey] ?? null);
                        if ($val !== null) {
                            $bucket[$key] = $val;
                        }
                    }
                    $porSemana[$chave] = $bucket;
                }

                ksort($porSemana);

                $labels = [];
                $dates = [];
                $planned = [];
                $actual = [];
                $original = [];

                foreach ($porSemana as $semana) {
                    /** @var Carbon $date */
                    $date = $semana['date'];
                    $labels[] = $date->locale('pt_BR')->isoFormat('DD MMM YY');
                    $dates[] = $date->valueOf();
                    $planned[] = $semana['planned'];
                    $actual[] = $semana['actual'];
                    $original[] = $semana['original'];
                }

                return compact('labels', 'dates', 'planned', 'actual', 'original');
            } catch (\Throwable $e) {
                Log::error('Constructin s-curve', ['msg' => $e->getMessage(), 'project' => $projectId]);

                return [];
            }
        });
    }

    /**
     * Retorna os percentuais atuais da curva S do projeto.
     *
     * @return array{percentual_obra:?float, percentual_obra_executado:?float, referencia:string}
     */
    public function getProgressPercentages(int $projectId): array
    {
        return $this->getProgressSnapshot($projectId);
    }

    /**
     * Snapshot dos percentuais na data de referência.
     *
     * @return array{percentual_obra:?float, percentual_obra_executado:?float, referencia:string}
     */
    public function getProgressSnapshot(int $projectId, ?Carbon $referenceDate = null): array
    {
        $referenceDate ??= now();

        return Cache::remember(
            "cin_progress_{$projectId}_{$referenceDate->toDateString()}",
            self::CACHE_S_CURVE,
            function () use ($projectId, $referenceDate): array {
                $points = $this->getSCurvePoints($projectId);

                return [
                    'percentual_obra' => $this->valueAtDateFromPoints($points, 'planned', $referenceDate),
                    'percentual_obra_executado' => $this->valueAtDateFromPoints($points, 'actual', $referenceDate),
                    'referencia' => $referenceDate->toDateString(),
                ];
            }
        );
    }

    /**
     * @return array<int, array{date:string, planned:?float, actual:?float, original:?float}>
     */
    public function getSCurvePoints(int $projectId): array
    {
        return Cache::remember("cin_scurve_points_{$projectId}", self::CACHE_S_CURVE, function () use ($projectId): array {
            try {
                $r = Http::withHeaders($this->headers())
                    ->timeout(20)->retry(2, 500)
                    ->get(self::BASE_URL."/project/{$projectId}/timeline/s-curve");

                if ($r->failed()) {
                    Log::warning('Constructin s-curve indisponível', [
                        'status' => $r->status(),
                        'project' => $projectId,
                    ]);

                    return [];
                }

                $points = $r->json('points') ?? [];

                if (empty($points)) {
                    return [];
                }

                return collect($points)
                    ->map(function (array $point): ?array {
                        $date = $point['date'] ?? null;
                        if (blank($date)) {
                            return null;
                        }

                        return [
                            'date' => (string) $date,
                            'planned' => $this->scaleProgressValue($point['planned_progress'] ?? null),
                            'actual' => $this->scaleProgressValue($point['actual_progress'] ?? null),
                            'original' => $this->scaleProgressValue($point['original_progress'] ?? null),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                Log::error('Constructin s-curve points', [
                    'msg' => $e->getMessage(),
                    'project' => $projectId,
                ]);

                return [];
            }
        });
    }

    private function scaleProgressValue(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return $number <= 1 ? round($number * 100, 2) : round($number, 2);
    }

    private function valueAtDateFromPoints(array $points, string $field, Carbon $referenceDate): ?float
    {
        $bestValue = null;
        $bestDate = null;

        foreach ($points as $point) {
            $date = $point['date'] ?? null;
            $value = $point[$field] ?? null;

            if (blank($date) || ! is_numeric($value)) {
                continue;
            }

            try {
                $currentDate = is_numeric($date)
                    ? Carbon::createFromTimestampMs((int) $date)
                    : Carbon::parse($date);
            } catch (\Throwable) {
                continue;
            }

            if ($currentDate->gt($referenceDate)) {
                continue;
            }

            if ($bestDate === null || $currentDate->gt($bestDate)) {
                $bestDate = $currentDate;
                $bestValue = round((float) $value, 2);
            }
        }

        if ($bestValue !== null) {
            return $bestValue;
        }

        foreach ($points as $point) {
            $value = $point[$field] ?? null;
            if (is_numeric($value)) {
                return round((float) $value, 2);
            }
        }

        return null;
    }

    public function getRdosList(int $projectId): array
    {
        return Cache::remember("cin_rdos_list_{$projectId}", self::CACHE_RDOS_LIST, function () use ($projectId) {
            try {
                $r = Http::withHeaders($this->headers())
                    ->timeout(20)->retry(2, 500)
                    ->get(self::BASE_URL."/project/{$projectId}/rdos", ['limit' => 100]);

                if ($r->failed()) {
                    return [];
                }

                $rdos = collect($r->json('rdos') ?? $r->json() ?? [])
                    ->filter(fn ($rdo) => filled($rdo['date'] ?? null))
                    ->sortByDesc('date');

                return $rdos->map(function ($rdo) {
                    $activities = collect($rdo['activities'] ?? [])
                        ->map(fn ($a) => [
                            'name' => $a['name'] ?? $a['activity'] ?? '—',
                            'percentage' => $a['percentage'] ?? null,
                        ])->values()->all();

                    $averagePercentage = data_get($rdo, 'averagePercentage');
                    if ($averagePercentage === null) {
                        $averagePercentage = data_get($rdo, 'percentage');
                    }
                    if ($averagePercentage === null && ! empty($activities)) {
                        $percentuais = collect($activities)->pluck('percentage')->filter(fn ($v) => is_numeric($v));
                        $averagePercentage = $percentuais->isEmpty() ? null : round($percentuais->avg(), 1);
                    }

                    return [
                        'id' => $rdo['id'],
                        'date' => $rdo['date'],
                        'title' => $rdo['title'] ?? $rdo['name'] ?? $rdo['description'] ?? null,
                        'activities' => $activities,
                        'averagePercentage' => $averagePercentage,
                    ];
                })->values()->all();
            } catch (\Throwable $e) {
                Log::error('Constructin rdos list', ['msg' => $e->getMessage()]);

                return [];
            }
        });
    }
}
