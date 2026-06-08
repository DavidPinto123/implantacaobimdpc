<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Resources\ProjetoResource;
use App\Filament\Widgets\Dashboard\Concerns\AppliesHomeFilters;
use App\Models\Projeto;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class HomeProjetosTableWidget extends TableWidget
{
    use AppliesHomeFilters;
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '';

    public function table(Table $table): Table
    {
        $headerStyle = 'background-color:#f5bf00;color:#111827;font-weight:700;';

        return $table
            ->query($this->getFilteredProjetosQuery())
            ->defaultSort('codigo')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable()
                    ->extraHeaderAttributes(['style' => $headerStyle]),
                TextColumn::make('nova_sigla')
                    ->label('Sigla')
                    ->state(fn ($record) => $record->nova_sigla ?: $record->sigla)
                    ->searchable()
                    ->sortable()
                    ->extraHeaderAttributes(['style' => $headerStyle]),
                TextColumn::make('nome')
                    ->label('Unidade')
                    ->searchable()
                    ->sortable()
                    ->extraHeaderAttributes(['style' => $headerStyle]),
            ])
            ->recordUrl(function (Projeto $record): ?string {
                return ProjetoResource::getUrl('painel', ['record' => $record]);
            })
            ->paginated([10, 25, 50]);
    }
}
