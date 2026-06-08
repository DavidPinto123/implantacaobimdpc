<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProjetoResource;
use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use App\Models\Projeto;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TableAcompanhamento extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $heading = 'Tabela de Projetos';

    public function table(Table $table): Table
    {
        return $table
            ->query(ProjetoResource::getEloquentQuery())
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25])
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('sigla')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nova_sigla')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => [
                        'Fase de Projeto' => 'danger',
                        'Em obra' => 'primary',
                        'Inaugurada' => 'success',
                    ][$state] ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('pipeline')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('etapas.nome')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('marca')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tipo_de_loja')
                    ->label('Tipo de Loja')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('prazo_inicio')
                    ->label('Início do Projeto')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('entrega_projeto')
                    ->label('Entrega do Projeto')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('inicio_obra')
                    ->label('Início da Obra')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('entrega_obra')
                    ->label('Entrega da Obra')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('inauguracao')
                    ->label('Inauguração')
                    ->date('d/m/Y')
                    ->alignCenter()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ano_inauguracao')
                    ->label('Ano da Inauguração')
                    ->alignCenter()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pais.nome')
                    ->label('País')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('estado.nome')
                    ->label('Estado')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cidade.nome')
                    ->label('Cidade')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Criado em')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->preload()
                    ->options(
                        Projeto::query()
                            ->whereNotNull('nome')
                            ->where('nome', '!=', '')
                            ->distinct()
                            ->orderBy('nome')
                            ->pluck('nome', 'nome')
                            ->toArray()
                    ),
                SelectFilter::make('sigla')
                    ->label('Sigla')
                    ->searchable()
                    ->preload()
                    ->options(
                        Projeto::query()
                            ->whereNotNull('sigla')
                            ->where('sigla', '!=', '')
                            ->distinct()
                            ->orderBy('sigla')
                            ->pluck('sigla', 'sigla')
                            ->toArray()
                    ),

                SelectFilter::make('nova_sigla')
                    ->label('Nova Sigla')
                    ->searchable()
                    ->preload()
                    ->options(
                        Projeto::query()
                            ->whereNotNull('nova_sigla')
                            ->where('nova_sigla', '!=', '')
                            ->distinct()
                            ->orderBy('nova_sigla')
                            ->pluck('nova_sigla', 'nova_sigla')
                            ->toArray()
                    ),

                SelectFilter::make('pipeline')
                    ->label('Pipe / Land')
                    ->options(
                        Projeto::query()
                            ->whereNotNull('pipeline')
                            ->where('pipeline', '!=', '')
                            ->distinct()
                            ->orderBy('pipeline')
                            ->pluck('pipeline', 'pipeline')
                    )
                    ->searchable()
                    ->multiple()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->searchable()
                    ->preload()
                    ->options(
                        Projeto::query()
                            ->whereNotNull('status')
                            ->where('status', '!=', '')
                            ->distinct()
                            ->orderBy('status')
                            ->pluck('status', 'status')
                            ->toArray()
                    ),

                SelectFilter::make('marca')
                    ->label('Marca')
                    ->searchable()
                    ->preload()
                    ->options(
                        Projeto::query()
                            ->whereNotNull('marca')
                            ->where('marca', '!=', '')
                            ->distinct()
                            ->orderBy('marca')
                            ->pluck('marca', 'marca')
                            ->toArray()
                    ),
                /*
                SelectFilter::make('tipo_de_loja')
                    ->label('Tipo de Loja')
                    ->searchable()
                    ->preload()
                    ->options(
                        Projeto::query()
                            ->whereNotNull('tipo_de_loja')
                            ->where('tipo_de_loja', '!=', '')
                            ->distinct()
                            ->orderBy('tipo_de_loja')
                            ->pluck('tipo_de_loja', 'tipo_de_loja')
                            ->toArray()
                    ),
                */
                Filter::make('localizacao')
                    ->form([
                        Grid::make(3)
                            ->schema([
                                Select::make('pais_id')
                                    ->label('País')
                                    ->options(Pais::orderBy('nome')->pluck('nome', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('estado_id', null)),

                                Select::make('estado_id')
                                    ->label('Estado')
                                    ->options(
                                        fn ($get) => Estado::where('pais_id', $get('pais_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->default(null)
                                    ->afterStateUpdated(fn (callable $set) => $set('cidade_id', null)),

                                Select::make('cidade_id')
                                    ->label('Cidade')
                                    ->options(
                                        fn ($get) => Cidade::where('estado_id', $get('estado_id'))
                                            ->orderBy('nome')
                                            ->pluck('nome', 'id')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->default(null),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['pais_id'], fn ($q, $pais) => $q->where('pais_id', $pais))
                            ->when($data['estado_id'], fn ($q, $estado) => $q->where('estado_id', $estado))
                            ->when($data['cidade_id'], fn ($q, $cidade) => $q->where('cidade_id', $cidade));
                    })
                    ->indicateUsing(fn (array $data): array => array_filter([
                        $data['pais_id'] ? 'País: '.(Pais::find($data['pais_id'])?->nome ?? '') : null,
                        $data['estado_id'] ? 'Estado: '.(Estado::find($data['estado_id'])?->nome ?? '') : null,
                        $data['cidade_id'] ? 'Cidade: '.(Cidade::find($data['cidade_id'])?->nome ?? '') : null,
                    ])),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                ViewAction::make()
                    ->label('')
                    ->url(fn ($record) => route('filament.admin.resources.projetos.view', $record))
                    ->openUrlInNewTab(false),
                EditAction::make()
                    ->label('')
                    ->url(fn ($record) => route('filament.admin.resources.projetos.edit', $record))
                    ->openUrlInNewTab(false),
            ], position: RecordActionsPosition::BeforeCells)
            ->bulkActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function view(): string
    {
        return view('filament.widgets.table-acompanhamento', [
            'table' => $this->table(ProjetoResource::getEloquentQuery()),
        ]);
    }
}
