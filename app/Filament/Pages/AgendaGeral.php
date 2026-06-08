<?php

namespace App\Filament\Pages;

use App\Models\AgendaEvent;
use App\Models\BibliotecaArquivo;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\RelatorioVisitaTecnica;
use App\Models\User;
use App\Services\AgendaGeralService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use UnitEnum;


class AgendaGeral extends Page
{
    use HasPageShield;
    use WithFileUploads;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static UnitEnum|string|null $navigationGroup = 'Implantação BIM';

    protected static ?string $navigationParentItem = 'Dashboard';

    protected static ?string $navigationLabel = 'Agenda Geral';

    protected static ?string $title = '';

    protected static ?string $slug = 'agenda-geral';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.agenda-geral';

    public string $viewMode = 'month';

    public string $mesReferencia = '';

    public ?string $selectedDate = null;

    public array $filters = [
        'origin' => 'all',
        'type' => 'all',
        'responsible_user_id' => '',
        'status' => 'all',
        'search' => '',
    ];

    public bool $showEventModal = false;

    public ?int $editingEventId = null;

    public ?string $selectedEventUid = null;

    public array $eventForm = [];

    public bool $agendaLoaded = false;

    public array $responsibleOptions = [];

    public array $unidadeOptions = [];

    public array $filteredUnidadeOptions = [];

    public string $unidadeSearch = '';

    public array $participantIds = [];

    public string $participantSearch = '';

    public array $filteredParticipants = [];

    public array $unidadeData = [];

    public bool $showAllParticipants = false;

    public bool $showActivitiesPrompt = false;

    public bool $linkActivity = false;

    public array $activityOptions = [];

    public string $selectedActivity = '';

    public string $activityHint = '';

    public ?string $relatorioFotograficoAgendadoEm = null;

    public array $myPendingInvites = [];

    public array $myAcceptedInvites = [];

    public array $myRejectedInvites = [];

    public bool $showMyInvitesTab = false;

    /** Acumulador de arquivos novos selecionados (pode receber vários uploads em sequência) */
    public array $novosAnexos = [];

    /** Input temporário de upload — a cada batch o conteúdo é movido para $novosAnexos */
    public array $anexosUploadInput = [];

    /** Snapshot dos anexos já persistidos para o evento em edição */
    public array $anexosExistentes = [];

    /** Modal de preview de anexo */
    public bool $showAnexoPreview = false;

    public ?array $anexoPreview = null;

    /** Modal de configurações da Agenda (paleta de cores por usuário) */
    public bool $showSettingsModal = false;

    /** Aba ativa do modal de configurações: 'cores' | 'tipos' */
    public string $settingsTab = 'cores';

    /** Paleta de cores por usuário visível: [ ['id' => user_id, 'nome' => name, 'setor' => ?nome, 'cor' => '#hex'], ... ] */
    public array $userPalette = [];

    /** Tipos de evento gerenciáveis pelo Coordenador (do seu setor) */
    public array $tiposGestao = [];

    /** Tipos disponíveis para usar no select de criação de evento (do setor do usuário) */
    public array $tiposCriacao = [];

    /** Tipos visíveis no filtro do sidebar (todos os setores que o usuário enxerga) */
    public array $tiposFiltro = [];

    /** Formulário de novo tipo */
    public string $novoTipoNome = '';

    public string $novoTipoCor = '#64748b';

