<?php

namespace App\Filament\Resources\ReuniaoResource\Pages;

use App\Filament\Resources\ReuniaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReuniaos extends ListRecords
{
    protected static string $resource = ReuniaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Reuniões';
    }
}
