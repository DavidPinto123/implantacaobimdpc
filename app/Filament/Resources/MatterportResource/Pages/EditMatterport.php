<?php

namespace App\Filament\Resources\MatterportResource\Pages;

use App\Filament\Resources\MatterportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMatterport extends EditRecord
{
    protected static string $resource = MatterportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
