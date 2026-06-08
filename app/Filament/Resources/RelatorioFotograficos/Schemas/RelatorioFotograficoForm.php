<?php

namespace App\Filament\Resources\RelatorioFotograficos\Schemas;

use App\Models\Projeto;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RelatorioFotograficoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Dados do Relatório')
                    ->schema([

                        TextInput::make('novo_projeto')
                            ->default(false)
                            ->hidden()
                            ->dehydrated(false)
                            ->live(),

                        Select::make('projeto_id')
                            ->label('Unidade')
                            ->relationship('projeto', 'nome')
                            ->searchable()
                            ->preload()
                            ->required(fn (string $operation, $livewire) => $operation !== 'view' && $livewire->statusToSave !== 'Rascunho')
                            ->live()
                            ->columnSpanFull()
                            ->createOptionForm([
                                TextInput::make('nome')
                                    ->label('Nome da Unidade')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $projeto = Projeto::create([
                                    'nome' => $data['nome'],
                                ]);

                                return $projeto->id;
                            })
                            ->createOptionAction(
                                fn ($action) => $action->after(function (Set $set) {
                                    $set('novo_projeto', true);
                                })
                            )
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (! $state) {
                                    $set('sigla', null);
                                    $set('endereco', null);
                                    $set('novo_projeto', false);

                                    return;
                                }

                                $projeto = Projeto::find($state);

                                if ($projeto) {
                                    $set('sigla', $projeto->sigla);
                                    $set('endereco', $projeto->endereco);
                                    $set('novo_projeto', false);
                                }
                            }),

                        TextInput::make('endereco')
                            ->label('Endereço')
                            ->columnSpanFull(),
                        // ->readOnly(fn($get) => ! $get('novo_projeto')),

                        TextInput::make('sigla')
                            ->label('Sigla'),
                        // ->readOnly(fn($get) => ! $get('novo_projeto')),

                        TextInput::make('autor_nome')
                            ->label('Autor do Relatório')
                            ->formatStateUsing(fn ($record) => $record?->autor?->name ?? auth()->user()?->name)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('autor_id')
                            ->default(fn () => auth()->id())
                            ->hidden(),

                        Select::make('status')
                            ->label('Status do relatório')
                            ->options(function ($get, $record) {
                                $options = [
                                    'aprovado_com_pendencia' => 'Aprovado com pendência',
                                    'concluido' => 'Concluído',
                                ];

                                if ($record && $record->status && ! isset($options[$record->status])) {
                                    $options[$record->status] = $record->status;
                                }

                                return $options;
                            })
                            ->native(false)
                            ->required(fn (string $operation, $livewire) => $operation !== 'view' && $livewire->statusToSave !== 'Rascunho'),

                        ToggleButtons::make('tipo_unidade')
                            ->label('Tipo da unidade')
                            ->options([
                                'bts' => 'BTS',
                                'padrao' => 'Padrão',
                            ])
                            ->inline()
                            ->required(fn (string $operation, $livewire) => $operation !== 'view' && $livewire->statusToSave !== 'Rascunho'),

                        DatePicker::make('data_posse')
                            ->label('Data da Posse')
                            ->required(fn (string $operation, $livewire) => $operation !== 'view' && $livewire->statusToSave !== 'Rascunho')
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('agendado_em')
                            ->label('Agendado em')
                            ->displayFormat('d/m/Y'),

                        Select::make('status_termo_de_posse')
                            ->label('Status do Termo de Posse')
                            ->options([
                                'pendente' => 'Pendente',
                                'em_validacao' => 'Em validação',
                                'em_assinatura' => 'Em assinatura',
                                'assinado' => 'Assinado',
                            ])
                            ->native(false)
                            ->default('pendente')
                            ->required(fn (string $operation, $livewire) => $operation !== 'view' && $livewire->statusToSave !== 'Rascunho'),

                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Entregas Contratuais')
                    ->schema([
                        Placeholder::make('sem_entregas_contratuais')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<div class="text-sm text-gray-500">Nenhuma entrega contratual.</div>'
                            ))
                            ->visible(fn ($get) => blank($get('entregas_contratuais')))
                            ->columnSpanFull(),

                        Repeater::make('entregas_contratuais')
                            ->hiddenLabel()
                            ->addActionLabel('+ Adicionar entrega contratual')
                            ->itemLabel(fn ($state) => $state['titulo'] ?? 'Nova entrega')
                            ->schema([
                                TextInput::make('titulo')
                                    ->label('Entrega contratual')
                                    ->required(fn (string $operation, $livewire) => $operation !== 'view' && $livewire->statusToSave !== 'Rascunho')
                                    ->live(),

                                Select::make('status')
                                    ->label('Status da entrega')
                                    ->options([
                                        'entregue' => 'Entregue',
                                        'nao_entregue' => 'Não entregue',
                                    ])
                                    ->required(fn (string $operation, $livewire) => $operation !== 'view' && $livewire->statusToSave !== 'Rascunho')
                                    ->live(),

                                DatePicker::make('data_prevista')
                                    ->label('Data prevista de entrega')
                                    ->visible(fn ($get) => $get('status') === 'nao_entregue')
                                    ->required(
                                        fn (string $operation, $livewire, $get) => $operation !== 'view'
                                            && $livewire->statusToSave !== 'Rascunho'
                                            && $get('status') === 'nao_entregue'
                                    ),

                                FileUpload::make('arquivo')
                                    ->label('Foto / Arquivo da entrega')
                                    ->multiple()
                                    ->directory(fn ($record) => filled($record?->id)
                                        ? "relatorios-rf/{$record->id}/entregas-contratuais"
                                        : 'relatorios-rf/tmp/entregas-contratuais')
                                    ->disk((string) config('filesystems.media_disk', 'r2'))
                                    ->visibility('public')
                                    ->downloadable()
                                    ->openable()
                                    ->fetchFileInformation(false)
                                    ->previewable(true)
                                    ->panelLayout('grid')
                                    // ->preserveFilenames()
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'image/jpg',
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    ])
                                    ->required(
                                        fn (string $operation, $livewire, $get) => $operation !== 'view'
                                            && $livewire->statusToSave !== 'Rascunho'
                                            && $get('status') === 'entregue'
                                    )
                                    ->validationMessages([
                                        'required' => 'Envie o arquivo da entrega.',
                                        'uploaded' => 'Não foi possível enviar o arquivo. Tente novamente.',
                                        'max' => 'Cada arquivo deve ter no máximo 200 MB.',
                                        'mimetypes' => 'Envie apenas JPG, PNG, PDF, DOC ou DOCX.',
                                        'mimes' => 'Envie apenas JPG, PNG, PDF, DOC ou DOCX.',
                                    ])
                                    ->maxSize(204800)
                                    ->maxParallelUploads(1)
                                    ->columnSpanFull(),

                                Textarea::make('comentario')
                                    ->label('Comentário')
                                    ->columnSpanFull(),

                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->reorderable()
                            ->columnSpanFull(),

                    ])
                    ->columnSpanFull(),

                Section::make('Fotos do Relatório')
                    ->schema([
                        FileUpload::make('fotos')
                            ->hiddenLabel()
                            ->multiple()
                            ->image()
                            ->directory(fn ($record) => filled($record?->id)
                                ? "relatorios-rf/{$record->id}/midia"
                                : 'relatorios-rf/tmp/midia')
                            ->reorderable()->disk((string) config('filesystems.media_disk', 'r2'))
                            ->visibility('public')
                            ->imagePreviewHeight('200')
                            ->openable()
                            ->fetchFileInformation(false)
                            ->downloadable()
                            ->panelLayout('grid')
                            ->columnSpanFull(),
                        // ->preserveFilenames(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

            ]);
    }
}
