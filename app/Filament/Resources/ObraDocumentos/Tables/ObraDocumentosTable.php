<?php

namespace App\Filament\Resources\ObraDocumentos\Tables;

use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Models\ObraDocumento;
use App\Models\Obras;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ObraDocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obra_id')
                    ->label('Obra')
                    ->formatStateUsing(fn ($state, $record): string => $record->obra instanceof Obras
                        ? ObraDocumentoResource::getObraLabel($record->obra)
                        : 'Obra não definida')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nome')
                    ->label('Nome do documento')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state): string => ObraDocumentoResource::getStatusLabel($state))
                    ->color(fn (?string $state): string => ObraDocumentoResource::getStatusColor($state))
                    ->badge(),

                TextColumn::make('arquivos_paths_resolved')
                    ->label('Uploads')
                    ->state(fn (ObraDocumento $record): string => (string) count($record->arquivos_paths_resolved))
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('obra_id')
                    ->label('Obra')
                    ->options(fn (): array => ObraDocumentoResource::getAvailableObrasOptions(Auth::user()))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ObraDocumentoResource::getStatusOptions()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (ObraDocumento $record): bool => ! ObraDocumentoResource::isSentStatus($record->status)),
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc');
    }
}
