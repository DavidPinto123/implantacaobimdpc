<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PipeResource\Pages;
use App\Models\Pipe;
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

class PipeResource extends Resource
{
    protected static ?string $model = Pipe::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static null|string|UnitEnum $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Dashboard';

    protected static ?string $navigationLabel = 'Cadastro de Pipeline';

    protected static ?string $modelLabel = 'Pipeline';

    protected static ?string $slug = 'pipelines';

    protected static ?string $breadcrumb = 'Pipelines';

    protected static ?string $pluralModelLabel = 'Lista de Pipelines';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('pipeline')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pipeline')
                    ->searchable(),
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
            'index' => Pages\ListPipes::route('/'),
            'create' => Pages\CreatePipe::route('/create'),
            'edit' => Pages\EditPipe::route('/{record}/edit'),
        ];
    }
}
