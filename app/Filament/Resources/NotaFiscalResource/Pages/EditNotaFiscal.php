<?php

namespace App\Filament\Resources\NotaFiscalResource\Pages;

use App\Filament\Resources\NotaFiscalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotaFiscal extends EditRecord
{
    protected static string $resource = NotaFiscalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
