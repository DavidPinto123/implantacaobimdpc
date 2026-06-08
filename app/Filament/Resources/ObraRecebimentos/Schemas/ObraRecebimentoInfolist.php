<?php

namespace App\Filament\Resources\ObraRecebimentos\Schemas;

use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ObraRecebimentoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recebimento')
                    ->schema([
                        TextEntry::make('obra.unidade')
                            ->label('Obra'),
                        TextEntry::make('nome')
                            ->label('Item entregue'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (?string $state): string => ObraRecebimentoResource::getStatusLabel($state))
                            ->color(fn (?string $state): string => ObraRecebimentoResource::getStatusColor($state))
                            ->badge(),
                        TextEntry::make('fornecedor.nome')
                            ->label('Fornecedor'),
                        TextEntry::make('usuario.name')
                            ->label('Criado por'),
                        IconEntry::make('foto_entrega_paths_resolved')
                            ->label('Foto enviada')
                            ->state(fn ($record): bool => $record->hasFotoEntrega())
                            ->boolean(),
                        IconEntry::make('nota_fiscal_paths_resolved')
                            ->label('NF enviada')
                            ->state(fn ($record): bool => $record->hasNotaFiscal())
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
