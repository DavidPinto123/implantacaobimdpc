<?php

namespace App\Filament\Pages\Treinamentos;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Certificados extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static UnitEnum|string|null $navigationGroup = 'Treinamentos';
    protected static ?string $navigationLabel = 'Certificados';
    protected static ?string $title = 'Certificados';
    protected static ?string $slug = 'treinamentos-certificados';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.em-construcao';
}
