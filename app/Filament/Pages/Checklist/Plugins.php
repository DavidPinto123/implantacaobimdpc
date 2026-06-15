<?php

namespace App\Filament\Pages\Checklist;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Plugins extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-circle';
    protected static UnitEnum|string|null $navigationGroup = 'Checklist de revisão';
    protected static ?string $navigationLabel = 'De plugins';
    protected static ?string $title = 'De plugins';
    protected static ?string $slug = 'checklist-plugins';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.em-construcao';
}
