<?php

namespace App\Filament\Resources\NormasHospitalaresResource\Pages;

use App\Filament\Resources\NormasHospitalaresResource;
use Filament\Resources\Pages\ListRecords;

class ListNormasHospitalares extends ListRecords
{
    protected static string $resource = NormasHospitalaresResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
