@php
    /**
     * Fork do filament.table-excel.page.index para a página
     * Financeiro › Notas Fiscais. Idêntico ao original, com duas mudanças:
     *   1) <tbody> recebe x-data="{ openGrupos: $persist({}) ... }" para
     *      controlar o estado collapsível dos grupos por unidade.
     *   2) O include do partial de linhas aponta para a versão customizada
     *      que injeta cabeçalho de grupo + x-show por linha.
     */

    /** @var \App\Filament\Tables\TableExcel\Page\TableExcelPage $config */
    $rawColumns = $config->getColumns();
    $ocultas = $this->colunasOcultas ?? [];
    $userOrder = $this->ordemColunas ?? [];

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

<div
    class="{{ implode(' ', $rootClasses) }}"
    x-data="{
        hydrated: false,

        persistedHidden: $persist([]).as(@js('te:'.($tableKey ?? 'default').':hidden')),
        persistedOrder: $persist([]).as(@js('te:'.($tableKey ?? 'default').':order')),
        persistedFrozen: $persist([]).as(@js('te:'.($tableKey ?? 'default').':frozen')),
        persistedWidths: $persist({}).as(@js('te:'.($tableKey ?? 'default').':widths')),
        persistedMostrarAvanc: $persist(false).as(@js('te:'.($tableKey ?? 'default').':mostrarAvanc')),

        async init() {
            await $wire.call('syncClientPrefs', {
                hidden: this.persistedHidden,
                order: this.persistedOrder,
                frozen: this.persistedFrozen,
                widths: this.persistedWidths,
                mostrarAvanc: this.persistedMostrarAvanc,
            });
            this.hydrated = true;

            this.$watch('$wire.colunasOcultas', v => this.persistedHidden = Array.isArray(v) ? [...v] : []);
            this.$watch('$wire.ordemColunas', v => this.persistedOrder = Array.isArray(v) ? [...v] : []);
            this.$watch('$wire.frozenColumns', v => this.persistedFrozen = Array.isArray(v) ? [...v] : []);
            this.$watch('$wire.columnWidths', v => this.persistedWidths = (v && typeof v === 'object') ? { ...v } : {});
            this.$watch('$wire.mostrarFiltrosAvancados', v => this.persistedMostrarAvanc = !!v);
        },
    }"
    :class="{ 'gs-table-excel--hydrated': hydrated }"
    @if ($tableKey) data-gs-table-key="{{ $tableKey }}" @endif
>
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
                <div
                    x-data="{
                        abertas: false,
                        refresh() {
                            const tbody = document.querySelector('[data-gs-tbody]');
                            if (! tbody) { this.abertas = false; return; }
                            const headers = tbody.querySelectorAll('[data-gs-grupo-key]');
                            if (headers.length === 0) { this.abertas = false; return; }
                            const open = tbody._x_dataStack?.[0]?.openGrupos ?? {};
                            this.abertas = Array.from(headers).every((el) => open[el.dataset.gsGrupoKey] === true);
                        },
                        toggle() {
                            const evt = this.abertas ? 'te-fechar-todas' : 'te-expandir-todas';
                            window.dispatchEvent(new CustomEvent(evt));
                            this.$nextTick(() => this.refresh());
                        }
                    }"
                    x-init="$nextTick(() => refresh())"
                    x-on:te-grupos-mudou.window="$nextTick(() => refresh())"
                    x-on:te-expandir-todas.window="$nextTick(() => refresh())"
                    x-on:te-fechar-todas.window="$nextTick(() => refresh())"
                    style="display: inline-flex;"
                >
                    <button
                        type="button"
                        x-on:click="toggle()"
                        class="fi-btn fi-btn-color-gray fi-btn-size-sm fi-color-gray fi-size-sm"
                        title="Expandir/fechar todas as unidades"
                        style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.7rem; font-size: 0.8rem; font-weight: 500; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #ffffff; color: #374151; cursor: pointer;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-show="! abertas">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" x-show="abertas" x-cloak>
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                        <span x-text="abertas ? 'Fechar todas' : 'Expandir todas'"></span>
                    </button>
                </div>
            </div>
        @endif
    </div>

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

    @if (! empty($this->notasSelecionadas))
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.7rem; margin: 0.5rem 0; background: #fef3c7; border: 1px solid #fde68a; border-radius: 0.5rem; font-size: 0.8rem;">
            <span style="font-weight: 500; color: #78350f;">
                {{ count($this->notasSelecionadas) }} {{ count($this->notasSelecionadas) === 1 ? 'nota selecionada' : 'notas selecionadas' }}
            </span>
            <button
                type="button"
                wire:click="limparSelecaoNotas"
                style="padding: 0.22rem 0.5rem; font-size: 0.8rem; border: 1px solid #d1d5db; border-radius: 0.375rem; background: #ffffff; color: #374151; cursor: pointer;"
            >
                Limpar seleção
            </button>
            {{ $this->baixarSelecionadasAction }}
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

    <div class="gs-table-excel-page__scroll">
        <table class="gs-table-excel-page__table">
            @php
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
                $todasNotasSelecionadas = $totalRegistros > 0 && count($this->notasSelecionadas ?? []) >= $totalRegistros;
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
                            @if ($column->key === 'selecao')
                                <input
                                    type="checkbox"
                                    wire:key="gs-master-select-{{ $todasNotasSelecionadas ? 'on' : 'off' }}"
                                    aria-label="Selecionar todas as notas"
                                    title="Selecionar/desmarcar todas as notas filtradas"
                                    @checked($todasNotasSelecionadas)
                                    wire:click="{{ $todasNotasSelecionadas ? 'limparSelecaoNotas' : 'selecionarTodasNotas' }}"
                                    style="cursor: pointer; width: 1rem; height: 1rem;"
                                >
                            @else
                                {{ $column->label }}
                            @endif
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

            <tbody
                wire:ignore
                data-gs-tbody
                x-data="{ openGrupos: $persist({}).as(@js('te:'.($tableKey ?? 'default').':grupos')) }"
                x-on:te-expandir-todas.window="$el.querySelectorAll('[data-gs-grupo-key]').forEach((el) => { openGrupos[el.dataset.gsGrupoKey] = true })"
                x-on:te-fechar-todas.window="$el.querySelectorAll('[data-gs-grupo-key]').forEach((el) => { openGrupos[el.dataset.gsGrupoKey] = false })"
            >
                @include('filament.pages.partials.financeiro-notas-fiscais-tbody-rows', [
                    'registros' => $registros,
                    'columns' => $columns,
                    'config' => $config,
                    'recordKey' => $recordKey,
                    'bulkEnabled' => $bulkEnabled,
                    'resizable' => $resizable,
                    'frozenCols' => $frozenCols,
                    'widths' => $widths,
                    'selIds' => $selIds,
                    'notasSelecionadas' => $this->notasSelecionadas ?? [],
                ])
            </tbody>
        </table>
    </div>

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

<script>
    (function () {
        const attach = () => {
            document.querySelectorAll('.gs-table-excel-page__row--clickable').forEach((row) => {
                if (row.dataset.gsRowBound) return;
                row.dataset.gsRowBound = '1';
                row.addEventListener('click', (event) => {
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
