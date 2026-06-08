<?php

namespace App\Filament\Resources\ListaEmails\Pages;

use App\Filament\Resources\ListaEmails\ListaEmailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListListaEmails extends ListRecords
{
    protected static string $resource = ListaEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
