<?php

namespace App\Filament\Pages\Checklist;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Manuais extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-circle';
    protected static UnitEnum|string|null $navigationGroup = 'Checklist de revisão';
    protected static ?string $navigationLabel = 'De manuais';
    protected static ?string $title = 'De manuais';
    protected static ?string $slug = 'checklist-manuais';
    protected static ?int $navigationSort = 5;
    protected string $view = 'filament.pages.em-construcao';
}
