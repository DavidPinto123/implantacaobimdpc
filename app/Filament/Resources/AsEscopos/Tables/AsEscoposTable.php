<?php

namespace App\Filament\Resources\AsEscopos\Tables;

use App\Models\AsEscopo;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AsEscoposTable
{
    private static function caminhoGrupoOi(AsEscopo $record): ?string
    {
        $grupo = $record->grupoOi;

        if (! $grupo) {
            return null;
        }

        $partes = [];
        while ($grupo) {
            array_unshift($partes, $grupo->nome);
            $grupo = $grupo->parent;
        }

        return implode(' > ', $partes);
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grupo')
                    ->label('Grupo (legado)')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('grupoOi.nome')
                    ->label('Grupo OI')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->tooltip(fn ($record) => self::caminhoGrupoOi($record)),

                TextColumn::make('numero_as')
                    ->label('A.S.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('escopo')
                    ->label('Escopo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('percentual_faturamento_mao_obra_default')
                    ->label('% M.O.')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(),

                TextColumn::make('percentual_faturamento_material_default')
                    ->label('% Material')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(),

                TextColumn::make('marcas.nome')
                    ->label('Marcas')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),

                IconColumn::make('is_personalizado')
                    ->label('Personalizado')
                    ->boolean(),

                TextColumn::make('criadoPor.name')
                    ->label('Criado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('grupo')
            ->defaultSort('numero_as');
    }
}
