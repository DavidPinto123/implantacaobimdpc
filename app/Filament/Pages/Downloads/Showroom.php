<?php

namespace App\Filament\Pages\Downloads;

use Filament\Pages\Page;
use UnitEnum;

class Showroom extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'Showroom';
    protected static ?string $title = 'Showroom';
    protected static ?string $slug = 'downloads-showroom';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.em-construcao';
}
