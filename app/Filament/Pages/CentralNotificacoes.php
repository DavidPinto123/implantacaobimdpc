<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification as DatabaseNotificationModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use UnitEnum;

class CentralNotificacoes extends Page
{
    use WithPagination;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static UnitEnum|string|null $navigationGroup = 'Central de Notificações';

    protected static ?string $navigationLabel = 'Central de notificações';

    protected static ?string $title = 'Central de notificações';

    protected static ?string $slug = 'central-de-notificacoes';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.central-notificacoes';

    public string $search = '';

    public string $status = 'all';

    public string $type = 'all';

    public string $period = '30';

    public string $sort = 'recent';

    public int $perPage = 10;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $count = $user->notifications()
            ->where('data->format', 'filament')
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('marcarTodasComoLidas')
                ->label('Marcar todas como lidas')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action('markAllAsRead'),

            Action::make('limparFiltros')
                ->label('Limpar filtros')
                ->icon('heroicon-o-funnel')
                ->color('gray')
                ->action('limparFiltros'),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingPeriod(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function limparFiltros(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->type = 'all';
        $this->period = '30';
        $this->sort = 'recent';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function markAllAsRead(): void
    {
        $updated = $this->baseNotificationsQuery()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $notification = FilamentNotification::make()->title(
            $updated > 0 ? 'Notificações marcadas como lidas' : 'Nenhuma notificação pendente'
        );

        if ($updated > 0) {
            $notification->success();
        } else {
            $notification->info();
        }

        $notification->send();

        $this->resetPage();
    }

    public function markAsRead(string $notificationId): void
    {
        $updated = $this->baseNotificationsQuery()
            ->whereKey($notificationId)
            ->update(['read_at' => now()]);

        if ($updated > 0) {
            FilamentNotification::make()
                ->title('Notificação marcada como lida')
                ->success()
                ->send();
        }

        $this->resetPage();
    }

    public function markAsUnread(string $notificationId): void
    {
        $updated = $this->baseNotificationsQuery()
            ->whereKey($notificationId)
            ->update(['read_at' => null]);

        if ($updated > 0) {
            FilamentNotification::make()
                ->title('Notificação marcada como não lida')
                ->success()
                ->send();
        }

        $this->resetPage();
    }

    public function getNotifications(): LengthAwarePaginator
    {
        return $this->filteredNotificationsQuery()
            ->paginate($this->perPage);
    }

    /**
     * @return array<int, array{label:string,value:string,subtitle:string,color:string}>
     */
    public function getOverviewCards(): array
    {
        $query = $this->baseNotificationsQuery();

        $total = (clone $query)->count();
        $naoLidas = (clone $query)->whereNull('read_at')->count();
        $lidas = (clone $query)->whereNotNull('read_at')->count();

        return [
            [
                'label' => 'Total de notificações',
                'value' => (string) $total,
                'subtitle' => 'Desde o inicio',
                'color' => 'blue',
                'icon' => 'heroicon-o-bell',
            ],
            [
                'label' => 'Não lidas',
                'value' => (string) $naoLidas,
                'subtitle' => 'Requerem sua atenção',
                'color' => 'amber',
                'icon' => 'heroicon-o-envelope',
            ],
            [
                'label' => 'Arquivadas (lidas)',
                'value' => (string) $lidas,
                'subtitle' => 'Histórico preservado',
                'color' => 'slate',
                'icon' => 'heroicon-o-archive-box',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getStatusOptions(): array
    {
        return [
            'all' => 'Todos',
            'unread' => 'Não lidas',
            'read' => 'Lidas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getTypeOptions(): array
    {
        return [
            'all' => 'Todos',
            'agenda' => 'Agenda',
            'financeiro' => 'Financeiro',
            'usuario' => 'Usuarios',
            'documentos' => 'Documentos',
            'sistema' => 'Sistema',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getPeriodOptions(): array
    {
        return [
            '7' => 'Ultimos 7 dias',
            '30' => 'Ultimos 30 dias',
            '90' => 'Ultimos 90 dias',
            '365' => 'Ultimos 12 meses',
            'all' => 'Desde o inicio',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSortOptions(): array
    {
        return [
            'recent' => 'Mais recentes',
            'oldest' => 'Mais antigas',
            'unread_first' => 'Não lidas primeiro',
        ];
    }

    protected function baseNotificationsQuery(): Builder
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return DatabaseNotificationModel::query()->whereRaw('1 = 0');
        }

        return $user->notifications()
            ->getQuery()
            ->where('data->format', 'filament');
    }

    protected function filteredNotificationsQuery(): Builder
    {
        $query = $this->baseNotificationsQuery();

        if ($this->status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->status === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($this->period !== 'all') {
            $days = (int) $this->period;

            $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
        }

        if (filled($this->search)) {
            $term = '%'.trim($this->search).'%';

            $query->where(function (Builder $query) use ($term): void {
                $query->where('data->title', 'like', $term)
                    ->orWhere('data->body', 'like', $term);
            });
        }

        if ($this->type !== 'all') {
            $this->applyTypeFilter($query, $this->type);
        }

        return match ($this->sort) {
            'oldest' => $query->orderBy('created_at'),
            'unread_first' => $query
                ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
                ->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    protected function applyTypeFilter(Builder $query, string $type): void
    {
        $keywords = match ($type) {
            'agenda' => ['agenda', 'vt', 'visita', 'cronograma', 'ponto', 'obra', 'projeto'],
            'financeiro' => ['nf', 'nota fiscal', 'fiscal', 'pagamento', 'recebimento', 'financeiro'],
            'usuario' => ['usuario', 'perfil', 'acesso', 'senha', 'convite'],
            'documentos' => ['documento', 'arquivo', 'anexo', 'pdf', 'foto'],
            default => [],
        };

        if ($keywords === []) {
            return;
        }

        $query->where(function (Builder $query) use ($keywords): void {
            foreach ($keywords as $index => $keyword) {
                $term = '%'.$keyword.'%';

                if ($index === 0) {
                    $query->where(function (Builder $query) use ($term): void {
                        $query->where('data->title', 'like', $term)
                            ->orWhere('data->body', 'like', $term);
                    });

                    continue;
                }

                $query->orWhere(function (Builder $query) use ($term): void {
                    $query->where('data->title', 'like', $term)
                        ->orWhere('data->body', 'like', $term);
                });
            }
        });
    }

    public function getNotificationTitle(DatabaseNotificationModel $record): string
    {
        return (string) data_get($record->data, 'title', 'Sem título');
    }

    public function getNotificationBody(DatabaseNotificationModel $record): string
    {
        $body = (string) data_get($record->data, 'body', '');

        return filled($body) ? $body : 'Sem conteúdo';
    }

    public function getNotificationType(DatabaseNotificationModel $record): string
    {
        $titleBody = Str::lower(trim(implode(' ', array_filter([
            (string) data_get($record->data, 'title', ''),
            (string) data_get($record->data, 'body', ''),
        ]))));

        if (Str::contains($titleBody, ['nota fiscal', 'nf', 'fiscal', 'pagamento', 'recebimento', 'financeiro'])) {
            return 'financeiro';
        }

        if (Str::contains($titleBody, ['agenda', 'vt', 'visita', 'cronograma', 'ponto', 'obra', 'projeto'])) {
            return 'agenda';
        }

        if (Str::contains($titleBody, ['usuario', 'perfil', 'acesso', 'senha', 'convite'])) {
            return 'usuario';
        }

        if (Str::contains($titleBody, ['documento', 'arquivo', 'anexo', 'pdf', 'foto'])) {
            return 'documentos';
        }

        return 'sistema';
    }

    /**
     * @return array{label:string,color:string,icon:string}
     */
    public function getTypeMeta(DatabaseNotificationModel $record): array
    {
        return match ($this->getNotificationType($record)) {
            'financeiro' => [
                'label' => 'Financeiro',
                'color' => 'financeiro',
                'icon' => 'heroicon-o-banknotes',
            ],
            'agenda' => [
                'label' => 'Agenda',
                'color' => 'agenda',
                'icon' => 'heroicon-o-calendar-days',
            ],
            'usuario' => [
                'label' => 'Usuários',
                'color' => 'usuario',
                'icon' => 'heroicon-o-user-group',
            ],
            'documentos' => [
                'label' => 'Documentos',
                'color' => 'documentos',
                'icon' => 'heroicon-o-document-text',
            ],
            default => [
                'label' => 'Sistema',
                'color' => 'sistema',
                'icon' => 'heroicon-o-bell',
            ],
        };
    }

    public function getNotificationActionUrl(DatabaseNotificationModel $record): ?string
    {
        $title = (string) data_get($record->data, 'title', '');

        // Não mostrar ação para estas notificações
        if (in_array($title, ['Item liberado para fornecedor', 'Nova AS gerada'], true)) {
            return null;
        }

        $actions = data_get($record->data, 'actions', []);

        if (! is_array($actions)) {
            return null;
        }

        return $this->extractFirstActionUrl($actions);
    }

    /**
     * @param  array<int, mixed>  $actions
     */
    protected function extractFirstActionUrl(array $actions): ?string
    {
        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $url = data_get($action, 'url');
            if (filled($url)) {
                return (string) $url;
            }

            $nestedActions = data_get($action, 'actions');
            if (is_array($nestedActions)) {
                $nestedUrl = $this->extractFirstActionUrl($nestedActions);

                if (filled($nestedUrl)) {
                    return $nestedUrl;
                }
            }
        }

        return null;
    }
}
