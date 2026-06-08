<?php

namespace App\Filament\Resources\PosObra\DisciplinaConfigResource\Pages;

use App\Filament\Resources\PosObra\DisciplinaConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDisciplinaConfig extends EditRecord
{
    protected static string $resource = DisciplinaConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
