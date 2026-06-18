<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\CronogramaFaseItem;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detalhes')
                ->columnSpanFull()
                ->schema([
                    Grid::make(4)->schema([
                        Select::make('setor_id')
                            ->label('Setor')
                            ->options(function () {
                                $user = auth()->user();

                                if (! $user) {
                                    return [];
                                }

                                return $user->setores()
                                    ->orderBy('setor')
                                    ->pluck('setores.setor', 'setores.id')
                                    ->toArray();
                            })
                            ->default(function () {
                                $user = auth()->user();

                                if (! $user) {
                                    return null;
                                }

                                return $user->setores()->count() === 1
                                    ? $user->setores()->first()->id
                                    : null;
                            })
                            ->columnSpan(1)
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('assigned_to', null)),

                        Hidden::make('status')
                            ->default('pendente')
                            ->hidden(fn (?Model $record) => $record !== null)
                            ->dehydrated(fn (?Model $record) => $record === null),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pendente'     => 'Não iniciada',
                                'em_andamento' => 'Em andamento',
                                'concluida'    => 'Concluída',
                                'cancelada'    => 'Cancelada',
                            ])
                            ->required()
                            ->hidden(fn (?Model $record) => $record === null)
                            ->dehydrated(fn (?Model $record) => $record !== null),

                        TextInput::make('title')
                            ->label('Nome da Tarefa')
                            ->columnSpan(4)
                            ->required(),

                        Textarea::make('description')
                            ->label('Informações da Tarefa')
                            ->rows(5)
                            ->columnSpanFull(),

                        Select::make('task_category_id')
                            ->label('Categoria da Tarefa')
                            ->options(fn () => TaskCategory::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),

                        TextInput::make('sigla')
                            ->label('Sigla')
                            ->maxLength(20),

                        Select::make('marca_id')
                            ->label('Unidade')
                            ->relationship('marca', 'nome')
                            ->searchable()
                            ->preload(),

                        Select::make('assigned_to')
                            ->label('Responsável')
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get) {
                                $setorId = $get('setor_id');

                                $query = User::query()
                                    ->where('is_active', true)
                                    ->orderBy('name');

                                if ($setorId) {
                                    $query->whereHas('setores', function ($q) use ($setorId) {
                                        $q->where('setores.id', $setorId);
                                    });
                                }

                                return $query->pluck('name', 'id')->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name),

                        Select::make('revisor_id')
                            ->label('Revisor')
                            ->searchable()
                            ->preload()
                            ->options(fn () => User::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name),

                        TextInput::make('valor')
                            ->label('Valor (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->placeholder('0,00')
                            ->helperText(fn (?Model $record) => $record?->cronograma_fase_item_id
                                ? 'Vinculado ao planejamento'
                                : null)
                            ->default(fn (?Model $record) => $record?->cronograma_fase_item?->valor),
                    ]),
                ]),

            Section::make('Datas')
                ->columnSpanFull()
                ->schema([
                    Grid::make(4)->schema([

                        DatePicker::make('inicio')
                            ->label('Data de início')
                            ->default(today())
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                self::calcularTerminoProgramado($set, $get, $state, $get('prazo'));
                            }),

                        TextInput::make('prazo')
                            ->label('Prazo (dias)')
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                self::calcularTerminoProgramado($set, $get, $get('inicio'), $state);
                            }),

                        Toggle::make('dias_corridos')
                            ->label('Dias corridos')
                            ->helperText('Marcado: conta dias corridos. Desmarcado: conta apenas dias úteis.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                self::calcularTerminoProgramado($set, $get, $get('inicio'), $get('prazo'));
                            }),

                        DatePicker::make('termino_programado')
                            ->label('Data de término programado')
                            ->disabled()
                            ->dehydrated(),

                        DateTimePicker::make('data_entrega')
                            ->label('Data de entrega')
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->native(false)
                            ->minutesStep(1)
                            ->hidden(
                                fn (?Model $record) => $record === null
                                    || ! auth()->user()?->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])
                            )
                            ->dehydrated(
                                fn (?Model $record) => $record !== null
                                    && auth()->user()?->hasAnyRole(['Coordenador', 'Gestor', 'Diretor'])
                            ),

                        Hidden::make('created_by')
                            ->default(fn () => auth()->id())
                            ->dehydrated(),
                    ]),
                ]),
        ]);
    }

    protected static function calcularTerminoProgramado(Set $set, Get $get, $inicio, $prazo): void
    {
        $set(
            'termino_programado',
            Task::calcularTerminoProgramadoData(
                $inicio,
                $prazo,
                (bool) $get('dias_corridos')
            )
        );
    }
}
