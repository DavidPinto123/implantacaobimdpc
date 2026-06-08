<x-filament-panels::page>
    <style>
        .cn-page { display: grid; gap: 1rem; }
        .cn-banner {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
            padding: 1rem 1.1rem; border: 1px solid #dbeafe; border-radius: 1rem;
            background: #eff6ff;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }
        .dark .cn-banner {
            border-color: #161617;
            background: #161617;
        }
        .cn-banner-left { display: flex; gap: .9rem; align-items: flex-start; }
        .cn-banner-icon {
            width: 2.45rem; height: 2.45rem; border-radius: 9999px; display: inline-flex;
            align-items: center; justify-content: center; background: #dbeafe; color: #2563eb; flex-shrink: 0;
        }
        .dark .cn-banner-icon { background: #161617; color: #e5e7eb; }
        .cn-banner-kicker {
            margin: 0; font-size: .75rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #2563eb;
        }
        .dark .cn-banner-kicker { color: #d1d5db; }
        .cn-banner-title { margin: .15rem 0 0; font-size: 1.05rem; font-weight: 800; color: #0f172a; }
        .dark .cn-banner-title { color: #f8fafc; }
        .cn-banner-copy { margin: .2rem 0 0; max-width: 78ch; font-size: .9rem; color: #475569; }
        .dark .cn-banner-copy { color: #cbd5e1; }

        .cn-summary { display: grid; gap: .75rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .cn-summary-card {
            display: flex; gap: .9rem; align-items: center; padding: 1rem;
            border: 1px solid #e5e7eb; border-radius: 1rem; background: #ffffff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        }
        .dark .cn-summary-card { background: #161617; border-color: #161617; }
        .cn-summary-icon {
            width: 3rem; height: 3rem; border-radius: .95rem; display: inline-flex;
            align-items: center; justify-content: center; flex-shrink: 0;
        }
        .cn-summary-card--blue .cn-summary-icon { background: #dbeafe; color: #1d4ed8; }
        .cn-summary-card--amber .cn-summary-icon { background: #fef3c7; color: #b45309; }
        .cn-summary-card--slate .cn-summary-icon { background: #e2e8f0; color: #334155; }
        .dark .cn-summary-card--blue .cn-summary-icon { background: #161617; color: #e5e7eb; }
        .dark .cn-summary-card--amber .cn-summary-icon { background: #161617; color: #fbbf24; }
        .dark .cn-summary-card--slate .cn-summary-icon { background: #161617; color: #cbd5e1; }
        .cn-summary-label {
            margin: 0; font-size: .76rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: #334155;
        }
        .dark .cn-summary-label { color: #e2e8f0; }
        .cn-summary-value { margin: .1rem 0 0; font-size: 1.6rem; line-height: 1; font-weight: 900; color: #0f172a; }
        .dark .cn-summary-value { color: #f8fafc; }
        .cn-summary-subtitle { margin: .25rem 0 0; font-size: .8rem; color: #64748b; }
        .dark .cn-summary-subtitle { color: #94a3b8; }

        .cn-layout { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        .cn-filters, .cn-feed {
            border: 1px solid #e5e7eb; border-radius: 1rem; background: #ffffff;
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.05); overflow: hidden;
        }
        .dark .cn-filters, .dark .cn-feed { background: #161617; border-color: #161617; }
        .cn-panel-head {
            padding: 1rem 1.1rem; border-bottom: 1px solid #e5e7eb;
            display: flex; justify-content: space-between; gap: .9rem; flex-wrap: wrap; align-items: center;
        }
        .dark .cn-panel-head { border-bottom-color: #161617; }
        .cn-panel-title { margin: 0; font-size: .98rem; font-weight: 800; color: #0f172a; }
        .dark .cn-panel-title { color: #f8fafc; }
        .cn-panel-copy { margin: .15rem 0 0; font-size: .8rem; color: #64748b; }
        .dark .cn-panel-copy { color: #94a3b8; }
        .cn-panel-body { padding: 1rem 1.1rem 1.1rem; }
        .cn-field { display: grid; gap: .35rem; margin-bottom: .95rem; }
        .cn-field label {
            font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #475569;
        }
        .dark .cn-field label { color: #cbd5e1; }
        .cn-input, .cn-select {
            width: 100%; border: 1px solid #dbe1ea; border-radius: .75rem; background: #f8fafc;
            color: #0f172a; padding: .8rem .9rem; font-size: .9rem; outline: none;
        }
        .dark .cn-input, .dark .cn-select {
            border-color: #343436;
            background: #202124;
            color: #f3f4f6;
        }
        .cn-select option {
            background: #ffffff;
            color: #111827;
        }
        .dark .cn-select option {
            background: #2a2a2a;
            color: #f3f4f6;
        }
        .dark .cn-input:focus, .dark .cn-select:focus {
            border-color: #52525b;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, .06);
        }
        .cn-input:focus, .cn-select:focus { border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(59, 130, 246, .12); }
        .cn-filter-actions { display: flex; justify-content: space-between; gap: .75rem; margin-top: 1rem; }
        .cn-button {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            padding: .75rem 1rem; border-radius: .75rem; border: 1px solid transparent;
            font-size: .88rem; font-weight: 700; cursor: pointer; transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
            text-decoration: none;
        }
        .cn-button:hover { transform: translateY(-1px); }
        .cn-button-primary { background: #1d4ed8; color: white; }
        .cn-button-soft { background: #f8fafc; border-color: #e2e8f0; color: #334155; }
        .dark .cn-button-soft {
            background: #202124;
            border-color: #343436;
            color: #f3f4f6;
        }

        .cn-feed-toolbar {
            display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; flex-wrap: wrap;
            padding: 1rem 1.1rem; border-bottom: 1px solid #e5e7eb;
        }
        .dark .cn-feed-toolbar { border-bottom-color: #161617; }
        .cn-feed-heading { display: flex; flex-direction: column; gap: .2rem; }
        .cn-feed-heading h2 { margin: 0; font-size: 1rem; font-weight: 900; color: #0f172a; }
        .dark .cn-feed-heading h2 { color: #f8fafc; }
        .cn-feed-heading p { margin: 0; font-size: .82rem; color: #64748b; }
        .dark .cn-feed-heading p { color: #94a3b8; }
        .cn-feed-controls { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
        .cn-sort { min-width: 190px; }
        .cn-list { padding: .9rem 1.1rem 1.1rem; display: grid; gap: .9rem; }
        .cn-item {
            display: grid; grid-template-columns: auto auto 1fr; gap: .95rem; align-items: flex-start;
            padding: 1.05rem 1.1rem; border: 1px solid #e5e7eb; border-radius: 1.25rem;
            background: #ffffff;
            position: relative; overflow: hidden;
            box-shadow: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .cn-item:hover {
            transform: translateY(-1px);
            border-color: #dbeafe;
        }
        .dark .cn-item { border-color: #2a2a2b; background: #1b1b1c; box-shadow: none; }
        .dark .cn-item:hover { border-color: #343436; }
        .cn-item::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: transparent;
        }
        .cn-item.is-unread::before { background: #374151; }
        .dark .cn-item.is-unread::before { background: #2a2a2b; }
        .cn-item-dot {
            width: .6rem; height: .6rem; border-radius: 9999px; margin-top: .8rem;
            background: #cbd5e1; box-shadow: 0 0 0 6px rgba(203, 213, 225, .18);
        }
        .cn-item.is-unread .cn-item-dot { background: #2563eb; }
        .dark .cn-item.is-unread .cn-item-dot { background: #d1d5db; }
        .cn-icon {
            width: 3rem; height: 3rem; border-radius: 1rem; display: inline-flex;
            align-items: center; justify-content: center; flex-shrink: 0;
            background: #dbeafe; color: #1d4ed8;
            box-shadow: none;
        }
        .cn-type-financeiro { background: #dcfce7; color: #15803d; }
        .cn-type-agenda { background: #e0e7ff; color: #4338ca; }
        .cn-type-usuario { background: #dbeafe; color: #2563eb; }
        .cn-type-documentos { background: #e2e8f0; color: #334155; }
        .cn-type-sistema { background: #e5e7eb; color: #475569; }
        .dark .cn-type-financeiro { background: #161617; color: #86efac; }
        .dark .cn-type-agenda { background: #161617; color: #e5e7eb; }
        .dark .cn-type-usuario { background: #161617; color: #e5e7eb; }
        .dark .cn-type-documentos { background: #161617; color: #cbd5e1; }
        .dark .cn-type-sistema { background: #161617; color: #cbd5e1; }
        .cn-content { min-width: 0; display: grid; gap: .6rem; }
        .cn-topline { display: flex; align-items: flex-start; justify-content: space-between; gap: .9rem; flex-wrap: wrap; }
        .cn-title { display: flex; align-items: center; gap: .5rem; min-width: 0; flex-wrap: wrap; }
        .cn-title strong { font-size: 1rem; line-height: 1.25; font-weight: 900; color: #0f172a; }
        .dark .cn-title strong { color: #f8fafc; }
        .cn-title .badge { font-size: .7rem; font-weight: 800; border-radius: 9999px; padding: .23rem .58rem; letter-spacing: .01em; }
        .cn-badge-unread { background: #dbeafe; color: #1d4ed8; }
        .cn-badge-read { background: #dcfce7; color: #166534; }
        .dark .cn-badge-unread { background: #2a2a2b; color: #e5e7eb; }
        .dark .cn-badge-read { background: #2a2a2b; color: #86efac; }
        .cn-date { font-size: .78rem; color: #64748b; white-space: nowrap; padding-top: .15rem; }
        .dark .cn-date { color: #94a3b8; }
        .cn-body {
            margin: 0; font-size: .92rem; line-height: 1.55; color: #475569;
            max-width: 82ch;
        }
        .dark .cn-body { color: #cbd5e1; }
        .cn-footer {
            display: flex; align-items: center; justify-content: space-between; gap: .9rem; flex-wrap: wrap;
            padding-top: .2rem;
        }
        .cn-meta-chip {
            display: inline-flex; align-items: center; gap: .4rem; padding: .25rem .6rem; border-radius: 9999px;
            font-size: .74rem; font-weight: 800; background: #f8fafc; color: #334155; border: 1px solid #e2e8f0;
        }
        .dark .cn-meta-chip { background: #161617; color: #cbd5e1; border-color: #161617; }
        .cn-actions {
            display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; justify-content: flex-end;
            margin-left: auto;
        }
        .cn-action {
            display: inline-flex; align-items: center; gap: .35rem; padding: .48rem .75rem; border-radius: .7rem;
            border: 1px solid #dbe1ea; background: #ffffff; color: #334155; font-size: .82rem; font-weight: 700; text-decoration: none; cursor: pointer;
            box-shadow: none;
        }
        .dark .cn-action { background: #161617; border-color: #161617; color: #e2e8f0; }
        .dark .cn-action-primary { background: #161617; border-color: #2a2a2b; color: #e5e7eb; }
        .cn-action-primary { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
        .cn-action-success { border-color: #86efac; background: #f0fdf4; color: #166534; }
        .cn-action:hover { transform: translateY(-1px); }
        .cn-empty {
            display: grid; place-items: center; min-height: 320px; text-align: center; padding: 2rem; color: #64748b;
        }
        .cn-empty-card { max-width: 28rem; display: grid; gap: .85rem; place-items: center; }
        .cn-empty-icon {
            width: 4rem; height: 4rem; border-radius: 9999px; background: #eff6ff; color: #2563eb;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .cn-pagination {
            display: flex; align-items: center; justify-content: flex-end; gap: .75rem; flex-wrap: wrap;
            padding: 0 1.1rem 1rem;
        }
        .cn-per-page { min-width: 120px; }
        @media (min-width: 1024px) {
            .cn-layout { grid-template-columns: 320px minmax(0, 1fr); align-items: start; }
            .cn-filters { position: sticky; top: 1rem; }
        }
        @media (min-width: 768px) { .cn-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    </style>

    @php
        $notifications = $this->getNotifications();
        $cards = $this->getOverviewCards();
        $resultCount = $notifications->total();
    @endphp

    <div class="cn-page" wire:poll.30s>
        <div class="cn-banner">
            <div class="cn-banner-left">
                <div class="cn-banner-icon">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6" />
                </div>
                <div>
                    <p class="cn-banner-kicker">Histórico preservado</p>
                    <h1 class="cn-banner-title">Nenhuma notificação é apagada</h1>
                    <p class="cn-banner-copy">
                        O botão “Limpar tudo” apenas marca como lidas na caixa do sino. Todo o histórico segue disponível nesta central.
                    </p>
                </div>
            </div>
        </div>

        <section class="cn-summary">
            @foreach ($cards as $card)
                <article class="cn-summary-card cn-summary-card--{{ $card['color'] }}">
                    <div class="cn-summary-icon">
                        <x-filament::icon :icon="$card['icon']" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="cn-summary-label">{{ $card['label'] }}</p>
                        <p class="cn-summary-value">{{ $card['value'] }}</p>
                        <p class="cn-summary-subtitle">{{ $card['subtitle'] }}</p>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="cn-layout">
            <aside class="cn-filters">
                <div class="cn-panel-head">
                    <div>
                        <h2 class="cn-panel-title">Filtros</h2>
                        <p class="cn-panel-copy">Atualização automática conforme você altera os campos.</p>
                    </div>
                </div>

                <div class="cn-panel-body">
                    <div class="cn-field">
                        <label for="cn-status">Status</label>
                        <select id="cn-status" class="cn-select" wire:model.live="status">
                            @foreach ($this->getStatusOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cn-field">
                        <label for="cn-type">Tipo</label>
                        <select id="cn-type" class="cn-select" wire:model.live="type">
                            @foreach ($this->getTypeOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cn-field">
                        <label for="cn-period">Período</label>
                        <select id="cn-period" class="cn-select" wire:model.live="period">
                            @foreach ($this->getPeriodOptions() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cn-field">
                        <label for="cn-search">Busca</label>
                        <input id="cn-search" type="search" class="cn-input" placeholder="Buscar por título ou conteúdo..." wire:model.live.debounce.350ms="search" />
                    </div>
                    <div class="cn-filter-actions">
                        <button type="button" class="cn-button cn-button-soft" wire:click="limparFiltros">Limpar filtros</button>
                    </div>
                </div>
            </aside>

            <main class="cn-feed">
                <div class="cn-feed-toolbar">
                    <div class="cn-feed-heading">
                        <h2>Notificações</h2>
                        <p>{{ number_format($resultCount, 0, ',', '.') }} resultados encontrados</p>
                    </div>
                    <div class="cn-feed-controls">
                        <div class="cn-field" style="margin-bottom:0; min-width: 210px;">
                            <label for="cn-sort">Ordenar por</label>
                            <select id="cn-sort" class="cn-select cn-sort" wire:model.live="sort">
                                @foreach ($this->getSortOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="cn-list">
                    @forelse ($notifications as $notification)
                        @php
                            $typeMeta = $this->getTypeMeta($notification);
                            $actionUrl = $this->getNotificationActionUrl($notification);
                            $isUnread = is_null($notification->read_at);
                        @endphp

                        <article class="cn-item {{ $isUnread ? 'is-unread' : '' }}" wire:key="notification-{{ $notification->getKey() }}">
                            <div class="cn-item-dot"></div>
                            <div class="cn-icon cn-type-{{ $typeMeta['color'] }}">
                                <x-filament::icon :icon="$typeMeta['icon']" class="h-5 w-5" />
                            </div>
                            <div class="cn-content">
                                <div class="cn-topline">
                                    <div class="cn-title">
                                        <strong>{{ $this->getNotificationTitle($notification) }}</strong>
                                        <span class="badge {{ $isUnread ? 'cn-badge-unread' : 'cn-badge-read' }}">
                                            {{ $isUnread ? 'Nao lida' : 'Lida' }}
                                        </span>
                                        <span class="badge" style="background:#f8fafc;color:#475569;border:1px solid #e2e8f0;">
                                            {{ $typeMeta['label'] }}
                                        </span>
                                    </div>
                                    <div class="cn-date">
                                        {{ $notification->created_at?->diffForHumans() ?? '-' }}
                                    </div>
                                </div>

                                <p class="cn-body">{{ \Illuminate\Support\Str::limit($this->getNotificationBody($notification), 190) }}</p>

                                <div class="cn-footer">
                                    <span class="cn-meta-chip">
                                        <x-filament::icon icon="heroicon-o-calendar-days" class="h-3.5 w-3.5" />
                                        {{ $notification->created_at?->format('d/m/Y H:i') ?? '-' }}
                                    </span>

                                    <div class="cn-actions">
                                        @if (filled($actionUrl))
                                            <a href="{{ $actionUrl }}" class="cn-action cn-action-primary" target="_blank" rel="noreferrer">
                                                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4" />
                                                Abrir
                                            </a>
                                        @endif

                                        @if ($isUnread)
                                            <button type="button" class="cn-action cn-action-success" wire:click="markAsRead('{{ $notification->getKey() }}')">
                                                <x-filament::icon icon="heroicon-o-check" class="h-4 w-4" />
                                                Marcar como lida
                                            </button>
                                        @else
                                            <button type="button" class="cn-action" wire:click="markAsUnread('{{ $notification->getKey() }}')">
                                                <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" />
                                                Marcar como nao lida
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="cn-empty">
                            <div class="cn-empty-card">
                                <div class="cn-empty-icon">
                                    <x-filament::icon icon="heroicon-o-bell-slash" class="h-8 w-8" />
                                </div>
                                <div>
                                    <h3 style="margin:0;font-size:1.05rem;font-weight:900;color:#0f172a;">Nenhuma notificação encontrada</h3>
                                    <p style="margin:.35rem 0 0;font-size:.9rem;line-height:1.45;">
                                        Ajuste os filtros ou aguarde novas notificações chegarem ao seu histórico.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="cn-pagination">
                    <div class="cn-field cn-per-page" style="margin-bottom:0; min-width: 120px;">
                        <label for="cn-per-page">Por página</label>
                        <select id="cn-per-page" class="cn-select" wire:model.live="perPage">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div style="padding:0 1.1rem 1rem;">
                    {{ $notifications->links() }}
                </div>
            </main>
        </section>
    </div>
</x-filament-panels::page>
