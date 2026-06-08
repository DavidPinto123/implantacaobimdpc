<?php

namespace App\Filament\Resources\CapexSimulacaos;

use App\Filament\Resources\CapexSimulacaos\Pages\CreateCapexSimulacao;
use App\Filament\Resources\CapexSimulacaos\Pages\EditCapexSimulacao;
use App\Filament\Resources\CapexSimulacaos\Pages\ListCapexSimulacaos;
use App\Filament\Resources\CapexSimulacaos\RelationManagers\ItensRelationManager;
use App\Filament\Resources\CapexSimulacaos\Schemas\CapexSimulacaoForm;
use App\Filament\Resources\CapexSimulacaos\Tables\CapexSimulacaosTable;
use App\Models\CapexSimulacao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CapexSimulacaoResource extends Resource
{
    protected static ?string $model = CapexSimulacao::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Simulação OI';

    protected static ?string $modelLabel = 'Simulação OI';

    protected static ?string $pluralModelLabel = 'Simulação OI';

    protected static string|null|UnitEnum $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Orçamentos';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return CapexSimulacaoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CapexSimulacaosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // ItensRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCapexSimulacaos::route('/'),
            'create' => CreateCapexSimulacao::route('/create'),
            'edit' => EditCapexSimulacao::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
