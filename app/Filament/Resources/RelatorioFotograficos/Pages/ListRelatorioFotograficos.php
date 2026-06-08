<?php

namespace App\Filament\Resources\RelatorioFotograficos\Pages;

use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRelatorioFotograficos extends ListRecords
{
    protected static string $resource = RelatorioFotograficoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (auth()->user()->hasRole('gestor_obra')) {
            return $query;
        }

        return $query->where('autor_id', auth()->id());
    }
}
