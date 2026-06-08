<?php

namespace App\Filament\Resources\ConstrutoraResource\Pages;

use App\Filament\Resources\ConstrutoraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConstrutoras extends ListRecords
{
    protected static string $resource = ConstrutoraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
