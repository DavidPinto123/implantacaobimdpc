<?php

namespace App\Filament\Resources\CapexSimulacaos\Schemas;

use App\Models\AsFaixaArea;
use App\Models\CapexSimulacao;
use App\Models\Estado;
use App\Models\Projeto;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Validation\Rule;
use Livewire\Component as Livewire;
use Illuminate\Support\HtmlString;

class CapexSimulacaoForm
{
    private static function preencherDadosProjeto(?int $projetoId, Set $set, bool $preencherArea = true): void
    {
        if (! $projetoId) {
            return;
        }

        $projeto = Projeto::with('estado')->find($projetoId);

        if (! $projeto) {
            return;
        }

        $set('nome', $projeto->nome);
        $set('sigla', $projeto->sigla);
        $set('uf', $projeto->estado?->uf);
        $set('endereco', $projeto->endereco);

        if ($preencherArea) {
            $set('area_unidade', $projeto->area_academia);
        }
    }

    private static function buscarFaixaPorArea(float $area): ?AsFaixaArea
    {
        if ($area <= 0) {
            return null;
        }

        return AsFaixaArea::query()
            ->where('area_min', '<=', $area)
            ->where(fn ($q) => $q->where('area_max', '>=', $area)->orWhereNull('area_max'))
            ->orderBy('area_min')
            ->first();
    }

    private static function atualizarFaixaArea(float $area, Set $set): void
    {
        $faixa = static::buscarFaixaPorArea($area);
        $set('as_faixa_area_id', $faixa?->id);
        $set('faixa_nome', $faixa?->nome ?? 'FAIXA NÃO IDENTIFICADA');
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aviso: Esta simulação pode ser vinculada a um projeto existente ou preenchida manualmente.')
                    //->description('Você pode vincular a simulação a um projeto existente ou preencher os dados manualmente.')
                    ->schema([
                        Toggle::make('vinculado')
                            ->label('Vinculado a um Projeto')
                            ->live()
                            ->hiddenOn('edit')
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, Get $get) {
                                $component->state(filled($get('projeto_id')));
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    $set('projeto_id', null);
                                }
                            }),

