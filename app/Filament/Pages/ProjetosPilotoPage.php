<?php

namespace App\Filament\Pages;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProjetosPilotoPage extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuProjetosPiloto';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rocket-launch';
    protected static UnitEnum|string|null $navigationGroup = null;
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'Projetos Piloto';
    protected static ?string $title = 'Projetos Piloto';
    protected static ?string $slug = 'projetos-piloto';
    protected string $view = 'filament.pages.em-construcao';
}
