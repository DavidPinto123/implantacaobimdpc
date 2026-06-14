<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Widgets\ApexGraficoAcompanhamento;
use App\Filament\Widgets\Dashboard\AcompanhamentoResumoTabelaWidget;
use App\Filament\Widgets\Dashboard\HomeProjetosTableWidget;
use App\Filament\Widgets\Dashboard\HomeStatusOverview;
use App\Models\Projeto;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public static function getNavigationLabel(): string
    {
        return 'Painel Central';
    }

    public function getTitle(): string
    {
        return 'Painel Central';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-home';
    }

    public function getWidgets(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        // Colaborador não vê o dashboard
        if ($user->hasRole('Colaborador')) {
            return [];
        }

        return [
            HomeStatusOverview::class,
            ApexGraficoAcompanhamento::class,
            AcompanhamentoResumoTabelaWidget::class,
            HomeProjetosTableWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('marca')
                    ->label('Marca')
                    ->options(
                        Projeto::query()
                            ->whereNotNull('marca')
                            ->where('marca', '<>', '')
                            ->orderBy('marca')
                            ->distinct()
                            ->pluck('marca', 'marca')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Select::make('pipeline')
                    ->label('Pipeline')
                    ->options(
                        Projeto::query()
                            ->whereNotNull('pipeline')
                            ->where('pipeline', '<>', '')
                            ->orderBy('pipeline')
                            ->distinct()
                            ->pluck('pipeline', 'pipeline')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Select::make('ano')
                    ->label('Ano')
                    ->options(function () {
                        $anos = Projeto::query()
                            ->whereNotNull('imp_fim')
                            ->selectRaw('DISTINCT YEAR(imp_fim) as ano')
                            ->orderByDesc('ano')
                            ->pluck('ano')
                            ->filter()
                            ->values()
                            ->all();

                        return collect($anos)->mapWithKeys(fn ($ano) => [(string) $ano => (string) $ano])->toArray();
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->columns([
                'md' => 3,
                'xl' => 3,
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Diretor', 'Gestor', 'Coordenador']);

    }

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        if ($user->hasRole('Comercial')) {
            redirect()->to(DashboardComercial::getUrl());
        } elseif (! $user->hasAnyRole(['Diretor', 'Gestor', 'Coordenador'])) {
            redirect()->to(ListTasks::getUrl());
        }
    }
}
