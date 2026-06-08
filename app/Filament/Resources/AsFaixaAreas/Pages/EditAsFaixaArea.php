<?php

namespace App\Filament\Resources\AsFaixaAreas\Pages;

use App\Filament\Resources\AsFaixaAreas\AsFaixaAreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAsFaixaArea extends EditRecord
{
    protected static string $resource = AsFaixaAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
