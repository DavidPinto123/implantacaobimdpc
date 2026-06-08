<?php

namespace App\Filament\Resources\MatterportResource\Pages;

use App\Filament\Resources\MatterportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMatterport extends ViewRecord
{
    protected static string $resource = MatterportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
