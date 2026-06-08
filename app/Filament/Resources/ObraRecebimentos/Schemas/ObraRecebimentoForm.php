<?php

namespace App\Filament\Resources\ObraRecebimentos\Schemas;

use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use App\Models\Construtora;
use App\Models\ObraRecebimento;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ObraRecebimentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Entrega de materiais')
                    ->schema([
                        Select::make('obra_id')
                            ->label('Obra')
                            ->options(fn (): array => ObraRecebimentoResource::getAvailableObrasOptions(Auth::user()))
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->live(),

                        TextInput::make('nome')
                            ->label('Nome do item entregue')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),

                        Select::make('status')
                            ->label('Status')
                            ->options(ObraRecebimentoResource::getStatusOptions())
                            ->default('pendente')
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Placeholder::make('construtora_nome')
                            ->label('Fornecedor')
                            ->content(function (Get $get, ?ObraRecebimento $record): string {
                                $construtoraId = $get('construtora_id')
                                    ?? $record?->construtora_id
                                    ?? ObraRecebimentoResource::resolveConstrutoraIdForObra($get('obra_id'), Auth::user());

                                if (! filled($construtoraId)) {
                                    return 'Não definida';
                                }

                                $construtora = Construtora::query()
                                    ->select(['id', 'nome'])
                                    ->find($construtoraId);

                                return $construtora?->nome ?? 'Não definida';
                            }),

                        TextInput::make('construtora_id')
                            ->default(fn (Get $get) => ObraRecebimentoResource::resolveConstrutoraIdForObra($get('obra_id'), Auth::user()))
                            ->hidden()
                            ->dehydrated(),

                        TextInput::make('usuario_id')
                            ->default(fn () => Auth::id())
                            ->hidden()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Comprovantes')
                    ->schema([
                        FileUpload::make('foto_entrega_paths')
                            ->label('Fotos da entrega')
                            ->multiple()
                            ->panelLayout('grid')
                            ->storeFileNamesIn('foto_entrega_nomes')
                            ->disk(ObraRecebimentoResource::getUploadDisk())
                            ->directory('obra-recebimentos/fotos')
                            ->visibility('public')
                            ->image()
                            ->disabled(fn (?ObraRecebimento $record): bool => ObraRecebimentoResource::isReceivedStatus($record?->status))
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->helperText('Envie uma ou mais fotos da entrega. Formatos: JPG, PNG ou WEBP. Máximo de 10MB por arquivo.'),

                        FileUpload::make('nota_fiscal_paths')
                            ->label('Notas fiscais')
                            ->multiple()
                            ->panelLayout('grid')
                            ->storeFileNamesIn('nota_fiscal_nomes')
                            ->disk(ObraRecebimentoResource::getUploadDisk())
                            ->directory('obra-recebimentos/notas-fiscais')
                            ->visibility('public')
                            ->disabled(fn (?ObraRecebimento $record): bool => ObraRecebimentoResource::isReceivedStatus($record?->status))
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->maxSize(10240)
                            ->helperText('Envie uma ou mais notas fiscais em PDF ou imagem. Máximo de 10MB por arquivo.'),

                    ])
                    ->columns(2),
            ]);
    }
}
