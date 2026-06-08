<?php

namespace App\Filament\Resources\ProjetoResource\Pages;

use App\Filament\Resources\ProjetoResource;
use Filament\Actions\Action;
use Filament\Schemas\Schema;

class VisualizarPonto extends EditarPonto
{
    protected string $view = 'filament.resources.projeto-resource.pages.visualizar-ponto';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check();
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.pages.dashboard-comercial') => 'Dashboard Comercial',
            '#' => 'Visualizar ponto',
        ];
    }

    public function getTitle(): string
    {
        return 'Visualizar ponto';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components($this->getFormComponents(true));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editar')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => ProjetoResource::getUrl('editar-ponto', ['record' => $this->record->getKey()])),
        ];
    }
}