    public function mount(): void
    {
        $this->mesReferencia = now()->startOfMonth()->toDateString();
        $this->selectedDate = now()->toDateString();

        $this->responsibleOptions = User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $this->unidadeOptions = Obras::query()
            ->with('projeto:id,nome,codigo')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'projeto_id', 'codigo', 'unidade'])
            ->mapWithKeys(fn (Obras $obra): array => [
                $obra->id => trim(collect([
                    $obra->codigo,
                    $obra->unidade,
                ])->filter()->implode(' · ')),
            ])
            ->toArray();

        $this->filteredUnidadeOptions = $this->unidadeOptions;
        $this->filteredParticipants = $this->responsibleOptions;
        $this->loadUserPalette();
        $this->loadTipos();
        $this->loadMyInvites();
        $this->resetEventForm();
    }

    public function getPodeGerenciarTiposProperty(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->podeGerenciarTiposAgenda();
    }

    protected function loadTipos(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            $this->tiposGestao = [];
            $this->tiposCriacao = [];
            $this->tiposFiltro = [];
            return;
        }

        $service = app(AgendaGeralService::class);
        $this->tiposGestao = $service->tiposEventoForCoordenador($user);
        $this->tiposCriacao = $service->tiposEventoForCreator($user);
        $this->tiposFiltro = $service->tiposEventoForViewer($user);
    }

    public function setSettingsTab(string $tab): void
    {
        if (! in_array($tab, ['cores', 'tipos'], true)) {
            return;
        }
        $this->settingsTab = $tab;
    }

    public function adicionarTipo(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        try {
            app(AgendaGeralService::class)->criarTipoEvento(
                coordenador: $user,
                nome: $this->novoTipoNome,
                cor: $this->novoTipoCor,
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Não foi possível adicionar')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->novoTipoNome = '';
        $this->novoTipoCor = '#64748b';
        $this->loadTipos();

        Notification::make()
            ->title('Tipo adicionado')
            ->success()
            ->send();
    }

    public function atualizarTipo(int $tipoId): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $tipo = collect($this->tiposGestao)->firstWhere('id', $tipoId);
        if (! $tipo) {
            return;
        }

        try {
            app(AgendaGeralService::class)->atualizarTipoEvento(
                coordenador: $user,
                tipoId: $tipoId,
                nome: $tipo['nome'] ?? null,
                cor: $tipo['cor'] ?? null,
            );
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Não foi possível atualizar')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->loadTipos();
    }

    public function removerTipo(int $tipoId): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        $resultado = app(AgendaGeralService::class)->removerTipoEvento($user, $tipoId);

        if (! $resultado['ok']) {
            Notification::make()
                ->title('Não foi possível remover')
                ->body($resultado['message'] ?? 'Erro desconhecido.')
                ->danger()
                ->send();

            return;
        }

        $this->loadTipos();

        Notification::make()
            ->title('Tipo removido')
            ->success()
            ->send();
    }

    protected function loadUserPalette(): void
    {
        $user = auth()->user();
        $this->userPalette = $user instanceof User
            ? app(AgendaGeralService::class)->userPaletteForViewer($user)
            : [];
    }

    protected function loadMyInvites(): void
    {
        $user = auth()->user();
        if (!$user instanceof User) {
            return;
        }

        $events = $user->agendaEventParticipations()->get();

        $this->myPendingInvites = $events
            ->where('pivot.status', 'pending')
            ->map(fn (AgendaEvent $event) => $this->formatInviteEvent($event, 'pending'))
            ->values()
            ->all();

        $this->myAcceptedInvites = $events
            ->where('pivot.status', 'accepted')
            ->map(fn (AgendaEvent $event) => $this->formatInviteEvent($event, 'accepted'))
            ->values()
            ->all();

        $this->myRejectedInvites = $events
            ->where('pivot.status', 'rejected')
            ->map(fn (AgendaEvent $event) => $this->formatInviteEvent($event, 'rejected'))
            ->values()
            ->all();
    }

    protected function formatInviteEvent(AgendaEvent $event, string $status): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'starts_at' => $event->starts_at?->format('d/m/Y H:i'),
            'ends_at' => $event->ends_at?->format('d/m/Y H:i'),
            'all_day' => $event->all_day,
            'location' => $event->location,
            'responsible_name' => $event->responsibleUser?->name,
            'status' => $status,
        ];
    }

    public function toggleInvitesTab(): void
    {
        $this->showMyInvitesTab = !$this->showMyInvitesTab;
    }

    public function acceptMyInvite(int $eventId): void
    {
        $user = auth()->user();
        if (!$user instanceof User) {
            return;
        }

        $event = AgendaEvent::query()->find($eventId);
        if (!$event || !$event->participants()->where('user_id', $user->id)->exists()) {
            Notification::make()
                ->title('Convite não encontrado')
                ->danger()
                ->send();
            return;
        }

        if ($this->hasConflictingEventForUser($user->id, $event)) {
            Notification::make()
                ->title('Conflito de agenda')
                ->body('Você tem outro evento no mesmo dia e horário.')
                ->warning()
                ->send();
            return;
        }

        $event->participants()->updateExistingPivot($user->id, [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        Notification::make()
            ->title('Convite aceito')
            ->body("Você aceitou o convite para \"{$event->title}\".")
            ->success()
            ->send();

        $this->loadMyInvites();
        $this->refreshSelectedEvent();
    }

    public function rejectMyInvite(int $eventId): void
    {
        $user = auth()->user();
        if (!$user instanceof User) {
            return;
        }

        $event = AgendaEvent::query()->find($eventId);
        if (!$event || !$event->participants()->where('user_id', $user->id)->exists()) {
            Notification::make()
                ->title('Convite não encontrado')
                ->danger()
                ->send();
            return;
        }

        $event->participants()->updateExistingPivot($user->id, [
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        Notification::make()
            ->title('Convite rejeitado')
            ->body("Você rejeitou o convite para \"{$event->title}\".")
            ->warning()
            ->send();

        $this->loadMyInvites();
        $this->refreshSelectedEvent();
    }

    protected function hasConflictingEventForUser(int $userId, AgendaEvent $newEvent): bool
    {
        if (!$newEvent->starts_at) {
            return false;
        }

        $startDate = $newEvent->starts_at->copy()->startOfDay();
        $endDate = $newEvent->ends_at?->copy()->startOfDay() ?? $startDate;

        if ($newEvent->all_day) {
            return AgendaEvent::where('responsible_user_id', $userId)
                ->where('id', '!=', $newEvent->id)
                ->where('all_day', true)
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->exists();
        }

        return AgendaEvent::where('responsible_user_id', $userId)
            ->where('id', '!=', $newEvent->id)
            ->where(function ($q) use ($newEvent) {
                $q->whereBetween('starts_at', [$newEvent->starts_at, $newEvent->ends_at ?? $newEvent->starts_at])
                    ->orWhereBetween('ends_at', [$newEvent->starts_at, $newEvent->ends_at ?? $newEvent->starts_at])
                    ->orWhere(function ($subQ) use ($newEvent) {
                        $subQ->where('starts_at', '<=', $newEvent->starts_at)
                            ->where('ends_at', '>=', $newEvent->ends_at ?? $newEvent->starts_at);
                    });
            })
            ->exists();
    }

    public function openSettingsModal(): void
    {
        $this->loadUserPalette();
        $this->loadTipos();
        $this->settingsTab = 'cores';
        $this->showSettingsModal = true;
    }

    public function closeSettingsModal(): void
    {
        $this->showSettingsModal = false;
    }

    public function saveUserCores(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        foreach ($this->userPalette as $entry) {
            $targetId = (int) ($entry['id'] ?? 0);
            $cor = (string) ($entry['cor'] ?? '');
            if ($targetId <= 0 || $cor === '') {
                continue;
            }
            app(AgendaGeralService::class)->saveUserCor($user, $targetId, $cor);
        }

        $this->showSettingsModal = false;

        Notification::make()
            ->title('Cores atualizadas')
            ->success()
            ->send();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('View:AgendaGeral');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function updatedSelectedDate(): void
    {
        if ($this->selectedDate) {
            $this->mesReferencia = Carbon::parse($this->selectedDate)->startOfMonth()->toDateString();
        }
    }

    public function updatedEventFormAllDay(): void
    {
        if ($this->eventForm['all_day'] ?? false) {
            if (!empty($this->eventForm['starts_at'])) {
                $startDate = Carbon::parse($this->eventForm['starts_at']);
                $this->eventForm['starts_at'] = $startDate->startOfDay()->format('Y-m-d\T00:00');
                $this->eventForm['ends_at'] = $startDate->endOfDay()->format('Y-m-d\T23:59');
            }
        }
    }

    public function updatedUnidadeSearch(): void
    {
        $search = trim(strtolower($this->unidadeSearch));

        if (empty($search)) {
            $this->filteredUnidadeOptions = $this->unidadeOptions;
            return;
        }

        $this->filteredUnidadeOptions = array_filter(
            $this->unidadeOptions,
            fn ($label) => str_contains(strtolower($label), $search)
        );
    }

    public function selectUnidade(string $obraId): void
    {
        $this->eventForm['obra_id'] = $obraId;
        $this->unidadeSearch = '';
        $this->filteredUnidadeOptions = $this->unidadeOptions;
        $this->loadUnidadeData($obraId);
        $this->updatedEventFormObraId();
    }

    protected function loadUnidadeData(string $obraId): void
    {
        $obra = Obras::query()->find((int)$obraId);
        if ($obra) {
            $this->unidadeData = [
                'endereco' => $obra->endereco,
                'cidade' => $obra->cidade,
                'uf' => $obra->uf,
                'pais' => 'Brasil', // Por padrão, ajuste se houver campo de país
            ];
        }
    }

    public function updatedParticipantSearch(): void
    {
        $search = trim(strtolower($this->participantSearch));
        $this->showAllParticipants = false;

        if (empty($search)) {
            $this->filteredParticipants = $this->responsibleOptions;
            return;
        }

        $this->filteredParticipants = array_filter(
            $this->responsibleOptions,
            fn ($name) => str_contains(strtolower($name), $search)
        );
    }

    public function toggleParticipant(int $userId): void
    {
        if (in_array($userId, $this->participantIds)) {
            $this->participantIds = array_filter(
                $this->participantIds,
                fn ($id) => $id !== $userId
            );
        } else {
            $this->participantIds[] = $userId;
        }
    }

    public function updatedEventFormObraId(): void
    {
        $obraId = $this->eventForm['obra_id'] ?? null;

        if (empty($obraId)) {
            $this->showActivitiesPrompt = false;
            $this->linkActivity = false;
            $this->activityOptions = [];
            $this->selectedActivity = '';
            $this->activityHint = '';
            return;
        }

        $this->showActivitiesPrompt = true;
        $this->linkActivity = false;
        $this->activityOptions = [];
        $this->selectedActivity = '';
        $this->activityHint = '';
    }

    public function declineActivity(): void
    {
        $this->showActivitiesPrompt = false;
        $this->linkActivity = false;
        $this->activityOptions = [];
        $this->selectedActivity = '';
        $this->activityHint = '';
        $this->relatorioFotograficoAgendadoEm = null;
    }

    public function acceptActivity(): void
    {
        $this->loadActivitiesForUnidade();
        $this->linkActivity = true;
    }

    public function loadActivitiesForUnidade(): void
    {
        $this->activityOptions = [
            'vt' => [
                'label' => 'Visita técnica',
                'hint'  => 'Criará um agendamento de VT vinculado ao projeto desta unidade.',
            ],
            'relatorio_fotografico' => [
                'label' => 'Relatório fotográfico de posse',
                'hint'  => 'Criará um relatório fotográfico e preencherá a data na unidade.',
            ],
        ];
    }

    public function updatedSelectedActivity(): void
    {
        if (empty($this->selectedActivity) || empty($this->eventForm['obra_id'])) {
            $this->activityHint = '';
            return;
        }

        $this->eventForm['event_type'] = $this->selectedActivity;

        if ($this->selectedActivity === 'relatorio_fotografico') {
            $startsAt = Carbon::parse($this->eventForm['starts_at']);
            $this->relatorioFotograficoAgendadoEm = $startsAt->format('Y-m-d');
        }

        $check = app(AgendaGeralService::class)->checkActivityExists(
            obraId: (int) $this->eventForm['obra_id'],
            activity: $this->selectedActivity,
        );
        $this->activityHint = $check['hint'];
    }

    public function irParaHoje(): void
    {
        $this->mesReferencia = now()->startOfMonth()->toDateString();
        $this->selectedDate = now()->toDateString();
    }

    public function mesAnterior(): void
    {
        $ref = Carbon::parse($this->selectedDate ?? $this->mesReferencia);

        $novoRef = match ($this->viewMode) {
            'week' => $ref->copy()->subWeek(),
            'day' => $ref->copy()->subDay(),
            default => $ref->copy()->subMonth()->startOfMonth(),
        };

        $this->selectedDate = $novoRef->toDateString();
        $this->mesReferencia = $novoRef->copy()->startOfMonth()->toDateString();
    }

    public function proximoMes(): void
    {
        $ref = Carbon::parse($this->selectedDate ?? $this->mesReferencia);

        $novoRef = match ($this->viewMode) {
            'week' => $ref->copy()->addWeek(),
            'day' => $ref->copy()->addDay(),
            default => $ref->copy()->addMonth()->startOfMonth(),
        };

        $this->selectedDate = $novoRef->toDateString();
        $this->mesReferencia = $novoRef->copy()->startOfMonth()->toDateString();
    }

    public function selecionarData(string $date): void
    {
        $this->selectedDate = $date;
        $this->mesReferencia = Carbon::parse($date)->startOfMonth()->toDateString();
    }

    public function selecionarDataMini(string $date): void
    {
        $this->selectedDate = $date;
        $this->mesReferencia = Carbon::parse($date)->startOfMonth()->toDateString();
        $this->viewMode = 'day';
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['month', 'week', 'day'], true)) {
            return;
        }

        $this->viewMode = $mode;
    }

    public function loadAgenda(): void
    {
        $this->agendaLoaded = true;
    }

    public function selectEvent(string $uid): void
    {
        $this->selectedEventUid = $uid;
    }

    public function refreshSelectedEvent(): void
    {
        // Force re-render by toggling the selected event
        $current = $this->selectedEventUid;
        $this->selectedEventUid = null;
        $this->selectedEventUid = $current;
    }

    public function openCreateEventModal(?string $date = null): void
    {
        if (empty($this->tiposCriacao)) {
            Notification::make()
                ->title('Nenhum tipo de evento disponível')
                ->body('Peça ao Coordenador do seu setor para cadastrar tipos em Configurações → Tipos.')
                ->warning()
                ->send();

            return;
        }

        $this->editingEventId = null;
        $this->unidadeSearch = '';
        $this->filteredUnidadeOptions = $this->unidadeOptions;
        $this->participantSearch = '';
        $this->filteredParticipants = $this->responsibleOptions;
        $this->showAllParticipants = false;
        $this->resetEventForm($date);
        $this->novosAnexos = [];
        $this->anexosUploadInput = [];
        $this->anexosExistentes = [];
        $this->showEventModal = true;
    }

    public function openEditEventModal(int $eventId): void
    {
        $event = AgendaEvent::query()->find($eventId);
        if (! $event || ! $this->canManageManualEvent($event)) {
            Notification::make()
                ->title('Sem permissao')
                ->body('Voce nao pode editar este evento.')
                ->danger()
                ->send();

            return;
        }

        $this->editingEventId = $event->id;
        $this->eventForm = [
            'title' => $event->title,
            'description' => $event->description ?? '',
            'starts_at' => optional($event->starts_at)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'),
            'ends_at' => optional($event->ends_at)->format('Y-m-d\TH:i') ?? '',
            'all_day' => (bool) $event->all_day,
            'event_type' => $event->event_type,
            'status' => $event->status,
            'color' => $event->color ?? '',
            'location' => $event->location ?? '',
            'responsible_user_id' => $event->responsible_user_id,
            'obra_id' => $event->obra_id,
        ];
        $this->participantIds = $event->participants()->pluck('user_id')->toArray() ?? [];
        $this->unidadeSearch = '';
        $this->filteredUnidadeOptions = $this->unidadeOptions;
        $this->participantSearch = '';
        $this->filteredParticipants = $this->responsibleOptions;
        $this->showAllParticipants = false;
        if (!empty($event->obra_id)) {
            $this->showActivitiesPrompt = true;
        }

        $this->novosAnexos = [];
        $this->anexosUploadInput = [];
        $this->anexosExistentes = $this->mapAnexos($event);

        $this->showEventModal = true;
    }

    /**
     * @return array<int, array{id:int,nome:string,url:?string,is_image:bool,is_pdf:bool,tamanho:?int}>
     */
    protected function mapAnexos(AgendaEvent $event): array
    {
        return $event->arquivos()
            ->orderBy('created_at')
            ->get()
            ->map(fn (BibliotecaArquivo $a): array => [
                'id' => (int) $a->id,
                'nome' => (string) $a->nome_original,
                'url' => $a->url,
                'is_image' => $a->is_image,
                'is_pdf' => $a->is_pdf,
                'tamanho' => $a->tamanho,
            ])
            ->all();
    }

    public function removerAnexo(int $arquivoId): void
    {
        if (! $this->editingEventId) {
            return;
        }

        $event = AgendaEvent::query()->find($this->editingEventId);
        if (! $event || ! $this->canManageManualEvent($event)) {
            return;
        }

        $event->removerArquivo($arquivoId);
        $this->anexosExistentes = $this->mapAnexos($event);

        Notification::make()
            ->title('Anexo removido')
            ->success()
            ->send();
    }

    public function removerNovoAnexo(int $index): void
    {
        if (isset($this->novosAnexos[$index])) {
            unset($this->novosAnexos[$index]);
            $this->novosAnexos = array_values($this->novosAnexos);
        }
    }

    public function abrirAnexoPreview(int $arquivoId): void
    {
        $arquivo = BibliotecaArquivo::query()
            ->where('referenciavel_type', AgendaEvent::class)
            ->find($arquivoId);

        if (! $arquivo) {
            return;
        }

        $this->anexoPreview = [
            'id' => (int) $arquivo->id,
            'nome' => (string) $arquivo->nome_original,
            'url' => $arquivo->url,
            'mime_type' => (string) $arquivo->mime_type,
            'is_image' => (bool) $arquivo->is_image,
            'is_pdf' => (bool) $arquivo->is_pdf,
            'tamanho' => $arquivo->tamanho,
        ];
        $this->showAnexoPreview = true;
    }

    public function fecharAnexoPreview(): void
    {
        $this->showAnexoPreview = false;
        $this->anexoPreview = null;
    }

    public function updatedAnexosUploadInput(): void
    {
        foreach ($this->anexosUploadInput as $arquivo) {
            if ($arquivo instanceof \Illuminate\Http\UploadedFile) {
                $this->novosAnexos[] = $arquivo;
            }
        }
        $this->anexosUploadInput = [];
    }

    public function closeEventModal(): void
    {
        $this->showEventModal = false;
        $this->editingEventId = null;
        $this->novosAnexos = [];
        $this->anexosUploadInput = [];
        $this->anexosExistentes = [];
        $this->unidadeData = [];
        $this->resetEventForm();
    }

    public function saveEvent(): void
    {
        $data = $this->eventForm;

        if (!($data['all_day'] ?? false) && !empty($data['ends_at']) && !empty($data['starts_at'])) {
            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);

            if ($endsAt->isBefore($startsAt)) {
                Notification::make()
                    ->title('Data inválida')
                    ->body('A data de fim não pode ser anterior à data de início.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $validated = validator($data, [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['boolean'],
            'event_type' => ['required', 'string', 'max:60'],
            'status' => ['required', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'obra_id' => ['nullable', 'integer', 'exists:obras,id'],
        ])->validate();

        // Valida os novos anexos: PDF ou imagem, até 10MB cada
        if (! empty($this->novosAnexos)) {
            try {
                validator(
                    ['novosAnexos' => $this->novosAnexos],
                    ['novosAnexos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240']],
                    [
                        'novosAnexos.*.mimes' => 'Apenas arquivos PDF ou imagens são permitidos.',
                        'novosAnexos.*.max' => 'Cada arquivo deve ter no máximo 10MB.',
                    ],
                )->validate();
            } catch (\Illuminate\Validation\ValidationException $e) {
                Notification::make()
                    ->title('Arquivo inválido')
                    ->body(collect($e->errors())->flatten()->first() ?? 'Verifique os anexos.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $event = $this->editingEventId
            ? AgendaEvent::query()->findOrFail($this->editingEventId)
            : null;

        if ($event && ! $this->canManageManualEvent($event)) {
            Notification::make()
                ->title('Sem permissao')
                ->body('Voce nao pode editar este evento.')
                ->danger()
                ->send();

            return;
        }

        // Se vinculando uma atividade, não criar evento manual, apenas criar/atualizar a atividade
        $savedEvent = null;
        if ($this->linkActivity && !empty($this->selectedActivity) && !empty($validated['obra_id'])) {
            $activityId = app(AgendaGeralService::class)->linkActivityToObra(
                obraId: (int) $validated['obra_id'],
                activity: $this->selectedActivity,
                startsAt: Carbon::parse($validated['starts_at']),
                createdBy: auth()->id(),
            );

            if ($this->selectedActivity === 'relatorio_fotografico' && $activityId) {
                if (!empty($this->relatorioFotograficoAgendadoEm)) {
                    $relatorioFotografico = \App\Models\RelatorioFotografico::find($activityId);
                    if ($relatorioFotografico) {
                        $relatorioFotografico->update([
                            'agendado_em' => Carbon::parse($this->relatorioFotograficoAgendadoEm),
                        ]);
                    }
                }
            }

            Notification::make()
                ->title('Atividade criada com sucesso')
                ->body("A atividade foi agendada para " . Carbon::parse($validated['starts_at'])->format('d/m/Y H:i'))
                ->success()
                ->send();
        } else {
            // Criar evento manual normalmente
            $savedEvent = app(AgendaGeralService::class)->saveManualEvent($validated, $event);

            if (!empty($this->participantIds)) {
                $syncData = [];
                foreach ($this->participantIds as $participantId) {
                    $syncData[$participantId] = [
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $savedEvent->participants()->sync($syncData);
            } else {
                $savedEvent->participants()->detach();
            }
        }

        if ($savedEvent && ! empty($this->novosAnexos)) {
            foreach ($this->novosAnexos as $arquivo) {
                if ($arquivo instanceof \Illuminate\Http\UploadedFile) {
                    $savedEvent->anexarArquivo($arquivo, auth()->id(), 'r2');
                }
            }
        }

        if ($savedEvent) {
            $this->notifyEventRecipients($savedEvent, $event !== null);
        }

        $this->showEventModal = false;
        $this->editingEventId = null;
        $this->selectedEventUid = null;
        $this->novosAnexos = [];
        $this->anexosUploadInput = [];
        $this->anexosExistentes = [];
        $this->resetEventForm();

        if (!($this->linkActivity && !empty($this->selectedActivity))) {
            Notification::make()
                ->title($event ? 'Evento atualizado' : 'Evento criado')
                ->success()
                ->send();
        }
    }

    protected function notifyEventRecipients(AgendaEvent $event, bool $isUpdate): void
    {
        $creatorId = (int) auth()->id();
        $participantIds = [];

        $event->loadMissing('participants:id');
        foreach ($event->participants as $participant) {
            $participantIds[] = (int) $participant->id;
        }

        if (empty($participantIds)) {
            return;
        }

        $recipients = User::whereIn('id', $participantIds)->get();
        $creatorName = auth()->user()?->name ?? 'Sistema';
        $startsAt = $event->starts_at ? $event->starts_at->format('d/m/Y H:i') : '';

        $title = 'Você foi convidado para um evento';
        $body = "{$creatorName} o/a convidou para o evento \"{$event->title}\" no dia {$startsAt}. Acesse sua agenda para aceitar ou rejeitar o convite.";

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->info()
                ->sendToDatabase($recipient);
        }
    }

    public function deleteEvent(int $eventId): void
    {
        $event = AgendaEvent::query()->find($eventId);
        if (! $event || ! $this->canManageManualEvent($event)) {
            Notification::make()
                ->title('Sem permissao')
                ->body('Voce nao pode excluir este evento.')
                ->danger()
                ->send();

            return;
        }

        $this->notifyEventDeletion($event);

        foreach ($event->arquivos as $arquivo) {
            $arquivo->deleteFromDisk();
            $arquivo->delete();
        }

        app(AgendaGeralService::class)->deleteManualEvent($event);
        $this->selectedEventUid = null;

        Notification::make()
            ->title('Evento excluido')
            ->success()
            ->send();
    }

    protected function notifyEventDeletion(AgendaEvent $event): void
    {
        $creatorId = (int) auth()->id();
        $recipientIds = collect();

        if ($event->responsible_user_id) {
            $recipientIds->push((int) $event->responsible_user_id);
        }

        $event->loadMissing('participants:id');
        foreach ($event->participants as $participant) {
            $recipientIds->push((int) $participant->id);
        }

        $recipientIds = $recipientIds->unique()->reject(fn ($id) => $id === $creatorId)->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        $recipients = User::whereIn('id', $recipientIds)->get();
        $creatorName = auth()->user()?->name ?? 'Sistema';
        $startsAt = $event->starts_at ? $event->starts_at->format('d/m/Y H:i') : '';

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title('Evento cancelado')
                ->body("{$creatorName} excluiu o evento \"{$event->title}\" agendado para {$startsAt}.")
                ->warning()
                ->sendToDatabase($recipient);
        }
    }

    protected function canManageManualEvent(AgendaEvent $event): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return (int) $event->responsible_user_id === (int) $user->id;
    }

    public function canManageEventById(?int $eventId): bool
    {
        if (!$eventId) {
            return false;
        }
        $event = AgendaEvent::query()->find($eventId);
        return $event ? $this->canManageManualEvent($event) : false;
    }

    protected function resetEventForm(?string $date = null): void
    {
        $baseDate = $date ? Carbon::parse($date) : ($this->selectedDate ? Carbon::parse($this->selectedDate) : now());

        $primeiroTipoSlug = $this->tiposCriacao[0]['slug'] ?? '';

        $this->eventForm = [
            'title' => '',
            'description' => '',
            'starts_at' => $baseDate->copy()->setTimeFromTimeString(now()->format('H:i'))->format('Y-m-d\TH:i'),
            'ends_at' => $baseDate->copy()->setTimeFromTimeString(now()->addHour()->format('H:i'))->format('Y-m-d\TH:i'),
            'all_day' => false,
            'event_type' => $primeiroTipoSlug,
            'status' => 'agendado',
            'color' => '',
            'location' => '',
            'responsible_user_id' => auth()->id(),
            'obra_id' => null,
        ];
        $this->participantIds = [];
        $this->unidadeSearch = '';
        $this->filteredUnidadeOptions = $this->unidadeOptions;
        $this->unidadeData = [];
        $this->showActivitiesPrompt = false;
        $this->linkActivity = false;
        $this->activityOptions = [];
        $this->showAllParticipants = false;
        $this->selectedActivity = '';
        $this->activityHint = '';
        $this->relatorioFotograficoAgendadoEm = null;
    }

    protected function resolveRange(): array
    {
        $reference = Carbon::parse($this->selectedDate ?? $this->mesReferencia ?? now());

        return match ($this->viewMode) {
            'week' => [
                'start' => $reference->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
                'end' => $reference->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
            ],
            'day' => [
                'start' => $reference->copy()->startOfDay(),
                'end' => $reference->copy()->endOfDay(),
            ],
            default => [
                'start' => Carbon::parse($this->mesReferencia ?: now())->startOfMonth()->startOfDay(),
                'end' => Carbon::parse($this->mesReferencia ?: now())->endOfMonth()->endOfDay(),
            ],
        };
    }

    protected function getViewData(): array
    {
        if (! $this->agendaLoaded) {
            return [
                'calendarWeeks' => [],
                'weekDays' => [],
                'dayHours' => [],
                'events' => [],
                'dayEvents' => [],
                'dayAllDayEvents' => [],
                'monthEvents' => [],
                'selectedEvent' => [],
            ];
        }

        $range = $this->resolveRange();
        $events = app(AgendaGeralService::class)
            ->collectEvents($range['start'], $range['end'], $this->filters, auth()->user())
            ->values();

        $monthEvents = [];
        foreach ($events as $event) {
            $dates = $event['display_dates'] ?? [$event['date_key'] ?? null];

            foreach ($dates as $date) {
                if (! $date) {
                    continue;
                }

                $monthEvents[$date][] = $event;
            }
        }

        $calendarWeeks = [];
        $weekDays = [];
        $dayHours = [];

        if ($this->viewMode === 'month') {
            $calendarWeeks = $this->buildMonthGrid($monthEvents);
        } elseif ($this->viewMode === 'week') {
            $weekDays = $this->buildWeekGrid($monthEvents, $range['start'], $range['end']);
        } else {
            $dayHours = $this->buildDayGrid($events, $range['start']);
        }

        $dayEvents = collect($monthEvents[$this->selectedDate] ?? [])
            ->sortBy('starts_at')
            ->values()
            ->all();

        $dayAllDayEvents = collect($dayEvents)
            ->filter(fn (array $event): bool => (bool) ($event['all_day'] ?? false))
            ->values()
            ->all();

        $selectedEvent = [];
        if ($this->selectedEventUid) {
            $selectedEvent = $events->firstWhere('uid', $this->selectedEventUid) ?? [];
        }

        if ($selectedEvent === [] && !empty($dayEvents)) {
            $selectedEvent = reset($dayEvents);
        }

        if (!empty($selectedEvent)) {
            if (($selectedEvent['origin'] ?? null) === 'manual' && !empty($selectedEvent['manual_event_id'] ?? null)) {
                $selectedEvent['can_manage'] = $this->canManageEventById((int) $selectedEvent['manual_event_id']);
                $manualEvent = AgendaEvent::query()->find((int) $selectedEvent['manual_event_id']);
                $selectedEvent['anexos'] = $manualEvent ? $this->mapAnexos($manualEvent) : [];
            } else {
                $selectedEvent['can_manage'] = false;
                $selectedEvent['anexos'] = [];
            }
        }

        return [
            'calendarWeeks' => $calendarWeeks,
            'weekDays' => $weekDays,
            'dayHours' => $dayHours,
            'events' => $events->all(),
            'dayEvents' => $dayEvents,
            'dayAllDayEvents' => $dayAllDayEvents,
            'monthEvents' => $monthEvents,
            'selectedEvent' => $selectedEvent,
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $monthEvents
     * @return array<int, array<int, array<string, mixed>|null>>
     */
    protected function buildMonthGrid(array $monthEvents): array
    {
        $startOfMonth = Carbon::parse($this->mesReferencia)->startOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;
        $startsOn = ((int) $startOfMonth->dayOfWeekIso) - 1;

        $cells = [];
        for ($i = 0; $i < $startsOn; $i++) {
            $cells[] = null;
        }

        $allEvents = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startOfMonth->copy()->day($day)->format('Y-m-d');
            $items = $monthEvents[$date] ?? [];

            foreach ($items as $event) {
                $uid = $event['uid'] ?? null;
                if ($uid && !isset($allEvents[$uid])) {
                    $allEvents[$uid] = $event;
                }
            }

            $singleDayItems = array_filter($items, function ($e) {
                $start = Carbon::parse($e['starts_at']);
                $end = !empty($e['ends_at']) ? Carbon::parse($e['ends_at']) : $start;
                return $start->format('Y-m-d') === $end->format('Y-m-d');
            });
            $singleDayItems = array_values($singleDayItems);

            $cells[] = [
                'day' => $day,
                'date' => $date,
                'is_today' => $date === now()->format('Y-m-d'),
                'is_selected' => $this->selectedDate === $date,
                'total' => count($items),
                'hidden_total' => max(count($singleDayItems) - 2, 0),
                'items' => array_slice($singleDayItems, 0, 2),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        $weeks = array_chunk($cells, 7);

        $weeksWithMultiDay = [];
        foreach ($weeks as $weekIndex => $week) {
            $firstDate = null;
            $lastDate = null;
            foreach ($week as $cell) {
                if ($cell !== null) {
                    if ($firstDate === null) {
                        $firstDate = Carbon::parse($cell['date']);
                    }
                    $lastDate = Carbon::parse($cell['date']);
                }
            }

            $multiDayBars = [];
            if ($firstDate && $lastDate) {
                $multiDayBars = $this->buildMultiDayBarsForWeek($allEvents, $week, $firstDate, $lastDate);
            }

            $weeksWithMultiDay[] = [
                'cells' => $week,
                'multi_day_bars' => $multiDayBars,
                'multi_day_count' => count($multiDayBars),
            ];
        }

        return $weeksWithMultiDay;
    }

    protected function buildMultiDayBarsForWeek(array $allEvents, array $week, Carbon $weekStart, Carbon $weekEnd): array
    {
        $bars = [];

        foreach ($allEvents as $uid => $event) {
            $start = Carbon::parse($event['starts_at']);
            $end = !empty($event['ends_at']) ? Carbon::parse($event['ends_at']) : $start;

            if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
                continue;
            }

            $eventStartDay = $start->copy()->startOfDay();
            $eventEndDay = $end->copy()->startOfDay();

            if ($eventEndDay->isBefore($weekStart->copy()->startOfDay()) || $eventStartDay->isAfter($weekEnd->copy()->startOfDay())) {
                continue;
            }

            $segmentStart = $eventStartDay->isBefore($weekStart->copy()->startOfDay()) ? $weekStart->copy()->startOfDay() : $eventStartDay;
            $segmentEnd = $eventEndDay->isAfter($weekEnd->copy()->startOfDay()) ? $weekEnd->copy()->startOfDay() : $eventEndDay;

            $startCol = null;
            foreach ($week as $colIndex => $cell) {
                if ($cell !== null && $cell['date'] === $segmentStart->format('Y-m-d')) {
                    $startCol = $colIndex + 1;
                    break;
                }
            }

            $endCol = null;
            foreach ($week as $colIndex => $cell) {
                if ($cell !== null && $cell['date'] === $segmentEnd->format('Y-m-d')) {
                    $endCol = $colIndex + 1;
                    break;
                }
            }

            if ($startCol === null || $endCol === null) {
                continue;
            }

            $span = $endCol - $startCol + 1;

            $bars[] = [
                'uid' => $uid,
                'title' => $event['title'] ?? '',
                'color' => $event['color'] ?? '#64748b',
                'starts_at' => $event['starts_at'],
                'ends_at' => $event['ends_at'],
                'startCol' => $startCol,
                'span' => $span,
                'continues_left' => $eventStartDay->isBefore($weekStart->copy()->startOfDay()),
                'continues_right' => $eventEndDay->isAfter($weekEnd->copy()->startOfDay()),
            ];
        }

        usort($bars, fn ($a, $b) => $a['startCol'] <=> $b['startCol']);

        return $bars;
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $monthEvents
     * @return array<int, array<string, mixed>>
     */
    protected function buildWeekGrid(array $monthEvents, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $days = [];
        $cursor = $rangeStart->copy();

        while ($cursor->lte($rangeEnd)) {
            $date = $cursor->format('Y-m-d');

            $days[] = [
                'date' => $date,
                'day_name' => ucfirst($cursor->translatedFormat('D')),
                'day_number' => $cursor->format('d'),
                'month_label' => $cursor->translatedFormat('M'),
                'is_today' => $date === now()->format('Y-m-d'),
                'is_selected' => $date === $this->selectedDate,
                'items' => collect($monthEvents[$date] ?? [])
                    ->sortBy('starts_at')
                    ->values()
                    ->all(),
            ];

            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @param Collection<int, array<string, mixed>> $events
     * @return array<int, array<string, mixed>>
     */
    protected function buildDayGrid(Collection $events, Carbon $rangeStart): array
    {
        $hours = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourEvents = $events
                ->filter(function (array $event) use ($rangeStart, $hour): bool {
                    $start = Carbon::parse($event['starts_at']);

                    return $start->isSameDay($rangeStart) && (int) $start->format('H') === $hour;
                })
                ->sortBy('starts_at')
                ->values()
                ->all();

            $hours[] = [
                'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00',
                'items' => $hourEvents,
            ];
        }

        return $hours;
    }
}
