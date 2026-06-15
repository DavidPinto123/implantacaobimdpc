<?php

namespace App\Filament\Pages\Atas;

use Filament\Pages\Page;
use UnitEnum;

class RegistroReuniao extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static UnitEnum|string|null $navigationGroup = 'Atas';
    protected static ?string $navigationLabel = 'Registro de assuntos tratados em reunião';
    protected static ?string $title = 'Registro de assuntos tratados em reunião';
    protected static ?string $slug = 'atas-registro-reuniao';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.em-construcao';
}
