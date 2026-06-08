<?php

namespace App\Filament\Resources\PosObra\ConfiguracaoSlaResource\Pages;

use App\Filament\Resources\PosObra\ConfiguracaoSlaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConfiguracaoSlas extends ListRecords
{
    protected static string $resource = ConfiguracaoSlaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
