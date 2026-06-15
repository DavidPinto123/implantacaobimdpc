<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstadoResource\Pages;
use App\Models\Estado;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class EstadoResource extends Resource
{
    protected static ?string $model = Estado::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Cadastro de Estados';

    protected static ?string $modelLabel = 'Estado';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $slug = 'estados';

    protected static ?string $breadcrumb = 'Estados';

    protected static ?int $navigationSort = 8;

    protected static ?string $pluralModelLabel = 'Lista de Estados';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('pais_id')
                    ->relationship(name: 'pais', titleAttribute: 'nome')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pais.nome')
                    ->label('País')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => Pages\ListEstados::route('/'),
            'create' => Pages\CreateEstado::route('/create'),
            'view' => Pages\ViewEstado::route('/{record}'),
            'edit' => Pages\EditEstado::route('/{record}/edit'),
        ];
    }
}
