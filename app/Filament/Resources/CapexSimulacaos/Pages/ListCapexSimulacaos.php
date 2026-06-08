<?php

namespace App\Filament\Resources\CapexSimulacaos\Pages;

use App\Filament\Resources\CapexSimulacaos\CapexSimulacaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCapexSimulacaos extends ListRecords
{
    protected static string $resource = CapexSimulacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
