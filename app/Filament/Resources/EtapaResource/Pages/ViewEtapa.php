<?php

namespace App\Filament\Resources\EtapaResource\Pages;

use App\Filament\Resources\EtapaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEtapa extends ViewRecord
{
    protected static string $resource = EtapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
