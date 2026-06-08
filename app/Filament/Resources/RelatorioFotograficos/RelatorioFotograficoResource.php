<?php

namespace App\Filament\Resources\RelatorioFotograficos;

use App\Filament\Resources\RelatorioFotograficos\Pages\CreateRelatorioFotografico;
use App\Filament\Resources\RelatorioFotograficos\Pages\EditRelatorioFotografico;
use App\Filament\Resources\RelatorioFotograficos\Pages\ListRelatorioFotograficos;
use App\Filament\Resources\RelatorioFotograficos\Pages\ViewRelatorioFotografico;
use App\Filament\Resources\RelatorioFotograficos\Schemas\RelatorioFotograficoForm;
use App\Filament\Resources\RelatorioFotograficos\Tables\RelatorioFotograficosTable;
use App\Models\RelatorioFotografico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RelatorioFotograficoResource extends Resource
{
    protected static ?string $model = RelatorioFotografico::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Camera;

    protected static ?int $navigationSort = 3;

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Engenharia';

    protected static ?string $navigationLabel = 'Relatório Fotográfico de Posse do imóvel';

    protected static ?string $modelLabel = 'Relatório Fotográfico de Posse do imóvel';

    protected static ?string $pluralModelLabel = 'Relatório Fotográfico de Posse do imóvel';

    public static function form(Schema $schema): Schema
    {
        return RelatorioFotograficoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RelatorioFotograficosTable::configure($table);
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
            'index' => ListRelatorioFotograficos::route('/'),
            'create' => CreateRelatorioFotografico::route('/create'),
            'view' => ViewRelatorioFotografico::route('/{record}'),
            'edit' => EditRelatorioFotografico::route('/{record}/edit'),
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
