<?php

namespace App\Filament\Resources\NotaFiscalResource\Pages;

use App\Filament\Resources\NotaFiscalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotaFiscals extends ListRecords
{
    protected static string $resource = NotaFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
