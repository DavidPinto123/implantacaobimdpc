<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmbientesResource\Pages;
use App\Models\Ambientes;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AmbientesResource extends Resource
{
    protected static ?string $model = Ambientes::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static UnitEnum|string|null $navigationGroup = 'Gestão Predial e Ativos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nova_sigla')->label('Nova Sigla'),
                TextColumn::make('unidade'),
                TextColumn::make('marca'),
                TextColumn::make('departamento'),
                TextColumn::make('ambiente'),
                TextColumn::make('area')->label('Área'),
                TextColumn::make('pavimento'),
                TextColumn::make('data_extracao')->label('Data de Extração')->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('nova_sigla')
                    ->searchable()
                    ->preload()
                    ->label('Nova Sigla')
                    ->options(
                        Ambientes::query()
                            ->distinct()
                            ->pluck('nova_sigla', 'nova_sigla')),
                SelectFilter::make('unidade')
                    ->searchable()
                    ->preload()
                    ->label('Unidade')
                    ->options(
                        Ambientes::query()
                            ->distinct()
                            ->pluck('unidade', 'unidade')),
                SelectFilter::make('marca')
                    ->searchable()
                    ->preload()
                    ->label('Marca')
                    ->options(
                        Ambientes::query()
                            ->distinct()
                            ->pluck('marca', 'marca')),
                SelectFilter::make('departamento')
                    ->searchable()
                    ->preload()
                    ->label('Departamento')
                    ->options(
                        Ambientes::query()
                            ->distinct()
                            ->pluck('departamento', 'departamento')),
                SelectFilter::make('ambiente')
                    ->searchable()
                    ->preload()
                    ->label('Ambiente')
                    ->options(
                        Ambientes::query()
                            ->distinct()
                            ->pluck('ambiente', 'ambiente')),
                SelectFilter::make('pavimento')
                    ->searchable()
                    ->preload()
                    ->label('Pavimento')
                    ->options(
                        Ambientes::query()
                            ->distinct()
                            ->pluck('pavimento', 'pavimento')),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmbientes::route('/'),
            'create' => Pages\CreateAmbientes::route('/create'),
            'edit' => Pages\EditAmbientes::route('/{record}/edit'),
        ];
    }
}
