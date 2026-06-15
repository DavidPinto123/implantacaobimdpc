<?php

namespace App\Filament\Pages\Treinamentos;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class MaterialDidatico extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static UnitEnum|string|null $navigationGroup = 'Treinamentos';
    protected static ?string $navigationLabel = 'Material didático';
    protected static ?string $title = 'Material didático';
    protected static ?string $slug = 'treinamentos-material-didatico';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.em-construcao';
}
