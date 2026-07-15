<?php

namespace App\Filament\Resources\AmbientacaoResource\Pages;

use App\Filament\Resources\AmbientacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAmbientacao extends EditRecord
{
    protected static string $resource = AmbientacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
