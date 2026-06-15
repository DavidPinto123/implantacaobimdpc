<?php

namespace App\Filament\Pages\Downloads;

use Filament\Pages\Page;
use UnitEnum;

class Plugins extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'Plugins';
    protected static ?string $title = 'Plugins';
    protected static ?string $slug = 'downloads-plugins';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.em-construcao';
}
