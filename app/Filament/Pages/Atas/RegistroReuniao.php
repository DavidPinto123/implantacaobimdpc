<?php

namespace App\Filament\Pages\Atas;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class RegistroReuniao extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuAtas';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static UnitEnum|string|null $navigationGroup = null;
    protected static ?string $navigationLabel = 'Atas';
    protected static ?string $title = 'Atas';
    protected static ?string $slug = 'atas-registro-reuniao';
    protected static ?int $navigationSort = 15;
    protected string $view = 'filament.pages.em-construcao';
}