                        Select::make('projeto_id')
                            ->label('Projeto vinculado')
                            ->hidden(fn (Get $get) => ! $get('vinculado'))
                            ->options(fn (?Model $record) => Projeto::query()
                                ->orderBy('nome')
                                ->whereNotIn('id', CapexSimulacao::whereNotNull('projeto_id')
                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->pluck('projeto_id'))
                                ->get()
                                ->mapWithKeys(fn ($projeto) => [
                                    $projeto->id => $projeto->nome ?: 'Projeto #'.$projeto->id,
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecione o projeto...')
                            ->helperText('Ao selecionar um projeto, nome, sigla, endereço, UF e área serão preenchidos automaticamente.')
                            ->live()
                            ->afterStateHydrated(function ($state, Get $get, Set $set) {
                                if (! $state) {
                                    return;
                                }

                                if (blank($get('nome')) && blank($get('sigla')) && blank($get('uf')) && blank($get('endereco'))) {
                                    static::preencherDadosProjeto($state, $set, preencherArea: blank($get('area_unidade')));
                                }
                            })
                            ->afterStateUpdated(function ($state, Get $get, Set $set, Livewire $livewire) {
                                if (! $state) {
                                    // Só limpa os campos se ainda está no modo vinculado (usuário limpou o select manualmente)
                                    if ($get('vinculado')) {
                                        $set('nome', null);
                                        $set('sigla', null);
                                        $set('endereco', null);
                                        $set('uf', null);
                                    }
                                } else {
                                    static::preencherDadosProjeto((int) $state, $set);
                                }

                                $area = blank($get('area_unidade')) ? 0 : (float) $get('area_unidade');

                                if ($area <= 0) {
                                    $set('as_faixa_area_id', null);
                                    $set('faixa_nome', null);
                                } else {
                                    static::atualizarFaixaArea($area, $set);
                                }

                                $livewire->dispatch('capex-recalcular-itens');
                            }),

                            Placeholder::make('modo_preenchimento')
                                ->hiddenLabel()
                                ->content(fn (Get $get): HtmlString => new HtmlString(
                                    $get('vinculado')
                                        ? 'Modo com projeto: os dados principais abaixo são herdados do projeto selecionado.'
                                        : 'Modo manual: preencha livremente nome, sigla, endereço, UF e área da unidade.
                                        <br><br>
                                        <span class="text-danger-600 font-semibold">
                                                OBS:
                                        </span>
                                        <span class="text-danger-600">
                                                Se a simulação não for vinculada a uma unidade ela não vai estar disponível para realizar a associação no controle de AS.
                                        </span>'
                                ))
                                ->columnSpanFull(),
                        Grid::make(5)
                            ->schema([
                                TextInput::make('nome')
                                    ->label('Nome da Simulação')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(3)
                                    ->readOnly(fn (Get $get) => $get('vinculado') && filled($get('projeto_id')))
                                    ->rules(fn (?Model $record, Get $get): array => ($get('vinculado') && filled($get('projeto_id')))
                                        ? []
                                        : [Rule::unique('capex_simulacoes', 'nome')->ignore($record?->id)]
                                    )
                                    ->validationMessages([
                                        'unique' => 'O NOME DA SIMULAÇÃO já está sendo utilizado.',
                                    ]),

                                TextInput::make('sigla')
                                    ->label('Sigla')
                                    ->maxLength(255)
                                    ->readOnly(fn (Get $get) => $get('vinculado') && filled($get('projeto_id'))),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        0 => 'Pendente',
                                        1 => 'Aprovado',
                                        2 => 'Reprovado',
                                    ])
                                    ->required()
                                    ->default(0)
                                    ->native(false),
                            ]),
                        Grid::make(5)
                            ->schema([
                                TextInput::make('endereco')
                                    ->label('Endereço')
                                    ->maxLength(255)
                                    ->columnSpan(4)
                                    ->readOnly(fn (Get $get) => $get('vinculado') && filled($get('projeto_id'))),

                                Select::make('uf')
                                    ->label('UF')
                                    ->options(fn () => Estado::query()
                                        ->whereNotNull('uf')
                                        ->where('uf', '!=', '')
                                        ->orderBy('uf')
                                        ->pluck('uf', 'uf')
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->disabled(fn (Get $get) => $get('vinculado') && filled($get('projeto_id')))
                                    ->dehydrated(true)
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? strtoupper(trim((string) $state)) : null),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('area_unidade')
                                    ->label('Área da Unidade')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->readOnly(fn (Get $get) => $get('vinculado') && filled($get('projeto_id')))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Livewire $livewire) {
                                        $area = blank($state) ? 0 : (float) $state;

                                        if ($area <= 0) {
                                            $set('as_faixa_area_id', null);
                                            $set('faixa_nome', null);
                                        } else {
                                            static::atualizarFaixaArea($area, $set);
                                        }

                                        $livewire->dispatch('capex-recalcular-itens');
                                    }),

                                TextInput::make('faixa_nome')
                                    ->label('Faixa Identificada')
                                    ->readOnly()
                                    ->extraAttributes(fn ($state) => [
                                        'class' => $state === 'FAIXA NÃO IDENTIFICADA'
                                            ? 'text-danger-600 font-bold'
                                            : '',
                                    ]),

                                Hidden::make('as_faixa_area_id'),
                            ]),
                        Textarea::make('comentario')
                            ->label('Comentário')
                            ->nullable()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Custos da Simulação')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('fator_correcao')
                                    ->label('Fator de Correção')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Livewire $livewire) {
                                        $livewire->dispatch('capex-recalcular-itens');
                                    }),

                                TextInput::make('custo_total_estimado')
                                    ->label('Custo Total Estimado')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.'))
                                    ->prefix('R$'),

                                TextInput::make('custo_por_m2')
                                    ->label('Custo por m²')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.'))
                                    ->prefix('R$'),
                            ]),
                    ])->columnSpanFull(),

            ]);
    }
}
