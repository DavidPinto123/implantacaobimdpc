<?php

namespace App\Filament\Resources\AmbientesResource\Pages;

use App\Filament\Resources\AmbientesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmbientes extends ListRecords
{
    protected static string $resource = AmbientesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
