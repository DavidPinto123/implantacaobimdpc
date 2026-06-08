<?php

namespace App\Filament\Resources\GestaoObraResource\Pages;

use App\Filament\Resources\GestaoObraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGestaoObras extends ListRecords
{
    protected static string $resource = GestaoObraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
