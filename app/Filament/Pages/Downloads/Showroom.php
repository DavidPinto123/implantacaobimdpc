<?php

namespace App\Filament\Pages\Downloads;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Showroom extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuDownloads';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static UnitEnum|string|null $navigationGroup = 'Downloads e Documentos';
    protected static ?string $navigationLabel = 'Showroom';
    protected static ?string $title = 'Showroom';
    protected static ?string $slug = 'downloads-showroom';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.em-construcao';
}
