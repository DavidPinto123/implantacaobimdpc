<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartamentosResource\Pages;
use App\Models\Departamentos;
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

class DepartamentosResource extends Resource
{
    protected static ?string $model = Departamentos::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Gestão Predial e Ativos';

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
                TextColumn::make('departamento'),
                TextColumn::make('area')->label('Área'),
                TextColumn::make('data_extracao')->label('Data de Extração')->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('nova_sigla')
                    ->searchable()
                    ->preload()
                    ->label('Nova Sigla')
                    ->options(
                        Departamentos::query()
                            ->distinct()
                            ->pluck('nova_sigla', 'nova_sigla')),
                SelectFilter::make('unidade')
                    ->searchable()
                    ->preload()
                    ->label('Unidade')
                    ->options(
                        Departamentos::query()
                            ->distinct()
                            ->pluck('unidade', 'unidade')),
                SelectFilter::make('departamento')
                    ->searchable()
                    ->preload()
                    ->label('Departamento')
                    ->options(
                        Departamentos::query()
                            ->distinct()
                            ->pluck('departamento', 'departamento')),
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
            'index' => Pages\ListDepartamentos::route('/'),
            'create' => Pages\CreateDepartamentos::route('/create'),
            'edit' => Pages\EditDepartamentos::route('/{record}/edit'),
        ];
    }
}
