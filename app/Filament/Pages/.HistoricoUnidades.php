<?php

namespace App\Filament\Pages;

use App\Enums\MotivoAlteracaoObra;
use App\Models\CronogramaFaseHistorico;
use App\Models\Projeto;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HistoricoUnidades extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'PMO';

    protected static ?string $navigationLabel = 'Histórico de Unidades';

    protected static ?string $title = 'Histórico de Unidades';

    protected static ?string $slug = 'historico-unidades';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.historico-unidades';

    public function getHeading(): string
    {
        return 'Histórico de Unidades';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->paginated([25, 50, 100])
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data Alteração')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('projeto.codigo')
                    ->label('Código')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('projeto.sigla')
                    ->label('Sigla')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('projeto.nome')
                    ->label('Unidade')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('projeto.marca')
                    ->label('Marca')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.escopo')
                    ->label('Escopo')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.pipeline')
                    ->label('Pipe')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('projeto.status')
                    ->label('Status')
                    ->placeholder('—')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_anterior')
                    ->label('Data Posse Antiga')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::parse($state)->format('d/m/Y') : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_novo')
                    ->label('Data Posse Nova')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::parse($state)->format('d/m/Y') : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delta_dias')
                    ->label('Δ Dias')
                    ->state(function (CronogramaFaseHistorico $record): ?int {
                        if (! $record->valor_anterior || ! $record->valor_novo) {
                            return null;
                        }

                        return (int) Carbon::parse($record->valor_anterior)
                            ->diffInDays(Carbon::parse($record->valor_novo), false);
                    })
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state > 0 => 'warning',
                        $state < 0 => 'success',
                        default => 'gray',
                    })
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : ($state > 0 ? "+{$state}" : (string) $state))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('DATEDIFF(valor_novo, valor_anterior) '.$direction);
                    }),

                Tables\Columns\TextColumn::make('motivo_codigo')
                    ->label('Motivo')
                    ->formatStateUsing(fn (?MotivoAlteracaoObra $state): string => $state?->label() ?? '—')
                    ->badge()
                    ->color(fn (?MotivoAlteracaoObra $state): string => $state?->color() ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('motivo_historico')
                    ->label('Motivo Histórico')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn (CronogramaFaseHistorico $record): ?string => $record->motivo_historico)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Alterado por')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('motivo_codigo')
                    ->label('Motivo')
                    ->options(MotivoAlteracaoObra::paraSelect())
                    ->multiple(),

                SelectFilter::make('projeto_status')
                    ->label('Status da unidade')
                    ->options(fn () => Projeto::query()
                        ->distinct()
                        ->whereNotNull('status')
                        ->pluck('status', 'status')
                        ->filter()
                        ->toArray())
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('projeto', fn (Builder $q) => $q->whereIn('status', $data['values']));
                    }),

                SelectFilter::make('contrato_assinado')
                    ->label('Status do contrato')
                    ->options([
                        'assinado' => 'Somente contratos assinados',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) !== 'assinado') {
                            return $query;
                        }

                        return $query->whereHas('projeto', function (Builder $q): void {
                            $q->whereNotNull('data_ass_contrato');
                        });
                    }),

                Filter::make('periodo')
                    ->form([
                        DatePicker::make('de')->label('Alterado de'),
                        DatePicker::make('ate')->label('Alterado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['de'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['ate'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),

                Filter::make('pipe_alterado')
                    ->label('Apenas pipe alterado (mudou de ano)')
                    ->toggle()
                    ->query(function (Builder $query, array $data): Builder {
                        if (! ($data['isActive'] ?? false)) {
                            return $query;
                        }

                        return $query->whereRaw('YEAR(valor_anterior) <> YEAR(valor_novo)');
                    }),
            ])
            ->emptyStateHeading('Sem alterações de Data de Posse')
            ->emptyStateDescription('Nenhuma unidade teve sua data de posse alterada ainda.');
    }

    private function getQuery(): Builder
    {
        return CronogramaFaseHistorico::query()
            ->where('campo_alterado', 'projeto.data_posse')
            ->whereNotNull('valor_anterior')
            ->whereNotNull('valor_novo')
            ->with(['projeto', 'usuario']);
    }
}
