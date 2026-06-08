<?php

namespace App\Filament\Resources\ConstrutoraResource\Pages;

use App\Filament\Resources\ConstrutoraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConstrutora extends EditRecord
{
    protected static string $resource = ConstrutoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
