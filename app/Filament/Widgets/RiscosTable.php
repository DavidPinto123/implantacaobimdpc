<?php

namespace App\Filament\Widgets;

use App\Models\Projeto;
use App\Models\RiscoResumo;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RiscosTable extends TableWidget
{
    protected static ?string $heading = '';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        // Inaugurada
        $subInaugurada = Projeto::withoutGlobalScopes()
            ->selectRaw("1 as id, 'Inaugurada' as categoria,
                COUNT(*) as total,
                SUM(CASE WHEN risco_obra = 1 THEN 1 ELSE 0 END) as risco,
                SUM(CASE WHEN risco_obra = 0 THEN 1 ELSE 0 END) as liquido")
            ->where('pipeline', 'PIPE 2025')
            ->where('status', 'Inaugurada');

        // Obras
        $subObras = Projeto::withoutGlobalScopes()
            ->selectRaw("2 as id,'Obras' as categoria,
                COUNT(*) as total,
                SUM(CASE WHEN risco_obra = 1 THEN 1 ELSE 0 END) as risco,
                SUM(CASE WHEN risco_obra = 0 THEN 1 ELSE 0 END) as liquido")
            ->where('pipeline', 'PIPE 2025')
            ->where('status', 'Obras');

        // Assinada
        $subAssinada = Projeto::withoutGlobalScopes()
            ->selectRaw("3 as id,'Assinada + Land Bank' as categoria,
                COUNT(*) as total,
                SUM(CASE WHEN risco_obra = 1 THEN 1 ELSE 0 END) as risco,
                SUM(CASE WHEN risco_obra = 0 THEN 1 ELSE 0 END) as liquido")
            ->where('pipeline', 'PIPE 2025')
            ->whereRaw("TRIM(UPPER(status_contrato)) = 'ASSINADO'");

        // Land Bank
        $subLandBank = Projeto::withoutGlobalScopes()
            ->selectRaw("4 as id,'Assinada + Land Bank' as categoria,
                COUNT(*) as total,
                SUM(CASE WHEN risco_obra = 1 THEN 1 ELSE 0 END) as risco,
                SUM(CASE WHEN risco_obra = 0 THEN 1 ELSE 0 END) as liquido")
            ->where('pipeline', 'LIKE', 'LAND BANK%');

        // União das subqueries
        $sub = $subInaugurada
            ->unionAll($subObras)
            ->unionAll($subAssinada)
            ->unionAll($subLandBank);

        // Query final agrupando categorias
        $dados = RiscoResumo::query()
            ->fromSub($sub, 'riscos')
            ->selectRaw('MIN(id) as id, categoria as status,
        SUM(total) as total,
        SUM(risco) as risco,
        SUM(liquido) as liquido')
            ->groupBy('categoria')
            ->orderByRaw("FIELD(status, 'Inaugurada', 'Obras', 'Assinada + Land Bank')");

        return $table
            ->query($dados) // impede qualquer ORDER BY extra
            ->columns([
                TextColumn::make('status')->label('Status'),

                TextColumn::make('total')
                    ->label('#')
                    ->summarize(Sum::make()->label('')),

                TextColumn::make('risco')
                    ->label('Risco')
                    ->color('danger')
                    ->summarize(Sum::make()->label('')),

                TextColumn::make('liquido')
                    ->label('Líquido')
                    ->summarize(Sum::make()->label('')),
            ])->paginated(false);
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return null; // não deixa o Filament escolher "id"
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return null;
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        return $query; // ignora qualquer ORDER BY extra
    }
}
