<?php

namespace App\Filament\Widgets\Dashboard\Concerns;

use App\Models\Projeto;
use Illuminate\Database\Eloquent\Builder;

trait AppliesHomeFilters
{
    protected function getFilteredProjetosQuery(): Builder
    {
        $filters = $this->pageFilters ?? [];

        $marca = $filters['marca'] ?? null;
        $pipeline = $filters['pipeline'] ?? null;
        $ano = $filters['ano'] ?? null;

        return Projeto::query()
            ->when(filled($marca), fn (Builder $query) => $query->where('marca', $marca))
            ->when(filled($pipeline), fn (Builder $query) => $query->where('pipeline', $pipeline))
            ->when(filled($ano), fn (Builder $query) => $query->whereYear('imp_fim', (int) $ano));
    }
}
