<?php

namespace App\Filament\Resources\RegiaoInteresseResource\Pages;

use App\Filament\Resources\RegiaoInteresseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegiaoInteresses extends ListRecords
{
    protected static string $resource = RegiaoInteresseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
