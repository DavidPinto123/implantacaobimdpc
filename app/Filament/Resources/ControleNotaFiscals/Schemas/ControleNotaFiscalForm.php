<?php

namespace App\Filament\Resources\ControleNotaFiscals\Schemas;

use App\Enums\TipoUnidade;
use App\Models\ControleNotaFiscal;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ControleNotaFiscalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do controle')
                    ->schema([
                        Select::make('elaboracao_aditivo_id')
                            ->label('Aditivo')
                            ->options(fn (): array => ElaboracaoAditivo::query()
                                ->latest('id')
                                ->get(['id'])
                                ->mapWithKeys(fn (ElaboracaoAditivo $aditivo): array => [
                                    $aditivo->id => 'Aditivo #'.$aditivo->id,
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live(),

                        Select::make('obra_id')
                            ->label('Obra')
                            ->options(fn (): array => Obras::query()
                                ->orderBy('unidade')
                                ->get(['id', 'unidade'])
                                ->mapWithKeys(fn (Obras $obra): array => [
                                    $obra->id => (string) ($obra->unidade ?: 'Obra #'.$obra->id),
                                ])
                                ->all())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if (! filled($state)) {
                                    $set('tipo_unidade', TipoUnidade::EXPANSAO->value);

                                    return;
                                }

                                $obra = Obras::query()->find($state);

                                $set('tipo_unidade', ControleNotaFiscal::resolveTipoUnidade(
                                    $obra,
                                    request()->boolean('retrofit'),
                                ));
                            }),

                        Select::make('tipo_unidade')
                            ->label('Tipo da unidade')
                            ->options(TipoUnidade::options())
                            ->default(TipoUnidade::EXPANSAO->value)
                            ->disabled()
                            ->dehydrated()
                            ->native(false),

                        DatePicker::make('data_base')
                            ->label('Data base')
                            ->native(true),

                        TextInput::make('unidade')
                            ->label('Unidade')
                            ->maxLength(255),

                        TextInput::make('sigla')
                            ->label('Sigla')
                            ->maxLength(255),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'ativo' => 'Ativo',
                                'aguardando_construtora' => 'Aguardando fornecedor',
                                'aguardando_financeiro' => 'Aguardando financeiro',
                                'aprovado' => 'Aprovado',
                                'reprovado' => 'Reprovado',
                                'encerrado' => 'Encerrado',
                            ])
                            ->default('ativo')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }
}
