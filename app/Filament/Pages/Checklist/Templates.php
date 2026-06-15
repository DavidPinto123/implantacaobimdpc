<?php

namespace App\Filament\Pages\Checklist;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Templates extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-circle';
    protected static UnitEnum|string|null $navigationGroup = 'Checklist de revisão';
    protected static ?string $navigationLabel = 'De templates';
    protected static ?string $title = 'De templates';
    protected static ?string $slug = 'checklist-templates';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.em-construcao';
}
