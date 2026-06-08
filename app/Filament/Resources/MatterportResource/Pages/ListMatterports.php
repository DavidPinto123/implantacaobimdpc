<?php

namespace App\Filament\Resources\MatterportResource\Pages;

use App\Filament\Resources\MatterportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMatterports extends ListRecords
{
    protected static string $resource = MatterportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('youtube')
                ->label('Ver Tutorial')
                ->icon('heroicon-o-play-circle')
                ->url('https://youtube.com/seu-link-aqui')
                ->openUrlInNewTab()
                ->color('danger'),

            Actions\CreateAction::make(),
        ];
    }
}
