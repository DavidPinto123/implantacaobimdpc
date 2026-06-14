<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EtapaResource\Pages;
use App\Models\Etapa;
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

class EtapaResource extends Resource
{
    protected static ?string $model = Etapa::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?string $navigationLabel = 'Cadastro de Etapas';

    protected static ?string $modelLabel = 'Etapa';

    protected static ?string $slug = 'etapas';

    protected static ?string $breadcrumb = 'Etapas';

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralModelLabel = 'Lista de Etapas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('Projetos nesta etapa'),
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
            'index' => Pages\ListEtapas::route('/'),
            'create' => Pages\CreateEtapa::route('/create'),
            'view' => Pages\ViewEtapa::route('/{record}'),
            'edit' => Pages\EditEtapa::route('/{record}/edit'),
        ];
    }
}
