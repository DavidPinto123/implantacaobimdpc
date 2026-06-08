<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\AppliesHomeFilters;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AcompanhamentoResumoTabelaWidget extends Widget
{
    use AppliesHomeFilters;
    use InteractsWithPageFilters;

    protected string $view = 'filament.widgets.dashboard.acompanhamento-resumo-tabela-widget';

    protected int|string|array $columnSpan = 'full';

    public function getResumo(): array
    {
        $labels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez', 'Propria', 'Total'];

        $inauguradaMes = $this->getMonthlySeries((clone $this->getFilteredProjetosQuery()), ['Inaugurada']);
        $implantacaoMes = $this->getMonthlySeries((clone $this->getFilteredProjetosQuery()), ['Implantação', 'Implantacao']);
        $obraMes = $this->getMonthlySeries((clone $this->getFilteredProjetosQuery()), ['Obras', 'Em obra', 'Em Obra']);
        $faseProjetoMes = $this->getMonthlySeries((clone $this->getFilteredProjetosQuery()), ['Em processo', 'Fase de projeto', 'Fase de Projeto']);

        $inauguradaPropria = $this->getPropriaTotal((clone $this->getFilteredProjetosQuery()), ['Inaugurada']);
        $implantacaoPropria = $this->getPropriaTotal((clone $this->getFilteredProjetosQuery()), ['Implantação', 'Implantacao']);
        $obraPropria = $this->getPropriaTotal((clone $this->getFilteredProjetosQuery()), ['Obras', 'Em obra', 'Em Obra']);
        $faseProjetoPropria = $this->getPropriaTotal((clone $this->getFilteredProjetosQuery()), ['Em processo', 'Fase de projeto', 'Fase de Projeto']);

        $inauguradaTotal = array_sum($inauguradaMes);
        $implantacaoTotal = array_sum($implantacaoMes);
        $obraTotal = array_sum($obraMes);
        $faseProjetoTotal = array_sum($faseProjetoMes);

        $totalMes = [];

        for ($i = 0; $i < 12; $i++) {
            $totalMes[$i] = $inauguradaMes[$i] + $implantacaoMes[$i] + $obraMes[$i] + $faseProjetoMes[$i];
        }

        $totalPropria = $inauguradaPropria + $implantacaoPropria + $obraPropria + $faseProjetoPropria;
        $totalGeral = array_sum($totalMes);

        $inauguradaAcumuladaMes = [];
        $acum = 0;

        for ($i = 0; $i < 12; $i++) {
            $acum += $inauguradaMes[$i];
            $inauguradaAcumuladaMes[$i] = $acum;
        }

        $metaAcumuladaPadrao = [2, 6, 8, 13, 20, 28, 38, 48, 60, 72, 86, 100];
        $metaAcumulada = $this->parseMetaAcumuladaFromEnv($metaAcumuladaPadrao);

        $metaMensal = [];
        $metaAnterior = 0;

        for ($i = 0; $i < 12; $i++) {
            $metaMensal[$i] = $metaAcumulada[$i] - $metaAnterior;
            $metaAnterior = $metaAcumulada[$i];
        }

        $deltaMetaMes = [];

        for ($i = 0; $i < 12; $i++) {
            $deltaMetaMes[$i] = $inauguradaAcumuladaMes[$i] - $metaAcumulada[$i];
        }

        $rows = [
            'Inaugurada' => [...$inauguradaMes, $inauguradaPropria, $inauguradaTotal],
            'Implantação' => [...$implantacaoMes, $implantacaoPropria, $implantacaoTotal],
            'Em Obra' => [...$obraMes, $obraPropria, $obraTotal],
            'Fase de projeto' => [...$faseProjetoMes, $faseProjetoPropria, $faseProjetoTotal],
            'Total' => [...$totalMes, $totalPropria, $totalGeral],
            'Inaug. Acumu.' => [...$inauguradaAcumuladaMes, null, null],
            'Meta Acumulada' => [...$metaAcumulada, null, null],
            'Meta' => [...$metaMensal, null, null],
            'Delta Meta' => [...$deltaMetaMes, null, null],
        ];

        return [
            'labels' => $labels,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<int, int>
     */
    protected function getMonthlySeries(Builder $query, array $statuses): array
    {
        $rows = $query
            ->select(DB::raw('MONTH(imp_fim) as mes'), DB::raw('COUNT(*) as total'))
            ->whereIn('status', $statuses)
            ->whereNotNull('imp_fim')
            ->groupBy(DB::raw('MONTH(imp_fim)'))
            ->pluck('total', 'mes')
            ->toArray();

        $series = array_fill(0, 12, 0);

        foreach ($rows as $mes => $total) {
            $series[(int) $mes - 1] = (int) $total;
        }

        return $series;
    }

    /**
     * @param  array<int, string>  $statuses
     */
    protected function getPropriaTotal(Builder $query, array $statuses): int
    {
        return (int) $query
            ->whereIn('status', $statuses)
            ->whereNotNull('tipo')
            ->whereRaw('LOWER(tipo) like ?', ['%propr%'])
            ->count();
    }

    /**
     * @param  array<int, int>  $fallback
     * @return array<int, int>
     */
    protected function parseMetaAcumuladaFromEnv(array $fallback): array
    {
        $raw = env('DASHBOARD_META_ACUMULADA');

        if (! is_string($raw) || trim($raw) === '') {
            return $fallback;
        }

        $parts = array_map('trim', explode(',', $raw));
        $numbers = [];

        foreach ($parts as $part) {
            if ($part === '' || ! is_numeric($part)) {
                return $fallback;
            }

            $numbers[] = (int) $part;
        }

        if (count($numbers) !== 12) {
            return $fallback;
        }

        return $numbers;
    }
}
