<?php

namespace App\Filament\Resources\ListaEmails\Pages;

use App\Filament\Resources\ListaEmails\ListaEmailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditListaEmail extends EditRecord
{
    protected static string $resource = ListaEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
