<?php

namespace App\Filament\Resources\PosObra;

use App\Enums\PosObra\UrgenciaPendencia;
use App\Filament\Resources\PosObra\ConfiguracaoSlaResource\Pages;
use App\Models\PosObra\ConfiguracaoSla;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ConfiguracaoSlaResource extends Resource
{
    protected static ?string $model = ConfiguracaoSla::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $navigationLabel = 'Configuração SLA';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Configuração SLA';

    protected static ?string $pluralModelLabel = 'Configurações SLA';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('urgencia')
                ->label('Urgência')
                ->options(collect(UrgenciaPendencia::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]))
                ->required(),
            Forms\Components\TextInput::make('prazo_horas')
                ->label('Prazo (horas)')
                ->numeric()
                ->required()
                ->minValue(1),
            Forms\Components\Toggle::make('ativo')
                ->label('Ativo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('urgencia')
                    ->label('Urgência')
                    ->formatStateUsing(fn ($state) => $state instanceof UrgenciaPendencia ? $state->label() : $state)
                    ->badge()
                    ->color(fn ($state) => $state instanceof UrgenciaPendencia ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('prazo_horas')->label('Prazo (h)'),
                Tables\Columns\IconColumn::make('ativo')->label('Ativo')->boolean(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfiguracaoSlas::route('/'),
            'create' => Pages\CreateConfiguracaoSla::route('/create'),
            'edit' => Pages\EditConfiguracaoSla::route('/{record}/edit'),
        ];
    }
}
