<?php

namespace App\Filament\Tables\Actions;

use App\Models\AprovacaoReuniaoComite;
use App\Models\AprovacaoViabilidade;
use App\Models\Etapa;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ViabilidadeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'viabilidade';
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
                    if ($livewire->activeTab !== 'Viabilidade') {
                        return false;
                    }
                }

                // Verifica se o projeto tem a etapa "Reunião de comitê"
                if (! $record->etapas->contains('nome', 'Viabilidade')) {
                    return false;
                }

                // Verifica os papéis do usuário
                $user = Filament::auth()->user();

                return $user->hasRole('Inteligência Global')
                    || $user->hasRole('super_admin');
            })
            ->modalHeading(
                fn ($record) => $this->jaExisteParaMeuRole($record)
                    ? 'Editar aprovação da viabilidade'
                    : 'Nova aprovação da viabilidade'
            )
            ->slideOver()

            ->fillForm(function ($record) {
                $user = Filament::auth()->user();
                $role = $user->getRoleNames()->first();

                // Defaults + comentários do PROJETO (sempre preencher)
                $defaults = [

                    'aprovacao' => null,
                    'comentarios_gerais' => null,
                    'anexo_consulta_previa' => null,
                    'observacoes_ressalva' => null,

                    // toggles (padrão false)
                    'consulta_previa' => false,
                    'estudoviabilidade' => false,
                    'visita_tecnica' => false,
                    'projetos_adicionais' => false,

                    // 🔹 Comentários do PROJETO (não da aprovação)
                    'anexo_consulta_previa_comentario' => $record->anexo_consulta_previa_comentario ?? null,
                    'anexo_visita_tecnica_comentario' => $record->anexo_visita_tecnica_comentario ?? null,
                    'anexo_projetos_adicionais_comentario' => $record->anexo_projetos_adicionais_comentario ?? null,
                    'anexo_estudoviabilidade_comentario' => $record->anexo_estudoviabilidade_comentario ?? null,
                ];

                // Busca aprovação do papel atual (se existir)
                $aprov = AprovacaoViabilidade::query()
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

                    'consulta_previa' => (bool) $aprov->consulta_previa,
                    'estudoviabilidade' => (bool) $aprov->estudoviabilidade,
                    'visita_tecnica' => (bool) $aprov->visita_tecnica,
                    'projetos_adicionais' => (bool) $aprov->projetos_adicionais,
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
                                    'reprovado' => 'Reprovado',
                                ])
                                ->reactive()
                                ->required()
                                ->disableOptionWhen(function (string $value, Get $get) use ($user) {
                                    if ($value !== 'aprovado') {
                                        return false;
                                    }

                                    if ($user->hasRole('super_admin')) {
                                        return ! ($get('consulta_previa') && $get('estudoviabilidade') && $get('visita_tecnica') && $get('projetos_adicionais'));
                                    }

                                    if ($user->hasRole('Inteligência Global')) {
                                        return ! ($get('consulta_previa') && $get('estudoviabilidade') && $get('visita_tecnica') && $get('projetos_adicionais'));
                                    }

                                    return true; // se for outro papel, mantenha bloqueado
                                })
                                // // dica do que falta para habilitar "Aprovado"
                                ->helperText(function (Get $get) use ($user) {
                                    $faltando = [];

                                    if ($user->hasRole('super_admin')) {
                                        if (! $get('consulta_previa')) {
                                            $faltando[] = 'Consulta Prévia';
                                        }
                                        if (! $get('estudoviabilidade')) {
                                            $faltando[] = 'Estudo de Viabilidade';
                                        }
                                        if (! $get('visita_tecnica')) {
                                            $faltando[] = 'Visita Técnica';
                                        }
                                        if (! $get('projetos_adicionais')) {
                                            $faltando[] = 'Projetos Adicionais';
                                        }
                                    }

                                    if ($user->hasRole('Inteligência Global')) {
                                        if (! $get('consulta_previa')) {
                                            $faltando[] = 'Consulta Prévia';
                                        }
                                        if (! $get('estudoviabilidade')) {
                                            $faltando[] = 'Estudo de Viabilidade';
                                        }
                                        if (! $get('visita_tecnica')) {
                                            $faltando[] = 'Visita Técnica';
                                        }
                                        if (! $get('projetos_adicionais')) {
                                            $faltando[] = 'Projetos Adicionais';
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

                    Section::make('Motivo da reprovação')
                        ->visible(fn (callable $get) => $get('aprovacao') === 'reprovado')
                        ->schema([
                            RichEditor::make('observacoes_ressalva')
                                ->label('Comentário / Justificativa')
                                ->helperText('Descreva o motivo da reprovação.')
                                ->required(fn (callable $get) => $get('aprovacao') === 'reprovado')
                                ->validationMessages([
                                    'required' => 'Campo obrigatório',
                                ]),

                            FileUpload::make('anexos_ressalva')
                                ->label('Anexos')
                                ->preserveFilenames()
                                ->downloadable()
                                ->openable()
                                ->multiple(),
                        ])
                        ->collapsible()
                        ->collapsed(),

                ];

                if ($user->hasAnyRole(['Inteligência Global', 'super_admin'])) {
                    $campos[] = Section::make('Checklist - Inteligência Global')
                        ->schema([
                            Toggle::make('consulta_previa')
                                ->label('Consulta Prévia')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_consulta_previa
                                        ? 'Marque após conferir a Consulta Prévia.'
                                        : 'Anexe a Consulta Prévia no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('pmo_cronograma') && $get('pmo_termo_abertura');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_consulta_previa)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Consulta Prévia (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_consulta_previa))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_consulta_previa')
                                ->label('Consulta Prévia (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_consulta_previa))
                                ->formatStateUsing(fn ($record) => $record?->anexo_consulta_previa),

                            RichEditor::make('proj_comentario_pmo_cronograma')
                                ->label('Comentário Consulta Prévia')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->anexo_consulta_previa_comentario ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),

                            Toggle::make('estudoviabilidade')
                                ->label('Estudo Viabilidade')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_estudoviabilidade
                                        ? 'Marque após conferir o Estudo de viabilidade.'
                                        : 'Anexe o Estudo de viabilidade no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('pmo_cronograma') && $get('pmo_termo_abertura');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_estudoviabilidade)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Estudo de viabilidade (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_estudoviabilidade))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_pmo_termo_abertura')
                                ->label('Estudo de viabilidade (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_estudoviabilidade))
                                ->formatStateUsing(fn ($record) => $record?->anexo_estudoviabilidade),

                            RichEditor::make('proj_comentario_pmo_termo_abertura')
                                ->label('Comentário Estudo de viabilidade')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->anexo_estudoviabilidade_comentario ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),

                            Toggle::make('visita_tecnica')
                                ->label('Visita Técnica')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_visita_tecnica
                                        ? 'Marque após conferir a Visita Técnica.'
                                        : 'Anexe a Visita Técnica no projeto para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('pmo_cronograma') && $get('pmo_termo_abertura');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_visita_tecnica)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Visita Técnica (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_visita_tecnica))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_pmo_termo_abertura')
                                ->label('Visita Técnica (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_visita_tecnica))
                                ->formatStateUsing(fn ($record) => $record?->anexo_visita_tecnica),

                            RichEditor::make('proj_comentario_pmo_termo_abertura')
                                ->label('Comentário Visita Técnica')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->anexo_visita_tecnica_comentario ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),

                            Toggle::make('projetos_adicionais')
                                ->label('Projetos Adicionais')
                                ->inline(false)
                                ->onIcon('heroicon-m-check')
                                ->offIcon('heroicon-o-x-mark')
                                ->onColor('success')
                                ->offColor('danger')
                                ->helperText(
                                    fn ($record) => $record?->anexo_projetos_adicionais
                                        ? 'Marque após conferir os Projetos Adicionais.'
                                        : 'Anexe Projetos Adicionais para habilitar.'
                                )
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $ok = $get('pmo_cronograma') && $get('pmo_termo_abertura');
                                    if (! $ok && $get('aprovacao') === 'aprovado') {
                                        $set('aprovacao', null);
                                    }
                                })
                                ->disabled(fn ($record) => empty($record?->anexo_projetos_adicionais)),

                            Placeholder::make('no_contrato_msg')
                                ->label('Projetos Adicionais (Projeto)')
                                ->visible(fn ($record) => empty($record?->anexo_projetos_adicionais))
                                ->content(new HtmlString('<div class="italic text-gray-500">Não existe arquivo anexado ao projeto</div>')),

                            FileUpload::make('proj_anexo_pmo_termo_abertura')
                                ->label('Projetos Adicionais (Projeto)')->disk((string) config('filesystems.media_disk', 'r2'))
                                ->openable()
                                ->downloadable()
                                ->imagePreviewHeight('140')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->dehydrated(false)
                                ->disabled()
                                ->visible(fn ($record) => filled($record?->anexo_projetos_adicionais))
                                ->formatStateUsing(fn ($record) => $record?->anexo_projetos_adicionais),

                            RichEditor::make('proj_comentario_pmo_termo_abertura')
                                ->label('Comentário Projetos Adicionais')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false)
                                ->formatStateUsing(fn ($record) => $record?->anexo_projetos_adicionais_comentario ?? '<p class="italic text-gray-500">Não tem comentário...</p>'),

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

                // 🔓 FAST-TRACK: se super_admin aprovou, aprova todo mundo e avança etapa.
                if ($user->hasRole('super_admin') && $status === 'aprovado') {
                    // 1) Aprovação da Diretoria (super_admin)
                    AprovacaoViabilidade::updateOrCreate(
                        ['projeto_id' => $record->id, 'role' => 'super_admin'],
                        [
                            'user_id' => $user->id,
                            'aprovacao' => 'aprovado',
                            'comentarios_gerais' => $data['comentarios_gerais'] ?? null,
                            // 'approved_at'     => now(), // se você adicionou esse campo
                        ]
                    );

                    // 2) Força aprovação dos demais papéis
                    foreach (['Inteligência Global'] as $r) {
                        AprovacaoViabilidade::updateOrCreate(
                            ['projeto_id' => $record->id, 'role' => $r],
                            [
                                'user_id' => $user->id,   // registra quem efetivou
                                'aprovacao' => 'aprovado',
                                // 'approved_at' => now(),
                            ]
                        );
                    }

                    // 3) Avança etapa para Viabilidade, removendo Reunião de comitê
                    $briefingId = Etapa::where('nome', 'Briefing e Layout')->value('id');
                    $viabilidadeId = Etapa::where('nome', 'Viabilidade')->value('id');

                    if (! $briefingId) {
                        Notification::make()
                            ->title('Etapa "Briefing e Layout" não encontrada')
                            ->danger()
                            ->send();

                        return;
                    }

                    \DB::transaction(function () use ($record, $briefingId, $viabilidadeId) {
                        if ($viabilidadeId) {
                            $record->etapas()->detach($viabilidadeId);
                        }
                        $record->etapas()->syncWithoutDetaching([$briefingId]);
                    });

                    Notification::make()
                        ->title('Diretoria aprovou: etapa avançada para Briefing e Layout')
                        ->success()
                        ->send();

                    return; // encerra aqui para super_admin
                }

                // === Validações por status ===
                if ($status === 'aprovado') {
                    // se tiver "aprovar como", use: $roleParaValidar = $user->hasRole('super_admin') ? ($data['acting_role'] ?? 'Comercial') : $role;
                    $roleParaValidar = $user->hasRole('super_admin') ? 'Comercial' : $role;

                    $msgs = [];

                    if ($roleParaValidar === 'Inteligência Global') {
                        if (! ($data['consulta_previa'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Consulta Prévia".';
                        }
                        if (! ($data['estudoviabilidade'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Estudo de Viabilidade".';
                        }
                        if (! ($data['visita_tecnica'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Visita Técnica".';
                        }
                        if (! ($data['projetos_adicionais'] ?? false)) {
                            $msgs[] = 'Marque o toggle "Projetos Adicionais".';
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

                $observacoesRessalva = $data['observacoes_ressalva'] ?? null;
                $anexosRessalva = $data['anexos_ressalva'] ?? null;

                if ($status !== 'reprovado') {
                    $observacoesRessalva = null;
                    $anexosRessalva = null;
                }

                if ($status === 'reprovado') {
                    $texto = trim(strip_tags($data['observacoes_ressalva'] ?? ''));
                    if ($texto === '') {
                        throw new \Exception('Informe o motivo da reprovação.');
                    }
                }

                // === Persiste ===
                AprovacaoViabilidade::updateOrCreate(
                    [
                        'projeto_id' => $record->id,
                        'role' => $role,
                    ],
                    [
                        'user_id' => $user->id,
                        'aprovacao' => $status,
                        'comentarios_gerais' => $data['comentarios_gerais'] ?? null,

                        // toggles (booleans)
                        'consulta_previa' => (bool) ($data['consulta_previa'] ?? false),
                        'estudoviabilidade' => (bool) ($data['estudoviabilidade'] ?? false),

                        'visita_tecnica' => (bool) ($data['visita_tecnica'] ?? false),
                        'projetos_adicionais' => (bool) ($data['projetos_adicionais'] ?? false),

                        'observacoes_ressalva' => $observacoesRessalva,           // longText
                        'anexos_ressalva' => $anexosRessalva,
                    ]
                );

                // === Verifica aprovações faltantes ===
                $aprovacoes = AprovacaoViabilidade::where('projeto_id', $record->id)
                    ->get()
                    ->keyBy('role');

                $rolesObrigatorias = ['Inteligência Global'];
                $faltando = [];
                foreach ($rolesObrigatorias as $r) {
                    if (! isset($aprovacoes[$r]) || ! in_array($aprovacoes[$r]->aprovacao, ['aprovado'], true)) {
                        $faltando[] = $r;
                    }
                }

                // === Avança etapa se tudo ok ===
                if (empty($faltando)) {
                    $briefingId = Etapa::where('nome', 'Briefing e Layout')->value('id');
                    $viabilidadeId = Etapa::where('nome', 'Viabilidade')->value('id');

                    if (! $briefingId) {
                        Notification::make()
                            ->title('Etapa "Viabilidade" não encontrada')
                            ->danger()
                            ->send();

                        return;
                    }

                    \DB::transaction(function () use ($record, $briefingId, $viabilidadeId) {
                        if ($viabilidadeId) {
                            $record->etapas()->detach($viabilidadeId);
                        }
                        $record->etapas()->syncWithoutDetaching([$briefingId]);
                    });

                    Notification::make()
                        ->title('Aprovação salva e etapa avançada para Briefing e Layout')
                        ->success()
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
