<?php

namespace App\Filament\Resources\AmbientacaoResource\Pages;

use App\Filament\Resources\AmbientacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmbientacaos extends ListRecords
{
    protected static string $resource = AmbientacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
