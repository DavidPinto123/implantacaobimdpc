<?php

namespace App\Filament\Resources\CapexSimulacaos\RelationManagers;

use App\Filament\Components\Forms\MoneyInput;
use App\Filament\Tables\Columns\InlineEditColumn;
use App\Models\AsEscopo;
use App\Services\AutorizacaoServicoComplementoService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class ItensRelationManager extends RelationManager
{
    protected static string $relationship = 'itens';

    protected static ?string $title = 'Itens da Simulação';

    protected static function asEscopoOptionLabel(AsEscopo $escopo): string
    {
        return collect([
            $escopo->grupo,
            $escopo->numero_as,
            $escopo->escopo,
        ])
            ->filter(fn ($value): bool => filled($value))
            ->implode(' - ');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Hidden::make('tipo')
                    ->default('manual'),

                Select::make('as_escopo_id')
                    ->label('Escopo AS')
                    ->relationship(
                        name: 'escopo',
                        titleAttribute: 'escopo',
                        modifyQueryUsing: fn ($query) => $query
                            ->globais()
                            ->where('is_active', true)
                            ->orderBy('grupo')
                            ->orderBy('numero_as')
                    )
                    ->getOptionLabelFromRecordUsing(fn (AsEscopo $record): string => self::asEscopoOptionLabel($record))
                    ->searchable(['grupo', 'numero_as', 'escopo'])
                    ->preload()
                    ->native(false)
                    ->live()
                    ->columnSpan([
                        'default' => 12,
                        'md' => 8,
                    ])
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if (! filled($state)) {
                            return;
                        }

                        $escopo = AsEscopo::query()->find((int) $state);

                        if ($escopo) {
                            $set('nome_escopo', $escopo->escopo);
                        }
                    })
                    ->createOptionForm([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Select::make('grupo')
                                    ->label('Grupo')
                                    ->options([
                                        'Civil' => 'Civil',
                                        'Ar Condicionado' => 'Ar Condicionado',
                                        'Elétrica' => 'Elétrica',
                                        'Combate a Incêndio' => 'Combate a Incêndio',
                                        'Homologados' => 'Homologados',
                                        'Shell' => 'Shell',
                                        'Projetos' => 'Projetos',
                                        'Solicitação Cliente' => 'Solicitação Cliente',
                                        'Legalização' => 'Legalização',
                                        'Orçamentos' => 'Orçamentos',
                                    ])
                                    ->native(false)
                                    ->searchable()
                                    ->required(),

                                TextInput::make('numero_as')
                                    ->label('A.S.')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(table: 'as_escopos', column: 'numero_as'),
                            ]),

                        TextInput::make('escopo')
                            ->label('Escopo')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'as_escopos', column: 'escopo'),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return AsEscopo::query()->create([
                            'grupo' => $data['grupo'],
                            'numero_as' => $data['numero_as'],
                            'escopo' => $data['escopo'],
                            'is_personalizado' => true,
                            'is_active' => true,
                        ])->getKey();
                    }),

                TextInput::make('numero_complemento')
                    ->label('Complemento')
                    ->maxLength(10)
                    ->dehydrateStateUsing(fn ($state): string => app(AutorizacaoServicoComplementoService::class)->normalizar($state))
                    ->helperText('Vazio para principal; C1, C2 etc. para complemento.')
                    ->columnSpan([
                        'default' => 12,
                        'md' => 4,
                    ]),

                TextInput::make('nome_escopo')
                    ->label('Nome exibido na OI')
                    ->required(fn (Get $get): bool => blank($get('as_escopo_id')))
                    ->maxLength(255)
                    ->helperText('Obrigatório quando o item não estiver vinculado a um escopo AS.')
                    ->columnSpan(12),

                MoneyInput::makeNonNull('valor_base_m2', 'Valor manual')
                    ->label('Valor manual')
                    ->required()
                    ->minValue(0)
                    ->default(0)
                    ->columnSpan([
                        'default' => 12,
                        'md' => 8,
                    ]),

                Toggle::make('incluir')
                    ->label('Incluir no total')
                    ->default(true)
                    ->required()
                    ->columnSpan([
                        'default' => 12,
                        'md' => 4,
                    ]),

                Textarea::make('comentario')
                    ->label('Comentário')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Detalhes do escopo manual, premissas ou origem do valor.')
                    ->columnSpan(12),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome_escopo')
            ->extraAttributes(['class' => 'capex-itens-table'])
            ->defaultPaginationPageOption(25)
            ->reorderable('ordem')
            ->defaultSort('ordem')
            ->columns([
                TextColumn::make('nome_escopo')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => $state === 'auto' ? 'info' : 'gray')
                    ->sortable(),

                TextColumn::make('numero_complemento')
                    ->label('Compl.')
                    ->placeholder('-')
                    ->badge()
                    ->sortable(),

                ToggleColumn::make('incluir')
                    ->label('Incluir')
                    ->onColor('success')   // verde
                    ->offColor('danger')   // vermelho
                    ->afterStateUpdated(function ($record, $state) {
                        if ($record->tipo === 'manual') {
                            $custoEstimado = $state
                                ? (float) $record->valor_base_m2
                                : 0;
                        } else {
                            $custoEstimado = $state
                                ? ((float) $record->valor_base_m2 * (float) $record->area * (float) $record->fator_correcao)
                                : 0;
                        }

                        $record->update([
                            'custo_estimado' => $custoEstimado,
                        ]);

                        $this->recalcularTotais();
                    }),

                InlineEditColumn::make('valor_base_m2')
                    ->label('Valor Base (R$/m² ou R$)')
                    ->sortable()
                    ->type('text')
                    ->inputMode('decimal')
                    ->step('0.01')
                    ->mask(RawJs::make(<<<'JS'
                        (() => {
                            const digits = String($input ?? '').replace(/\D/g, '');

                            if (digits === '') {
                                return '';
                            }

                            const cents = digits.slice(-2).padStart(2, '0');
                            const integer = digits.slice(0, -2).replace(/^0+(?=\d)/, '');
                            const formattedInteger = (integer === '' ? '0' : integer)
                                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                            return `${formattedInteger},${cents}`;
                        })()
                    JS))
                    ->step('0.01')
                    ->rules(['regex:/^\d+(?:\.\d{3})*(?:,\d{1,2})?$/'])
                    ->extraAttributes(fn ($record) => [
                        'data-was-updated' => $record->valor_base_m2_editado ? 'true' : 'false',
                        'min' => 0,
                    ])
                    ->updateStateUsing(function ($record, $state) {
                        $owner = $this->getOwnerRecord();
                        $valorAnterior = (float) $record->valor_base_m2;
                        $valorBase = max(MoneyInput::parse($state) ?? 0.0, 0.0);

                        if ($record->tipo === 'manual') {
                            $custoEstimado = $record->incluir
                                ? ($valorBase * (float) $owner->fator_correcao)
                                : 0;
                        } else {
                            $custoEstimado = $record->incluir
                                ? ($valorBase * (float) $record->area * (float) $record->fator_correcao)
                                : 0;
                        }

                        $record->update([
                            'valor_base_m2' => $valorBase,
                            'valor_base_m2_editado' => $valorBase !== $valorAnterior,
                            'custo_estimado' => $custoEstimado,
                        ]);

                        $this->recalcularTotais();

                        return $valorBase;
                    })
                    ->formatStateUsing(fn ($state) => 'R$ '.number_format((float) $state, 2, ',', '.')),

                TextColumn::make('area')
                    ->label('Área (m²)')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->tipo === 'manual' ? 'gray' : null)
                    ->getStateUsing(function ($record) {
                        if ($record->tipo === 'manual' || $record->area === null || $record->area === '') {
                            return 'N/A';
                        }

                        return number_format((float) $record->area, 2, ',', '.');
                    }),

                TextColumn::make('custo_estimado')
                    ->label('Custo Estimado (R$)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'R$ '.number_format((float) $state, 2, ',', '.')),

                TextColumn::make('percentual')
                    ->label('%')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.').'%'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                /*
                Action::make('importarEscopos')
                ->label('Importar escopos')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                $owner = $this->getOwnerRecord();

                if (! $owner->as_faixa_area_id) {
                    Notification::make()
                        ->title('Nenhuma faixa identificada.')
                        ->body('Preencha a área da unidade para identificar a faixa antes de importar os escopos.')
                        ->danger()
                        ->send();

                    return;
                }

                $escopos = AsEscopo::query()
                    ->globais()
                    ->where('is_active', true)
                    ->with('faixasArea')
                    ->orderBy('escopo')
                    ->get();

                foreach ($escopos as $escopo) {
                    // se o escopo não tem nenhuma faixa cadastrada, não importa
                    if ($escopo->faixasArea->isEmpty()) {
                        continue;
                    }

                    // tenta achar a faixa da simulação entre as faixas do escopo
                    $faixaDaSimulacao = $escopo->faixasArea
                        ->firstWhere('id', $owner->as_faixa_area_id);

                    // se não encontrar a faixa da simulação, valor vira 0
                    $valorBase = (float) ($faixaDaSimulacao?->pivot?->valor_m2 ?? 0);

                    $itemExistente = $owner->itens()
                        ->where('tipo', 'auto')
                        ->where('as_escopo_id', $escopo->id)
                        ->first();

                    $dados = [
                        'as_escopo_id'   => $escopo->id,
                        'tipo'           => 'auto',
                        'incluir'        => true,
                        'nome_escopo'    => $escopo->escopo,
                        'valor_base_m2'  => $valorBase,
                        'area'           => $owner->area_unidade,
                        'fator_correcao' => $owner->fator_correcao,
                        'custo_estimado' => $valorBase * (float) $owner->area_unidade * (float) $owner->fator_correcao,
                        'percentual'     => 0,
                    ];

                    if ($itemExistente) {
                        $itemExistente->update($dados);
                    } else {
                        $owner->itens()->create($dados);
                    }
                }

                            $this->recalcularTotais();

                            Notification::make()
                                ->title('Escopos importados com sucesso.')
                                ->success()
                                ->send();
                        }),*/
                /*
                Action::make('ordenar_por_custo')
                    ->label('Ordenar por custo')
                    ->icon('heroicon-o-bars-arrow-down')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Ordenar por custo estimado')
                    ->modalDescription('Os itens serão reordenados do maior para o menor custo estimado. Essa ordem será salva e refletida no PDF.')
                    ->modalSubmitActionLabel('Ordenar')
                    ->action(function () {
                        $owner = $this->getOwnerRecord();

                        $owner->itens()
                            ->orderByDesc('custo_estimado')
                            ->get()
                            ->each(function ($item, $index) {
                                $item->update(['ordem' => $index + 1]);
                            });

                        $this->recarregarTabelaItens();

                        Notification::make()
                            ->title('Itens reordenados por custo estimado.')
                            ->success()
                            ->send();
                    }),
                */
                CreateAction::make()
                    ->label('Adicionar escopo manual')
                    ->using(function (array $data) {
                        $owner = $this->getOwnerRecord();
                        $escopo = filled($data['as_escopo_id'] ?? null)
                            ? AsEscopo::query()->find((int) $data['as_escopo_id'])
                            : null;

                        $data['area'] = null;
                        $data['fator_correcao'] = (float) $owner->fator_correcao;
                        $data['nome_escopo'] = filled($data['nome_escopo'] ?? null)
                            ? $data['nome_escopo']
                            : $escopo?->escopo;

                        $data['custo_estimado'] = ! empty($data['incluir'])
                            ? ((float) $data['valor_base_m2'] * (float) $owner->fator_correcao)
                            : 0;

                        $data['percentual'] = 0;

                        return $this->getRelationship()->create($data);
                    })
                    ->after(function () {
                        $this->recalcularTotais();
                    }),
            ])
            ->recordActions([
                Action::make('converter_para_manual')
                    ->label('')
                    ->tooltip('Converter para manual')
                    ->icon('heroicon-o-hand-raised')
                    ->color('gray')
                    ->iconButton()
                    ->visible(fn ($record) => $record->tipo === 'auto')
                    ->modalHeading('Converter para item manual')
                    ->modalDescription('O item deixará de ser calculado pela faixa/área e passará a usar um valor fixo. A área ficará em N/A.')
                    ->modalSubmitActionLabel('Converter')
                    ->form([
                        TextInput::make('valor_base_m2')
                            ->label('Valor Manual (R$)')
                            ->prefix('R$')
                            ->placeholder('Ex: 3.584.650,52')
                            ->helperText('Use vírgula como separador decimal. Ex: 3.584.650,52')
                            ->required(),
                    ])
                    ->fillForm(fn ($record) => [
                        'valor_base_m2' => number_format((float) $record->valor_base_m2, 2, ',', '.'),
                    ])
                    ->action(function ($record, array $data) {
                        $owner = $this->getOwnerRecord();
                        $valorBase = (float) (MoneyInput::parse($data['valor_base_m2'] ?? 0) ?? 0);

                        $record->update([
                            'tipo' => 'manual',
                            'as_escopo_id' => null,
                            'area' => null,
                            'valor_base_m2' => $valorBase,
                            'valor_base_m2_editado' => false,
                            'fator_correcao' => (float) $owner->fator_correcao,
                            'custo_estimado' => $record->incluir
                                ? ($valorBase * (float) $owner->fator_correcao)
                                : 0,
                        ]);

                        $this->recalcularTotais();
                        $this->recarregarTabelaItens();
                        $this->dispatch('capex-itens-recarregados');
                        $this->dispatch('capex-totais-atualizados');

                        Notification::make()
                            ->title("Item \"{$record->nome_escopo}\" convertido para manual.")
                            ->success()
                            ->send();
                    }),

                Action::make('restaurar_valor_original')
                    ->label('')
                    ->tooltip('Restaurar valor original')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->iconButton()
                    ->visible(fn ($record) => $record->tipo === 'auto' && $record->valor_base_m2_editado)
                    ->requiresConfirmation()
                    ->modalHeading('Restaurar valor original')
                    ->modalDescription('Este item voltará a usar o valor original calculado pela faixa da simulação.')
                    ->modalSubmitActionLabel('Restaurar')
                    ->action(function ($record) {
                        $owner = $this->getOwnerRecord();

                        $record->update([
                            'valor_base_m2_editado' => false,
                        ]);

                        $owner->recalcularItensAutomaticosETotais();
                        $this->recarregarTabelaItens();
                        $this->dispatch('capex-itens-recarregados');
                        $this->dispatch('capex-totais-atualizados');

                        Notification::make()
                            ->title("Valor original restaurado no item {$record->nome_escopo}.")
                            ->success()
                            ->send();
                    }),

                Action::make('comentario')
                    ->label('')
                    ->tooltip(fn ($record) => filled(trim((string) $record->comentario))
                        ? 'Editar comentário'
                        : 'Adicionar comentário'
                    )
                    ->icon(fn ($record) => filled(trim((string) $record->comentario))
                        ? 'heroicon-s-chat-bubble-left-ellipsis'
                        : 'heroicon-o-chat-bubble-left-ellipsis'
                    )
                    ->color(fn ($record) => filled(trim((string) $record->comentario)) ? 'info' : 'gray')
                    ->iconButton()
                    ->modalHeading('Comentário')
                    ->modalSubmitActionLabel('Salvar')
                    ->modalCancelActionLabel('Fechar')
                    ->form([
                        Textarea::make('comentario')
                            ->label('Comentário')
                            ->rows(6)
                            ->placeholder('Sem comentário')
                            ->nullable(),
                    ])
                    ->fillForm(fn ($record) => [
                        'comentario' => $record->comentario,
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'comentario' => blank(trim((string) ($data['comentario'] ?? null)))
                                ? null
                                : trim((string) $data['comentario']),
                        ]);
                    })
                    ->successNotificationTitle('Comentário salvo com sucesso'),
                /*
                EditAction::make()
                    ->label('')
                    ->color('gray')
                    ->tooltip('Editar escopo manual')
                    ->visible(fn($record) => $record->tipo === 'manual')
                    ->using(function ($record, array $data) {
                        $owner = $this->getOwnerRecord();

                        $data['area'] = null;
                        $data['fator_correcao'] = (float) $owner->fator_correcao;

                        $data['custo_estimado'] = ! empty($data['incluir'])
                            ? ((float) $data['valor_base_m2'] * (float) $owner->fator_correcao)
                            : 0;

                        $record->update($data);

                        return $record;
                    })
                    ->after(function () {
                        $this->recalcularTotais();
                    }),*/
                /*
                DeleteAction::make()
                    ->after(function () {
                        $this->recalcularTotais();
                    }),*/
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            $this->recalcularTotais();
                        }),
                ]),
            ]);
    }

    protected function recalcularTotais(): void
    {
        $owner = $this->getOwnerRecord();
        $owner->load('itens');

        $total = $owner->itens
            ->where('incluir', true)
            ->sum('custo_estimado');

        foreach ($owner->itens as $item) {
            $percentual = ($total > 0 && $item->incluir)
                ? (($item->custo_estimado / $total) * 100)
                : 0;

            $item->update([
                'percentual' => $percentual,
            ]);
        }

        $owner->update([
            'custo_total_estimado' => $total,
            'custo_por_m2' => ((float) $owner->area_unidade > 0)
                ? ($total / (float) $owner->area_unidade)
                : 0,
        ]);

        $owner->refresh();

        $this->dispatch('capex-totais-atualizados');
    }

    #[On('capex-itens-recarregados')]
    public function recarregarTabelaItens(): void
    {
        $owner = $this->getOwnerRecord();
        $owner->unsetRelation('itens');
        $owner->refresh();
        $this->flushCachedTableRecords();
        $this->resetTable();
    }
}
