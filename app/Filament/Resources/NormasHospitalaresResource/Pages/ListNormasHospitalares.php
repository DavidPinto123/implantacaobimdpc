<?php

namespace App\Filament\Resources\NormasHospitalaresResource\Pages;

use App\Filament\Resources\NormasHospitalaresResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListNormasHospitalares extends ListRecords
{
    protected static string $resource = NormasHospitalaresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.normas-hospitalares.table-style');
    }
}
