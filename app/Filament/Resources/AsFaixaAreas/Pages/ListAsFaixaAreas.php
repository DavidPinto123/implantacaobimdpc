<?php

namespace App\Filament\Resources\AsFaixaAreas\Pages;

use App\Filament\Resources\AsFaixaAreas\AsFaixaAreaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsFaixaAreas extends ListRecords
{
    protected static string $resource = AsFaixaAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
