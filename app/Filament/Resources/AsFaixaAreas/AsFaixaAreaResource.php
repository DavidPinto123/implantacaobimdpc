<?php

namespace App\Filament\Resources\AsFaixaAreas;

use App\Filament\Resources\AsFaixaAreas\Pages\CreateAsFaixaArea;
use App\Filament\Resources\AsFaixaAreas\Pages\EditAsFaixaArea;
use App\Filament\Resources\AsFaixaAreas\Pages\ListAsFaixaAreas;
use App\Filament\Resources\AsFaixaAreas\Schemas\AsFaixaAreaForm;
use App\Filament\Resources\AsFaixaAreas\Tables\AsFaixaAreasTable;
use App\Models\AsFaixaArea;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AsFaixaAreaResource extends Resource
{
    protected static ?string $model = AsFaixaArea::class;

    protected static ?string $navigationLabel = 'Cadastro de Faixas';

    protected static ?string $modelLabel = 'Faixas';

    protected static ?string $pluralModelLabel = 'Cadastro de Faixas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';
    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $recordTitleAttribute = 'nome';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return AsFaixaAreaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsFaixaAreasTable::configure($table);
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
            'index' => ListAsFaixaAreas::route('/'),
            'create' => CreateAsFaixaArea::route('/create'),
            'edit' => EditAsFaixaArea::route('/{record}/edit'),
        ];
    }
}
