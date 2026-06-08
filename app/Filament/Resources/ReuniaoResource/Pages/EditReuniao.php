<?php

namespace App\Filament\Resources\ReuniaoResource\Pages;

use App\Filament\Resources\ReuniaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReuniao extends EditRecord
{
    protected static string $resource = ReuniaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
