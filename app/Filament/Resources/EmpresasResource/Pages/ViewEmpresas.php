<?php

namespace App\Filament\Resources\EmpresasResource\Pages;

use App\Filament\Resources\EmpresasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmpresas extends ViewRecord
{
    protected static string $resource = EmpresasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
