<?php

namespace App\Filament\Resources\AsEscopos;

use App\Filament\Resources\AsEscopos\Pages\CreateAsEscopo;
use App\Filament\Resources\AsEscopos\Pages\EditAsEscopo;
use App\Filament\Resources\AsEscopos\Pages\ListAsEscopos;
use App\Filament\Resources\AsEscopos\RelationManagers\FaixasAreaRelationManager;
use App\Filament\Resources\AsEscopos\Schemas\AsEscopoForm;
use App\Filament\Resources\AsEscopos\Tables\AsEscoposTable;
use App\Models\AsEscopo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AsEscopoResource extends Resource
{
    protected static ?string $model = AsEscopo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Cadastro de Escopo';

    protected static ?string $modelLabel = 'Escopo';

    protected static ?string $pluralModelLabel = 'Escopos';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $slug = 'escopos';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return AsEscopoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsEscoposTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->globais();
    }

    public static function getRelations(): array
    {
        return [
            FaixasAreaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAsEscopos::route('/'),
            'create' => CreateAsEscopo::route('/create'),
            'edit' => EditAsEscopo::route('/{record}/edit'),
        ];
    }
}
