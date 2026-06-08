<?php

namespace App\Filament\Resources\ControlePedidos\Pages;

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Models\Obras;
use Filament\Resources\Pages\CreateRecord;

class CreateControlePedido extends CreateRecord
{
    protected static string $resource = ControlePedidoResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->prefillProjetoFromRequest();
    }

    protected function prefillProjetoFromRequest(): void
    {
        $obraId = request()->query('obra_id');

        if (! filled($obraId)) {
            return;
        }

        $obra = Obras::query()->find($obraId);

        if (! $obra || blank($obra->projeto_id)) {
            return;
        }

        $this->form->fill([
            'projeto_id' => $obra->projeto_id,
        ]);
    }

    protected function afterCreate(): void
    {
        ControlePedidoResource::afterSave($this->record);
    }
}
