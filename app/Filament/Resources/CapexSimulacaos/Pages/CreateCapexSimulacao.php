<?php

namespace App\Filament\Resources\CapexSimulacaos\Pages;

use App\Filament\Resources\CapexSimulacaos\CapexSimulacaoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCapexSimulacao extends CreateRecord
{
    protected static string $resource = CapexSimulacaoResource::class;

    protected function afterCreate(): void
    {
        $this->record->importarEscoposAutomaticos();
        //$this->record->importarEscoposManuais();
        $this->record->ordenarItensPorCustoEstimado();
        $this->record->refresh();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
