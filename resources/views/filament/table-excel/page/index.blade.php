@php
    /**
     * Partial principal do modo Page do Table Excel.
     *
     * Inclusão esperada (dentro de uma view de Page que use HasTableExcelPage):
     *   @include('filament.table-excel.page.index', $this->getTableExcelViewData())
     *
     * Variáveis recebidas:
     *  - $config: TableExcelPage
     *  - $registros: Collection
     *  - $totalRegistros, $totalPaginas, $paginaAtual, $porPagina: int
     *  - $primeiroRegistroIndex, $ultimoRegistroIndex: int
     */

    /** @var \App\Filament\Tables\TableExcel\Page\TableExcelPage $config */
    $rawColumns = $config->getColumns();
    $ocultas = $this->colunasOcultas ?? [];
    $userOrder = $this->ordemColunas ?? [];

    // Aplica ordem persistida somente entre colunas reorderable.
    // Colunas não-reorderable (ex.: actions) ficam na posição declarada.
    if (! empty($userOrder)) {
        $fixed = array_values(array_filter($rawColumns, fn ($c) => ! $c->reorderable));
        $reorderable = array_values(array_filter($rawColumns, fn ($c) => $c->reorderable));
        $byKey = [];
        foreach ($reorderable as $c) {
            $byKey[$c->key] = $c;
        }
        $ordered = [];
        foreach ($userOrder as $k) {
            if (isset($byKey[$k])) {
                $ordered[] = $byKey[$k];
                unset($byKey[$k]);
            }
        }
        foreach ($byKey as $c) {
            $ordered[] = $c;
        }
        $allColumns = array_merge($fixed, $ordered);
    } else {
        $allColumns = $rawColumns;
    }

    $columns = array_values(array_filter(
        $allColumns,
        fn ($c) => ! in_array($c->key, $ocultas, true),
    ));
    $filters = $config->getFilters();
    $hasSearch = $config->hasSearch();
    $recordKey = $config->getRecordKey();

    $tableKey = $config->getTableKey();
    $resizable = $config->isResizable();
    $freezable = $config->isFreezable();
    $frozenCols = $this->frozenColumns ?? [];
    $widths = $this->columnWidths ?? [];
@endphp

@php
    $rootClasses = ['gs-table-excel', 'gs-table-excel--page'];
    if ($config->isDense()) $rootClasses[] = 'gs-table-excel--dense';
    if ($config->isStriped()) $rootClasses[] = 'gs-table-excel--striped';
    if ($config->isStickyHeader()) $rootClasses[] = 'gs-table-excel--sticky-header';
    if ($config->isStickyActions()) $rootClasses[] = 'gs-table-excel--sticky-actions';
@endphp

@php
    $tableExcelConfig = json_encode([
        'tableKey' => $tableKey ?? 'default',
        'presets' => $this->getColumnPresets(),
        'currentUserId' => auth()->id(),
    ], JSON_UNESCAPED_SLASHES);
@endphp

