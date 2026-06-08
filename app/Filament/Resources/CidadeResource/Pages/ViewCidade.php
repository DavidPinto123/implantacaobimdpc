<?php

namespace App\Filament\Resources\CidadeResource\Pages;

use App\Filament\Resources\CidadeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCidade extends ViewRecord
{
    protected static string $resource = CidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
