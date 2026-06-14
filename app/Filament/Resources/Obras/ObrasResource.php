<?php

namespace App\Filament\Resources\Obras;

use App\Filament\Resources\Obras\Pages\CreateObras;
use App\Filament\Resources\Obras\Pages\EditObras;
use App\Filament\Resources\Obras\Pages\ViewObra;
use App\Filament\Pages\ListaObrasNova;
use App\Filament\Resources\Obras\Schemas\ObrasForm;
use App\Filament\Resources\Obras\Tables\ObrasTable;
use App\Models\Obras;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ObrasResource extends Resource
{
    protected static ?string $model = Obras::class;

    // protected static ?string $navigationLabel = 'Controle de Pedidos';
    // protected static ?string $pluralModelLabel = 'Controle de Pedidos';
    // protected static ?string $modelLabel = 'Controle de Pedido';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Engenharia';

    public static function form(Schema $schema): Schema
    {
        return ObrasForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ObrasTable::configure($table);
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
            'index' => ListaObrasNova::route('/'),
            'create' => CreateObras::route('/create'),
            'view' => ViewObra::route('/{record}'),
            'edit' => EditObras::route('/{record}/edit'),
        ];
    }
}
