<?php

namespace App\Filament\Resources\DadosResource\Pages;

use App\Filament\Resources\DadosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDados extends ListRecords
{
    protected static string $resource = DadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Mobiliários/Equipamentos';
    }
}
