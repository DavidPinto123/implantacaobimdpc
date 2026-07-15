<?php

namespace App\Filament\Resources\AmbientacaoResource\Pages;

use App\Filament\Resources\AmbientacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmbientacao extends ViewRecord
{
    protected static string $resource = AmbientacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
