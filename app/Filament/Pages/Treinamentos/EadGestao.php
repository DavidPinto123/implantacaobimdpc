<?php

namespace App\Filament\Pages\Treinamentos;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class EadGestao extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuTreinamentos';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static UnitEnum|string|null $navigationGroup = 'Treinamentos';
    protected static ?string $navigationLabel = 'EAD Gestão';
    protected static ?string $title = 'EAD Gestão';
    protected static ?string $slug = 'treinamentos-ead-gestao';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.em-construcao';
}
