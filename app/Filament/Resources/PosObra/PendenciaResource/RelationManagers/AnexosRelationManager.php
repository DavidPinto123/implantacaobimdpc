<?php

namespace App\Filament\Resources\PosObra\PendenciaResource\RelationManagers;

use App\Enums\PosObra\TipoAnexo;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AnexosRelationManager extends RelationManager
{
    protected static string $relationship = 'anexos';

    protected static ?string $title = 'Anexos / Evidências';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TipoAnexo ? $state->label() : $state),
                Tables\Columns\TextColumn::make('nome_arquivo')->label('Arquivo'),
                Tables\Columns\TextColumn::make('uploadedBy.name')->label('Enviado por'),
                Tables\Columns\TextColumn::make('created_at')->label('Em')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\ImageColumn::make('url')->label('Foto')->square(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
