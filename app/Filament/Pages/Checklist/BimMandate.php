<?php

namespace App\Filament\Pages\Checklist;

use Filament\Pages\Page;
use UnitEnum;

class BimMandate extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static UnitEnum|string|null $navigationGroup = 'Checklist de revisão';
    protected static ?string $navigationLabel = 'De BIM Mandate';
    protected static ?string $title = 'De BIM Mandate';
    protected static ?string $slug = 'checklist-bim-mandate';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.em-construcao';
}
