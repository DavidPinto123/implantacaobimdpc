<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Matterport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-camera'; // Ícone no menu
    protected static ?string $navigationLabel = 'Tour 360°'; // Nome no menu
  	protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Tour 360°'; // Título da página
    protected static string $view = 'filament.pages.matterport'; // Caminho da view Blade
  
  
  	public static function canAccess(): bool {
    
        return auth()->user()?->can('page_Matterport');
    }
    public static function shouldRegisterNavigation(): bool {

        return auth()->user()?->can('page_Matterport');
    }
}
