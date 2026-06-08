<?php

namespace App\Filament\Resources\AmbientesResource\Pages;

use App\Filament\Resources\AmbientesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmbientes extends EditRecord
{
    protected static string $resource = AmbientesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
