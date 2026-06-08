<?php

namespace App\Filament\Resources\AsEscopos\Schemas;

use App\Models\AsEscopo;
use App\Models\GrupoOi;
use App\Models\Marca;
use App\Models\ObraRecebimento;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class AsEscopoForm
{
    private static function opcoesGrupoOi(): array
    {
        $grupos = GrupoOi::query()
            ->ativos()
            ->orderBy('nivel')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get(['id', 'parent_id', 'nome']);

        $porPai = $grupos->groupBy('parent_id');

        $opcoes = [];
        $construir = function (?int $paiId, string $prefixo) use (&$construir, $porPai, &$opcoes): void {
            $chave = $paiId ?? '';
            foreach ($porPai->get($chave, collect()) as $grupo) {
                $caminho = $prefixo === ''
                    ? $grupo->nome
                    : $prefixo.' > '.$grupo->nome;

                $temFilhos = $porPai->has($grupo->id);

                if (! $temFilhos) {
                    $opcoes[$grupo->id] = $caminho;
                }

                $construir($grupo->id, $caminho);
            }
        };

        $construir(null, '');

        return $opcoes;
    }

    private static function parsePercentual(mixed $state): float
    {
        return round(max(0, min(100, self::parsePercentualRaw($state))), 2);
    }

    private static function parsePercentualRaw(mixed $state): float
    {
        return is_numeric($state)
            ? (float) $state
            : (float) str_replace(',', '.', str_replace(['%', ' '], '', (string) $state));
    }

    private static function formatPercentual(mixed $state): string
    {
        return number_format(self::parsePercentual($state), 2, ',', '');
    }

    private static function percentualMask(): RawJs
    {
        return RawJs::make(<<<'JS'
            (() => {
                let value = String($input ?? '')
                    .replace(/[^\d,.]/g, '')
                    .replace(/\./g, ',')
                    .replace(/,+/g, ',');

                const parts = value.split(',');
                let integer = parts[0] || '';
                const decimal = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;

                integer = integer.replace(/^0+(?=\d)/, '');

                if (integer === '') {
                    integer = decimal === null ? '' : '0';
                }

                if (Number(integer || 0) > 100) {
                    return '100';
                }

                if (integer === '100' && decimal !== null && Number(decimal) > 0) {
                    return '100,00';
                }

                return decimal === null ? integer : `${integer},${decimal}`;
            })()
        JS);
    }

    private static function atualizarPercentuais(Set $set, string $campoAtual, string $campoComplementar, mixed $state): void
    {
        $percentual = self::parsePercentual($state);

        $set($campoAtual, self::formatPercentual($percentual));
        $set($campoComplementar, self::formatPercentual(100 - $percentual));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cadastro de Escopo de A.S.')
                    ->schema([
                        Select::make('grupo')
                            ->label('Grupo (legado)')
                            ->helperText('Mantido por compatibilidade — prefira o "Grupo OI" hierárquico.')
                            ->options([
                                'Civil' => 'Civil',
                                'Ar Condicionado' => 'Ar Condicionado',
                                'Elétrica' => 'Elétrica',
                                'Combate a Incêndio' => 'Combate a Incêndio',
                                'Homologados' => 'Homologados',
                                'Shell' => 'Shell',
                                // Adicional
                                'Projetos' => 'Projetos',
                                'Solicitação Cliente' => 'Solicitação Cliente',
                                'Legalização' => 'Legalização',
                                // Complementar
                                'Orçamentos' => 'Orçamentos',
                            ])
                            ->native(false)
                            ->searchable()
                            ->required(),

                        Select::make('grupo_oi_id')
                            ->label('Grupo OI')
                            ->helperText('Selecione apenas grupos folha (sem sub-grupos) para vincular ao escopo.')
                            ->options(fn (): array => self::opcoesGrupoOi())
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('numero_as')
                            ->label('A.S.')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Esse número de A.S. já está cadastrado.',
                            ]),

                        TextInput::make('escopo')
                            ->label('Escopo')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('percentual_faturamento_mao_obra_default')
                            ->label('% mão de obra padrão')
                            ->inputMode('decimal')
                            ->mask(self::percentualMask())
                            ->rule('numeric')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(60)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                self::atualizarPercentuais(
                                    $set,
                                    'percentual_faturamento_mao_obra_default',
                                    'percentual_faturamento_material_default',
                                    $state,
                                );
                            })
                            ->mutateStateForValidationUsing(fn (mixed $state): float => round(self::parsePercentualRaw($state), 2))
                            ->dehydrateStateUsing(fn (mixed $state): float => round(self::parsePercentualRaw($state), 2))
                            ->required(),

                        TextInput::make('percentual_faturamento_material_default')
                            ->label('% material padrão')
                            ->inputMode('decimal')
                            ->mask(self::percentualMask())
                            ->rule('numeric')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(40)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                self::atualizarPercentuais(
                                    $set,
                                    'percentual_faturamento_material_default',
                                    'percentual_faturamento_mao_obra_default',
                                    $state,
                                );
                            })
                            ->mutateStateForValidationUsing(fn (mixed $state): float => round(self::parsePercentualRaw($state), 2))
                            ->dehydrateStateUsing(fn (mixed $state): float => round(self::parsePercentualRaw($state), 2))
                            ->required(),

                        Toggle::make('item_recebimento_personalizado')
                            ->label('Usar nome personalizado para o item de recebimento')
                            ->helperText('Ative para digitar um nome livre. Caso contrário, escolha um dos itens padrão.')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Toggle $component, ?AsEscopo $record): void {
                                $valorSalvo = trim((string) ($record?->item_recebimento ?? ''));
                                $personalizado = $valorSalvo !== ''
                                    && ! in_array($valorSalvo, ObraRecebimento::ITENS_PADRAO, true);
                                $component->state($personalizado);
                            }),

                        Select::make('item_recebimento')
                            ->label('Item de recebimento')
                            ->helperText('Quando este escopo for contratado em uma obra, um item correspondente é criado no Controle de Recebimentos.')
                            ->options(fn (): array => array_combine(
                                ObraRecebimento::ITENS_PADRAO,
                                ObraRecebimento::ITENS_PADRAO
                            ))
                            ->native(false)
                            ->searchable()
                            ->visible(fn (Get $get): bool => ! (bool) $get('item_recebimento_personalizado'))
                            ->dehydrated(fn (Get $get): bool => ! (bool) $get('item_recebimento_personalizado')),

                        TextInput::make('item_recebimento')
                            ->label('Item de recebimento (personalizado)')
                            ->helperText('Nome livre do item que será criado no Controle de Recebimentos.')
                            ->maxLength(120)
                            ->visible(fn (Get $get): bool => (bool) $get('item_recebimento_personalizado'))
                            ->dehydrated(fn (Get $get): bool => (bool) $get('item_recebimento_personalizado'))
                            ->required(fn (Get $get): bool => (bool) $get('item_recebimento_personalizado')),

                        Select::make('marcas')
                            ->label('Marcas')
                            ->relationship('marcas', 'nome')
                            ->options(fn (): array => Marca::query()->orderBy('nome')->pluck('nome', 'id')->all())
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        Toggle::make('is_personalizado')
                            ->label('Personalizado')
                            ->helperText('Marcado quando o escopo é cadastrado diretamente do controle de medição.')
                            ->default(false),

                        Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
