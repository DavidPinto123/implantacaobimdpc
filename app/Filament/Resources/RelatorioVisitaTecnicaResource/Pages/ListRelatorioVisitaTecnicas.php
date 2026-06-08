<?php

namespace App\Filament\Resources\RelatorioVisitaTecnicaResource\Pages;

use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRelatorioVisitaTecnicas extends ListRecords
{
    protected static string $resource = RelatorioVisitaTecnicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $user = Filament::auth()->user();

        // Gestor e Super Admin veem tudo
        if ($user->hasRole(['super_admin', 'gestor_obras'])) {
            return $query;
        }

        // Visita Técnica vê apenas as próprias
        if ($user->hasRole('Visita Técnica')) {
            return $query->where('autor', $user->name);
        }

        return $query;
    }
}
