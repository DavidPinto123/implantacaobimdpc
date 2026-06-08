<?php

namespace App\Filament\Resources\EtapaResource\Pages;

use App\Filament\Resources\EtapaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEtapa extends EditRecord
{
    protected static string $resource = EtapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
