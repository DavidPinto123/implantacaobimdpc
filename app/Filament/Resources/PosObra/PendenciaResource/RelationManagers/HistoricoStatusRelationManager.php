<?php

namespace App\Filament\Resources\PosObra\PendenciaResource\RelationManagers;

use App\Enums\PosObra\StatusPendencia;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class HistoricoStatusRelationManager extends RelationManager
{
    protected static string $relationship = 'atualizacoesStatus';

    protected static ?string $title = 'Histórico de Status';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status_anterior')
                    ->label('De')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof StatusPendencia ? $state->label() : ($state ?? '—'))
                    ->color(fn ($state) => $state instanceof StatusPendencia ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('status_novo')
                    ->label('Para')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof StatusPendencia ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof StatusPendencia ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('atualizado_por')->label('Por'),
                Tables\Columns\TextColumn::make('comentario')->label('Comentário'),
                Tables\Columns\TextColumn::make('created_at')->label('Em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
