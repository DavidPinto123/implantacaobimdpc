<?php

namespace App\Filament\Resources\ListaEmails\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListaEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('emails_count')
                    ->label('Qtd. e-mails')
                    ->state(fn ($record) => count($record->emails ?? []))
                    ->sortable(false),

                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}
