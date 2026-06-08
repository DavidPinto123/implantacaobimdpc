<x-filament-panels::page>
    <style>
        .obx-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.04); }
        .dark .obx-card { background:#111113; border-color:#1f2023; }
        .obx-toolbar { display:flex; justify-content:space-between; gap:10px; padding:12px 16px; border-bottom:1px solid #e5e7eb; align-items:center; flex-wrap:wrap; }
        .dark .obx-toolbar { border-bottom-color:#1f2023; }
        .obx-toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .obx-toolbar-right { display:flex; align-items:center; gap:8px; margin-left:auto; }
        .obx-input { min-height:38px; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px; font-size:12px; background:#fff; color:#111827; }
        .dark .obx-input { background:#17181c; border-color:#374151; color:#e5e7eb; }
        .obx-btn { min-height:38px; padding:0 12px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:12px; cursor:pointer; }
        .dark .obx-btn { background:#17181c; border-color:#374151; color:#e5e7eb; }
        .obx-icon-btn { width:38px; height:38px; border:1px solid #d1d5db; border-radius:10px; background:#fff; color:#6b7280; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; position:relative; }
        .obx-icon-btn:hover { color:#111827; border-color:#9ca3af; }
        .obx-icon-btn.active { color:#FBBA00; border-color:#f3cf6f; background:#fffbeb; }
        .dark .obx-icon-btn { background:#17181c; border-color:#374151; color:#9ca3af; }
        .dark .obx-icon-btn:hover { color:#f3f4f6; }
        .dark .obx-icon-btn.active { background:rgba(251,186,0,.1); border-color:rgba(251,186,0,.35); color:#fbbf24; }
        .obx-table-wrap { overflow:auto; max-height:76vh; }
        .obx-table { width:100%; border-collapse:collapse; font-size:13px; }
        .obx-table th { position:sticky; top:0; z-index:5; background:#f9fafb; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; font-size:11px; font-weight:700; padding:9px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; text-align:left; }
        .obx-table td { padding:9px 10px; border-bottom:1px solid #f3f4f6; white-space:nowrap; color:#374151; font-size:12px; }
        .obx-table .group-head th { top:0; z-index:7; text-align:center; color:#111827; background:#f3f4f6; }
        .obx-table .col-head th { top:32px; z-index:6; }
        .obx-row:hover { background:#fffbeb; }
        .dark .obx-table th { background:#0d0e11; border-bottom-color:#1f2023; color:#9ca3af; }
        .dark .obx-table .group-head th { background:#15161a; color:#e5e7eb; }
        .dark .obx-table td { border-bottom-color:#1f2023; color:#d1d5db; }
        .dark .obx-row:hover { background:rgba(251,186,0,.08); }
        .obx-actions { display:inline-flex; align-items:center; gap:8px; }
        .obx-row-icon { width:1.45rem; height:1.45rem; border:none; background:transparent; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; color:#6b7280; text-decoration:none; }
        .obx-row-icon:hover { background:#f3f4f6; color:#111827; }
        .obx-row-icon-danger { color:#dc2626; }
        .obx-row-icon-danger:hover { background:#fef2f2; color:#b91c1c; }
        .dark .obx-row-icon { color:#9ca3af; }
        .dark .obx-row-icon:hover { background:#1f2937; color:#e5e7eb; }
        .dark .obx-row-icon-danger { color:#f87171; }
        .dark .obx-row-icon-danger:hover { background:rgba(220,38,38,.12); color:#fca5a5; }
        .obx-status { min-height:32px; border:1px solid #d1d5db; border-radius:8px; padding:5px 8px; font-size:12px; background:#fff; min-width:160px; }
        .obx-col-frozen { position:sticky !important; z-index:9; background:#fffbeb !important; box-shadow:2px 0 4px -1px rgba(0,0,0,.08); }
        .dark .obx-col-frozen { background:rgba(251,186,0,.08) !important; box-shadow:2px 0 4px -1px rgba(0,0,0,.35); }
        .obx-table .group-head th.obx-col-frozen,
        .obx-table .col-head th.obx-col-frozen { z-index:10; }
    </style>

    <div
        class="obx-card"
        x-data="{
            filterModal:false,
            columnModal:false,
            frozenCols: JSON.parse(localStorage.getItem('fi_frozen_' + window.location.pathname) || '[]'),
            normalize(name) {
                return (name || '').replace(/[._]/g, '-');
            },
            sortFrozenByDom(cols) {
                const allHeaders = [...this.$el.querySelectorAll('.obx-th-filter')];
                return [...cols].sort((a, b) => {
                    const elA = this.$el.querySelector('.obx-th-' + this.normalize(a));
                    const elB = this.$el.querySelector('.obx-th-' + this.normalize(b));
                    if (!elA || !elB) return 0;
                    return allHeaders.indexOf(elA) - allHeaders.indexOf(elB);
                });
            },
            isFrozen(name) {
                return this.frozenCols.includes(name);
            },
            toggleFreeze(name) {
                const idx = this.frozenCols.indexOf(name);
                if (idx > -1) {
                    this.frozenCols.splice(idx, 1);
                } else {
                    this.frozenCols.push(name);
                }
                this.frozenCols = this.sortFrozenByDom(this.frozenCols);
                localStorage.setItem('fi_frozen_' + window.location.pathname, JSON.stringify(this.frozenCols));
                this.$nextTick(() => this.applyFreeze());
            },
            clearFreeze() {
                this.frozenCols = [];
                localStorage.removeItem('fi_frozen_' + window.location.pathname);
                this.$nextTick(() => this.applyFreeze());
            },
            applyFreeze() {
                const table = this.$el.querySelector('.obx-table');
                if (!table) return;

                table.querySelectorAll('.obx-col-frozen').forEach((el) => {
                    el.classList.remove('obx-col-frozen');
                    el.style.removeProperty('left');
                });

                let offset = 0;

                const actionHeader = table.querySelector('thead tr.group-head th:first-child');
                if (actionHeader) {
                    actionHeader.classList.add('obx-col-frozen');
                    actionHeader.style.left = '0px';
                    const actionWidth = actionHeader.getBoundingClientRect().width;

                    table.querySelectorAll('tbody tr').forEach((row) => {
                        const firstCell = row.querySelector('td:first-child');
                        if (firstCell) {
                            firstCell.classList.add('obx-col-frozen');
                            firstCell.style.left = '0px';
                        }
                    });

                    offset += actionWidth;
                }

                this.frozenCols.forEach((name) => {
                    const cls = this.normalize(name);
                    const header = table.querySelector('.obx-th-' + cls);
                    if (!header) return;

                    const width = header.getBoundingClientRect().width;
                    header.classList.add('obx-col-frozen');
                    header.style.left = offset + 'px';

                    table.querySelectorAll('.obx-td-' + cls).forEach((cell) => {
                        cell.classList.add('obx-col-frozen');
                        cell.style.left = offset + 'px';
                    });

                    offset += width;
                });
            },
            init() {
                this.frozenCols = this.sortFrozenByDom(this.frozenCols);
                this.$nextTick(() => this.applyFreeze());
                if (window.Livewire) {
                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => this.$nextTick(() => this.applyFreeze()));
                    });
                }
            }
        }"
    >
        @php
            $activeFiltersCount = 0;

            foreach ($this->selectFilterValues as $values) {
                if (is_array($values) && count($values) > 0) {
                    $activeFiltersCount++;
                }
            }

            foreach ($this->dateFilterValues as $range) {
                if (filled($range['from'] ?? null) || filled($range['until'] ?? null)) {
                    $activeFiltersCount++;
                }
            }
        @endphp

        <div class="obx-toolbar">
            <div class="obx-toolbar-left">
                <a href="{{ \App\Filament\Resources\Obras\ObrasResource::getUrl('create') }}" class="fi-btn fi-btn-color-primary fi-btn-size-md">+ Criar obra</a>
            </div>

            <div class="obx-toolbar-right">
                <input class="obx-input" type="text" placeholder="Pesquisar..." wire:model.live.debounce.300ms="search" style="min-width:260px;">

                <button class="obx-icon-btn {{ $activeFiltersCount > 0 ? 'active' : '' }}" type="button" @click="filterModal = true" title="Filtros">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd"/>
                    </svg>
                    @if ($activeFiltersCount > 0)
                        <span class="fi-filters-badge">{{ $activeFiltersCount }}</span>
                    @endif
                </button>

                <button class="obx-icon-btn" type="button" @click="columnModal = true" title="Colunas">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2.75 3A1.75 1.75 0 0 0 1 4.75v10.5C1 16.216 1.784 17 2.75 17h14.5A1.75 1.75 0 0 0 19 15.25V4.75A1.75 1.75 0 0 0 17.25 3H2.75ZM7 4.5v11H2.75a.25.25 0 0 1-.25-.25V4.75c0-.138.112-.25.25-.25H7Zm1.5 0h3v11h-3v-11Zm4.5 0h4.25c.138 0 .25.112.25.25v10.5a.25.25 0 0 1-.25.25H13v-11Z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="obx-table-wrap">
            <table class="obx-table">
                <thead>
                    <tr class="group-head">
                        <th rowspan="2" style="min-width:84px;">Acoes</th>
                        @foreach($this->getColumnGroupsForView() as $group)
                            <th colspan="{{ $group['count'] }}">{{ $group['label'] }}</th>
                        @endforeach
                    </tr>
                    <tr class="col-head">
                        @foreach($this->getVisibleColumnsFlat() as $column)
                            <th
                                class="obx-th-filter obx-th-{{ str($column['key'])->replace(['.', '_'], '-') }}"
                                wire:key="obx-header-filter-{{ str($column['key'])->replace(['.', '_'], '-') }}"
                            >
                                {{ $column['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->rows as $obra)
                        <tr class="obx-row" wire:key="obra-row-{{ $obra->id }}">
                            <td>
                                <div class="obx-actions">
                                    <a class="obx-row-icon" href="{{ \App\Filament\Resources\Obras\ObrasResource::getUrl('view', ['record' => $obra]) }}" title="Visualizar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 4.5c-3.784 0-7.04 2.113-8.5 5.5 1.46 3.387 4.716 5.5 8.5 5.5 3.784 0 7.04-2.113 8.5-5.5-1.46-3.387-4.716-5.5-8.5-5.5Zm0 9.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/>
                                            <path d="M10 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                                        </svg>
                                    </a>
                                    <a class="obx-row-icon" href="{{ \App\Filament\Resources\Obras\ObrasResource::getUrl('edit', ['record' => $obra]) }}" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="m13.5 3.5 3 3L7.25 15.75l-3.5.5.5-3.5L13.5 3.5Zm1.06-1.06a1.5 1.5 0 0 1 2.12 0l1.88 1.88a1.5 1.5 0 0 1 0 2.12l-.94.94-3-3 .94-.94Z"/>
                                        </svg>
                                    </a>
                                    <button class="obx-row-icon obx-row-icon-danger" type="button" title="Excluir" wire:click="deleteObra({{ $obra->id }})" wire:confirm="Deseja excluir esta obra?">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.5 2a1 1 0 0 0-1 1V4H5a.75.75 0 0 0 0 1.5h.53l.65 9.09A2 2 0 0 0 8.17 16.5h3.66a2 2 0 0 0 1.99-1.91l.65-9.09H15a.75.75 0 0 0 0-1.5h-2.5V3a1 1 0 0 0-1-1h-3Zm2.5 3.5a.75.75 0 0 0-1.5 0v7a.75.75 0 0 0 1.5 0v-7Zm-3 0a.75.75 0 0 0-1.5 0v7a.75.75 0 0 0 1.5 0v-7Zm6.5.75a.75.75 0 0 0-1.5 0v6.25a.75.75 0 0 0 1.5 0V6.25Z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            @foreach($this->getVisibleColumnsFlat() as $column)
                                <td class="obx-td-{{ str($column['key'])->replace(['.', '_'], '-') }}">
                                    @if($column['type'] === 'status_select')
                                        <select class="obx-status" wire:change="updateStatus({{ $obra->id }}, $event.target.value)">
                                            @foreach($this->getStatusOptions() as $statusValue => $statusLabel)
                                                <option value="{{ $statusValue }}" @selected($obra->status === $statusValue)>{{ $statusLabel }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        {{ $this->formatCell($obra, $column) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($this->getVisibleColumnsFlat()) + 1 }}" style="text-align:center; padding:20px; color:#9ca3af;">Nenhuma obra encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <template x-teleport="body">
            <div
                x-show="filterModal"
                x-transition.opacity.duration.150ms
                class="fi-colmgr-overlay"
                @click.self="filterModal = false"
                @keydown.escape.window="filterModal = false"
                style="display:none;"
                x-cloak
            >
                <div class="fi-colmgr-modal" @click.stop>
                    <div class="fi-colmgr-head">
                        <span>Filtros</span>
                        <div class="fi-colmgr-head-actions">
                            <button type="button" class="fi-colmgr-reset-btn" wire:click="resetFilters">
                                Limpar filtros
                            </button>
                            <button type="button" @click="filterModal = false" class="fi-colmgr-close-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="fi-colmgr-body">
                        @foreach($this->getFilterGroupsForModal() as $groupLabel => $filters)
                            <div class="fi-colmgr-group">
                                <div class="fi-colmgr-group-head">
                                    <div class="fi-colmgr-group-head-inner">
                                        <span class="fi-colmgr-group-label">{{ $groupLabel }}</span>
                                    </div>
                                </div>
                                <div class="fi-colmgr-group-items" style="display:block;">
                                    @foreach($filters as $filter)
                                        <div class="fi-filtmgr-row">
                                            <span class="fi-filtmgr-label">{{ $filter['label'] }}</span>
                                            <div class="fi-filtmgr-control">
                                                @if($filter['type'] === 'select')
                                                    <div
                                                        x-data="{
                                                            open: false,
                                                            options: @js($filter['options']),
                                                            get values() {
                                                                const values = $wire.selectFilterValues?.['{{ $filter['key'] }}'];
                                                                return Array.isArray(values) ? values : [];
                                                            },
                                                            isSelected(value) {
                                                                return this.values.includes(value);
                                                            },
                                                            toggleValue(value) {
                                                                const values = this.isSelected(value)
                                                                    ? this.values.filter((item) => item !== value)
                                                                    : [...this.values, value];

                                                                $wire.set('selectFilterValues.{{ $filter['key'] }}', values);
                                                            },
                                                            clearValues() {
                                                                $wire.set('selectFilterValues.{{ $filter['key'] }}', []);
                                                            },
                                                            get selectedLabels() {
                                                                return this.values.map((value) => this.options?.[value] ?? value);
                                                            },
                                                        }"
                                                        class="fi-filtmgr-multi"
                                                    >
                                                        <button
                                                            type="button"
                                                            class="fi-filtmgr-multi-trigger"
                                                            @click="open = ! open"
                                                            :class="{ 'is-open': open }"
                                                        >
                                                            <template x-if="selectedLabels.length === 0">
                                                                <span class="fi-filtmgr-multi-placeholder">Todos</span>
                                                            </template>
                                                            <div class="fi-filtmgr-multi-tags" x-show="selectedLabels.length > 0">
                                                                <template x-for="label in selectedLabels" :key="label">
                                                                    <span class="fi-filtmgr-multi-tag" x-text="label"></span>
                                                                </template>
                                                            </div>
                                                            <span class="fi-filtmgr-multi-chevron">▾</span>
                                                        </button>

                                                        <div
                                                            x-show="open"
                                                            x-transition.opacity.duration.120ms
                                                            @click.outside="open = false"
                                                            class="fi-filtmgr-multi-panel"
                                                            x-cloak
                                                        >
                                                            <button
                                                                type="button"
                                                                class="fi-filtmgr-multi-option fi-filtmgr-multi-clear"
                                                                @click="clearValues()"
                                                            >
                                                                Limpar
                                                            </button>

                                                            @foreach ($filter['options'] as $optValue => $optLabel)
                                                                <button
                                                                    type="button"
                                                                    class="fi-filtmgr-multi-option"
                                                                    :class="{ 'active': isSelected(@js($optValue)) }"
                                                                    @click="toggleValue(@js($optValue))"
                                                                >
                                                                    {{ $optLabel }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="fi-filtmgr-date-group">
                                                        <input class="fi-filtmgr-date" type="date" wire:model.live.debounce.800ms="dateFilterValues.{{ $filter['key'] }}.from">
                                                        <span class="fi-filtmgr-date-sep">-</span>
                                                        <input class="fi-filtmgr-date" type="date" wire:model.live.debounce.800ms="dateFilterValues.{{ $filter['key'] }}.until">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="fi-colmgr-foot">
                        <button type="button" @click="filterModal = false" class="fi-colmgr-btn fi-colmgr-btn-apply">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-show="columnModal"
                x-transition.opacity.duration.150ms
                class="fi-colmgr-overlay"
                @click.self="columnModal = false"
                @keydown.escape.window="columnModal = false"
                style="display:none;"
                x-cloak
            >
                <div class="fi-colmgr-modal" @click.stop>
                    <div class="fi-colmgr-head">
                        <span>Configurar Colunas</span>
                        <div class="fi-colmgr-head-actions">
                            <button type="button" class="fi-colmgr-reset-btn" @click="$wire.call('resetVisibleColumns'); clearFreeze();">Resetar</button>
                            <button type="button" @click="columnModal = false" class="fi-colmgr-close-btn">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="fi-colmgr-legend">
                        <div class="fi-colmgr-legend-item">
                            <svg class="fi-colmgr-legend-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 4.5c-3.784 0-7.04 2.113-8.5 5.5 1.46 3.387 4.716 5.5 8.5 5.5 3.784 0 7.04-2.113 8.5-5.5-1.46-3.387-4.716-5.5-8.5-5.5Zm0 9.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/>
                            </svg>
                            <span>Visibilidade</span>
                        </div>
                        <div class="fi-colmgr-legend-item">
                            <svg class="fi-colmgr-legend-icon fi-colmgr-legend-freeze" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.5 8V6a4.5 4.5 0 1 1 9 0v2h.75A1.75 1.75 0 0 1 17 9.75v6.5A1.75 1.75 0 0 1 15.25 18h-10.5A1.75 1.75 0 0 1 3 16.25v-6.5A1.75 1.75 0 0 1 4.75 8h.75Zm1.5 0h6V6a3 3 0 1 0-6 0v2Z" clip-rule="evenodd"/>
                            </svg>
                            <span>Congelar</span>
                        </div>
                    </div>

                    <div class="fi-colmgr-body">
                        @foreach($this->getAllColumnsGroupedForModal() as $group)
                            <div class="fi-colmgr-group">
                                <div class="fi-colmgr-group-head">
                                    <div class="fi-colmgr-group-head-inner">
                                        <span class="fi-colmgr-group-label">{{ $group['label'] }}</span>
                                    </div>
                                </div>
                                <div class="fi-colmgr-group-items">
                                    @foreach($group['columns'] as $column)
                                        @php
                                            $isVisible = in_array($column['key'], $this->visibleColumns, true);
                                            $isLastVisible = count($this->visibleColumns) === 1 && $isVisible;
                                        @endphp
                                        <div class="fi-colmgr-row fi-colmgr-row-standalone">
                                            <button
                                                type="button"
                                                class="fi-colmgr-eye-btn {{ $isVisible ? 'active' : '' }}"
                                                wire:click="toggleColumn('{{ $column['key'] }}')"
                                                @disabled($isLastVisible)
                                                title="{{ $isVisible ? 'Ocultar coluna' : 'Mostrar coluna' }}"
                                            >
                                                <svg class="fi-colmgr-eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 4.5c-3.784 0-7.04 2.113-8.5 5.5 1.46 3.387 4.716 5.5 8.5 5.5 3.784 0 7.04-2.113 8.5-5.5-1.46-3.387-4.716-5.5-8.5-5.5Zm0 9.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z"/>
                                                </svg>
                                            </button>
                                            <span class="fi-colmgr-col-label">{{ $column['label'] }}</span>
                                            <button
                                                type="button"
                                                class="fi-colmgr-freeze-btn"
                                                :class="{ 'active': isFrozen('{{ $column['key'] }}') }"
                                                @click.stop="toggleFreeze('{{ $column['key'] }}')"
                                                title="Congelar coluna"
                                            >
                                                <svg class="fi-colmgr-freeze-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.5 8V6a4.5 4.5 0 1 1 9 0v2h.75A1.75 1.75 0 0 1 17 9.75v6.5A1.75 1.75 0 0 1 15.25 18h-10.5A1.75 1.75 0 0 1 3 16.25v-6.5A1.75 1.75 0 0 1 4.75 8h.75Zm1.5 0h6V6a3 3 0 1 0-6 0v2Z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="fi-colmgr-foot">
                        <button type="button" @click="applyFreeze(); columnModal = false" class="fi-colmgr-btn fi-colmgr-btn-close">
                            Aplicar
                        </button>
                        <button type="button" @click="columnModal = false" class="fi-colmgr-btn fi-colmgr-btn-apply">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-filament-panels::page>
