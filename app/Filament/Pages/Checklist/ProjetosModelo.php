<?php

namespace App\Filament\Pages\Checklist;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ProjetosModelo extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuChecklist';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-check-circle';
    protected static UnitEnum|string|null $navigationGroup = 'Checklist de revisão';
    protected static ?string $navigationLabel = 'De projetos modelo';
    protected static ?string $title = 'De projetos modelo';
    protected static ?string $slug = 'checklist-projetos-modelo';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.em-construcao';
}
