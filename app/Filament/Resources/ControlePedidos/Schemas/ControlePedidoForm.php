<?php

namespace App\Filament\Resources\ControlePedidos\Schemas;

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Forms\Components\CnpjInput;
use App\Models\Projeto;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ControlePedidoForm
{
    public static function configure(Schema $schema): Schema
    {
        $retrofitMode = request()->boolean('retrofit');

        return $schema
            ->columns(12)
            ->components([

                Section::make('Projeto')
                    ->description($retrofitMode
                        ? 'Mostrando apenas projetos vinculados a obras com sigla contendo _RET.'
                        : null)
                    ->schema([
                        Select::make('projeto_id')
                            ->options(function () use ($retrofitMode): array {
                                $query = Projeto::query()->orderBy('nome');

                                if ($retrofitMode) {
                                    $query->whereHas('obras', function ($obraQuery): void {
                                        $obraQuery->where('sigla', 'like', '%\_RET');
                                    });
                                }

                                return $query->pluck('nome', 'id')->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload()
                            ->afterStateUpdated(
                                fn ($state, callable $set) => self::preencherDadosProjeto($state, $set)
                            )
                            ->afterStateHydrated(
                                fn ($state, callable $set) => self::preencherDadosProjeto($state, $set)
                            ),
                    ])
                    ->columnSpan(12)
                    ->collapsible(),

                /*
                |--------------------------------------------------------------------------
                | DADOS DO PROJETO
                |--------------------------------------------------------------------------
                */

                Section::make('Dados do Projeto')
                    ->schema([
                        TextInput::make('sigla_view')
                            ->label('Nova Sigla')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('endereco_view')
                            ->label('Endereço')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('cidade_view')
                            ->label('Cidade')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('uf_view')
                            ->label('UF')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('bandeira_view')
                            ->label('Bandeira')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('pipe_view')
                            ->label('PIPE')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('area_view')
                            ->label('Área')
                            ->disabled()
                            ->dehydrated(false),
                        DatePicker::make('io_view')
                            ->label('I.O.')
                            ->disabled()
                            ->dehydrated(false),
                        DatePicker::make('eo_view')
                            ->label('E.O.')
                            ->disabled()
                            ->dehydrated(false),
                        DatePicker::make('inauguracao_view')
                            ->label('Inauguração')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('dias_eo_view')
                            ->label('DIAS (E.O.)')
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix(fn ($state) => ' dias'),
                    ])
                    ->columns(3)
                    ->columnSpan(12)
                    ->collapsible()
                    ->collapsed(),

                /*
                |--------------------------------------------------------------------------
                | DADOS DO CONTROLE
                |--------------------------------------------------------------------------
                */

                Section::make('Dados do Controle')
                    ->schema([
                        DatePicker::make('elaboracao_contrato')
                            ->label('Elaboração de Contrato'),
                        CnpjInput::make('cnpj'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'definitivo' => 'CNPJ DEFINITIVO',
                                'provisorio' => 'CNPJ PROVISÓRIO',
                            ])
                            ->required()
                            ->native(false),
                        DatePicker::make('contratacao')
                            ->label('Contratação'),
                        Textarea::make('observacoes')
                            ->label('Observações')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpan(6)
                    ->collapsible(),

                /*
                |--------------------------------------------------------------------------
                | FINANCEIRO
                |--------------------------------------------------------------------------
                */

                Section::make('Financeiro')
                    ->schema([

                        Grid::make(2)->schema([

                            TextInput::make('valor_oi')
                                ->label('Valor OI')
                                ->prefix('R$')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $valorRealizado = (float) $get('valor_realizado');
                                    $set('saldo', (float) $state - $valorRealizado);
                                }),

                            TextInput::make('valor_realizado')
                                ->label('Valor Realizado')
                                ->prefix('R$')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $valorOi = (float) $get('valor_oi');
                                    $set('saldo', $valorOi - (float) $state);
                                }),

                            TextInput::make('realizado_nf')
                                ->label('Realizado NF')
                                ->prefix('R$')
                                ->numeric(),

                            TextInput::make('saldo')
                                ->label('Saldo')
                                ->prefix('R$')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(true),

                        ]),

                    ])
                    ->columnSpan(6)
                    ->collapsible(),

                /*
                |--------------------------------------------------------------------------
                | RESPONSÁVEIS
                |--------------------------------------------------------------------------
                */

                Section::make('Responsáveis')
                    ->schema([

                        Select::make('situacao')
                            ->label('Situação')
                            ->options([
                                'inaugurada' => 'Inaugurada',
                                'em_processo' => 'Em Processo',
                                'em_obras' => 'Em Obras',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('responsavel_orc')
                            ->label('Responsável Orçamento')
                            ->options(
                                User::role('colaborador_orcamento')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload(),

                        Select::make('gestor_obra')
                            ->label('Gestor de Obra')
                            ->options(
                                User::role('engenharia')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload(),

                        Select::make('construtora_id')
                            ->label('Fornecedor')
                            ->relationship('construtora', 'nome')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('tamanho')
                            ->label('Tamanho')
                            ->options([
                                '0' => '0',
                                'G' => 'G',
                            ])
                            ->native(false),

                        Select::make('numero')
                            ->label('Número')
                            ->options([
                                1 => '1',
                            ])
                            ->native(false),

                    ])
                    ->columns(2)
                    ->columnSpan(12)
                    ->collapsible(),

                /*
                |--------------------------------------------------------------------------
                | PEDIDOS ORGANIZADOS
                |--------------------------------------------------------------------------
                */

                Section::make('Pedidos Contratados')
                    ->schema([
                        Grid::make(3)
                            ->schema(
                                collect(ControlePedidoResource::pedidosMap())
                                    ->map(function ($items, $groupName) {

                                        $codigo = $items[0];

                                        return Section::make($groupName.' - '.$codigo)
                                            ->schema([
                                                Grid::make(1)
                                                    ->schema([
                                                        Toggle::make('pedidos.'.str_replace('.', '_', $codigo))
                                                            ->label('')
                                                            ->hiddenLabel()
                                                            ->inline(false)
                                                            ->onColor('success')
                                                            ->offColor('danger'),
                                                    ])
                                                    ->extraAttributes([
                                                        'class' => 'flex justify-center py-6',
                                                    ]),
                                            ])
                                            ->collapsible();
                                    })->toArray()
                            ),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNÇÃO CENTRAL PARA PREENCHER DADOS DO PROJETO
    |--------------------------------------------------------------------------
    */

    protected static function preencherDadosProjeto($state, callable $set): void
    {
        if (! $state) {
            return;
        }

        $projeto = Projeto::with(['cidade', 'estado'])->find($state);

        if (! $projeto) {
            return;
        }

        $set('sigla_view', $projeto->nova_sigla);
        // $set('endereco_view', "{$projeto->rua}, {$projeto->bairro} - {$projeto->cep}");
        $set('endereco_view', $projeto->endereco);
        $set('cidade_view', $projeto->cidade?->nome);
        $set('uf_view', $projeto->estado?->nome);
        $set('bandeira_view', $projeto->marca);
        $set('pipe_view', $projeto->pipeline);
        $set('area_view', $projeto->area_academia);

        $set('io_view', $projeto->inicio_obra);
        $set('eo_view', $projeto->entrega_obra);
        $set('inauguracao_view', $projeto->inauguracao);

        if ($projeto->entrega_obra) {

            $dias = Carbon::parse($projeto->entrega_obra)
                ->startOfDay()
                ->diffInDays(now()->startOfDay());

            $set('dias_eo_view', (int) $dias);
        }
    }
}
