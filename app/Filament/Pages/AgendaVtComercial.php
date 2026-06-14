<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProjetoResource;
use App\Models\Projeto;
use App\Models\RelatorioVisitaTecnica;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AgendaVtComercial extends Page
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static UnitEnum|string|null $navigationGroup = 'Outros';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Agenda VT';

    protected static ?string $title = 'Agenda de visitas técnicas';

    protected static ?string $slug = 'agenda-vt-comercial';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.agenda-vt-comercial';

    public string $mesReferencia = '';

    public string $mesLabel = '';

    public ?string $dataSelecionada = null;

    public array $calendarWeeks = [];

    public array $agendaDoDia = [];

    public array $agendaDoMes = [];

    public bool $sidebarOpen = false;

    public ?array $sidebarPonto = null;

    public function mount(): void
    {
        $this->mesReferencia = now()->startOfMonth()->toDateString();
        $this->loadAgenda();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mesAnterior')
                ->label('Mes anterior')
                ->icon('heroicon-o-chevron-left')
                ->action(fn () => $this->mesAnterior()),
            Action::make('mesAtual')
                ->label('Mes atual')
                ->icon('heroicon-o-calendar')
                ->action(fn () => $this->irParaMesAtual()),
            Action::make('proximoMes')
                ->label('Proximo mes')
                ->icon('heroicon-o-chevron-right')
                ->action(fn () => $this->proximoMes()),
        ];
    }

    public function mesAnterior(): void
    {
        $this->mesReferencia = Carbon::parse($this->mesReferencia)->subMonth()->startOfMonth()->toDateString();
        $this->loadAgenda();
    }

    public function proximoMes(): void
    {
        $this->mesReferencia = Carbon::parse($this->mesReferencia)->addMonth()->startOfMonth()->toDateString();
        $this->loadAgenda();
    }

    public function irParaMesAtual(): void
    {
        $this->mesReferencia = now()->startOfMonth()->toDateString();
        $this->loadAgenda();
    }

    public function selecionarData(string $data): void
    {
        $this->dataSelecionada = $data;
        // Rebuild calendar cells so selected-day styling is reflected immediately.
        $this->loadAgenda();
    }

    public function abrirSidebarPonto(int $projetoId): void
    {
        $query = Projeto::query()
            ->with(['cidade:id,nome', 'estado:id,uf,nome', 'responsavelCom:id,name', 'relatorioVisitaTecnica:id,projeto_id,agendado_em'])
            ->whereKey($projetoId);

        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        if (! $user->hasRole('super_admin') && ! $this->isGestorComercial($user)) {
            $query->where('resp_com', $user->id);
        }

        $projeto = $query->first();
        if (! $projeto) {
            return;
        }

        $this->sidebarPonto = [
            'id' => $projeto->id,
            'codigo' => $projeto->codigo ?: '-',
            'nome' => $projeto->nome ?: '-',
            'marca' => $projeto->marca ?: '-',
            'status_comite' => $projeto->status_comite ?: '-',
            'responsavel' => $projeto->responsavelCom?->name ?: '-',
            'cidade_uf' => trim(($projeto->cidade?->nome ?: '').' / '.($projeto->estado?->uf ?: '')),
            'endereco' => $projeto->endereco ?: '-',
            'agendamento_vt' => $projeto->relatorioVisitaTecnica?->agendado_em?->format('d/m/Y H:i') ?? '-',
            'visualizar_url' => ProjetoResource::getUrl('visualizar-ponto', ['record' => $projeto->id]),
            'editar_url' => ProjetoResource::getUrl('editar-ponto', ['record' => $projeto->id]),
        ];
        $this->sidebarOpen = true;
    }

    public function fecharSidebarPonto(): void
    {
        $this->sidebarOpen = false;
        $this->sidebarPonto = null;
    }

    public function loadAgenda(): void
    {
        $startOfMonth = Carbon::parse($this->mesReferencia)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $this->mesLabel = $startOfMonth->translatedFormat('F/Y');

        $registrosMes = $this->baseAgendaQuery()
            ->whereBetween('agendado_em', [$startOfMonth, $endOfMonth->copy()->endOfDay()])
            ->orderBy('agendado_em')
            ->get();

        $eventsByDate = $registrosMes
            ->groupBy(fn ($r) => Carbon::parse($r->agendado_em)->format('Y-m-d'))
            ->map(function ($items) {
                return $items->map(function ($r) {
                    return [
                        'codigo' => $r->projeto?->codigo ?: '-',
                        'ponto' => $r->projeto?->nome ?: 'Ponto sem nome',
                        'hora' => Carbon::parse($r->agendado_em)->format('H:i'),
                        'projeto_id' => $r->projeto?->id,
                    ];
                })->values()->all();
            })
            ->toArray();

        $startsOn = ((int) $startOfMonth->dayOfWeekIso) - 1;
        $daysInMonth = (int) $startOfMonth->daysInMonth;

        $cells = [];
        for ($i = 0; $i < $startsOn; $i++) {
            $cells[] = null;
        }

        $today = now()->format('Y-m-d');
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startOfMonth->copy()->day($day)->format('Y-m-d');
            $items = $eventsByDate[$date] ?? [];
            $cells[] = [
                'day' => $day,
                'date' => $date,
                'is_today' => $date === $today,
                'is_selected' => $this->dataSelecionada === $date,
                'total' => count($items),
                'items' => array_slice($items, 0, 2),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        $this->calendarWeeks = array_chunk($cells, 7);

        if (! $this->dataSelecionada || ! str_starts_with($this->dataSelecionada, $startOfMonth->format('Y-m'))) {
            $this->dataSelecionada = $startOfMonth->format('Y-m-d');
        }

        $this->agendaDoMes = $registrosMes->map(function ($r) {
            $agendamento = Carbon::parse($r->agendado_em);

            return [
                'data' => $agendamento->format('d/m/Y'),
                'hora' => $agendamento->format('H:i'),
                'codigo' => $r->projeto?->codigo ?: '-',
                'ponto' => $r->projeto?->nome ?: 'Ponto sem nome',
                'projeto_id' => $r->projeto?->id,
            ];
        })->values()->all();

        $this->loadAgendaDoDia();
    }

    protected function loadAgendaDoDia(): void
    {
        if (! $this->dataSelecionada) {
            $this->agendaDoDia = [];

            return;
        }

        $this->agendaDoDia = $this->baseAgendaQuery()
            ->whereDate('agendado_em', $this->dataSelecionada)
            ->orderBy('agendado_em')
            ->get()
            ->map(function ($r) {
                $agendamento = Carbon::parse($r->agendado_em);

                return [
                    'data' => $agendamento->format('d/m/Y'),
                    'hora' => $agendamento->format('H:i'),
                    'codigo' => $r->projeto?->codigo ?: '-',
                    'ponto' => $r->projeto?->nome ?: 'Ponto sem nome',
                    'projeto_id' => $r->projeto?->id,
                ];
            })
            ->values()
            ->all();
    }

    protected function baseAgendaQuery(): Builder
    {
        $query = RelatorioVisitaTecnica::query()
            ->with(['projeto:id,nome,codigo,resp_com'])
            ->whereNotNull('agendado_em');

        $user = auth()->user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($this->isGestorComercial($user)) {
            return $query;
        }

        if ($this->isComercialComercial($user)) {
            return $query->whereHas('projeto', fn (Builder $q) => $q->where('resp_com', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    protected function hasSetorComercial(User $user): bool
    {
        return $user->setores()
            ->whereRaw('LOWER(setor) = ?', ['comercial'])
            ->exists();
    }

    protected function isGestorComercial(User $user): bool
    {
        return $user->hasRole('Gestor') && $this->hasSetorComercial($user);
    }

    protected function isComercialComercial(User $user): bool
    {
        return $user->hasRole('Comercial') && $this->hasSetorComercial($user);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        if (! $user->can('View:AgendaVtComercial')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $hasSetorComercial = $user->setores()->whereRaw('LOWER(setor) = ?', ['comercial'])->exists();
        if (! $hasSetorComercial) {
            return false;
        }

        return $user->hasAnyRole(['Gestor', 'Comercial']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
