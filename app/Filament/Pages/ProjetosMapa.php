<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProjetosMapa extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Projetos por Estado';

    protected string $view = 'filament.pages.projetos-mapa';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Mapas';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:ProjetosMapa');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('View:ProjetosMapa');
    }
}
