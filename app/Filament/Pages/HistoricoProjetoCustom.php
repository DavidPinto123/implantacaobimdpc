<?php

namespace App\Filament\Pages;

use App\Models\HistoricoProjeto;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class HistoricoProjetoCustom extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.historico-projeto-custom';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationLabel = 'Histórico dos Projetos';

    protected function getViewData(): array
    {
        $historico = HistoricoProjeto::with(['usuario', 'projeto'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('fase');

        return [
            'historico' => $historico,
        ];
    }

    public function getTitle(): string
    {
        return 'Histórico dos Projetos';
    }

    public static function canAccess(): bool
    {

        return auth()->user()?->can('View:HistoricoProjetoCustom');
    }

    public static function shouldRegisterNavigation(): bool
    {

        return auth()->user()?->can('View:HistoricoProjetoCustom');
    }
}
