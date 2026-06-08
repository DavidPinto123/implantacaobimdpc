<?php

namespace App\Filament\Resources\Asas\Tables;

use App\Enums\AsStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

class AsasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (EloquentBuilder $query, $livewire) {
                if (filled($livewire->projetoFiltro)) {
                    $query->where('projeto_id', $livewire->projetoFiltro);
                }

                if (filled($livewire->construtoraFiltro)) {
                    $query->where('solicitante', $livewire->construtoraFiltro);
                }

                /*
                if (filled($livewire->statusFiltro)) {
                    $query->where('status', $livewire->statusFiltro);
                }
                */
                return $query;
            })
            ->columns([
                TextColumn::make('numero_asa')
                    ->label('N ASA')
                    ->limit(10)
                    ->tooltip(fn ($record) => $record->numero_asa)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('projeto.nome')
                    ->label('UNIDADE')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('descricao')
                    ->label('DESCRICAO')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('solicitante')
                    ->label('SOLICITANTE')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sigla')
                    ->label('Sigla')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->badge()
                    ->sortable()
                    ->getStateUsing(fn ($record) => self::normalizeStatus($record->status))
                    ->formatStateUsing(fn (?string $state) => AsStatus::labelFrom($state))
                    ->colors([
                        'info' => [AsStatus::SOLICITADO->value, AsStatus::CRIADA->value, AsStatus::ENVIADA->value],
                        'warning' => [AsStatus::EM_APROVACAO_GESTOR->value, AsStatus::EM_APROVACAO_ORCAMENTO->value],
                        'gray' => [AsStatus::RASCUNHO->value, null],
                        'success' => [AsStatus::APROVADO->value],
                        'danger' => [AsStatus::REPROVADO_GESTOR->value, AsStatus::REPROVADO_ORCAMENTO->value, AsStatus::CANCELADA->value],
                    ]),

                TextColumn::make('contrato')
                    ->label('GRUPO')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('codigo_as_emitida')
                    ->label('AS EMITIDA')
                    ->searchable()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('valor_bruto')
                    ->label('VALOR')
                    ->money('BRL')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('desconto')
                    ->label('DESCONTO')
                    ->money('BRL')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('valor_total')
                    ->label('TOTAL')
                    ->money('BRL')
                    ->alignCenter()
                    ->sortable(),
                /*
                TextColumn::make('valor_total')
                    ->label('Total')
                    ->money('BRL')
                    ->alignCenter()
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label('Em analise')
                            ->query(fn(Builder $query) => $query->where('status', 'em_analise'))
                            ->money('BRL'),

                        Sum::make()
                            ->label('Aprovado')
                            ->query(fn(Builder $query) => $query->where('status', 'aprovado'))
                            ->money('BRL'),
                    ]),
                */

                TextColumn::make('data_solicitacao')
                    ->label('SOLICITACAO')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable(),
                /*
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                */
            ])
            ->recordActions([
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar ASA'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir ASA'),
            ])->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->defaultSort('id', 'desc');
    }

    protected static function normalizeStatus(AsStatus|string|null $status): ?string
    {
        if ($status instanceof AsStatus) {
            return $status->value;
        }

        if (blank($status)) {
            return null;
        }

        return AsStatus::tryFrom(trim(mb_strtolower((string) $status)))?->value;
    }
}
