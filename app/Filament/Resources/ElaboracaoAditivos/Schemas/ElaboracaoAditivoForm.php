<?php

namespace App\Filament\Resources\ElaboracaoAditivos\Schemas;

use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use App\Models\Obras;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ElaboracaoAditivoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fluxo de aprovacoes')
                    ->description('Acompanhe o status e justificativas do processo.')
                    ->schema([
                        Placeholder::make('status_fluxo_info')
                            ->label('Status do fluxo')
                            ->content(fn ($record) => match ($record?->status_fluxo) {
                                'elaboracao' => 'Elaboracao',
                                'em_aprovacao_gestor' => 'Em aprovacao do gestor',
                                'em_aprovacao_orcamento' => 'Em aprovacao do orcamentista',
                                'aprovado' => 'Aprovado pelo orcamentista',
                                'reprovado_gestor' => 'Reprovado pelo gestor',
                                'reprovado_orcamento' => 'Reprovado pelo orcamentista',
                                default => 'Elaboracao',
                            }),

                        Textarea::make('justificativa_reprovacao_gestor')
                            ->label('Justificativa da reprovacao (gestor)')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => filled($record?->justificativa_reprovacao_gestor)),

                        Textarea::make('justificativa_reprovacao_orcamento')
                            ->label('Justificativa da reprovacao (orcamentista)')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => filled($record?->justificativa_reprovacao_orcamento)),
                    ])
                    ->columnSpanFull(),

                Section::make('Cabecalho')
                    ->schema([
                        Select::make('obra_id')
                            ->label('Obra')
                            ->options(
                                Obras::query()
                                    ->orderBy('unidade')
                                    ->pluck('unidade', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): null => $set('as_escopo_id', null)),

                        Select::make('gestor_id')
                            ->label('Gestor')
                            ->options(
                                User::query()
                                    ->whereHas('roles', fn ($query) => $query->where('name', 'Gestor'))
                                    ->whereHas('setores', fn ($query) => $query->where('setor', 'Obras'))
                                    ->orderBy('name')
                                    ->get(['id', 'name'])
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('data')
                            ->label('Data')
                            ->default(now())
                            ->required(),

                        TextInput::make('ref_servico')
                            ->label('Ref. servico'),

                        Select::make('as_escopo_id')
                            ->label('Escopo do aditivo')
                            ->options(fn (Get $get): array => ElaboracaoAditivoResource::opcoesRefServicoPorObra((int) $get('obra_id')))
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Get $get): bool => blank($get('obra_id')))
                            ->rules(fn (Get $get): array => [
                                Rule::in(array_keys(ElaboracaoAditivoResource::opcoesRefServicoPorObra((int) $get('obra_id')))),
                            ])
                            ->required(),

                        TextInput::make('construtora_nome')
                            ->label('Fornecedora')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->construtora?->nome ?? Auth::user()?->construtora?->nome ?? '-'),

                        TextInput::make('construtora_id')
                            ->default(fn () => Auth::user()?->construtoras_id)
                            ->hidden()
                            ->dehydrated(),

                        TextInput::make('user_id')
                            ->default(fn () => Auth::id())
                            ->hidden()
                            ->dehydrated(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Justificativa e anexos')
                    ->schema([
                        Textarea::make('justificativa')
                            ->label('Justificativa do aditivo')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('anexos')
                            ->label('Anexos')
                            ->multiple()
                            ->directory('elaboracao-aditivos/anexos')->disk((string) config('filesystems.media_disk', 'r2'))
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Itens')
                    ->schema([
                        Repeater::make('itens')
                            ->relationship()
                            ->collapsible()
                            ->hiddenLabel()
                            ->addActionLabel('+ Adicionar item de servico')
                            ->itemLabel(fn ($state) => ($state['item'] ?? '1.1').' - '.($state['descricao_servico'] ?? 'Novo item'))
                            ->schema([
                                TextInput::make('item')
                                    ->label('Item')
                                    ->default(function ($get) {
                                        $itens = $get('../../itens') ?? [];

                                        return '1.'.max(count($itens), 1);
                                    })
                                    ->readOnly()
                                    ->dehydrated()
                                    ->columnSpan(1),

                                TextInput::make('descricao_servico')
                                    ->label('Descricao do servico')
                                    ->required()
                                    ->columnSpan(3)
                                    ->live(),

                                TextInput::make('quantidade')
                                    ->label('QT.')
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $quantidade = (float) ($state ?? 0);
                                        $mat = (float) ($get('valor_material_unitario') ?? 0);
                                        $mo = (float) ($get('valor_mao_obra_unitario') ?? 0);

                                        $totalUnit = $mat + $mo;
                                        $set('total_unitario', round($totalUnit, 2));
                                        $set('valor_total_geral', round($quantidade * $totalUnit, 2));
                                    })
                                    ->columnSpan(1),

                                TextInput::make('unidade')
                                    ->label('UND.')
                                    ->columnSpan(1),

                                TextInput::make('valor_material_unitario')
                                    ->label('R$ MAT. (UNIT.)')
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $quantidade = (float) ($get('quantidade') ?? 0);
                                        $mat = (float) ($state ?? 0);
                                        $mo = (float) ($get('valor_mao_obra_unitario') ?? 0);

                                        $totalUnit = $mat + $mo;
                                        $set('total_unitario', round($totalUnit, 2));
                                        $set('valor_total_geral', round($quantidade * $totalUnit, 2));
                                    })
                                    ->columnSpan(2),

                                TextInput::make('valor_mao_obra_unitario')
                                    ->label('R$ M.O. (UNIT.)')
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $quantidade = (float) ($get('quantidade') ?? 0);
                                        $mat = (float) ($get('valor_material_unitario') ?? 0);
                                        $mo = (float) ($state ?? 0);

                                        $totalUnit = $mat + $mo;
                                        $set('total_unitario', round($totalUnit, 2));
                                        $set('valor_total_geral', round($quantidade * $totalUnit, 2));
                                    })
                                    ->columnSpan(2),

                                TextInput::make('total_unitario')
                                    ->label('TOTAL UNIT (MAT.+ M.O.)')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0)
                                    ->columnSpan(2),

                                TextInput::make('valor_total_geral')
                                    ->label('R$ TOTAL GERAL')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0)
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->addActionLabel('Adicionar item'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
