<?php

namespace App\Filament\Resources\Asas\Widgets;

use App\Models\Asa;
use Filament\Widgets\Widget;
use Livewire\Attributes\Reactive;

class AsasResumoTabela extends Widget
{
    protected string $view = 'filament.resources.asas.widgets.asas-resumo-tabela';

    protected static bool $isLazy = false;

    #[Reactive]
    public ?string $projetoFiltro = null;

    #[Reactive]
    public ?string $construtoraFiltro = null;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $query = Asa::query();

        if (filled($this->projetoFiltro)) {
            $query->where('projeto_id', $this->projetoFiltro);
        }

        if (filled($this->construtoraFiltro)) {
            $query->where('solicitante', $this->construtoraFiltro);
        }

        $aprovado = (clone $query)->where('status', 'aprovado');
        $emAnalise = (clone $query)->where('status', 'em_analise');

        return [
            'linhas' => [
                [
                    'titulo' => 'Aprovado',
                    'total' => (float) $aprovado->sum('valor_total'),
                ],
                [
                    'titulo' => 'Em análise',
                    'total' => (float) $emAnalise->sum('valor_total'),
                ],
            ],
        ];
    }
}
