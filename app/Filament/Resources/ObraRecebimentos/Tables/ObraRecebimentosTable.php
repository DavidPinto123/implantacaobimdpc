<?php

namespace App\Filament\Resources\ObraRecebimentos\Tables;

use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use App\Models\ObraRecebimento;
use App\Models\Obras;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ObraRecebimentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obra_id')
                    ->label('Obra')
                    ->formatStateUsing(fn ($state, $record): string => $record->obra instanceof Obras
                        ? ObraRecebimentoResource::getObraLabel($record->obra)
                        : 'Obra não definida')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nome')
                    ->label('Item entregue')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state): string => ObraRecebimentoResource::getStatusLabel($state))
                    ->color(fn (?string $state): string => ObraRecebimentoResource::getStatusColor($state))
                    ->badge(),

                TextColumn::make('fornecedor.nome')
                    ->label('Fornecedor')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('foto_entrega_paths_resolved')
                    ->label('Foto')
                    ->state(fn (ObraRecebimento $record): bool => $record->hasFotoEntrega())
                    ->boolean(),

                IconColumn::make('nota_fiscal_paths_resolved')
                    ->label('NF')
                    ->state(fn (ObraRecebimento $record): bool => $record->hasNotaFiscal())
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('obra_id')
                    ->label('Obra')
                    ->options(fn (): array => ObraRecebimentoResource::getAvailableObrasOptions(Auth::user()))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ObraRecebimentoResource::getStatusOptions()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(function (ObraRecebimento $record): bool {
                        return ! ObraRecebimentoResource::isReceivedStatus($record->status);
                    }),
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => ObraRecebimentoResource::canManageAll(Auth::user())),
                ]),
            ]);
    }
}
