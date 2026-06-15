<?php

namespace App\Filament\Pages\Treinamentos;

use App\Traits\HasMenuPermission;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class ComentariosInstrutor extends Page
{
    use HasMenuPermission;

    protected static function menuPermission(): string
    {
        return 'View:MenuTreinamentos';
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static UnitEnum|string|null $navigationGroup = 'Treinamentos';
    protected static ?string $navigationLabel = 'Comentários do instrutor sobre as turmas';
    protected static ?string $title = 'Comentários do instrutor sobre as turmas';
    protected static ?string $slug = 'treinamentos-comentarios-instrutor';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.em-construcao';
}
