<?php

namespace App\Filament\Resources\ObraDocumentos\Schemas;

use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Models\ObraDocumento;
use App\Models\Obras;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ObraDocumentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Envio de documentos')
                    ->schema([
                        Select::make('obra_id')
                            ->label('Obra')
                            ->options(fn (): array => ObraDocumentoResource::getAvailableObrasOptions(Auth::user()))
                            ->getOptionLabelUsing(function ($value): ?string {
                                if (! filled($value)) {
                                    return null;
                                }

                                $obra = Obras::query()
                                    ->with(['projeto:id,nome,sigla,nova_sigla'])
                                    ->find($value);

                                return $obra ? ObraDocumentoResource::getObraLabel($obra) : null;
                            })
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->searchable()
                            ->preload(),

                        TextInput::make('nome')
                            ->label('Nome do documento')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),

                        Select::make('status')
                            ->label('Status')
                            ->options(ObraDocumentoResource::getStatusOptions())
                            ->default('pendente')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('usuario_id')
                            ->default(fn () => Auth::id())
                            ->hidden()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Uploads')
                    ->schema([
                        FileUpload::make('arquivos_paths')
                            ->label('Arquivos enviados')
                            ->multiple()
                            ->panelLayout('grid')
                            ->columnSpanFull()
                            ->storeFileNamesIn('arquivos_nomes')
                            ->disk(ObraDocumentoResource::getUploadDisk())
                            ->directory('obra-documentos/arquivos')
                            ->visibility('public')
                            ->disabled(fn (?ObraDocumento $record): bool => ObraDocumentoResource::isSentStatus($record?->status))
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(51200)
                            ->helperText('Envie um ou mais PDFs. Tamanho máximo de 50MB por arquivo.'),
                    ]),
            ]);
    }
}
