<?php

namespace App\Filament\Resources\AsEscopos\Pages;

use App\Filament\Resources\AsEscopos\AsEscopoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsEscopos extends ListRecords
{
    protected static string $resource = AsEscopoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
