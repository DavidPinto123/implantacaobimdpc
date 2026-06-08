export default function TableExcelData(config) {
    return {
        hydrated: false,
        tableKey: config.tableKey,

        // UI state em localStorage (Alpine.$persist)
        persistedHidden: window.Alpine && window.Alpine.persist ? window.Alpine.persist([]).as(`te:${config.tableKey}:hidden`) : [],
        persistedOrder: window.Alpine && window.Alpine.persist ? window.Alpine.persist([]).as(`te:${config.tableKey}:order`) : [],
        persistedFrozen: window.Alpine && window.Alpine.persist ? window.Alpine.persist([]).as(`te:${config.tableKey}:frozen`) : [],
        persistedWidths: window.Alpine && window.Alpine.persist ? window.Alpine.persist({}).as(`te:${config.tableKey}:widths`) : {},
        persistedMostrarAvanc: window.Alpine && window.Alpine.persist ? window.Alpine.persist(false).as(`te:${config.tableKey}:mostrarAvanc`) : false,

        // Column preset tabs
        persistedColumnTabs: window.Alpine && window.Alpine.persist ? window.Alpine.persist({ customTabs: [], activeTabId: null }).as(`te:${config.tableKey}:column_tabs`) : { customTabs: [], activeTabId: null },
        activePreset: null,
        _applyingPreset: false,
        _showSavePresetModal: false,
        _presetLabel: '',
        columnPresets: config.presets,

        async init() {
            // 1. Sincroniza Livewire com prefs de UI do localStorage
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

            // Restaura aba visualmente ativa
            this.activePreset = this.persistedColumnTabs.activeTabId ?? null;

            // 2. Propaga mudanças do servidor
            this.$watch('$wire.colunasOcultas', v => this.persistedHidden = Array.isArray(v) ? [...v] : []);
            this.$watch('$wire.ordemColunas', v => this.persistedOrder = Array.isArray(v) ? [...v] : []);
            this.$watch('$wire.frozenColumns', v => this.persistedFrozen = Array.isArray(v) ? [...v] : []);
            this.$watch('$wire.columnWidths', v => this.persistedWidths = (v && typeof v === 'object') ? { ...v } : {});
            this.$watch('$wire.mostrarFiltrosAvancados', v => this.persistedMostrarAvanc = !!v);

            // Quando colunas mudam externamente (ManageColumnsAction), desativa preset
            this.$watch('$wire.colunasOcultas', () => {
                if (!this._applyingPreset) {
                    this.activePreset = null;
                    this.persistedColumnTabs = { ...this.persistedColumnTabs, activeTabId: null };
                }
            });

            // Listener para iniciar fluxo de salvar preset
            this.$el.addEventListener('open-save-preset-flow', () => {
                this._pendingSavePreset = true;
                document.querySelector('[data-action="gerenciarColunas"]')?.click();
            });

            // Observa mudanças para detectar quando o modal foi salvo
            let lastHidden = this.persistedHidden;
            this.$watch('persistedHidden', (newHidden) => {
                if (this._pendingSavePreset && JSON.stringify(lastHidden) !== JSON.stringify(newHidden)) {
                    lastHidden = [...newHidden];
                    this._pendingSavePreset = false;
                    this.$nextTick(() => {
                        this.openSavePresetModal();
                    });
                }
            });
        },

        async applyPreset(tabId) {
            const allTabs = [...this.columnPresets, ...this.persistedColumnTabs.customTabs];
            const tab = allTabs.find(t => t.id === tabId);
            if (!tab) return;
            this._applyingPreset = true;
            this.activePreset = tabId;
            this.persistedColumnTabs = { ...this.persistedColumnTabs, activeTabId: tabId };
            if (window.$wire) {
                await window.$wire.call('applyColumnTab', tab.hidden);
            }
            this._applyingPreset = false;
        },

        deleteCustomTab(id) {
            this.persistedColumnTabs = {
                ...this.persistedColumnTabs,
                customTabs: this.persistedColumnTabs.customTabs.filter(t => t.id !== id),
                activeTabId: this.activePreset === id ? null : this.persistedColumnTabs.activeTabId,
            };
            if (this.activePreset === id) this.activePreset = null;
        },

        openSavePresetModal() {
            this._showSavePresetModal = true;
            this._presetLabel = '';
        },

        confirmSavePreset(label) {
            const trimmed = label.trim();
            if (!trimmed) return;
            const id = 'tab_' + Date.now();
            const hidden = Array.isArray(window.$wire?.colunasOcultas) ? [...window.$wire.colunasOcultas] : [];
            const newTab = { id, label: trimmed, hidden };
            this.persistedColumnTabs = {
                ...this.persistedColumnTabs,
                customTabs: [...this.persistedColumnTabs.customTabs, newTab],
                activeTabId: id,
            };
            this.activePreset = id;
            this._showSavePresetModal = false;
            this._presetLabel = '';
        },
    };
}

// Torna disponível globalmente para Alpine.js
if (typeof window !== 'undefined') {
    window.TableExcelData = TableExcelData;
}
