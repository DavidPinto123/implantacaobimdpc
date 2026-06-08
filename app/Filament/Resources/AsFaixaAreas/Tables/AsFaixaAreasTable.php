<?php

namespace App\Filament\Resources\AsFaixaAreas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AsFaixaAreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Faixa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('area_min')
                    ->label('Área Mínima (m²)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.')),

                TextColumn::make('area_max')
                    ->label('Área Máxima (m²)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state !== null
                            ? number_format((float) $state, 2, ',', '.')
                            : 'Sem limite'
                    ),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('area_min');
    }
}