<script>
    const TableExcelDataFactory = (config) => {
        const tableKey = config.tableKey;
        return {
            hydrated: false,
            tableKey: tableKey,
            currentUserId: config.currentUserId,
            columnPresets: config.presets,
            _pendingSavePreset: false,
            _savingGlobalView: false,
            _savingGlobalViewLabel: '',
            persistedHidden: [],
            persistedOrder: [],
            persistedFrozen: [],
            persistedWidths: {},
            persistedMostrarAvanc: false,
            persistedColumnTabs: { customTabs: [], activeTabId: null },
            activePreset: null,
            _applyingPreset: false,
            _showNewViewModal: false,
            _newViewName: '',
            _assignViewToAll: false,
            _pendingSaveView: false,
            _justSavedPreset: false,
            _showDeletePresetModal: false,
            _deletePresetTarget: null,
            _deletePresetLabel: '',
            selectedPresets: [],
            async init() {
                if (window.$wire) {
                    await window.$wire.call('syncClientPrefs', {
                        hidden: this.persistedHidden,
                        order: this.persistedOrder,
                        frozen: this.persistedFrozen,
                        widths: this.persistedWidths,
                        mostrarAvanc: this.persistedMostrarAvanc,
                    });
                }
                this.hydrated = true;
                this.activePreset = this.persistedColumnTabs.activeTabId ?? null;

                localStorage.removeItem('te:' + tableKey + ':apply_preset_after_reload');

                if (this.activePreset) {
                    const tab = this.columnPresets.find(p => p.id === this.activePreset);
                    if (tab && Array.isArray(tab.hidden)) {
                        this._applyingPreset = true;
                        try {
                            await this.$wire.call('applyColumnTab', tab.hidden);
                        } catch (e) {}
                        this.$nextTick(() => { this._applyingPreset = false; });
                    }
                }
                this.$watch('$wire.colunasOcultas', v => { this.persistedHidden = Array.isArray(v) ? [...v] : []; localStorage.setItem('te:' + tableKey + ':hidden', JSON.stringify(this.persistedHidden)); });
                this.$watch('$wire.ordemColunas', v => { this.persistedOrder = Array.isArray(v) ? [...v] : []; localStorage.setItem('te:' + tableKey + ':order', JSON.stringify(this.persistedOrder)); });
                this.$watch('$wire.frozenColumns', v => { this.persistedFrozen = Array.isArray(v) ? [...v] : []; localStorage.setItem('te:' + tableKey + ':frozen', JSON.stringify(this.persistedFrozen)); });
                this.$watch('$wire.columnWidths', v => { this.persistedWidths = (v && typeof v === 'object') ? { ...v } : {}; localStorage.setItem('te:' + tableKey + ':widths', JSON.stringify(this.persistedWidths)); });
                this.$watch('$wire.mostrarFiltrosAvancados', v => { this.persistedMostrarAvanc = !!v; localStorage.setItem('te:' + tableKey + ':mostrarAvanc', JSON.stringify(this.persistedMostrarAvanc)); });
                let _initialColumnsSettled = false;
                setTimeout(() => { _initialColumnsSettled = true; }, 1500);
                this.$watch('$wire.colunasOcultas', () => {
                    if (!_initialColumnsSettled) return;
                    if (!this._applyingPreset && !this._justSavedPreset) {
                        this.selectedPresets = [];
                        this.persistedColumnTabs = { ...this.persistedColumnTabs, activeTabId: null };
                        localStorage.setItem('te:' + tableKey + ':column_tabs', JSON.stringify(this.persistedColumnTabs));
                        localStorage.setItem('te:' + tableKey + ':selected_presets', JSON.stringify(this.selectedPresets));
                    }
                });
                this.$el.addEventListener('open-save-preset-flow', () => {
                    this._pendingSavePreset = true;
                    document.querySelector('[data-action="gerenciarColunas"]')?.click();
                });
                let lastHidden = JSON.stringify(this.persistedHidden);
                this.$watch('persistedHidden', (newHidden) => {
                    const newHiddenStr = JSON.stringify(newHidden);
                    console.log('[preset] persistedHidden mudou', { _pendingSaveView: this._pendingSaveView, lastHidden, newHiddenStr });
                    if (this._pendingSaveView) {
                        lastHidden = newHiddenStr;
                        const hidden = Array.isArray(newHidden) ? [...newHidden] : [];
                        const viewName = localStorage.getItem('te:' + tableKey + ':pending_view_name');
                        const assignToAll = localStorage.getItem('te:' + tableKey + ':pending_assign_to_all') === '1';

                        console.log('[preset] salvando', { viewName, assignToAll, hiddenCount: hidden.length });

                        if (assignToAll && this.$wire && typeof this.$wire.call === 'function') {
                            const label = viewName || this._newViewName;
                            // Marca como concluído ANTES de chamar Livewire,
                            // senão o watch reentra ao receber a resposta do
                            // applyColumnTab e dispara savePresetTab uma 2ª vez.
                            this._pendingSaveView = false;
                            this._applyingPreset = true;
                            this._savingGlobalView = true;
                            this._savingGlobalViewLabel = label;
                            (async () => {
                                try {
                                    const result = await this.$wire.call('savePresetTab', label, hidden, assignToAll);
                                    await this.$wire.call('applyColumnTab', hidden);
                                    const newId = (result && result.id) ? 'preset_global_' + result.id : null;
                                    if (newId) {
                                        localStorage.setItem('te:' + tableKey + ':selected_presets', JSON.stringify([newId]));
                                        localStorage.setItem('te:' + tableKey + ':column_tabs', JSON.stringify({ ...this.persistedColumnTabs, activeTabId: newId }));
                                    }
                                    localStorage.removeItem('te:' + tableKey + ':pending_view_name');
                                    localStorage.removeItem('te:' + tableKey + ':pending_assign_to_all');
                                    window.location.reload();
                                } catch (err) {
                                    this._savingGlobalView = false;
                                    this._applyingPreset = false;
                                    console.error('[preset] erro ao salvar vista global', err);
                                }
                            })();
                            return;
                        } else {
                            const id = 'tab_' + Date.now();
                            const newTab = { id, label: viewName || this._newViewName, hidden };
                            this.persistedColumnTabs = {
                                ...this.persistedColumnTabs,
                                customTabs: [...this.persistedColumnTabs.customTabs, newTab],
                                activeTabId: id,
                            };
                            localStorage.setItem('te:' + tableKey + ':column_tabs', JSON.stringify(this.persistedColumnTabs));
                        }

                        localStorage.removeItem('te:' + tableKey + ':pending_view_name');
                        localStorage.removeItem('te:' + tableKey + ':pending_assign_to_all');
                        this.selectedPresets = [];
                        this._pendingSaveView = false;
                        this._newViewName = '';
                        this._assignViewToAll = false;
                    }
                });
            },
            togglePreset(tabId) {
                if (tabId === 'preset_visao_geral') {
                    // Visão Geral nunca combina com outros: clicar nela limpa o resto
                    // e mantém apenas ela selecionada (não permite desmarcar).
                    this.selectedPresets = ['preset_visao_geral'];
                } else {
                    const idx = this.selectedPresets.indexOf(tabId);
                    if (idx >= 0) {
                        this.selectedPresets.splice(idx, 1);
                        // Se nada mais ficou selecionado, força Visão Geral.
                        if (this.selectedPresets.length === 0) {
                            this.selectedPresets = ['preset_visao_geral'];
                        }
                    } else {
                        // Marcar um preset comum: remove Visão Geral (não combina).
                        this.selectedPresets = this.selectedPresets.filter(p => p !== 'preset_visao_geral');
                        this.selectedPresets.push(tabId);
                    }
                }
                this.applySelectedPresets();
            },
            async applySelectedPresets() {
                this._applyingPreset = true;
                const allTabs = [...this.columnPresets, ...this.persistedColumnTabs.customTabs];
                let hidden = [];

                if (this.selectedPresets.length > 0) {
                    const visibleSets = this.selectedPresets.map(presetId => {
                        const preset = allTabs.find(t => t.id === presetId);
                        if (!preset || !preset.hidden || !Array.isArray(preset.hidden)) {
                            return new Set();
                        }
                        const allCols = new Set();
                        allTabs.forEach(tab => {
                            if (tab.hidden && Array.isArray(tab.hidden)) {
                                tab.hidden.forEach(col => allCols.add(col));
                            }
                        });
                        const hiddenSet = new Set(preset.hidden);
                        const visible = Array.from(allCols).filter(col => !hiddenSet.has(col));
                        return new Set(visible);
                    });

                    const unionVisible = new Set();
                    visibleSets.forEach(set => {
                        set.forEach(col => unionVisible.add(col));
                    });

                    const allColsSet = new Set();
                    allTabs.forEach(tab => {
                        if (tab.hidden && Array.isArray(tab.hidden)) {
                            tab.hidden.forEach(col => allColsSet.add(col));
                        }
                    });

                    hidden = Array.from(allColsSet).filter(col => !unionVisible.has(col));
                }

                localStorage.setItem('te:' + tableKey + ':selected_presets', JSON.stringify(this.selectedPresets));
                if (this.$wire && typeof this.$wire.call === 'function') {
                    await this.$wire.call('applyColumnTab', hidden);
                }
                this._applyingPreset = false;
            },
            async applyPreset(tabId) {
                const allTabs = [...this.columnPresets, ...this.persistedColumnTabs.customTabs];
                const tab = allTabs.find(t => t.id === tabId);
                if (!tab || !tab.hidden) return;
                this._applyingPreset = true;
                this.activePreset = tabId;
                localStorage.setItem('te:' + tableKey + ':column_tabs', JSON.stringify({ ...this.persistedColumnTabs, activeTabId: tabId }));
                if (this.$wire && typeof this.$wire.call === 'function') {
                    await this.$wire.call('applyColumnTab', tab.hidden);
                }
                this._applyingPreset = false;
            },
            deleteCustomTab(id) {
                this.persistedColumnTabs = {
                    ...this.persistedColumnTabs,
                    customTabs: this.persistedColumnTabs.customTabs.filter(t => t.id !== id),
                    activeTabId: this.activePreset === id ? null : this.persistedColumnTabs.activeTabId,
                };
                localStorage.setItem('te:' + tableKey + ':column_tabs', JSON.stringify(this.persistedColumnTabs));
                if (this.activePreset === id) this.activePreset = null;
            },
            deleteGlobalTab(id) {
                if (!id || !id.startsWith('preset_global_')) return;
                const tab = this.columnPresets.find(p => p.id === id);
                this._deletePresetTarget = id;
                this._deletePresetLabel = tab?.label ?? '';
                this._showDeletePresetModal = true;
            },
            cancelDeleteGlobalTab() {
                this._showDeletePresetModal = false;
                this._deletePresetTarget = null;
                this._deletePresetLabel = '';
            },
            confirmDeleteGlobalTab() {
                const id = this._deletePresetTarget;
                this._showDeletePresetModal = false;
                this._deletePresetTarget = null;
                this._deletePresetLabel = '';
                if (!id || !id.startsWith('preset_global_')) return;
                const globalId = parseInt(id.replace('preset_global_', ''));
                if (isNaN(globalId)) return;
                if (this.$wire && typeof this.$wire.call === 'function') {
                    this.$wire.call('deleteGlobalPreset', globalId).then(() => {
                        this.columnPresets = this.columnPresets.filter(p => p.id !== id);
                        this.selectedPresets = this.selectedPresets.filter(p => p !== id);
                        if (this.selectedPresets.length === 0) {
                            this.selectedPresets = ['preset_visao_geral'];
                            this.applySelectedPresets();
                        }
                        if (this.activePreset === id) this.activePreset = null;
                    });
                }
            },
            confirmNewView(viewName) {
                const trimmed = viewName.trim();
                if (!trimmed) return;
                this._newViewName = trimmed;
                this._showNewViewModal = false;
                this._pendingSaveView = true;
                localStorage.setItem('te:' + tableKey + ':pending_view_name', trimmed);
                localStorage.setItem('te:' + tableKey + ':pending_assign_to_all', this._assignViewToAll ? '1' : '0');
                this.$nextTick(() => {
                    const btn = document.querySelector('[wire\\:click="gerenciarColunasAction"]') ||
                                document.querySelector('button[data-action="gerenciarColunas"]') ||
                                Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Configurar colunas'));
                    if (btn) {
                        btn.click();
                    } else {
                        console.warn('Botão "Configurar colunas" não encontrado');
                    }
                });
            },
        };
    };
