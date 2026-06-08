<?php

namespace App\Filament\Resources\PipeResource\Pages;

use App\Filament\Resources\PipeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPipe extends EditRecord
{
    protected static string $resource = PipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
