<?php

namespace App\Filament\Pages\Treinamentos;

use Filament\Pages\Page;
use UnitEnum;

class ConteudoProgramatico extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static UnitEnum|string|null $navigationGroup = 'Treinamentos';
    protected static ?string $navigationLabel = 'Conteúdo programático';
    protected static ?string $title = 'Conteúdo programático';
    protected static ?string $slug = 'treinamentos-conteudo-programatico';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.em-construcao';
}
