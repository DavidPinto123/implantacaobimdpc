<?php

namespace App\Filament\Pages\Downloads;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProjetoModelo extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'Projeto modelo';
    protected static ?string $title = 'Projeto modelo';
    protected static ?string $slug = 'downloads-projeto-modelo';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.em-construcao';
}
