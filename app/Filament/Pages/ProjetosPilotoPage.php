<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;

class ProjetosPilotoPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static UnitEnum|string|null $navigationGroup = 'Projetos Piloto';
    protected static ?string $navigationLabel = 'Projetos Piloto';
    protected static ?string $title = 'Projetos Piloto';
    protected static ?string $slug = 'projetos-piloto';
    protected string $view = 'filament.pages.em-construcao';
}
