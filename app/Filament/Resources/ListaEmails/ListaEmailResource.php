<?php

namespace App\Filament\Resources\ListaEmails;

use App\Filament\Resources\ListaEmails\Pages\CreateListaEmail;
use App\Filament\Resources\ListaEmails\Pages\EditListaEmail;
use App\Filament\Resources\ListaEmails\Pages\ListListaEmails;
use App\Filament\Resources\ListaEmails\Schemas\ListaEmailForm;
use App\Filament\Resources\ListaEmails\Tables\ListaEmailsTable;
use App\Models\ListaEmail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ListaEmailResource extends Resource
{
    protected static ?string $model = ListaEmail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nome';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $navigationLabel = 'Cadastro de Emails';

    public static function form(Schema $schema): Schema
    {
        return ListaEmailForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ListaEmailsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user?->can('ViewAny:ListaEmail') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListListaEmails::route('/'),
            'create' => CreateListaEmail::route('/create'),
            'edit' => EditListaEmail::route('/{record}/edit'),
        ];
    }
}
