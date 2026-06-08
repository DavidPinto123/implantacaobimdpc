<?php

namespace App\Filament\Resources\DepartamentosResource\Pages;

use App\Filament\Resources\DepartamentosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartamentos extends EditRecord
{
    protected static string $resource = DepartamentosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
