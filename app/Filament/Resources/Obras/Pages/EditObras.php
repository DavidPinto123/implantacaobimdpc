<?php

namespace App\Filament\Resources\Obras\Pages;

use App\Filament\Resources\Obras\ObrasResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditObras extends EditRecord
{
    protected static string $resource = ObrasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
