<?php

namespace App\Filament\Pages\Downloads;

use Filament\Pages\Page;
use UnitEnum;

class Templates extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'Templates';
    protected static ?string $title = 'Templates';
    protected static ?string $slug = 'downloads-templates';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.em-construcao';
}
