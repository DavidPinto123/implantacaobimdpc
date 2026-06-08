<?php

namespace App\Filament\Resources\PosObra\ConfiguracaoSlaResource\Pages;

use App\Filament\Resources\PosObra\ConfiguracaoSlaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConfiguracaoSla extends EditRecord
{
    protected static string $resource = ConfiguracaoSlaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
