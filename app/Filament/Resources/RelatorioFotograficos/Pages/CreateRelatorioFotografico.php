<?php

namespace App\Filament\Resources\RelatorioFotograficos\Pages;

use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRelatorioFotografico extends CreateRecord
{
    protected static string $resource = RelatorioFotograficoResource::class;

    public function mount(): void
    {
        parent::mount();

        $record = static::getModel()::create([
            'status' => 'Rascunho',
            'status_relatorio' => 'Rascunho',
            'autor_id' => auth()->id(),
            'gestor_id' => auth()->id(),
        ]);

        $this->redirect($this->getResource()::getUrl('edit', [
            'record' => $record,
        ]));
    }
}
