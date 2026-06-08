<?php

namespace App\Filament\Resources\Obras\Pages;

use App\Filament\Resources\Obras\ObrasResource;
use App\Filament\Tables\TableExcel\Concerns\HasTableExcel;
use Filament\Resources\Pages\ListRecords;

class ListObras extends ListRecords
{
    use HasTableExcel;

    protected static string $resource = ObrasResource::class;

    public function getTitle(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
