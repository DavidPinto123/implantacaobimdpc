<?php

namespace App\Filament\Resources\Obras\Pages;

use App\Filament\Resources\Obras\ObrasResource;
use App\Models\Obras;
use App\Services\ControleNotaFiscal\CriaControleNotaFiscalExpansao;
use Filament\Resources\Pages\CreateRecord;

class CreateObras extends CreateRecord
{
    protected static string $resource = ObrasResource::class;

    protected function afterCreate(): void
    {
        if ($this->record instanceof Obras) {
            app(CriaControleNotaFiscalExpansao::class)->handle($this->record);
        }
    }
}
