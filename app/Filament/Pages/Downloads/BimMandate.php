<?php

namespace App\Filament\Pages\Downloads;

use Filament\Pages\Page;
use UnitEnum;

class BimMandate extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'BIM Mandate';
    protected static ?string $title = 'BIM Mandate';
    protected static ?string $slug = 'downloads-bim-mandate';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.em-construcao';
}
