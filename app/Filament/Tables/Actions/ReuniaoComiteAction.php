<?php

namespace App\Filament\Tables\Actions;

use App\Models\AprovacaoReuniaoComite;
use App\Models\Etapa;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class ReuniaoComiteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reuniao_comite';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn ($record) => $this->jaExisteParaMeuRole($record) ? '' : '')
            ->icon('heroicon-o-megaphone')
            ->tooltip(
                fn ($record) => $this->jaExisteParaMeuRole($record)
                    ? 'Você (ou alguém da sua função) já respondeu. Clique para editar.'
                    : 'Clique para enviar sua aprovação.'
            )
            ->visible(function ($record, $livewire) {
                // Verifica se é a tab correta (quando existir)
                if (property_exists($livewire, 'activeTab')) {
                    if ($livewire->activeTab !== 'Reunião de comitê') {
                        return false;
                    }
                }

                // Verifica se o projeto tem a etapa "Reunião de comitê"
                if (! $record->etapas->contains('nome', 'Reunião de comitê')) {
                    return false;
                }

                // Verifica os papéis do usuário
                $user = Filament::auth()->user();

                return $user->hasRole('PMO')
                    || $user->hasRole('Comercial')
                    || $user->hasRole('Planejamento Estratégico')
                    || $user->hasRole('super_admin');
            })

            ->modalHeading(
                fn ($record) => $this->jaExisteParaMeuRole($record)
                    ? 'Editar aprovação do Comitê'
                    : 'Nova aprovação do Comitê'
            )
            ->slideOver()

            ->fillForm(function ($record) {
                $user = Filament::auth()->user();
                $role = $user->getRoleNames()->first();

                // Defaults + comentários do PROJETO (sempre preencher)
                $defaults = [
                    'aprovacao' => null,
                    'comentarios_gerais' => null,
                    'observacoes_ressalva' => null,
                    'anexos_ressalva' => null,

                    // toggles (padrão false)
                    'pmo_cronograma' => false,
                    'pmo_termo_abertura' => false,
                    'comercial_proposta' => false,
                    'comercial_contrato' => false,
                    'planejamento_plano' => false,
                    'planejamento_estudo' => false,

                    // 🔹 Comentários do PROJETO (não da aprovação)
                    'proj_anexo_proposta_comercial_comentario' => $record->anexo_proposta_comercial_comentario ?? null,
                    'proj_anexo_contrato_assinado_comentario' => $record->anexo_contrato_assinado_comentario ?? null,
                ];

                // Busca aprovação do papel atual (se existir)
                $aprov = AprovacaoReuniaoComite::query()
                    ->where('projeto_id', $record->id)
                    ->where('role', $role)
                    ->first();

                if (! $aprov) {
                    return $defaults;
                }

                return array_merge($defaults, [
                    'aprovacao' => $aprov->aprovacao,
                    'comentarios_gerais' => $aprov->comentarios_gerais,
                    'observacoes_ressalva' => $aprov->observacoes_ressalva,
                    'anexos_ressalva' => $aprov->anexos_ressalva,

                    'pmo_cronograma' => (bool) $aprov->pmo_cronograma,
                    'pmo_termo_abertura' => (bool) $aprov->pmo_termo_abertura,
                    'comercial_proposta' => (bool) $aprov->comercial_proposta,
                    'comercial_contrato' => (bool) $aprov->comercial_contrato,
                    'planejamento_plano' => (bool) $aprov->planejamento_plano,
                    'planejamento_estudo' => (bool) $aprov->planejamento_estudo,
                ]);
            })

            ->form(function () {
                $user = Filament::auth()->user();

                $campos = [
                    /*
                    // ---- Anexos do Projeto (somente leitura) ----
                    Section::make('Anexos do Projeto')
                        ->schema([
                            Forms\Components\FileUpload::make('anexos_preview')
                                ->label('')
                                ->multiple()
                                ->openable()       // botão Abrir
                                ->downloadable()   // botão Baixar
                                ->disabled()       // só leitura
                                ->dehydrated(false)
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->formatStateUsing(fn($record) => (array) ($record->anexos ?? []))
                                ->visibility('public'),
                        ])
                        ->collapsible()
                        ->collapsed(false),
                        */

                    Section::make('Aprovação')
                        ->schema([
                            // ---- Status e comentários ----
                            Radio::make('aprovacao')
                                ->label('Status de Aprovação')
                                ->options([
                                    'aprovado' => 'Aprovado',
                                    'aprovado_com_ressalva' => 'Aprovado com Ressalva',
                                    'reprovado' => 'Reprovado',
                                ])
                                ->reactive()
                                ->required()
                                ->validationMessages([
                                    'required' => 'Campo obrigatório',
                                ])
                                ->disableOptionWhen(function (string $value, Get $get) use ($user) {
                                    if ($value !== 'aprovado') {
                                        return false;
                                    }

                                    if ($user->hasRole('super_admin')) {
                                        return ! ($get('comercial_proposta') && $get('comercial_contrato'));
                                    }

                                    if ($user->hasRole('PMO')) {
                                        return ! ($get('pmo_cronograma') && $get('pmo_termo_abertura'));
                                    }

                                    if ($user->hasRole('Comercial')) {
                                        return ! ($get('comercial_proposta') && $get('comercial_contrato'));
                                    }

                                    if ($user->hasRole('Planejamento Estratégico')) {
                                        return ! ($get('planejamento_plano') && $get('planejamento_estudo'));
                                    }

                                    return true; // se for outro papel, mantenha bloqueado
                                })
                                // dica do que falta para habilitar "Aprovado"
                                ->helperText(function (Get $get) use ($user) {
                                    $faltando = [];

                                    if ($user->hasRole('super_admin')) {
                                        if (! $get('comercial_proposta')) {
                                            $faltando[] = 'Proposta Comercial';
                                        }
                                        if (! $get('comercial_contrato')) {
                                            $faltando[] = 'Contrato Assinado';
                                        }
                                    }

                                    if ($user->hasRole('PMO')) {
                                        if (! $get('pmo_cronograma')) {
                                            $faltando[] = 'Cronograma';
                                        }
                                        if (! $get('pmo_termo_abertura')) {
                                            $faltando[] = 'Termo de Abertura';
                                        }
                                    }

                                    if ($user->hasRole('Comercial')) {
                                        if (! $get('comercial_proposta')) {
                                            $faltando[] = 'Proposta Comercial';
                                        }
                                        if (! $get('comercial_contrato')) {
                                            $faltando[] = 'Contrato Assinado';
                                        }
                                    }

                                    if ($user->hasRole('Planejamento Estratégico')) {
                                        if (! $get('planejamento_plano')) {
                                            $faltando[] = 'Plano Estratégico';
                                        }
                                        if (! $get('planejamento_estudo')) {
                                            $faltando[] = 'Estudo de Mercado';
                                        }
                                    }

                                    return $faltando
                                        ? 'Só é possível aprovar se o checklist estiver tudo marcado'
                                        : null;
                                })
                                ->inline(),

                            RichEditor::make('comentarios_gerais')
                                ->label('Comentários Gerais'),
                        ])
                        ->collapsible()
                        ->collapsed(),

                    Section::make('Ressalva / Motivo da reprovação')
                        ->visible(fn (callable $get) => in_array($get('aprovacao'), ['aprovado_com_ressalva', 'reprovado'], true))
                        ->schema([
                            RichEditor::make('observacoes_ressalva')
                                ->label(fn (callable $get) => $get('aprovacao') === 'reprovado' ? 'Comentário/ Justificativa' : 'Comentário/ Justificativa')
                                ->helperText(fn (callable $get) => $get('aprovacao') === 'reprovado'
                                    ? 'Descreva o motivo da reprovação.'
                                    : 'Detalhe as ressalvas necessárias.')
                                // obrigatório em ambos os casos (ressalva ou reprovação)
                                ->required(fn (callable $get) => in_array($get('aprovacao'), ['aprovado_com_ressalva', 'reprovado'], true))
                                ->validationMessages([
                                    'required' => 'Campo obrigatório',
                                ]),

                            FileUpload::make('anexos_ressalva')
                                ->label('Anexos')
                                ->preserveFilenames()
                                ->downloadable()
                                ->openable()
                                ->multiple(),
                            // anexos só são obrigatórios para "aprovado com ressalva"
                            // ->required(fn(callable $get) => $get('aprovacao') === 'aprovado_com_ressalva'),
                        ])
                        ->collapsible()
                        ->collapsed(),
                ];

                // ---- PMO: toggles deslizantes ----
                if ($user->hasRole('PMO')) {
                    $campos[] = Section::make('Checklist - PMO')
                        ->schema([
                            Toggle::make('pmo_cronograma')
                                ->label('Cronograma')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_pmo_cronograma
                                        ? 'Marque após conferir o Cronograma.'
                                        : 'Anexe o Cronograma no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('pmo_cronograma') && $get('pmo_termo_abertura');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_pmo_cronograma)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Cronograma (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_pmo_cronograma))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_pmo_cronograma')
                                ->label('Cronograma (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_pmo_cronograma))
                                ->formatStateUsing(fn ($record) => $record?->anexo_pmo_cronograma),

                            RichEditor::make('proj_comentario_pmo_cronograma')
                                ->label('Comentário Cronograma')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->comentario_pmo_cronograma ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),

                            Toggle::make('pmo_termo_abertura')
                                ->label('Termo de Abertura')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_pmo_termo_abertura
                                        ? 'Marque após conferir o Termo de Abertura.'
                                        : 'Anexe o Termo de Abertura no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('pmo_cronograma') && $get('pmo_termo_abertura');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_pmo_termo_abertura)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Termo de Abertura (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_pmo_termo_abertura))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_pmo_termo_abertura')
                                ->label('Termo de Abertura (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_pmo_termo_abertura))
                                ->formatStateUsing(fn ($record) => $record?->anexo_pmo_termo_abertura),

                            RichEditor::make('proj_comentario_pmo_termo_abertura')
                                ->label('Comentário Termo de Abertura')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->comentario_pmo_termo_abertura ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->collapsed();
                }

                // ---- Comercial ----
                if ($user->hasAnyRole(['Comercial', 'super_admin'])) {
                    $campos[] = Section::make('Comercial — Conferência e Comentários')
                        ->columns(2)
                        ->schema([
                            // LINHA 1: Toggle (proposta) + Documento (proposta)
                            Toggle::make('comercial_proposta')
                                ->label('Proposta Comercial')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_proposta_comercial
                                        ? 'Marque após conferir a Proposta Comercial.'
                                        : 'Anexe a Proposta no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('comercial_proposta') && $get('comercial_contrato');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_proposta_comercial)),

                            Placeholder::make('no_proposta_msg')
                                ->label('Proposta Comercial (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_proposta_comercial))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_proposta_comercial')
                                ->label('Proposta Comercial (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_proposta_comercial))
                                ->formatStateUsing(fn ($record) => $record?->anexo_proposta_comercial),

                            // LINHA 2: Comentário (proposta) ocupa as 2 colunas
                            RichEditor::make('proj_anexo_proposta_comercial_comentario')
                                ->label('Comentário da Proposta Comercial')
                                ->columnSpan(2)
                                ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'orderedList', 'bulletList', 'blockquote'])
                                // ->helperText('Comente sobre a proposta enviada.')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(function ($state) {
                                    $plain = trim(strip_tags($state ?? ''));

                                    return $plain !== ''
                                        ? $state
                                        // placeholder visual dentro do editor:
                                        : '<p class="italic text-gray-500">Não tem comentário...</p>';
                                })
                                ->maxLength(5000),

                            // LINHA 3: Toggle (contrato) + Documento (contrato)
                            Toggle::make('comercial_contrato')
                                ->label('Contrato Assinado')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_contrato_assinado
                                        ? 'Marque após conferir o Contrato Assinado.'
                                        : 'Anexe o Contrato no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('comercial_proposta') && $get('comercial_contrato');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_contrato_assinado)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Contrato Assinado (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_contrato_assinado))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_contrato_assinado')
                                ->label('Contrato Assinado (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_contrato_assinado))
                                ->formatStateUsing(fn ($record) => $record?->anexo_contrato_assinado),

                            // LINHA 4: Comentário (contrato) ocupa as 2 colunas
                            RichEditor::make('proj_anexo_contrato_assinado_comentario')
                                ->label('Comentário do Contrato Assinado')
                                ->columnSpan(2)
                                ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'orderedList', 'bulletList', 'blockquote'])
                                // ->helperText('Comente sobre o contrato anexado.')
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(function ($state) {
                                    $plain = trim(strip_tags($state ?? ''));

                                    return $plain !== ''
                                        ? $state
                                        // placeholder visual dentro do editor:
                                        : '<p class="italic text-gray-500">Não tem comentário...</p>';
                                })
                                ->maxLength(5000),
                        ])
                        ->collapsible()
                        ->collapsed(false);
                }
                // ---- Planejamento Estratégico ----
                if ($user->hasRole('Planejamento Estratégico')) {
                    $campos[] = Section::make('Checklist - Planejamento Estratégico')
                        ->schema([
                            Toggle::make('planejamento_plano')
                                ->label('Plano Estratégico')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_planejamento_plano
                                        ? 'Marque após conferir o Plano Estratégico.'
                                        : 'Anexe o Plano Estratégico no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('planejamento_plano') && $get('planejamento_estudo');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_planejamento_plano)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Plano Estratégico (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_planejamento_plano))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_planejamento_plano')
                                ->label('Plano Estratégico (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_planejamento_plano))
                                ->formatStateUsing(fn ($record) => $record?->anexo_planejamento_plano),

                            RichEditor::make('proj_planejamento_plano_comentario')
                                ->label('Comentário do Plano Estratégico')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->planejamento_plano_comentario ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),

                            Toggle::make('planejamento_estudo')
                                ->label('Estudo de Mercado')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_planejamento_estudo
                                        ? 'Marque após conferir o Estudo de Mercado.'
                                        : 'Anexe o Estudo de Mercado no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('planejamento_plano') && $get('planejamento_estudo');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_planejamento_estudo)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Estudo de Mercado (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_planejamento_estudo))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_planejamento_estudo')
                                ->label('Estudo de Mercado (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_planejamento_estudo))
                                ->formatStateUsing(fn ($record) => $record?->anexo_planejamento_estudo),

                            RichEditor::make('proj_planejamento_estudo_comentario')
                                ->label('Comentário do Estudo de Mercado')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->planejamento_estudo_comentario ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->collapsed();
                }

                return $campos;
            })

            ->action(function (array $data, $record) {
                $user = Filament::auth()->user();
                $role = $user->getRoleNames()->first();
                $status = $data['aprovacao'] ?? null;

                if (! $status) {
                    Notification::make()
                        ->title('Selecione o status de aprovação.')
                        ->warning()
                        ->send();

                    return;
                }

                // === FAST-TRACK para super_admin ===
                if ($user->hasRole('super_admin')) {
                    if ($status === 'aprovado' || $status === 'aprovado_com_ressalva') {
                        // Aprovação do super_admin
                        AprovacaoReuniaoComite::updateOrCreate(
                            ['projeto_id' => $record->id, 'role' => 'super_admin'],
                            [
                                'user_id' => $user->id,
                                'aprovacao' => $status,
                                'comentarios_gerais' => $data['comentarios_gerais'] ?? null,
                            ]
                        );

                        // Avança etapa
                        $viabilidadeId = Etapa::where('nome', 'Viabilidade')->value('id');
                        $comiteId = Etapa::where('nome', 'Reunião de comitê')->value('id');

                        if (! $viabilidadeId) {
                            Notification::make()
                                ->title('Etapa "Viabilidade" não encontrada')
                                ->danger()
                                ->send();

                            return;
                        }

                        \DB::transaction(function () use ($record, $viabilidadeId, $comiteId, $status) {
                            if ($status === 'aprovado' && $comiteId) {
                                // remove Reunião de comitê apenas se aprovado normal
                                $record->etapas()->detach($comiteId);
                            }
                            $record->etapas()->syncWithoutDetaching([$viabilidadeId]);
                        });

                        $msg = $status === 'aprovado'
                            ? 'Diretoria aprovou: etapa avançada para Viabilidade'
                            : 'Diretoria aprovou com ressalva: Viabilidade adicionada, Reunião de comitê mantida';

                        Notification::make()
                            ->title($msg)
                            ->success()
                            ->send();

                        return; // encerra fast-track
                    }
                }

                // === Validações por status ===
                if ($status === 'aprovado') {
                    // se tiver "aprovar como", use: $roleParaValidar = $user->hasRole('super_admin') ? ($data['acting_role'] ?? 'Comercial') : $role;
                    $roleParaValidar = $user->hasRole('super_admin') ? 'Comercial' : $role;

                    $msgs = [];

                    if ($roleParaValidar === 'PMO') {
                        if (! ($data['pmo_cronograma'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Cronograma".';
                        }
                        if (! ($data['pmo_termo_abertura'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Termo de Abertura".';
                        }
                    }

                    if ($roleParaValidar === 'Comercial') {
                        if (empty($record->anexo_proposta_comercial)) {
                            $msgs[] = 'Anexe a Proposta Comercial no projeto.';
                        }
                        if (empty($record->anexo_contrato_assinado)) {
                            $msgs[] = 'Anexe o Contrato Assinado no projeto.';
                        }
                        if (! ($data['comercial_proposta'] ?? false)) {
                            $msgs[] = 'Marque a opção "Proposta Comercial".';
                        }
                        if (! ($data['comercial_contrato'] ?? false)) {
                            $msgs[] = 'Marque a opção "Contrato Assinado".';
                        }
                    }

                    if ($roleParaValidar === 'Planejamento Estratégico') {
                        if (! ($data['planejamento_plano'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Plano Estratégico".';
                        }
                        if (! ($data['planejamento_estudo'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Estudo de Mercado".';
                        }
                    }

                    if ($msgs) {
                        Notification::make()
                            ->title('Não é possível aprovar')
                            ->body(implode('<br>', $msgs))
                            ->danger()
                            ->send();

                        return; // interrompe a action sem salvar
                    }
                }

                if (in_array($status, ['aprovado_com_ressalva', 'reprovado'], true)) {
                    $texto = trim(strip_tags($data['observacoes_ressalva'] ?? ''));
                    if ($texto === '') {
                        throw new \Exception(
                            $status === 'reprovado'
                                ? 'Informe o motivo da reprovação.'
                                : 'Descreva as observações da aprovação com ressalva.'
                        );
                    }
                }

                // (Opcional) Torne anexos obrigatórios quando for "aprovado com ressalva".
                // Comente este bloco se quiser que seja opcional.
                /*
                if ($status === 'aprovado_com_ressalva' && empty($data['anexos_ressalva'])) {
                    throw new \Exception('Anexe os arquivos da ressalva.');
                }
                */

                // === Persiste ===
                AprovacaoReuniaoComite::updateOrCreate(
                    [
                        'projeto_id' => $record->id,
                        'role' => $role,
                    ],
                    [
                        'user_id' => $user->id,
                        'aprovacao' => $status,
                        'comentarios_gerais' => $data['comentarios_gerais'] ?? null,
                        'observacoes_ressalva' => $data['observacoes_ressalva'] ?? null,
                        'anexos_ressalva' => $data['anexos_ressalva'] ?? null,

                        // toggles (booleans)
                        'pmo_cronograma' => (bool) ($data['pmo_cronograma'] ?? false),
                        'pmo_termo_abertura' => (bool) ($data['pmo_termo_abertura'] ?? false),

                        'comercial_proposta' => (bool) ($data['comercial_proposta'] ?? false),
                        'comercial_contrato' => (bool) ($data['comercial_contrato'] ?? false),

                        'planejamento_plano' => (bool) ($data['planejamento_plano'] ?? false),
                        'planejamento_estudo' => (bool) ($data['planejamento_estudo'] ?? false),
                    ]
                );

                // === Verifica aprovações faltantes e ressalvas ===
                $aprovacoes = AprovacaoReuniaoComite::where('projeto_id', $record->id)
                    ->get()
                    ->keyBy('role');

                $rolesObrigatorias = ['PMO', 'Comercial', 'Planejamento Estratégico'];
                $faltando = [];
                $comRessalva = false;

                foreach ($rolesObrigatorias as $r) {
                    if (! isset($aprovacoes[$r]) || $aprovacoes[$r]->aprovacao === 'reprovado') {
                        $faltando[] = $r;
                    } elseif ($aprovacoes[$r]->aprovacao === 'aprovado_com_ressalva') {
                        $comRessalva = true;
                    }
                }

                // === Avança etapa se tudo ok ===
                if (empty($faltando)) {
                    $viabilidadeId = Etapa::where('nome', 'Viabilidade')->value('id');
                    $comiteId = Etapa::where('nome', 'Reunião de comitê')->value('id');

                    if (! $viabilidadeId) {
                        Notification::make()
                            ->title('Etapa "Viabilidade" não encontrada')
                            ->danger()
                            ->send();

                        return;
                    }

                    \DB::transaction(function () use ($record, $viabilidadeId, $comiteId, $comRessalva) {
                        if (! $comRessalva && $comiteId) {
                            // remove Reunião de comitê apenas se não houver ressalva
                            $record->etapas()->detach($comiteId);
                        }
                        // adiciona Viabilidade em qualquer caso
                        $record->etapas()->syncWithoutDetaching([$viabilidadeId]);
                    });

                    $mensagem = 'Aprovação salva e etapa avançada para Viabilidade';
                    if ($comRessalva) {
                        $mensagem .= ' (com ressalva, Reunião de comitê mantida)';
                    }

                    Notification::make()
                        ->title('Aprovação processada')
                        ->success()
                        ->body($mensagem)
                        ->send();
                } else {
                    Notification::make()
                        ->title('Aprovação salva')
                        ->warning()
                        ->body('Ainda faltam aprovações de: '.implode(', ', $faltando))
                        ->send();
                }
            });
    }

    protected function jaExisteParaMeuRole($record): bool
    {
        $user = Filament::auth()->user();
        $role = $user->getRoleNames()->first();

        return AprovacaoReuniaoComite::query()
            ->where('projeto_id', $record->id)
            ->where('role', $role) // verifica por função
            ->exists();
    }
}
