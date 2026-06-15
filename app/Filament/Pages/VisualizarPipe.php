<?php

namespace App\Filament\Pages;

use App\Models\Acompanhamento;
use App\Models\Projeto;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

class VisualizarPipe extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pipe 2025';

    // protected static ?string $label = 'PIPE 2025';

    protected static ?int $navigationSort = 2;

    protected static string|null|UnitEnum $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Dashboard';

    protected string $view = 'filament.pages.visualizar-pipe';

    // Data that we will pass to our View
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

    // Carregando todos os registros com entrega de obra para o mês de fevereiro da tabela Acompanhamento
    protected function getRegistrosPorMes(int $ano, int $mes): Collection
    {
        $inicio = Carbon::create($ano, $mes, 1)->startOfMonth()->toDateString();
        $fim = Carbon::create($ano, $mes, 1)->endOfMonth()->toDateString();

        return Projeto::query()
            ->where('pipeline', 'PIPE 2025')
            ->where('status', '<>', 'Cancelada')
            ->whereBetween('imp_fim', [$inicio, $fim])
            ->orderBy('imp_fim')
            ->get()
            ->map(function (Projeto $item) {
                return [
                    'id' => $item->id,
                    'nome' => $item->nome,
                    'nova_sigla' => $item->nova_sigla,
                    'inicio_obra' => $item->inicio_obra ? date('d/m/Y', strtotime($item->inicio_obra)) : null,
                    'entrega_obra' => $item->entrega_obra ? date('d/m/Y', strtotime($item->entrega_obra)) : null,
                    // 'implantacao' => $item->implantacao ? date("d/m/Y", strtotime($item->implantacao)) : null,
                    'inauguracao' => $item->inauguracao ? date('d/m/Y', strtotime($item->inauguracao)) : null,
                    'status' => $item->status ?? null,
                ];
            });
    }

    public function getTitle(): string
    {
        return 'Acompanhamento Mensal - PIPE 2025'; // título mostrado no topo da página
    }

    public static function canAccess(): bool
    {

        return auth()->user()?->can('View:VisualizarPipe');
    }

    public static function shouldRegisterNavigation(): bool
    {

        return auth()->user()?->can('View:VisualizarPipe');
    }
}
