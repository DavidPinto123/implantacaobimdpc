<?php

namespace App\Filament\Resources\AsEscopos\Pages;

use App\Filament\Resources\AsEscopos\AsEscopoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAsEscopo extends EditRecord
{
    protected static string $resource = AsEscopoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
