<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CidadeResource\Pages;
use App\Models\Cidade;
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

class CidadeResource extends Resource
{
    protected static ?string $model = Cidade::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Cadastro de Cidades';

    protected static ?string $modelLabel = 'Cidade';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $slug = 'cidades';

    protected static ?string $breadcrumb = 'Cidades';

    protected static ?int $navigationSort = 9;

    protected static ?string $pluralModelLabel = 'Lista de Cidades';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('estado_id')
                    ->relationship(name: 'estado', titleAttribute: 'nome')
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
                    ->label('Cidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado.nome')
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
            'index' => Pages\ListCidades::route('/'),
            'create' => Pages\CreateCidade::route('/create'),
            'view' => Pages\ViewCidade::route('/{record}'),
            'edit' => Pages\EditCidade::route('/{record}/edit'),
        ];
    }
}
