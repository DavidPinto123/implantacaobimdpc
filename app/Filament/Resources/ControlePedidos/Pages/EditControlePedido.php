<?php

namespace App\Filament\Resources\ControlePedidos\Pages;

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditControlePedido extends EditRecord
{
    protected static string $resource = ControlePedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        ControlePedidoResource::afterSave($this->record);
    }
}
