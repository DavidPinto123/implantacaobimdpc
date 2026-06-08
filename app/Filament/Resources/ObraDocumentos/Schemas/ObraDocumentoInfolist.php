<?php

namespace App\Filament\Resources\ObraDocumentos\Schemas;

use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Models\ObraDocumento;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ObraDocumentoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Documento')
                    ->schema([
                        TextEntry::make('obra.unidade')
                            ->label('Obra'),
                        TextEntry::make('nome')
                            ->label('Nome do documento'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (?string $state): string => ObraDocumentoResource::getStatusLabel($state))
                            ->color(fn (?string $state): string => ObraDocumentoResource::getStatusColor($state))
                            ->badge(),
                        TextEntry::make('arquivos_paths_resolved')
                            ->label('Uploads')
                            ->state(fn (ObraDocumento $record): array => $record->arquivos_nomes_resolved)
                            ->listWithLineBreaks(),
                        TextEntry::make('usuario.name')
                            ->label('Criado por'),
                    ])
                    ->columns(2),
            ]);
    }
}
