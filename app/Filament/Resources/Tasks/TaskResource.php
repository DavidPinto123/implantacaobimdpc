<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\Pages\ViewTask;
use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Schemas\TaskInfolist;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Filament\Resources\Tasks\Widgets\TaskStats;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $navigationLabel = 'Tarefas';

    protected static ?string $modelLabel = 'Tarefa';

    protected static ?string $pluralModelLabel = 'Tarefas';

    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|null|UnitEnum $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Dashboard';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaskInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $query = Task::query()->where('status', 'pendente');

        $setorIds = $user->setores()->pluck('setores.id')->toArray();

        if ($user->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])) {
            $count = $query->whereIn('setor_id', $setorIds)->count();

            return $count > 0 ? (string) $count : null;
        }

        if ($user->hasRole('Colaborador')) {
            $count = $query->where('assigned_to', $user->id)->count();

            return $count > 0 ? (string) $count : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['category', 'solicitante', 'responsavel', 'marca', 'setor']);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $setorIds = $user->setores()->pluck('setores.id')->toArray();

        if ($user->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])) {
            return $query->whereIn('setor_id', $setorIds);
        }

        if ($user->hasRole('Colaborador')) {
            return $query->where('assigned_to', $user->id);
        }
        
        return $query->whereRaw('1 = 0');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            // 'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            TaskStats::class,
        ];
    }
}
