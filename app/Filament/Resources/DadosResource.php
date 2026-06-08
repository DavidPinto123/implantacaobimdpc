<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DadosResource\Pages;
use App\Models\Dados;
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

class DadosResource extends Resource
{
    protected static ?string $model = Dados::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Gestão Predial e Ativos';

    protected static ?string $navigationLabel = 'Mobiliários/ Equipamentos';

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
                TextColumn::make('bloco_tipo')->label('Bloco/Tipo'),
                TextColumn::make('categoria'),
                TextColumn::make('descricao')->label('Descrição'),
                TextColumn::make('quantidade')->label('QNT'),
                TextColumn::make('pavimento'),
                TextColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('nova_sigla')
                    ->searchable()
                    ->preload()
                    ->label('Nova Sigla')
                    ->options(
                        Dados::query()
                            ->distinct()
                            ->pluck('nova_sigla', 'nova_sigla')),
                SelectFilter::make('unidade')
                    ->searchable()
                    ->preload()
                    ->label('Unidade')
                    ->options(
                        Dados::query()
                            ->distinct()
                            ->pluck('unidade', 'unidade')),
                SelectFilter::make('marca')
                    ->searchable()
                    ->preload()
                    ->label('Marca')
                    ->options(
                        Dados::query()
                            ->distinct()
                            ->pluck('marca', 'marca')),
                SelectFilter::make('bloco_tipo')
                    ->searchable()
                    ->preload()
                    ->label('Bloco/Tipo')
                    ->options(
                        Dados::query()
                            ->distinct()
                            ->pluck('bloco_tipo', 'bloco_tipo')),
                SelectFilter::make('categoria')
                    ->searchable()
                    ->preload()
                    ->label('Categoria')
                    ->options(
                        Dados::query()
                            ->distinct()
                            ->pluck('categoria', 'categoria')),
                SelectFilter::make('pavimento')
                    ->searchable()
                    ->preload()
                    ->label('Pavimento')
                    ->options(
                        Dados::query()
                            ->whereNotNull('pavimento')
                            ->distinct()
                            ->pluck('pavimento', 'pavimento')),
                SelectFilter::make('status')
                    ->searchable()
                    ->preload()
                    ->label('Status')
                    ->options(
                        Dados::query()
                            ->whereNotNull('status')
                            ->distinct()
                            ->pluck('status', 'status')),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                /*
                Tables\Actions\EditAction::make(),
                */
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
            'index' => Pages\ListDados::route('/'),
            /*
            'create' => Pages\CreateDados::route('/create'),
            'edit' => Pages\EditDados::route('/{record}/edit'),
            */
        ];
    }
}
