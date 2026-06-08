<?php

namespace App\Filament\Resources\PipeResource\Pages;

use App\Filament\Resources\PipeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPipes extends ListRecords
{
    protected static string $resource = PipeResource::class;

    protected static ?string $navigationLabel = 'Cadastro de Pipeline';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
