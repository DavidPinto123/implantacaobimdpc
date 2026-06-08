<?php

namespace App\Filament\Resources\RelatorioVisitaTecnicaResource\Pages;

use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use App\Filament\Tables\Actions\VisitaTecnica\ConverterParaPdf;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRelatorioVisitaTecnica extends ViewRecord
{
    protected static string $resource = RelatorioVisitaTecnicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            ConverterParaPdf::make(),
        ];
    }
}
