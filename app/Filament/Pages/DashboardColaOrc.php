<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DashboardColaOrc extends Page
{
    // protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard - Colaborador Orçamento';

    protected static ?string $title = 'Dashboard - Colaborador Orçamento';

    // URL: /admin/minha-pagina
    protected static ?string $slug = 'dashboard-colaborador-orcamento';

    // Coloque true se quiser aparecer no menu
    protected static bool $shouldRegisterNavigation = false;

    // Blade que será renderizada
    protected string $view = 'filament.pages.dashboard-cola-orc';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('colaborador_orcamento') ?? false;
    }
}
