@php
    use Filament\Tables\Enums\ColumnManagerResetActionPosition;
    use Illuminate\View\ComponentAttributeBag;
@endphp

@props([
    'applyAction',
    'columns' => null,
    'hasReorderableColumns',
    'hasToggleableColumns',
    'headingTag' => 'h3',
    'reorderAnimationDuration' => 300,
    'resetActionPosition' => ColumnManagerResetActionPosition::Header,
])

<div
    x-data="(() => {
        const base = filamentTableColumnManager({
            columns: $wire.entangle('tableColumns'),
            isLive: {{ $applyAction->isVisible() ? 'false' : 'true' }},
        });
        const origInit = base.init;
        const merged = Object.defineProperties({}, Object.getOwnPropertyDescriptors(base));
        Object.assign(merged, {
            modalOpen: false,
            frozenCols: JSON.parse(localStorage.getItem('fi_frozen_' + window.location.pathname) || '[]'),
            isFrozen(name) {
                return this.frozenCols.includes(name);
            },
            sortFrozenByDom(cols) {
                const allHeaders = [...document.querySelectorAll('.fi-ta-header-cell')];
                return [...cols].sort((a, b) => {
                    const elA = document.querySelector('.fi-ta-header-cell-' + a.replace(/[._]/g, '-'));
                    const elB = document.querySelector('.fi-ta-header-cell-' + b.replace(/[._]/g, '-'));
                    if (!elA || !elB) return 0;
                    return allHeaders.indexOf(elA) - allHeaders.indexOf(elB);
                });
            },
            toggleFreeze(name) {
                const idx = this.frozenCols.indexOf(name);
                if (idx > -1) this.frozenCols.splice(idx, 1);
                else this.frozenCols.push(name);
                this.$nextTick(() => {
                    this.frozenCols = this.sortFrozenByDom(this.frozenCols);
                    localStorage.setItem('fi_frozen_' + window.location.pathname, JSON.stringify(this.frozenCols));
                    this.applyFreeze();
                });
            },
            applyFreeze() {
                document.querySelectorAll('.fi-ta-col-frozen').forEach(el => {
                    el.classList.remove('fi-ta-col-frozen');
                    el.style.removeProperty('left');
                });

                if (!this.frozenCols.length) return;

                const table = document.querySelector('.fi-ta-table');
                if (!table) return;

                const thead = table.querySelector('thead');
                if (!thead) return;

                const isVisible = (el) => !!(el && (el.offsetParent || el.getClientRects().length));
                const headerRow = [...thead.querySelectorAll('tr')]
                    .reverse()
                    .find(row => row.querySelector('.fi-ta-header-cell, .fi-ta-actions-header-cell, .fi-ta-empty-header-cell'));
                const groupRow = thead.querySelector('.fi-ta-table-head-groups-row');
                let offset = 0;

                // 1) Freeze action header cell + body action cells
                const actionsHeaderCell = headerRow
                    ? [...headerRow.querySelectorAll('.fi-ta-actions-header-cell, .fi-ta-empty-header-cell')].find(isVisible)
                    : [...thead.querySelectorAll('.fi-ta-actions-header-cell, .fi-ta-empty-header-cell')].find(isVisible);
                if (actionsHeaderCell) {
                    actionsHeaderCell.style.left = '0px';
                    const actionTop = window.getComputedStyle(actionsHeaderCell).top;
                    actionsHeaderCell.style.top = (actionTop && actionTop !== 'auto') ? actionTop : '0px';
                    actionsHeaderCell.classList.add('fi-ta-col-frozen');
                    offset = actionsHeaderCell.getBoundingClientRect().width;

                    // Body action cells
                    table.querySelectorAll('tbody tr').forEach(row => {
                        const firstCell = row.querySelector('td.fi-ta-cell');
                        if (firstCell && firstCell.querySelector('.fi-ta-actions')) {
                            firstCell.style.left = '0px';
                            firstCell.classList.add('fi-ta-col-frozen');
                        }
                    });
                }

                // 2) Freeze each frozen column (header + body)
                this.frozenCols.forEach(name => {
                    const cls = name.replace(/[._]/g, '-');
                    const headers = [...table.querySelectorAll('.fi-ta-header-cell-' + cls)];
                    const header = headers.find(isVisible) ?? headers[0] ?? null;
                    const cells = table.querySelectorAll('.fi-ta-cell-' + cls);
                    if (header) {
                        header.style.left = offset + 'px';
                        const headerTop = window.getComputedStyle(header).top;
                        header.style.top = (headerTop && headerTop !== 'auto') ? headerTop : '0px';
                        header.classList.add('fi-ta-col-frozen');
                        const w = header.getBoundingClientRect().width;
                        cells.forEach(cell => {
                            cell.style.left = offset + 'px';
                            cell.classList.add('fi-ta-col-frozen');
                        });
                        offset += w;
                    }
                });

                // 3) Freeze group header cells that align with frozen columns
                if (groupRow && headerRow) {
                    const headerCells = [...headerRow.querySelectorAll('th')];
                    const groupCells = [...groupRow.querySelectorAll('th')];
                    let colIdx = 0;

                    groupCells.forEach(groupCell => {
                        const colspan = groupCell.colSpan || 1;
                        let allFrozen = true;
                        let firstLeft = null;

                        for (let i = colIdx; i < colIdx + colspan && i < headerCells.length; i++) {
                            if (headerCells[i].classList.contains('fi-ta-col-frozen')) {
                                if (firstLeft === null) firstLeft = headerCells[i].style.left;
                            } else {
                                allFrozen = false;
                            }
                        }

                        if (allFrozen && firstLeft !== null) {
                            groupCell.style.left = firstLeft;
                            const groupTop = window.getComputedStyle(groupCell).top;
                            groupCell.style.top = (groupTop && groupTop !== 'auto') ? groupTop : '0px';
                            groupCell.classList.add('fi-ta-col-frozen');
                        }

                        colIdx += colspan;
                    });
                }

                // 4) Ensure sticky top is correct for frozen header cells on multi-row thead.
                // This keeps frozen headers aligned while vertically scrolling the table body.
                const headerRows = [...thead.querySelectorAll('tr')].filter(row => row.querySelector('th'));
                let accumulatedTop = 0;

                headerRows.forEach((row, rowIndex) => {
                    const frozenHeaderCells = row.querySelectorAll('th.fi-ta-col-frozen');

                    frozenHeaderCells.forEach((cell) => {
                        cell.style.top = accumulatedTop + 'px';
                        // Keep group/header rows above body frozen cells.
                        cell.style.zIndex = String(20 - rowIndex);
                    });

                    const rowHeight = row.getBoundingClientRect().height;
                    accumulatedTop += rowHeight > 0 ? rowHeight : 0;
                });
            },
            closeModal() {
                this.modalOpen = false;
            },
            init() {
                origInit.call(this);
                const self = this;
                this.$nextTick(() => {
                    this.applyFreeze();
                    const dd = this.$el.closest('.fi-dropdown');
                    if (dd) {
                        const ddData = Alpine.$data(dd);
                        if (ddData) {
                            ddData.toggle = function() { self.modalOpen = true; };
                            ddData.open = function() { self.modalOpen = true; };
                        }
                    }
                });

                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => {
                        requestAnimationFrame(() => self.applyFreeze());
                    });
                });
            }
        });
        return merged;
    })()"
