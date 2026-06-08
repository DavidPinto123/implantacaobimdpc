<?php

namespace App\Filament\Resources\EtapaResource\Pages;

use App\Filament\Resources\EtapaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEtapas extends ListRecords
{
    protected static string $resource = EtapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
