<?php

namespace App\Filament\Resources\ImportacaoNotaFiscals\Schemas;

use App\Models\ControleNotaFiscalNota;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImportacaoNotaFiscalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da nota fiscal')
                    ->schema([
                        TextEntry::make('unidade')
                            ->label('Unidade')
                            ->state(fn (ControleNotaFiscalNota $record): string => (string) ($record->itemDerivado()?->controleNotaFiscal?->obra?->unidade
                                ?? $record->auxiliarDerivado()?->controleNotaFiscal?->obra?->unidade
                                ?? '-')),
                        TextEntry::make('construtora')
                            ->label('Fornecedor')
                            ->state(fn (ControleNotaFiscalNota $record): string => (string) ($record->empresa
                                ?? $record->item?->empresa
                                ?? $record->auxiliar?->empresa
                                ?? '-')),
                        TextEntry::make('tipo_nota_fiscal')
                            ->label('Tipo de nota fiscal')
                            ->state(fn (ControleNotaFiscalNota $record): string => $record->isAdicional() ? 'adicional' : 'principal')
                            ->formatStateUsing(fn (string $state): string => $state === 'adicional' ? 'Adicional' : 'Principal')
                            ->badge(),
                        TextEntry::make('destino_controle')
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
                            ])->filter()->implode(' - ')) ?: '-')),
                        TextEntry::make('tipo_medicao')
                            ->label('Tipo de medição')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'material' => 'Material',
                                'mao_obra' => 'Mão de Obra',
                                'transporte' => 'Transporte',
                                default => '-',
                            })
                            ->badge(),
                        TextEntry::make('empresa')
                            ->label('Razão Social do Emissor da Nota'),

                        TextEntry::make('cnpj_fornecedor')
                            ->label('CNPJ do Emissor da Nota'),

                        TextEntry::make('numero_nf')
                            ->label('Número da nota fiscal'),

                        TextEntry::make('cnpj_faturamento')
                            ->label('CNPJ do Destinatário/Remetente'),

                        TextEntry::make('instrucoes_pagamento')
                            ->label('Instruções de pagamento')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'pix' => 'PIX',
                                'transferencia' => 'Transferência',
                                'dados_bancarios' => 'Dados Bancários',
                                'boleto_bancario' => 'Boleto Bancário',
                                default => '-',
                            }),

                        TextEntry::make('data_vencimento_boleto')
                            ->label('Data de vencimento do boleto')
                            ->date('d/m/Y')
                            ->visible(fn (ControleNotaFiscalNota $record): bool => $record->instrucoes_pagamento === 'boleto_bancario'),

                        TextEntry::make('banco')
                            ->label('Banco')
                            ->visible(fn (ControleNotaFiscalNota $record): bool => in_array($record->instrucoes_pagamento, ['transferencia', 'dados_bancarios'], true)),

                        TextEntry::make('agencia')
                            ->label('Agência')
                            ->visible(fn (ControleNotaFiscalNota $record): bool => in_array($record->instrucoes_pagamento, ['transferencia', 'dados_bancarios'], true)),

                        TextEntry::make('conta_corrente')
                            ->label('Conta Corrente')
                            ->visible(fn (ControleNotaFiscalNota $record): bool => in_array($record->instrucoes_pagamento, ['transferencia', 'dados_bancarios'], true)),

                        TextEntry::make('boleto_path')
                            ->label('Boleto bancário')
                            ->state(fn (?string $state): string => filled($state) ? 'Anexado' : 'Não anexado'),

                        TextEntry::make('envio')
                            ->label('Data de envio da nota')
                            ->date('d/m/Y'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => ControleNotaFiscalNota::getStatusLabel($state))
                            ->color(fn (?string $state): string => ControleNotaFiscalNota::getStatusColor($state)),
                        TextEntry::make('arquivo_path')
                            ->label('Anexo')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Arquivo anexado' : 'Sem arquivo enviado'),
                        TextEntry::make('importadoPor.name')
                            ->label('Enviado por')
                            ->placeholder('-'),
                        TextEntry::make('observacoes')
                            ->label('Observações')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
