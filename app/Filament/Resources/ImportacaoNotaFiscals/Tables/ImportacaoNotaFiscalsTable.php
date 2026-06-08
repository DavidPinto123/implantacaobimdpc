<?php

namespace App\Filament\Resources\ImportacaoNotaFiscals\Tables;

use App\Models\Construtora;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImportacaoNotaFiscalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->view('filament.resources.importacao-notas-fiscais.table-index')
            ->columns([
                TextColumn::make('unidade')
                    ->label('Unidade')
                    ->state(fn (ControleNotaFiscalNota $record): string => (string) ($record->itemDerivado()?->controleNotaFiscal?->obra?->unidade
                        ?? $record->auxiliarDerivado()?->controleNotaFiscal?->obra?->unidade
                        ?? '-')),

                TextColumn::make('tipo_nota_fiscal')
                    ->label('Tipo de nota fiscal')
                    ->state(fn (ControleNotaFiscalNota $record): string => $record->isAdicional() ? 'adicional' : 'principal')
                    ->formatStateUsing(fn (string $state): string => $state === 'adicional' ? 'Adicional' : 'Principal')
                    ->badge(),

                TextColumn::make('destino_controle')
                    ->label('Grupo - AS - Escopo')
                    ->state(fn (ControleNotaFiscalNota $record): string => (string) (trim(collect([
                        $record->isAdicional()
                            ? $record->auxiliarDerivado()?->grupo
                            : ($record->itemDerivado()?->grupo ?? $record->itemDerivado()?->asEscopo?->grupo),
                        $record->isAdicional()
                            ? (filled($record->auxiliarDerivado()?->numero_as) ? 'AS '.$record->auxiliarDerivado()?->numero_as : null)
                            : (filled($record->itemDerivado()?->numero_as) ? 'AS '.$record->itemDerivado()?->numero_as : null),
                        $record->isAdicional()
                            ? $record->auxiliarDerivado()?->escopo
                            : ($record->itemDerivado()?->escopo ?? $record->itemDerivado()?->asEscopo?->escopo),
                    ])->filter()->implode(' - ')) ?: '-'))
                    ->wrap(),

                TextColumn::make('tipo_medicao')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'material' => 'Material',
                        'mao_obra' => 'Mão de Obra',
                        'transporte' => 'Transporte',
                        default => '-',
                    })
                    ->badge(),

                TextColumn::make('numero_nf')
                    ->label('NF')
                    ->searchable(),

                TextColumn::make('empresa')
                    ->label('Razão Social do Emissor da Nota')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('cnpj_fornecedor')
                    ->label('CNPJ do Emissor da Nota')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cnpj_faturamento')
                    ->label('CNPJ do Destinatário')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valor_acumulado_medido_nf')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                            ->money('BRL')
                    ),

                TextColumn::make('instrucoes_pagamento')
                    ->label('Pagamento')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pix' => 'PIX',
                        'transferencia' => 'Transferência',
                        'dados_bancarios' => 'Dados Bancários',
                        'boleto_bancario' => 'Boleto Bancário',
                        default => '-',
                    })
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('emissao')
                    ->label('Emissão')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('envio')
                    ->label('Data de envio da nota')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('importadoPor.name')
                    ->label('Enviado por')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ControleNotaFiscalNota::getStatusLabel($state))
                    ->color(fn (?string $state): string => ControleNotaFiscalNota::getStatusColor($state)),

                TextColumn::make('arquivo_path')
                    ->label('Anexo')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Arquivo enviado' : 'Sem anexo')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'gray'),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('unidade')
                    ->label('Unidade')
                    ->options(fn (): array => Obras::query()
                        ->orderBy('codigo')
                        ->orderBy('unidade')
                        ->get(['id', 'codigo', 'unidade'])
                        ->mapWithKeys(function (Obras $obra): array {
                            $codigo = trim((string) ($obra->codigo ?? ''));
                            $unidade = trim((string) ($obra->unidade ?? ''));
                            $label = trim(($codigo !== '' ? ($codigo.' - ') : '').$unidade);

                            return [
                                $obra->id => ($label !== '' ? $label : ('Obra #'.$obra->id)),
                            ];
                        })
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        $obraId = (int) $data['value'];

                        return $query->where(function (Builder $builder) use ($obraId): void {
                            $builder
                                ->whereHas('autorizacaoServico.controleNotaFiscalItem.controleNotaFiscal', function (Builder $sub) use ($obraId): void {
                                    $sub->where('obra_id', $obraId);
                                })
                                ->orWhereHas('asa.controleNotaFiscalAuxiliar.controleNotaFiscal', function (Builder $sub) use ($obraId): void {
                                    $sub->where('obra_id', $obraId);
                                });
                        });
                    }),

                SelectFilter::make('tipo_medicao')
                    ->label('Tipo')
                    ->options([
                        'mao_obra' => 'Mão de Obra',
                        'material' => 'Material',
                        'transporte' => 'Transporte',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn (): array => ControleNotaFiscalNota::getStatusOptions()),

                SelectFilter::make('construtora')
                    ->label('Fornecedor')
                    ->options(fn (): array => Construtora::query()
                        ->orderBy('nome')
                        ->pluck('nome', 'id')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        $construtora = Construtora::query()->find($data['value']);

                        if (! $construtora) {
                            return $query->whereRaw('1 = 0');
                        }

                        return $query->where(function (Builder $builder) use ($construtora): void {
                            $builder
                                ->where('empresa', $construtora->nome)
                                ->orWhereHas('autorizacaoServico.controleNotaFiscalItem', function (Builder $itemBuilder) use ($construtora): void {
                                    $itemBuilder->where('empresa', $construtora->nome);
                                })
                                ->orWhereHas('asa.controleNotaFiscalAuxiliar', function (Builder $auxiliarBuilder) use ($construtora): void {
                                    $auxiliarBuilder->where('empresa', $construtora->nome);
                                });
                        });
                    }),

                SelectFilter::make('arquivo_path')
                    ->label('Com anexo')
                    ->options([
                        '1' => 'Sim',
                        '0' => 'Não',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === '1' || $value === 1 || $value === true) {
                            return $query->whereNotNull('arquivo_path')->where('arquivo_path', '!=', '');
                        }

                        if ($value === '0' || $value === 0 || $value === false) {
                            return $query->where(function (Builder $builder): void {
                                $builder->whereNull('arquivo_path')->orWhere('arquivo_path', '');
                            });
                        }

                        return $query;
                    }),

                SelectFilter::make('instrucoes_pagamento')
                    ->label('Instruções de pagamento')
                    ->options([
                        'pix' => 'PIX',
                        'transferencia' => 'Transferência',
                        'dados_bancarios' => 'Dados Bancários',
                        'boleto_bancario' => 'Boleto Bancário',
                    ]),

                Filter::make('emissao')
                    ->label('Emissão')
                    ->form([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('emissao', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('emissao', '<=', $date))),

                Filter::make('envio')
                    ->label('Envio da nota')
                    ->form([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('envio', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('envio', '<=', $date))),
            ])
            ->persistFiltersInSession()
            ->recordActions([
                ViewAction::make('visualizar')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Visualizar')
                    ->modalHeading('Detalhes da nota fiscal')
                    ->modalCancelActionLabel('Fechar')
                    ->schema([
                        Section::make('Resumo da nota fiscal')
                            ->schema([
                                TextEntry::make('numero_nf')->label('Número da NF'),
                                TextEntry::make('empresa')->label('Razão Social do Emissor da Nota'),
                                TextEntry::make('cnpj_fornecedor')->label('CNPJ do Emissor da Nota'),
                                TextEntry::make('cnpj_faturamento')->label('CNPJ do Destinatário/Remetente'),
                                TextEntry::make('tipo_medicao')
                                    ->label('Tipo de medição')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'material' => 'Material',
                                        'mao_obra' => 'Mão de Obra',
                                        'transporte' => 'Transporte',
                                        default => '-',
                                    })
                                    ->badge(),
                                TextEntry::make('valor_acumulado_medido_nf')->label('Valor')->money('BRL'),
                                TextEntry::make('instrucoes_pagamento')
                                    ->label('Instruções de pagamento')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'pix' => 'PIX',
                                        'transferencia' => 'Transferência',
                                        'dados_bancarios' => 'Dados Bancários',
                                        'boleto_bancario' => 'Boleto Bancário',
                                        default => '-',
                                    }),
                                TextEntry::make('emissao')->label('Emissão')->date('d/m/Y'),
                                TextEntry::make('envio')->label('Data de envio da nota')->date('d/m/Y'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => ControleNotaFiscalNota::getStatusLabel($state))
                                    ->color(fn (?string $state): string => ControleNotaFiscalNota::getStatusColor($state)),
                                TextEntry::make('importadoPor.name')->label('Enviado por')->placeholder('-'),
                                TextEntry::make('observacoes')->label('Observações')->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('updated_at', 'desc');
    }
}
