<?php

namespace App\Filament\Resources\NotaFiscalResource\Pages;

use App\Filament\Resources\NotaFiscalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaFiscal extends CreateRecord
{
    protected static string $resource = NotaFiscalResource::class;

    protected function beforeCreate(): void
    {
        // Se quiser inspecionar os dados, descomenta abaixo:
        // dd($this->data['tipos_faturamento']);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        dd($data);

        return $data;
    }
}
