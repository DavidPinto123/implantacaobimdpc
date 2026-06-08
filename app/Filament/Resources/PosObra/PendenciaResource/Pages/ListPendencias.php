<?php

namespace App\Filament\Resources\PosObra\PendenciaResource\Pages;

use App\Filament\Pages\PosObra\AprovacoesPage;
use App\Filament\Pages\PosObra\KanbanPendencias;
use App\Filament\Resources\PosObra\PendenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendencias extends ListRecords
{
    protected static string $resource = PendenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('aprovacoes')
                ->label('Aprovações')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(fn () => AprovacoesPage::getUrl()),
            Actions\Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->url(fn () => KanbanPendencias::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
