<?php

namespace App\Filament\Resources\GrupoOis\Pages;

use App\Filament\Resources\GrupoOis\GrupoOiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGrupoOis extends ListRecords
{
    protected static string $resource = GrupoOiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