</script>

<div
    class="{{ implode(' ', $rootClasses) }}"
    x-data="TableExcelDataFactory({{ $tableExcelConfig }})"
    :class="{ 'gs-table-excel--hydrated': hydrated }"
    @if ($tableKey) data-gs-table-key="{{ $tableKey }}" @endif
    x-init="
        const tk = tableKey;
        const ls = (k, d) => { try { return JSON.parse(localStorage.getItem('te:' + tk + ':' + k)) ?? d; } catch(e) { return d; } };
        persistedHidden = ls('hidden', []);
        persistedOrder = ls('order', []);
        persistedFrozen = ls('frozen', []);
        persistedWidths = ls('widths', {});
        persistedMostrarAvanc = ls('mostrarAvanc', false);
        persistedColumnTabs = ls('column_tabs', { customTabs: [], activeTabId: null });
        selectedPresets = ls('selected_presets', []);
        if (!Array.isArray(selectedPresets) || selectedPresets.length === 0) {
            selectedPresets = ['preset_visao_geral'];
        }
        $nextTick(() => init());
    "
>
    {{-- Overlay de carregamento ao salvar/aplicar vista global --}}
    <div
        class="gs-te-saving-overlay"
        x-show="_savingGlobalView"
        x-cloak
        x-transition.opacity
    >
        <div class="gs-te-saving-overlay__card">
            <div class="gs-te-saving-overlay__spinner" aria-hidden="true"></div>
            <div class="gs-te-saving-overlay__title">Salvando vista global…</div>
            <div class="gs-te-saving-overlay__subtitle"
                 x-text="_savingGlobalViewLabel
                    ? ('Aplicando “' + _savingGlobalViewLabel + '” para todos os usuários')
                    : 'Aplicando configurações para todos os usuários'"></div>
            <div class="gs-te-saving-overlay__hint">Não feche a página, ela será atualizada em instantes.</div>
        </div>
    </div>

    @php
        $activeFiltersCount = 0;
        foreach ($filters as $f) {
            $v = $this->filtros[$f->key] ?? null;
            if (! $f->isEmptyValue($v)) {
                $activeFiltersCount++;
            }
        }
        $toggleableColumns = array_values(array_filter($allColumns, fn ($c) => $c->toggleable));
    @endphp

    @php
        $toolbarActions = method_exists($this, 'getTableExcelToolbarActions')
            ? $this->getTableExcelToolbarActions()
            : [];
    @endphp

    {{-- Barra principal: busca (com botão de limpar embutido) + actions --}}
    <div class="gs-table-excel__quick-filters">
        @if ($hasSearch)
            <div class="gs-table-excel__qf-search">
                <input
                    type="search"
                    placeholder="{{ $config->getSearchPlaceholder() }}"
                    wire:model.live.debounce.500ms="busca"
                    class="gs-table-excel__qf-input"
                    aria-label="{{ $config->getSearchPlaceholder() }}"
                >
                @if ($busca !== '')
                    <button
                        type="button"
                        class="gs-table-excel__qf-search-clear"
                        wire:click="$set('busca', '')"
                        title="Limpar busca"
                        aria-label="Limpar busca"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                @endif
            </div>
        @endif

        @if (! empty($toolbarActions))
            <div class="gs-table-excel__qf-actions">
                @foreach ($toolbarActions as $actionName)
                    @php $toolbarAction = $this->{$actionName}; @endphp
                    @if ($toolbarAction && ! $toolbarAction->isHidden())
                        {{ $toolbarAction }}
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Column Preset Tabs --}}
    <div class="gs-te-preset-tabs">
        <template x-for="tab in columnPresets" :key="tab.id">
            <span class="gs-te-preset-tab"
                  :class="{ 'gs-te-preset-tab--active': selectedPresets.includes(tab.id) }">
                <button type="button"
                    @click="togglePreset(tab.id)"
                    :title="'Clique para selecionar/desselecionar: ' + tab.label"
                    x-text="tab.label"></button>
                <template x-if="tab.is_global && tab.created_by === currentUserId">
                    <button type="button" class="gs-te-preset-tab__del"
                        @click.stop="deleteGlobalTab(tab.id)"
                        title="Remover vista global">×</button>
                </template>
            </span>
        </template>

        <template x-for="tab in persistedColumnTabs.customTabs" :key="tab.id">
            <span class="gs-te-preset-tab gs-te-preset-tab--custom"
                  :class="{ 'gs-te-preset-tab--active': activePreset === tab.id }">
                <button type="button" @click="applyPreset(tab.id)" x-text="tab.label"></button>
                <button type="button" class="gs-te-preset-tab__del" @click.stop="deleteCustomTab(tab.id)" title="Remover aba">×</button>
            </span>
        </template>

        <button type="button" class="gs-te-preset-tab gs-te-preset-tab--add"
            @click="_showNewViewModal = true"
            title="Criar nova vista">
            + Criar vista
        </button>
    </div>

    {{-- Modal para criar nova vista --}}
    <template x-if="_showNewViewModal">
        <div class="gs-te-new-tab-backdrop" @click="_showNewViewModal=false">
            <div class="gs-te-new-tab-modal" @click.stop>
                <div class="gs-te-new-tab-modal__header">
                    Criar nova vista
                </div>
                <input type="text" x-model="_newViewName" placeholder="Digite um nome para a vista"
                    class="gs-te-new-tab-modal__input"
                    maxlength="40"
                    @keydown.enter="confirmNewView(_newViewName)"
                    @keydown.escape="_showNewViewModal=false"
                    x-init="$nextTick(() => $el.focus())">
                <label class="gs-te-new-tab-modal__checkbox">
                    <input type="checkbox" x-model="_assignViewToAll">
                    <span>Atribuir vista para todos?</span>
                </label>
                <div class="gs-te-new-tab-modal__actions">
                    <button type="button" class="gs-te-new-tab-modal__btn gs-te-new-tab-modal__btn--primary"
                        @click="confirmNewView(_newViewName)"
                        :disabled="!_newViewName.trim()">Próximo</button>
                    <button type="button" class="gs-te-new-tab-modal__btn gs-te-new-tab-modal__btn--secondary"
                        @click="_showNewViewModal=false">Cancelar</button>
                </div>
            </div>
        </div>
    </template>

    {{-- Modal de confirmação de remoção de vista global --}}
    <template x-if="_showDeletePresetModal">
        <div class="gs-te-new-tab-backdrop" @click="cancelDeleteGlobalTab()" @keydown.escape.window="cancelDeleteGlobalTab()">
            <div class="gs-te-new-tab-modal gs-te-confirm-modal" @click.stop role="alertdialog" aria-modal="true" aria-labelledby="gs-te-confirm-title">
                <div class="gs-te-confirm-modal__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                </div>
                <div class="gs-te-confirm-modal__body">
                    <div class="gs-te-confirm-modal__title" id="gs-te-confirm-title">Remover vista global</div>
                    <div class="gs-te-confirm-modal__text">
                        Tem certeza que deseja remover
                        <strong x-text="_deletePresetLabel ? '“' + _deletePresetLabel + '”' : 'esta vista'"></strong>?
                        Ela será removida para <strong>todos os usuários</strong> e essa ação não pode ser desfeita.
                    </div>
                </div>
                <div class="gs-te-new-tab-modal__actions gs-te-confirm-modal__actions">
                    <button type="button"
                        class="gs-te-new-tab-modal__btn gs-te-new-tab-modal__btn--secondary"
                        @click="cancelDeleteGlobalTab()">Cancelar</button>
                    <button type="button"
                        class="gs-te-new-tab-modal__btn gs-te-confirm-modal__btn-danger"
                        x-init="$nextTick(() => $el.focus())"
                        @click="confirmDeleteGlobalTab()">Remover</button>
                </div>
            </div>
        </div>
    </template>

    @include('filament.table-excel.page.partials.active-filters', [
        'filters' => $filters,
        'activeFilters' => $this->filtros,
    ])

    @if ($config->isBulkEnabled() && ! empty($selecionados))
        <div class="gs-table-excel__bulk-bar">
            <span class="gs-table-excel__bulk-count">
                {{ count($selecionados) }} selecionado(s)
            </span>
            <button
                type="button"
                class="gs-table-excel__qf-toggle"
                wire:click="limparSelecao"
            >
                Limpar seleção
            </button>
            <button
                type="button"
                class="gs-table-excel__bulk-danger"
                wire:click="excluirSelecionados"
                wire:confirm="Confirma excluir {{ count($selecionados) }} registro(s)?"
            >
                Excluir selecionados
            </button>
        </div>
    @endif

    @if (! empty($secondaryFilters) && $mostrarFiltrosAvancados)
        <div class="gs-table-excel__adv-filters">
            @foreach ($secondaryFilters as $filter)
                <div class="gs-table-excel__adv-filter">
                    <label class="gs-table-excel__adv-filter-label">{{ $filter->label }}</label>
                    @include('filament.table-excel.page.partials.filter-input', ['filter' => $filter])
                </div>
            @endforeach
        </div>
    @endif

    {{-- Tabela HTML pura --}}
    <div class="gs-table-excel-page__scroll">
        <table class="gs-table-excel-page__table">
            @php
                // Consolida colunas em grupos consecutivos pelo atributo ->group.
                $groupRuns = [];
                foreach ($columns as $column) {
                    $label = $column->group;
                    if ($groupRuns && end($groupRuns)['label'] === $label) {
                        $groupRuns[array_key_last($groupRuns)]['span']++;
                    } else {
                        $groupRuns[] = ['label' => $label, 'span' => 1];
                    }
                }
                $hasAnyGroup = collect($groupRuns)->contains(fn ($run) => filled($run['label']));
            @endphp

            @php
                $bulkEnabled = $config->isBulkEnabled();
                $selIds = array_map('strval', $selecionados ?? []);
                $paginaIds = $registros->pluck($recordKey)->map(fn ($i) => (string) $i)->all();
                $todosSelecionados = $paginaIds !== [] && empty(array_diff($paginaIds, $selIds));
            @endphp

            <thead>
                @if ($hasAnyGroup)
                    <tr class="gs-table-excel-page__group-row">
                        @if ($bulkEnabled)
                            <th class="gs-table-excel-page__group-th gs-table-excel-page__group-th--empty"></th>
                        @endif
                        @foreach ($groupRuns as $run)
                            <th
                                class="gs-table-excel-page__group-th {{ $run['label'] ? '' : 'gs-table-excel-page__group-th--empty' }}"
                                colspan="{{ $run['span'] }}"
                            >
                                {{ $run['label'] }}
                            </th>
                        @endforeach
                    </tr>
                @endif
                <tr>
                    @php
                        $sortCol = $this->ordenacao['coluna'] ?? null;
                        $sortDir = $this->ordenacao['direcao'] ?? 'asc';
                    @endphp
                    @if ($bulkEnabled)
                        <th class="gs-table-excel-page__th gs-table-excel-page__th--align-center">
                            <input
                                type="checkbox"
                                aria-label="Selecionar todos da página"
                                @checked($todosSelecionados)
                                wire:click="{{ $todosSelecionados ? 'limparSelecao' : 'selecionarPaginaAtual' }}"
                            >
                        </th>
                    @endif
                    @foreach ($columns as $column)
                        @php
                            $isSortActive = $sortCol === $column->key;
                            $isFrozen = in_array($column->key, $frozenCols, true);
                            $w = $widths[$column->key] ?? null;
                            $style = '';
                            if ($resizable && $w) {
                                $style = "width: {$w}px; min-width: {$w}px; max-width: {$w}px;";
                            }
                            $thClasses = [
                                'gs-table-excel-page__th',
                                "gs-table-excel-page__th--align-{$column->align}",
                            ];
                            if ($column->sortable) $thClasses[] = 'gs-table-excel-page__th--sortable';
                            if ($isSortActive) $thClasses[] = 'gs-table-excel-page__th--sort-active';
                            if ($isFrozen) $thClasses[] = 'gs-table-excel__col-sticky gs-table-excel__col-sticky--left';
                            if ($column->headerClass) $thClasses[] = $column->headerClass;
                        @endphp
                        <th
                            class="{{ implode(' ', $thClasses) }}"
                            data-gs-column="{{ $column->key }}"
                            @if ($isSortActive) aria-sort="{{ $sortDir === 'desc' ? 'descending' : 'ascending' }}" @endif
                            @if ($resizable) data-gs-resizable="1" @endif
                            @if ($isFrozen) data-gs-frozen="1" @endif
                            @if ($style) style="{{ $style }}" @endif
                            @if ($column->sortable)
                                wire:click="ordenarPor('{{ $column->key }}')"
                            @endif
                        >
                            {{ $column->label }}
                            @if ($column->sortable)
                                <span class="gs-table-excel-page__sort-ico" aria-hidden="true">
                                    @if ($sortCol === $column->key && $sortDir === 'asc')
                                        ▲
                                    @elseif ($sortCol === $column->key && $sortDir === 'desc')
                                        ▼
                                    @else
                                        ⇅
                                    @endif
                                </span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            {{-- wire:ignore: Livewire NÃO faz morph aqui. Os handlers wire:click/
                 wire:change das células continuam funcionando (ignore só bloqueia
                 morph, não event handling). As atualizações do corpo da tabela
                 (após filtrar/ordenar/paginar/editar) chegam via AJAX: handlers
                 Livewire disparam 'te-refresh-rows' → JS chama fetchRowsHtml()
                 e substitui este innerHTML. Ver scripts.blade.php. --}}
            <tbody wire:ignore data-gs-tbody>
                @include('filament.table-excel.page.partials.tbody-rows', [
                    'registros' => $registros,
                    'columns' => $columns,
                    'config' => $config,
                    'recordKey' => $recordKey,
                    'bulkEnabled' => $bulkEnabled,
                    'resizable' => $resizable,
                    'frozenCols' => $frozenCols,
                    'widths' => $widths,
                    'selIds' => $selIds,
                ])
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    @if ($totalRegistros > 0)
        <div class="gs-table-excel-page__pagination">
            <label class="gs-table-excel-page__pag-perpage">
                Por página:
                <select wire:model.live="porPagina" class="gs-table-excel__qf-select">
                    @foreach ($config->getPerPageOptions() as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </label>

            <button
                type="button"
                class="gs-table-excel-page__pag-btn"
                wire:click="paginaAnterior"
                @disabled($paginaAtual <= 1)
            >
                Anterior
            </button>

            <span class="gs-table-excel-page__pag-status">
                <input
                    type="number"
                    min="1"
                    max="{{ $totalPaginas }}"
                    value="{{ $paginaAtual }}"
                    class="gs-table-excel-page__pag-goto"
                    wire:change="irParaPagina($event.target.value)"
                    aria-label="Ir para página"
                >
                / {{ $totalPaginas }}
                <span class="gs-table-excel-page__pag-total">
                    ({{ $primeiroRegistroIndex }}–{{ $ultimoRegistroIndex }} de {{ $totalRegistros }})
                </span>
            </span>

            <button
                type="button"
                class="gs-table-excel-page__pag-btn"
                wire:click="proximaPagina"
                @disabled($paginaAtual >= $totalPaginas)
            >
                Seguinte
            </button>
        </div>
    @endif
</div>

{{-- Navegação por clique na linha (quando rowUrl definido) --}}
<script>
    (function () {
        const attach = () => {
            document.querySelectorAll('.gs-table-excel-page__row--clickable').forEach((row) => {
                if (row.dataset.gsRowBound) return;
                row.dataset.gsRowBound = '1';
                row.addEventListener('click', (event) => {
                    // Não navega se o alvo do clique é interativo
                    const interactiveSelectors = 'button,select,input,textarea,a,.gs-pill-dropdown,[wire\\:click]';
                    if (event.target.closest(interactiveSelectors)) return;
                    const url = row.dataset.gsRowUrl;
                    if (url) {
                        window.location.assign(url);
                    }
                });
            });
        };
        document.addEventListener('DOMContentLoaded', attach);
        document.addEventListener('livewire:navigated', attach);
        document.addEventListener('livewire:update', attach);
        attach();
    })();
</script>
