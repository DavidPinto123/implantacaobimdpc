<?php

namespace App\Filament\Resources\Asas\Widgets;

use App\Models\Asa;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

class PercentualGruposAditivos extends ChartWidget
{
    #[Reactive]
    public ?string $projetoFiltro = null;

    #[Reactive]
    public ?string $construtoraFiltro = null;

    protected ?string $heading = '% POR GRUPOS DE ADITIVOS APROVADOS';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $grupos = [
            'Projetos',
            'Cliente',
            'Legalização',
            'Shell',
            'Orçamentos',
            'Operação',
        ];

        $data = collect($grupos)->map(function ($grupo) {
            $query = Asa::query()
                ->where('contrato', $grupo)
                ->where('status', 'aprovado');

            if (filled($this->projetoFiltro)) {
                $query->where('projeto_id', $this->projetoFiltro);
            }

            if (filled($this->construtoraFiltro)) {
                $query->where('solicitante', $this->construtoraFiltro);
            }

            return $query->count();
        })->toArray();

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => [
                        '#F97316', // Projetos
                        '#EAB308', // Cliente
                        '#22C55E', // Legalização
                        '#7C3AED', // Shell
                        '#A16207', // Orçamentos
                        '#0F766E', // Operação
                    ],
                    'borderColor' => [
                        '#F97316',
                        '#EAB308',
                        '#22C55E',
                        '#7C3AED',
                        '#A16207',
                        '#0F766E',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $grupos,
        ];
    }
}
