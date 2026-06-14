<?php

namespace App\Filament\Resources\GrupoOis;

use App\Filament\Resources\GrupoOis\Pages\CreateGrupoOi;
use App\Filament\Resources\GrupoOis\Pages\EditGrupoOi;
use App\Filament\Resources\GrupoOis\Pages\ListGrupoOis;
use App\Filament\Resources\GrupoOis\Schemas\GrupoOiForm;
use App\Filament\Resources\GrupoOis\Tables\GrupoOisTable;
use App\Models\GrupoOi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GrupoOiResource extends Resource
{
    protected static ?string $model = GrupoOi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $navigationLabel = 'Grupos OI';

    protected static ?string $modelLabel = 'Grupo OI';

    protected static ?string $pluralModelLabel = 'Grupos OI';

    protected static UnitEnum|string|null $navigationGroup = 'Cadastros';

    protected static ?string $recordTitleAttribute = 'nome';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return GrupoOiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrupoOisTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrupoOis::route('/'),
            'create' => CreateGrupoOi::route('/create'),
            'edit' => EditGrupoOi::route('/{record}/edit'),
        ];
    }
}
