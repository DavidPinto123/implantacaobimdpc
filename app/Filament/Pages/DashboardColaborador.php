<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class DashboardColaborador extends Page
{
    use HasPageShield;

    // protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard - Colaborador';

    protected static ?string $title = 'Dashboard - Colaborador';

    // URL: /admin/minha-pagina
    protected static ?string $slug = 'dashboard-colaborador';

    // Coloque true se quiser aparecer no menu
    protected static bool $shouldRegisterNavigation = false;

    // Blade que será renderizada
    protected string $view = 'filament.pages.dashboard-colaborador';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Colaborador') ?? false;
    }
}
