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
                NavigationGroup::make()
                    ->label('Implantação BIM')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Retrofit / Ampliação')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Telão')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Painel Global')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Tarefas')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Planejamento')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Comercial')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Orçamento')
                    // ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Contratos')
                    // ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Fornecedor')
                    // ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Pós Obra')
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Mapas')
                    // ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                //                NavigationGroup::make()
                //                    ->label('Localização')
                //                    // ->icon('heroicon-o-cog-6-tooth')
                //                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Gestão Predial e Ativos')
                    // ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                //                NavigationGroup::make()
                //                    ->label('Projetistas BIM')
                //                    // ->icon('heroicon-o-cog-6-tooth')
                //                    ->collapsed(),
                //                NavigationGroup::make()
                //                    ->label('Gerenciar Perfis')
                //                    // ->icon('heroicon-o-cog-6-tooth')
                //                    ->collapsed(),
                //                NavigationGroup::make()
                //                    ->label('WhatsApp')
                //                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Cadastros')
                    // ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Outros')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Configurações')
                    ->collapsed(),
            ])
            // Subgrupos (item sem url para ser apenas agrupamento), colocar para página/resource corresponder ao subgrupo
            ->navigationItems([
                NavigationItem::make('Dashboard')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-squares-2x2')
                    ->sort(1)
                    ->url(null),
                NavigationItem::make('PMO')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->sort(2)
                    ->url(null),
                NavigationItem::make('Comercial')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-briefcase')
                    ->sort(3)
                    ->url(null),
                NavigationItem::make('Arquitetura')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-building-library')
                    ->sort(4)
                    ->url(null),
                NavigationItem::make('Legalização')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-scale')
                    ->sort(5)
                    ->url(null),
                NavigationItem::make('Engenharia')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-building-office-2')
                    ->sort(6)
                    ->url(null),
                NavigationItem::make('Orçamentos')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-banknotes')
                    ->sort(7)
                    ->url(null),
                NavigationItem::make('Financeiro')
                    ->group('Implantação BIM')
                    ->icon('heroicon-o-currency-dollar')
                    ->sort(8)
                    ->url(null),
                ...FilamentDemoNavigation::items(),
                NavigationItem::make('Segurança')
                    ->group('Configurações')
                    ->icon('heroicon-o-shield-check')
                    ->sort(1)
                    ->url(null),
                NavigationItem::make('Fornecedor')
                    ->group('Outros')
                    ->icon('heroicon-o-building-office-2')
                    ->sort(1)
                    ->url(null),
                NavigationItem::make('Mapas')
                    ->group('Outros')
                    ->icon('heroicon-o-map')
                    ->sort(1)
                    ->url(null),
                NavigationItem::make('Gestão Predial e Ativos')
                    ->group('Outros')
                    ->icon('heroicon-o-building-office')
                    ->sort(1)
                    ->url(null),
                NavigationItem::make('Cadastros')
                    ->group('Outros')
                    ->icon('heroicon-o-document-text')
                    ->sort(1)
                    ->url(null),

                NavigationItem::make('Notas Fiscais')
                    ->hidden(),
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
                    ->navigationGroup('Configurações')
                    ->navigationSort(99)
                    ->navigationParentItem('Segurança')
                    ->registerNavigation(true),
                FilamentApexChartsPlugin::make(),
                StickyTableHeaderPlugin::make(),
                // ->shouldScrollToTopOnPageChanged(enabled: true, behavior: "smooth"),
            ])->databaseNotifications();

        // ->collapsibleNavigationGroups(true);
    }
}
