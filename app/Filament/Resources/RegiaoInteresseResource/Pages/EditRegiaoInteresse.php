<?php

namespace App\Filament\Resources\RegiaoInteresseResource\Pages;

use App\Filament\Resources\RegiaoInteresseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegiaoInteresse extends EditRecord
{
    protected static string $resource = RegiaoInteresseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
