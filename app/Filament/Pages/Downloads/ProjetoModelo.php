<?php

namespace App\Filament\Pages\Downloads;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProjetoModelo extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuDownloads';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'Projeto modelo';
    protected static ?string $title = 'Projeto modelo';
    protected static ?string $slug = 'downloads-projeto-modelo';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.em-construcao';
}
