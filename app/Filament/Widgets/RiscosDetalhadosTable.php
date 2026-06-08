<?php

namespace App\Filament\Widgets;

use App\Models\Projeto;
use App\Models\RiscoResumo;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RiscosDetalhadosTable extends TableWidget
{
    protected static ?string $heading = '';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        // Documento
        $subDocumento = Projeto::withoutGlobalScopes()
            ->selectRaw("1 as id, 'Documento' as tipo_risco,
        SUM(CASE WHEN locacao = 'Mono usuário' THEN 1 ELSE 0 END) as mono,
        SUM(CASE WHEN locacao = 'Multiusuário' THEN 1 ELSE 0 END) as multi,
        COUNT(*) as total")
            ->where('pipeline', 'PIPE 2025')
            ->where('risco_obra', 0);

        // Obra
        $subObra = Projeto::withoutGlobalScopes()
            ->selectRaw("2 as id, 'Obra' as tipo_risco,
        SUM(CASE WHEN locacao = 'Mono usuário' THEN 1 ELSE 0 END) as mono,
        SUM(CASE WHEN locacao = 'Multiusuário' THEN 1 ELSE 0 END) as multi,
        COUNT(*) as total")
            ->where('pipeline', 'PIPE 2025')
            ->where('risco_obra', 1);

        // Documento + Obra
        $subDocObra = Projeto::withoutGlobalScopes()
            ->selectRaw("3 as id, 'Documento + Obra' as tipo_risco,
        SUM(CASE WHEN locacao = 'Mono usuário' THEN 1 ELSE 0 END) as mono,
        SUM(CASE WHEN locacao = 'Multiusuário' THEN 1 ELSE 0 END) as multi,
        COUNT(*) as total")
            ->where('pipeline', 'PIPE 2025')
            ->whereIn('id', function ($query) {
                $query->select('id')
                    ->from('projetos as p')
                    ->where('pipeline', 'PIPE 2025')
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('projetos as p2')
                            ->whereColumn('p2.id', 'p.id')
                            ->where('p2.pipeline', 'PIPE 2025')
                            ->where('p2.risco_obra', 1);
                    })
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('projetos as p3')
                            ->whereColumn('p3.id', 'p.id')
                            ->where('p3.pipeline', 'PIPE 2025')
                            ->where('p3.risco_obra', 0);
                    });
            });

        // União das três linhas fixas
        $sub = $subDocumento
            ->unionAll($subObra)
            ->unionAll($subDocObra);

        $dados = RiscoResumo::query()
            ->fromSub($sub, 'riscos')
            ->select('id', 'tipo_risco', 'mono', 'multi', 'total')
            ->orderByRaw("FIELD(tipo_risco, 'Documento', 'Obra', 'Documento + Obra')");

        return $table
            ->query($dados)
            ->columns([
                TextColumn::make('tipo_risco')
                    ->label('Riscos'),
                TextColumn::make('mono')
                    ->label('Mono')
                    ->summarize(Sum::make()->label('')),
                TextColumn::make('multi')
                    ->label('Multi')
                    ->summarize(Sum::make()->label('')),
                TextColumn::make('total')->label('#')
                    ->color(fn ($state) => $state > 0 ? 'danger' : null)
                    ->summarize(Sum::make()->label('')),
            ])->paginated(false);
    }

    protected function applySortingToTableQuery(Builder $query): Builder
    {
        return $query; // ignora qualquer ORDER BY extra
    }
}
