<?php

namespace App\Filament\Resources\GrupoOis\Schemas;

use App\Models\GrupoOi;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class GrupoOiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cadastro de Grupo OI')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Grupo pai')
                            ->helperText('Deixe vazio para criar um grupo raiz.')
                            ->options(fn (?GrupoOi $record): array => self::opcoesHierarquicas($record))
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $set('nivel', self::calcularNivel($state));
                            }),

                        TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nivel')
                            ->label('Nível')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->readOnly()
                            ->helperText('Calculado automaticamente a partir do grupo pai.')
                            ->dehydrateStateUsing(fn (mixed $state): int => (int) ($state ?: 1)),

                        TextInput::make('ordem')
                            ->label('Ordem')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function opcoesHierarquicas(?GrupoOi $record): array
    {
        $idsExcluidos = $record
            ? self::coletarIdsSubarvore($record)
            : [];

        $grupos = GrupoOi::query()
            ->when($idsExcluidos !== [], fn ($q) => $q->whereNotIn('id', $idsExcluidos))
            ->orderBy('nivel')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get(['id', 'parent_id', 'nome']);

        $porPai = $grupos->groupBy('parent_id');

        $opcoes = [];
        $construir = function (?int $paiId, string $prefixo) use (&$construir, $porPai, &$opcoes): void {
            $chave = $paiId ?? '';
            foreach ($porPai->get($chave, collect()) as $grupo) {
                $opcoes[$grupo->id] = $prefixo === ''
                    ? $grupo->nome
                    : $prefixo.' > '.$grupo->nome;
                $construir($grupo->id, $opcoes[$grupo->id]);
            }
        };

        $construir(null, '');

        return $opcoes;
    }

    private static function coletarIdsSubarvore(GrupoOi $grupo): array
    {
        $ids = [$grupo->id];
        foreach ($grupo->children()->get() as $filho) {
            $ids = array_merge($ids, self::coletarIdsSubarvore($filho));
        }

        return $ids;
    }

    private static function calcularNivel(mixed $parentId): int
    {
        if (! $parentId) {
            return 1;
        }

        $pai = GrupoOi::find($parentId);

        return $pai ? ($pai->nivel + 1) : 1;
    }
}
