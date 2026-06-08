<?php

namespace App\Filament\Resources\PosObra;

use App\Filament\Resources\PosObra\DisciplinaConfigResource\Pages;
use App\Models\PosObra\DisciplinaConfig;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class DisciplinaConfigResource extends Resource
{
    protected static ?string $model = DisciplinaConfig::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $navigationLabel = 'Disciplinas';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Disciplina';

    protected static ?string $pluralModelLabel = 'Disciplinas';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('codigo')
                ->label('Código')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('label')
                ->label('Nome')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('ordem')
                ->label('Ordem')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('ativo')
                ->label('Ativa')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')->label('Código')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('label')->label('Nome')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('ordem')->label('Ordem')->sortable(),
                Tables\Columns\IconColumn::make('ativo')->label('Ativa')->boolean(),
            ])
            ->defaultSort('ordem')
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisciplinaConfigs::route('/'),
            'create' => Pages\CreateDisciplinaConfig::route('/create'),
            'edit' => Pages\EditDisciplinaConfig::route('/{record}/edit'),
        ];
    }
}
