<?php

namespace App\Filament\Resources\Asas\Widgets;

use App\Models\Asa;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class ResumoAditivos extends ChartWidget
{
    #[Reactive]
    public ?string $projetoFiltro = null;

    #[Reactive]
    public ?string $construtoraFiltro = null;

    protected ?string $heading = 'RESUMO ADITIVOS';

    protected ?string $description = 'CONSIDERADOS ADICIONAIS APROVADOS E EM ANALISE';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $grupos = [
            'Cliente',
            'Shell',
            'Projetos',
            'Operação',
        ];

        $data = collect($grupos)->map(function ($grupo) {
            $query = Asa::query()
                ->where('subgrupo', $grupo)
                ->whereIn('status', ['em_analise', 'aprovado']);

            if (filled($this->projetoFiltro)) {
                $query->where('projeto_id', $this->projetoFiltro);
            }

            if (filled($this->construtoraFiltro)) {
                $query->where('solicitante', $this->construtoraFiltro);
            }

            return (float) $query->sum('valor_total');
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Aditivos',
                    'data' => $data,
                ],
            ],
            'labels' => $grupos,
        ];
    }
}
