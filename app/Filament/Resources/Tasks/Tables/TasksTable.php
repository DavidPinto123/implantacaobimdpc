<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Schemas\TaskInfolist;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc') // ✅ mais recente primeiro
            ->columns([
                TextColumn::make('id')->label('ID')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendente' => 'Não iniciada',
                        'em_andamento' => 'Em andamento',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada',
                        default => (string) $state,
                    })
                    ->color(function ($state, $record) {
                        $isOverdue =
                            $record->termino_programado
                            && $record->status !== 'concluida'
                            && $record->status !== 'cancelada'
                            && $record->termino_programado->lt(today());

                        if ($isOverdue) {
                            return 'danger';
                        }

                        return match ($state) {
                            // 'nao_iniciada' => 'gray',
                            'pendente' => 'warning',
                            'em_andamento' => 'info',
                            'concluida' => 'success',
                            'cancelada' => 'gray',
                            default => 'gray',
                        };
                    })
                    ->sortable(),

                TextColumn::make('setor.setor')
                    ->label('Setor')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Tarefa')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description)
                    ->extraAttributes([
                        'style' => 'min-width: 350px;',
                    ]),

                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sigla')
                    ->label('Sigla')
                    ->searchable(),
                TextColumn::make('marca.nome')
                    ->label('Unidade')
                    ->searchable(),
                TextColumn::make('solicitante.name')
                    ->label('Solicitante')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('responsavel.name')
                    ->label('Responsável')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('prazo')
                    ->label('Prazo (dias)')
                    ->sortable(),

                TextColumn::make('dias_corridos')
                    ->label('Contagem')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Dias corridos' : 'Dias úteis')
                    ->color(fn ($state) => $state ? 'warning' : 'success')
                    ->sortable(),
                TextColumn::make('termino_programado')
                    ->label('Término Programado')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_entrega')
                    ->label('Data de Entrega')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                /*
                SelectFilter::make('status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        // 'nao_iniciada' => 'Não Iniciada',
                        'pendente' => 'Não iniciada',
                        'em_andamento' => 'Em andamento',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada',
                    ]),

                SelectFilter::make('task_category_id')
                    ->label('Categoria')
                    ->multiple()
                    ->relationship('category', 'name'),
                */
                SelectFilter::make('assigned_to')
                    ->label('Responsável')
                    ->options(function () {
                        return User::whereIn(
                            'id',
                            Task::query()
                                ->select('assigned_to')
                                ->distinct()
                                ->pluck('assigned_to')
                        )->pluck('name', 'id');
                    })
                    ->searchable()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'Coordenador', 'Gestor', 'Diretor'])),
                /*
                SelectFilter::make('overdue')
                    ->label('Atrasadas')
                    ->placeholder('Todos')
                    ->options([
                        'overdue' => 'Somente atrasadas',
                        'not_overdue' => 'Somente não atrasadas',
                    ])
                    ->query(function ($query, array $data) {
                        // Filament pode usar 'value' (e algumas configs usam 'state')
                        $value = $data['value'] ?? $data['state'] ?? null;

                        // "Todos" -> não aplica nada
                        if (blank($value)) {
                            return $query;
                        }

                        if ($value === 'overdue') {
                            return $query
                                ->whereNotIn('status', ['concluida', 'cancelada'])
                                ->whereNotNull('termino_programado')
                                ->whereDate('termino_programado', '<', today());
                        }

                        // not_overdue
                        return $query->where(function ($q) {
                            $q->whereNull('termino_programado')
                                ->orWhereDate('termino_programado', '>=', today())
                                ->orWhereIn('status', ['concluida', 'cancelada']);
                        });
                    }),
                */

            ])->filtersLayout(FiltersLayout::AboveContent)->deferFilters(false)
            // ->filtersFormColumns(2)
            ->recordActions([
                Action::make('changeStatus')
                    ->label('') // sem texto
                    ->tooltip('Alterar status')
                    ->icon('heroicon-o-arrow-path') // pode trocar
                    // ->visible(fn () => auth()->user()?->isCoordenadorOrcamento())
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                // 'nao_iniciada' => 'Não iniciada',
                                'pendente' => 'Não iniciada',
                                'em_andamento' => 'Em andamento',
                                'concluida' => 'Concluída',
                                'cancelada' => 'Cancelada',
                            ])
                            ->required()
                            ->default(fn ($record) => $record->status),
                    ])
                    ->action(function (array $data, $record) {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),

                Action::make('alterar_responsavel')
                    ->label('')
                    ->tooltip('Alterar responsável')
                    ->icon('heroicon-o-user-circle')
                    ->modalHeading('Alterar responsável da tarefa')
                    ->modalSubmitActionLabel('Salvar')
                    ->form([
                        Select::make('assigned_to')
                            ->label('Responsável')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(function ($record) {
                                if (! $record?->setor_id) {
                                    return [];
                                }

                                return User::query()
                                    ->whereHas('setores', function ($query) use ($record) {
                                        $query->where('setores.id', $record->setor_id);
                                    })
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),
                    ])
                    ->fillForm(fn ($record) => [
                        'assigned_to' => $record->assigned_to,
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'assigned_to' => $data['assigned_to'],
                        ]);

                        if ($record->category?->name === 'Visita Técnica') {
                            $record->responsaveis()->detach();
                        }
                    })
                    ->successNotificationTitle('Responsável alterado com sucesso!'),

                Action::make('view')
                    ->label('')
                    ->icon(null)
                    ->extraAttributes([
                        'class' => 'hidden', // 👈 esconde o botão mas mantém a action viva
                    ])
                    ->modalHeading(fn ($record) => $record->title)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->infolist(
                        fn (Schema $schema) => TaskInfolist::configure($schema)
                    ),

                Action::make('editModal')
                    ->label('')
                    ->tooltip('Editar')
                    ->icon('heroicon-o-pencil-square')
                    // ->visible(fn () => auth()->user()?->isCoordenadorOrcamento()) // opcional (tire se todos podem editar)
                    ->visible(function ($record) {
                        $user = auth()->user();

                        if (! $user) {
                            return false;
                        }

                        // Coordenador edita tudo
                        if ($user->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])) {
                            return true;
                        }

                        // Colaborador só edita o que ELE criou
                        return (int) $record->created_by === (int) $user->id;
                    })
                    ->modalHeading(fn ($record) => "Editar: {$record->title}")
                    ->modalWidth('7xl')
                    // ✅ reaproveita o MESMO formulário do Resource
                    ->schema(fn () => TaskForm::configure(Schema::make())->getComponents())
                    // ✅ preenche o form com os dados do registro
                    ->fillForm(fn ($record) => [
                        'title' => $record->title,
                        'setor_id' => $record->setor_id,
                        'description' => $record->description,
                        'task_category_id' => $record->task_category_id,
                        'sigla' => $record->sigla,
                        'marca_id' => $record->marca_id,
                        'created_by' => $record->created_by,
                        'assigned_to' => $record->assigned_to,
                        'inicio' => optional($record->inicio)?->toDateString(),
                        'prazo' => $record->prazo,
                        'dias_corridos' => (bool) $record->dias_corridos,
                        'termino_programado' => optional($record->termino_programado)?->toDateString(),
                        'data_entrega' => optional($record->data_entrega)?->format('Y-m-d H:i'),
                        'status' => $record->status,
                    ])
                    // ✅ salva
                    ->action(function (array $data, $record) {
                        $user = auth()->user();

                        $isCoord = $user?->hasAnyRole(['Coordenador', 'Gestor', 'Diretor']) === true;

                        // ✅ Nunca mudar o solicitante no edit
                        unset($data['created_by']);

                        // ✅ Se NÃO for coordenador, não deixa mudar responsável (nem por Hidden default)
                        if (! $isCoord) {
                            unset($data['assigned_to']);
                        }

                        // ✅ Atualiza apenas o que sobrou (safe update)
                        $record->update([
                            'title' => $data['title'],
                            'setor_id' => $data['setor_id'],
                            'description' => $data['description'] ?? null,
                            'task_category_id' => $data['task_category_id'],
                            'sigla' => $data['sigla'] ?? null,
                            'marca_id' => $data['marca_id'],
                            'inicio' => $data['inicio'] ?? null,
                            'prazo' => $data['prazo'] ?? null,
                            'dias_corridos' => (bool) ($data['dias_corridos'] ?? false),
                            'termino_programado' => $data['termino_programado'] ?? null,
                            'data_entrega' => $data['data_entrega'] ?? null,
                            'status' => $data['status'],

                            // ✅ só coordenador consegue alterar
                            'assigned_to' => $isCoord
                                ? ($data['assigned_to'] ?? $record->assigned_to)
                                : $record->assigned_to,
                        ]);
                    }),
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->recordUrl(null)
            ->recordAction('view')
            // ->recordClasses('cursor-pointer hover:bg-gray-800 dark:hover:bg-gray-800')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
