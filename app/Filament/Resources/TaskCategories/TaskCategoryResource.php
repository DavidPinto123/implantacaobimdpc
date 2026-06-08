<?php

namespace App\Filament\Resources\TaskCategories;

use App\Filament\Resources\TaskCategories\Pages\CreateTaskCategory;
use App\Filament\Resources\TaskCategories\Pages\EditTaskCategory;
use App\Filament\Resources\TaskCategories\Pages\ListTaskCategories;
use App\Filament\Resources\TaskCategories\Pages\ViewTaskCategory;
use App\Filament\Resources\TaskCategories\Schemas\TaskCategoryForm;
use App\Filament\Resources\TaskCategories\Schemas\TaskCategoryInfolist;
use App\Filament\Resources\TaskCategories\Tables\TaskCategoriesTable;
use App\Models\TaskCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TaskCategoryResource extends Resource
{
    protected static ?string $navigationLabel = 'Categorias de Tarefas';

    protected static ?string $modelLabel = 'Categorias de Tarefas';

    protected static ?string $pluralModelLabel = 'Categorias de Tarefas';

    protected static ?string $model = TaskCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Cadastros';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return TaskCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaskCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskCategoriesTable::configure($table);
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
            'index' => ListTaskCategories::route('/'),
            'create' => CreateTaskCategory::route('/create'),
            'view' => ViewTaskCategory::route('/{record}'),
            'edit' => EditTaskCategory::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['Coordenador', 'Gestor', 'Diretor', 'super_admin']);
    }
}
