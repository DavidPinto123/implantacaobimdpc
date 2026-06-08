<?php

namespace App\Filament\Resources\ControleNotaFiscals\Tables;

use App\Enums\TipoUnidade;
use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ControleNotaFiscalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('obra.unidade')
                    ->label('Unidade')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('tipo_unidade_label')
                    ->label('Tipo')
                    ->badge()
                    ->colors([
                        'info' => TipoUnidade::RETROFIT->value,
                        'warning' => TipoUnidade::EXPANSAO->value,
                    ])
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'ativo' => 'Ativo',
                        'aguardando_construtora' => 'Aguardando fornecedor',
                        'aguardando_financeiro' => 'Aguardando financeiro',
                        'aprovado' => 'Aprovado',
                        'reprovado' => 'Reprovado',
                        'encerrado' => 'Encerrado',
                        default => $state ?: '-',
                    })
                    ->colors([
                        'info' => 'ativo',
                        'warning' => ['aguardando_construtora', 'aguardando_financeiro'],
                        'success' => 'aprovado',
                        'danger' => 'reprovado',
                        'gray' => 'encerrado',
                    ])
                    ->sortable(),

                TextColumn::make('data_base')
                    ->label('Data base')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('itens_count')
                    ->label('Itens')
                    ->counts('itens')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Abrir')
                    ->url(fn ($record): string => EditControleNotaFiscal::getUrl(['record' => $record])),
            ]);
    }
}
