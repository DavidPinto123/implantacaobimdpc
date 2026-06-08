<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SetorResource\Pages;
use App\Models\Setor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SetorResource extends Resource
{
    protected static ?string $model = Setor::class;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $navigationLabel = 'Cadastro de Setores';

    protected static ?string $modelLabel = 'Setor';

    protected static ?string $slug = 'setores';

    protected static ?string $breadcrumb = 'Setores';

    protected static ?string $pluralModelLabel = 'Lista de Setores';

    protected static ?int $navigationSort = 4;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('setor')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('setor')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListSetors::route('/'),
            'create' => Pages\CreateSetor::route('/create'),
            'edit' => Pages\EditSetor::route('/{record}/edit'),
        ];
    }
}