>
    <template x-teleport="body">
        <div x-show="modalOpen"
             x-transition.opacity.duration.150ms
             class="fi-colmgr-overlay"
             @click.self="closeModal()"
             @keydown.escape.window="closeModal()"
             style="display:none;">

            <div class="fi-colmgr-modal" @click.stop>

                {{-- Head --}}
                <div class="fi-colmgr-head">
                    <span>Configurar Colunas</span>
                    <div class="fi-colmgr-head-actions">
                        <button type="button"
                                x-on:click="
                                    $wire.call('resetTableColumnManager').then(() => {
                                        resetDeferredColumns();
                                        frozenCols = [];
                                        localStorage.removeItem('fi_frozen_' + window.location.pathname);
                                        localStorage.removeItem('fi_col_widths_' + window.location.pathname);
                                        document.querySelectorAll('.fi-ta-header-cell').forEach(el => {
                                            el.style.removeProperty('width');
                                            el.style.removeProperty('min-width');
                                            el.style.removeProperty('max-width');
                                        });
                                        $nextTick(() => applyFreeze());
                                        new FilamentNotification()
                                            .title('Configuração resetada')
                                            .success()
                                            .duration(3000)
                                            .send();
                                    })
                                "
                                class="fi-colmgr-reset-btn">
                            Resetar
                        </button>
                        <button type="button" @click="closeModal()" class="fi-colmgr-close-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Legenda --}}
                <div class="fi-colmgr-legend">
                    <span class="fi-colmgr-legend-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="fi-colmgr-legend-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        Visibilidade
                    </span>
                    <span class="fi-colmgr-legend-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="fi-colmgr-legend-icon fi-colmgr-legend-freeze">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        Congelar
                    </span>
                </div>

                {{-- Body --}}
                <div class="fi-colmgr-body">
                    <template
                        x-for="(column, index) in columns.filter((c) => !c.isHidden && c.label)"
                        x-bind:key="(column.type === 'group' ? 'g::' : 'c::') + column.name + '_' + index"
                    >
                        <div>
                            {{-- Grupo --}}
                            <template x-if="column.type === 'group'">
                                <div class="fi-colmgr-group">
                                    <div class="fi-colmgr-group-head">
                                        <div class="fi-colmgr-group-head-inner">
                                            @if ($hasToggleableColumns)
                                                <button type="button"
                                                        x-on:click="toggleGroup(column.name)"
                                                        x-bind:disabled="(groupedColumns[column.name] || {}).disabled || false"
                                                        class="fi-colmgr-eye-btn"
                                                        x-bind:class="{
                                                            'active': (groupedColumns[column.name] || {}).checked && !(groupedColumns[column.name] || {}).indeterminate,
                                                            'partial': (groupedColumns[column.name] || {}).indeterminate
                                                        }">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="fi-colmgr-eye-icon"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" /></svg>
                                                </button>
                                            @endif
                                            <span x-html="column.label" class="fi-colmgr-group-label"></span>
                                        </div>
                                    </div>
                                    <div class="fi-colmgr-group-items">
                                        <template
                                            x-for="(gc, gi) in column.columns.filter((c) => !c.isHidden && c.label)"
                                            x-bind:key="'gc::' + gc.name + '_' + gi"
                                        >
                                            <div class="fi-colmgr-row">
                                                @if ($hasToggleableColumns)
                                                    <button type="button"
                                                            x-on:click="toggleColumn(gc.name, column.name)"
                                                            x-bind:disabled="(getColumn(gc.name, column.name) || {}).isToggleable === false"
                                                            class="fi-colmgr-eye-btn"
                                                            x-bind:class="{ 'active': (getColumn(gc.name, column.name) || {}).isToggled }">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="fi-colmgr-eye-icon"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" /></svg>
                                                    </button>
                                                @endif
                                                <span x-html="gc.label" class="fi-colmgr-col-label"></span>
                                                <button type="button"
                                                        x-on:click.stop="toggleFreeze(gc.name)"
                                                        title="Congelar coluna"
                                                        class="fi-colmgr-freeze-btn"
                                                        x-bind:class="{ 'active': isFrozen(gc.name) }">
                                                    <template x-if="isFrozen(gc.name)">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="fi-colmgr-freeze-icon"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" /></svg>
                                                    </template>
                                                    <template x-if="!isFrozen(gc.name)">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="fi-colmgr-freeze-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                                    </template>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            {{-- Coluna avulsa --}}
                            <template x-if="column.type !== 'group'">
                                <div class="fi-colmgr-row fi-colmgr-row-standalone">
                                    @if ($hasToggleableColumns)
                                        <button type="button"
                                                x-on:click="toggleColumn(column.name)"
                                                x-bind:disabled="(getColumn(column.name, null) || {}).isToggleable === false"
                                                class="fi-colmgr-eye-btn"
                                                x-bind:class="{ 'active': (getColumn(column.name, null) || {}).isToggled }">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="fi-colmgr-eye-icon"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 0 1 0-1.113ZM17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" clip-rule="evenodd" /></svg>
                                        </button>
                                    @endif
                                    <span x-html="column.label" class="fi-colmgr-col-label"></span>
                                    <button type="button"
                                            x-on:click.stop="toggleFreeze(column.name)"
                                            title="Congelar coluna"
                                            class="fi-colmgr-freeze-btn"
                                            x-bind:class="{ 'active': isFrozen(column.name) }">
                                        <template x-if="isFrozen(column.name)">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="fi-colmgr-freeze-icon"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" /></svg>
                                        </template>
                                        <template x-if="!isFrozen(column.name)">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="fi-colmgr-freeze-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                        </template>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Foot --}}
                <div class="fi-colmgr-foot">
                    @if ($applyAction->isVisible())
                        <button type="button"
                                x-on:click="
                                    applyTableColumnManager().then(() => {
                                        window.location.reload();
                                    })
                                "
                                class="fi-colmgr-btn fi-colmgr-btn-apply">
                            Aplicar
                        </button>
                    @endif
                    <button type="button" @click="closeModal()"
                            class="fi-colmgr-btn fi-colmgr-btn-close">
                        Fechar
                    </button>
                </div>

            </div>
        </div>
    </template>
</div>
