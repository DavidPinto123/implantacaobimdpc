<?php

namespace App\Filament\Pages\Downloads;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Manuais extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuDownloads';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'Manuais';
    protected static ?string $title = 'Manuais';
    protected static ?string $slug = 'downloads-manuais';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.em-construcao';
}
