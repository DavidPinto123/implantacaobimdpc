<?php

namespace App\Filament\Resources\DadosResource\Pages;

use App\Filament\Resources\DadosResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDados extends EditRecord
{
    protected static string $resource = DadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
