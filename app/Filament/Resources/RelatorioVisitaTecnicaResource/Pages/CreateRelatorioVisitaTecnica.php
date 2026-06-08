<?php

namespace App\Filament\Resources\RelatorioVisitaTecnicaResource\Pages;

use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRelatorioVisitaTecnica extends CreateRecord
{
    protected static string $resource = RelatorioVisitaTecnicaResource::class;

    public function mount(): void
    {
        parent::mount();

        $record = static::getModel()::create([
            'status' => 'Rascunho',
            'concluido_em' => null,
            'iniciado_em' => now(),
            'autor' => auth()->user()?->name,
        ]);

        $this->redirect($this->getResource()::getUrl('edit', [
            'record' => $record,
        ]));
    }
}
