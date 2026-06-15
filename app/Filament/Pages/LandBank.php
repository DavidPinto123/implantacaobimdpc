<?php

namespace App\Filament\Pages;

use App\Models\Projeto;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

class LandBank extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Land Bank 2025';

    protected static ?int $navigationSort = 3; // muda a ordem se precisar

    protected static string|null|UnitEnum $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Dashboard';

    protected string $view = 'filament.pages.land-bank';

    protected function getViewData(): array
    {
        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        $dados = [];

        foreach ($meses as $numero => $nome) {
            $registros = $this->getRegistrosPorMes(2025, $numero);
            $dados["registros{$nome}"] = $registros;
            $dados["contador{$nome}"] = $registros->count();
        }

        return $dados;
    }

    protected function getRegistrosPorMes(int $ano, int $mes): Collection
    {
        return Projeto::query()
            ->where('pipeline', 'LAND BANK 2025')
            ->where('status', '<>', 'Cancelada')
            ->whereYear('data_posse', $ano)          // filtra o ano
            ->where('mes_posse', $mes)       // filtra o mês direto
            ->orderBy('data_posse')
            ->get()
            ->map(function (Projeto $item) {
                return [
                    'id' => $item->id,
                    'nome' => $item->nome,
                    'nova_sigla' => $item->nova_sigla,
                    'posse_data' => $item->data_posse ? Carbon::parse($item->data_posse)->format('d/m/Y') : null,
                    'posse_status' => $item->posse_status,
                    'posse_engenharia' => $item->posse_engenharia,
                    'posse_legalizacao' => $item->posse_legalizacao,
                ];
            });
    }

    public function getTitle(): string
    {
        return 'Acompanhamento Mensal - LAND BANK 2025';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:LandBank');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('View:LandBank');
    }
}
