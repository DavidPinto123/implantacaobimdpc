<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EmailVerificationPrompt;
use App\Filament\Auth\Login;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\DashboardColaOrc;
use App\Support\FilamentDemoNavigation;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
// use Filament\Pages\Dashboard;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use WatheqAlshowaiter\FilamentStickyTableHeader\StickyTableHeaderPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            /*
            ->bootUsing(function () {
                app()->bind(LoginResponse::class, CustomLoginResponse::class);
            })
            */
            ->emailVerification(EmailVerificationPrompt::class)
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->brandName('DPC Consultoria BIM')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->darkModeBrandLogo(fn () => view('filament.brand-logo-dark'))
            ->favicon(asset('images/favicon_dpc.png'))
            ->brandLogoHeight('3rem')
            ->colors([
                'primary' => '#fbba00',
                'secondary' => '#fbba00',
                'warning' => '#fbba00',
                'success' => '#00FF00',
                'danger' => '#ff0000',
                'green' => '#217346',
                'blue' => '#6495EDAA',
            ])

            ->navigationGroups([
                // ── Grupos principais ────────────────────────────────────────────
                // Planejamento, Tarefas, WhatsApp — itens únicos, direto no sidebar
                NavigationGroup::make()->label('Registro fotográfico')->collapsed(),
                NavigationGroup::make()->label('Mapas')->collapsed(),
                NavigationGroup::make()->label('Checklist de revisão')->collapsed(),
                // Upload de documentos, Atas, Visualizador 3D — itens únicos, direto no sidebar
                NavigationGroup::make()->label('Downloads e Documentos')->collapsed(),
                NavigationGroup::make()->label('Treinamentos')->collapsed(),
                NavigationGroup::make()->label('Outros')->collapsed(),
            ])
            ->navigationItems([
                // ── Subgrupos de Outros ──────────────────────────────────────────
                NavigationItem::make('Dashboard')
                    ->group('Outros')->icon('heroicon-o-squares-2x2')->sort(0)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('PMO')
                    ->group('Outros')->icon('heroicon-o-presentation-chart-line')->sort(1)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Comercial')
                    ->group('Outros')->icon('heroicon-o-briefcase')->sort(4)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Arquitetura')
                    ->group('Outros')->icon('heroicon-o-building-library')->sort(5)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Legalização')
                    ->group('Outros')->icon('heroicon-o-scale')->sort(6)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Engenharia')
                    ->group('Outros')->icon('heroicon-o-building-office-2')->sort(7)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Orçamentos')
                    ->group('Outros')->icon('heroicon-o-banknotes')->sort(8)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Financeiro')
                    ->group('Outros')->icon('heroicon-o-currency-dollar')->sort(9)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Pós Obra')
                    ->group('Outros')->icon('heroicon-o-building-office')->sort(10)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Retrofit / Ampliação')
                    ->group('Outros')->icon('heroicon-o-wrench-screwdriver')->sort(11)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Gestão Predial e Ativos')
                    ->group('Outros')->icon('heroicon-o-building-office-2')->sort(12)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Cadastros')
                    ->group('Outros')->icon('heroicon-o-rectangle-stack')->sort(13)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Central de Notificações')
                    ->group('Outros')->icon('heroicon-o-bell')->sort(14)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                NavigationItem::make('Configurações')
                    ->group('Outros')->icon('heroicon-o-cog-6-tooth')->sort(15)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                ...FilamentDemoNavigation::items(),
                // ── Outros ───────────────────────────────────────────────────────
                NavigationItem::make('Fornecedor')
                    ->group('Outros')->icon('heroicon-o-building-office-2')->sort(16)->url(null)
                    ->visible(fn () => auth()->user()?->can('View:MenuOutros')),
                // ── Ocultar item avulso ──────────────────────────────────────────
                NavigationItem::make('Notas Fiscais')->hidden(),
            ])

            /*
                NavigationItem::make('Calendário')
                    ->url('#')
                    ->icon('heroicon-o-calendar')
                    ->sort(6)
              		->group('Projetistas BIM'),
                NavigationItem::make('Aprenda aqui')
                    ->url('#')
                    ->icon('heroicon-o-arrow-up-right')
                    ->sort(8)
              		->group('Projetistas BIM'),
                NavigationItem::make('InfraBIM')
                    ->url('#')
                    ->icon('heroicon-o-arrow-up-right')
                    ->sort(9)
              		->group('Projetistas BIM'),
                NavigationItem::make('BIM Mandate')
                    ->url('#')
                    ->icon('heroicon-o-arrow-up-right')
                    ->sort(10)
              		->group('Projetistas BIM'),
                NavigationItem::make('Mentoria')
                    ->url('#')
                    ->icon('heroicon-o-arrow-up-right')
                    ->sort(11)
              ->group('Projetistas BIM'),

                NavigationItem::make('Financeiro')
                    ->url('#')
                    ->icon('heroicon-o-arrow-up-right')
                    ->sort(12)
                NavigationItem::make('Plugins')
                    ->url('#')
                    ->icon('heroicon-o-arrow-up-right')
                    ->sort(13)
              		->group('Projetistas BIM'),
            ])
            */
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                // \BezhanSalleh\FilamentShield\Resources\RoleResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pipeline::class,
                // Dashboard::class,
                DashboardColaOrc::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => view('filament.table-excel.scripts')->render(),
            )
            ->darkMode()
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationLabel('Perfis e Permissões')
                    ->navigationIcon('heroicon-o-shield-check')
                    ->activeNavigationIcon('heroicon-s-shield-check')
                    ->navigationGroup('Outros')
                    ->navigationSort(99)
                    ->navigationParentItem('Configurações')
                    ->registerNavigation(true),
                FilamentApexChartsPlugin::make(),
                StickyTableHeaderPlugin::make(),
                // ->shouldScrollToTopOnPageChanged(enabled: true, behavior: "smooth"),
            ])->databaseNotifications();

        // ->collapsibleNavigationGroups(true);
    }
}
