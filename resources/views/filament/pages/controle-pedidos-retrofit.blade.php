<x-filament::page>
    @push('styles')
        <style>
            .cpr-scroll .cpr-thead-main > tr > th {
                position: sticky;
                top: 0;
                z-index: 40;
            }

            .cpr-scroll .cpr-parent-sticky {
                position: sticky;
                top: var(--cpr-thead-h, 0px);
                z-index: 30;
            }

            .cpr-scroll .cpr-child-thead > tr > th {
                position: sticky;
                top: calc(var(--cpr-thead-h, 0px) + var(--cpr-parent-h, 0px));
                z-index: 20;
            }

            .cpr-scroll .cpr-parent-row + tr > td {
                overflow: visible;
            }

            /* Hover para botões de ação */
            .cpr-action-btn {
                transition: all 0.2s ease;
                border-radius: 0.375rem;
            }

            .cpr-action-btn.text-red-600:hover {
                background-color: rgba(220, 38, 38, 0.1);
                color: #dc2626;
            }

            .cpr-action-btn.dark.text-red-400:hover {
                background-color: rgba(248, 113, 113, 0.1);
                color: #f87171;
            }

            .cpr-action-btn.text-emerald-600:hover {
                background-color: rgba(16, 185, 129, 0.1);
                color: #059669;
            }

            .cpr-action-btn.dark.text-emerald-400:hover {
                background-color: rgba(110, 231, 183, 0.1);
                color: #6ee7b7;
            }

            .cpr-action-btn:disabled {
                cursor: not-allowed;
            }

            /* ===== Pills de status (Controle de Pedidos Retrofit) ===== */
            .cpr-pill {
                display: inline-flex;
                align-items: center;
                justify-content: space-between;
                gap: 6px;
                width: 100%;
                padding: 4px 10px;
                border-radius: 9999px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.02em;
                line-height: 1.1;
                color: #fff;
                border: 0;
                cursor: pointer;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
                transition: filter .12s ease, transform .12s ease;
            }
            .cpr-pill:hover { filter: brightness(1.05); }
            .cpr-pill:active { transform: translateY(1px); }
            .cpr-pill__label {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .cpr-pill__chevron { width: 12px; height: 12px; opacity: .9; flex-shrink: 0; }

            .cpr-pill--analisar           { background: #6b7280; }
            .cpr-pill--cotacao            { background: #f9a8d4; color: #4a1d3a; }
            .cpr-pill--as-enviada         { background: #84cc16; }
            .cpr-pill--entrega-programada { background: #f97316; }
            .cpr-pill--em-execucao        { background: #3b82f6; }
            .cpr-pill--entregue           { background: #16a34a; }
            .cpr-pill--verificar          { background: #ef4444; }

            /* Menu flutuante */
            .cpr-pill-menu {
                position: absolute;
                z-index: 9999;
                min-width: 180px;
                padding: 6px;
                border-radius: 10px;
                background: #ffffff;
                border: 1px solid rgba(0, 0, 0, 0.08);
                box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .dark .cpr-pill-menu {
                background: #1f2937;
                border-color: rgba(255, 255, 255, 0.08);
            }
            .cpr-pill-option {
                display: flex;
                align-items: center;
                gap: 10px;
                width: 100%;
                text-align: left;
                padding: 7px 10px;
                background: transparent;
                border: 0;
                border-radius: 6px;
                cursor: pointer;
                font-size: 12px;
                font-weight: 500;
                color: #374151;
                transition: background-color .12s ease;
            }
            .dark .cpr-pill-option { color: #e5e7eb; }
            .cpr-pill-option:hover { background: rgba(0, 0, 0, 0.05); }
            .dark .cpr-pill-option:hover { background: rgba(255, 255, 255, 0.06); }
            .cpr-pill-option--selected { background: rgba(99, 102, 241, 0.08); }
            .dark .cpr-pill-option--selected { background: rgba(99, 102, 241, 0.16); }

            .cpr-pill-option__dot {
                width: 10px;
                height: 10px;
                border-radius: 9999px;
                flex-shrink: 0;
            }
            .cpr-pill-option__dot[data-color="analisar"]           { background: #6b7280; }
            .cpr-pill-option__dot[data-color="cotacao"]            { background: #f9a8d4; }
            .cpr-pill-option__dot[data-color="as-enviada"]         { background: #84cc16; }
            .cpr-pill-option__dot[data-color="entrega-programada"] { background: #f97316; }
            .cpr-pill-option__dot[data-color="em-execucao"]        { background: #3b82f6; }
            .cpr-pill-option__dot[data-color="entregue"]           { background: #16a34a; }
            .cpr-pill-option__dot[data-color="verificar"]          { background: #ef4444; }

            .cpr-pill-option__label { flex: 1; white-space: nowrap; }

            .cpr-fornecedor-select,
            .cpr-escopo-select { color-scheme: light; }
            .dark .cpr-fornecedor-select,
            .dark .cpr-escopo-select {
                color-scheme: dark;
                background-color: #2a2a2a !important;
                color: #f3f4f6;
            }
            .cpr-fornecedor-select option,
            .cpr-escopo-select option {
                background-color: #ffffff;
                color: #111827;
            }
            .dark .cpr-fornecedor-select option,
            .dark .cpr-escopo-select option {
                background-color: #2a2a2a;
                color: #f3f4f6;
            }

            /* ===== Pill de Status da Unidade (não-editável) ===== */
            .cpr-obra-pill {
                display: inline-flex;
                align-items: center;
                padding: 3px 10px;
                border-radius: 9999px;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.02em;
                color: #fff;
                white-space: nowrap;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            }
            .cpr-obra-pill--success { background: #16a34a; }
            .cpr-obra-pill--info    { background: #3b82f6; }
            .cpr-obra-pill--warning { background: #eab308; color: #3b2900; }
            .cpr-obra-pill--danger  { background: #ef4444; }
            .cpr-obra-pill--neutral { background: #6b7280; }

            /* ===== Responsividade para zoom baixo ===== */
            .cpr-zoom-target {
                position: relative;
            }

            .cpr-zoom-target table {
                border-collapse: collapse;
            }

            .cpr-zoom-target table th,
            .cpr-zoom-target table td {
                overflow: hidden;
                text-overflow: ellipsis;
                word-break: break-word;
            }

            /* Input responsivos nas células */
            .cpr-zoom-target input,
            .cpr-zoom-target select {
                box-sizing: border-box;
            }

            /* Garante que células de QTD, Valor e Fornecedor não quebrem */
            .cpr-zoom-target td input[type="number"],
            .cpr-zoom-target td input[type="text"],
            .cpr-zoom-target td select {
                min-width: 100%;
                width: 100%;
            }

            /* Flex nas células para melhor distribuição */
            .cpr-zoom-target table th {
                flex-shrink: 0;
            }
        </style>
    @endpush

    @php
        $obrasColecao = $this->obras;
        $totalObras = $obrasColecao->total();
        $itensTodos = $obrasColecao->getCollection()->flatMap(fn ($o) => $o->controlesNotaFiscal->flatMap->itens);
        $totalItens = $itensTodos->count();
        $totalContratado = (float) $itensTodos->sum('valor_global_a');
        $totalCapex = (float) $obrasColecao->getCollection()->sum(fn ($o) => (float) ($o->capex ?? 0));
    @endphp

    {{-- KPIs no topo --}}
    {{--
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-xl border border-gray-200 dark:border-white/5 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Obras</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ number_format($totalObras, 0, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                    @svg('heroicon-o-building-office-2', 'w-5 h-5')
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-white/5 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Subelementos</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ number_format($totalItens, 0, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                    @svg('heroicon-o-clipboard-document-list', 'w-5 h-5')
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-white/5 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">CAPEX</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">R$ {{ number_format($totalCapex, 2, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                    @svg('heroicon-o-banknotes', 'w-5 h-5')
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-white/5 bg-white dark:bg-gray-900 p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Valor contratado</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">R$ {{ number_format($totalContratado, 2, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    @svg('heroicon-o-check-badge', 'w-5 h-5')
                </div>
            </div>
        </div>
    </div>
    }}

    {{-- Container principal --}}
    <div class="rounded-xl border border-gray-200 dark:border-white/5 bg-white dark:bg-gray-900 shadow-sm flex flex-col" style="height: calc(100vh - 10rem)">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-white/5 bg-gray-50/60 dark:bg-white/[0.02]">
            <div class="relative flex-1 min-w-[260px]">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                    @svg('heroicon-m-magnifying-glass', 'w-4 h-4')
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="busca"
                    placeholder="Buscar por unidade ou sigla..."
                    class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>

            <div class="gs-table-excel gs-table-excel--page">
                {{ $this->filtrosModalAction }}
            </div>

            @can('create', \App\Models\ControlePedido::class)
                <div class="gs-table-excel gs-table-excel--page">
                    {{ $this->criarControlePedidoRetrofitAction }}
                </div>
            @endcan

            <div
                class="flex items-center gap-1 ml-auto rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 shadow-sm px-1 py-0.5"
                x-data="cprZoomControl()"
                x-init="init()"
            >
                <button
                    type="button"
                    x-on:click="diminuir()"
                    :disabled="zoom <= min"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    title="Diminuir zoom"
                >
                    @svg('heroicon-m-minus', 'w-4 h-4')
                </button>
                <button
                    type="button"
                    x-on:click="resetar()"
                    class="inline-flex items-center justify-center min-w-[3.25rem] h-7 px-2 rounded-md text-xs font-semibold tabular-nums text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                    title="Resetar para 100%"
                    x-text="Math.round(zoom * 100) + '%'"
                ></button>
                <button
                    type="button"
                    x-on:click="aumentar()"
                    :disabled="zoom >= max"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    title="Aumentar zoom"
                >
                    @svg('heroicon-m-plus', 'w-4 h-4')
                </button>
            </div>
        </div>

        {{-- Painel de seleção --}}
        @if(count($selecionadas) > 0)
            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-white/5 bg-yellow-50 dark:bg-yellow-500/10">
                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ count($selecionadas) }} selecionado(s)
                </span>
                <div class="flex items-center gap-2">
                    <x-filament::button size="sm" color="gray" icon="heroicon-m-x-mark" wire:click="executarAcaoEmMassa('limpar_selecao')">
                        Limpar seleção
                    </x-filament::button>
                    <x-filament::button size="sm" color="danger" icon="heroicon-m-trash" wire:click="executarAcaoEmMassa('limpar_selecao')">
                        Excluir selecionados
                    </x-filament::button>
                    <x-filament::button size="sm" color="primary" icon="heroicon-m-arrow-down-tray" wire:click="executarAcaoEmMassa('exportar')">
                        Exportar
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- Active filters (chips) --}}
        @if($this->filtrosAtivos > 0)
            <div class="gs-table-excel gs-table-excel--page">
                <div class="gs-table-excel__active-filters">
                    <span class="gs-table-excel__active-filters-label">Filtros ativos</span>

                    @if(! empty($filtroStatus))
                        @php
                            $rotulos = collect($filtroStatus)->map(fn ($k) => $statusOptions[$k] ?? $k)->implode(', ');
                        @endphp
                        <span class="gs-table-excel__active-filter-chip">
                            <span class="gs-table-excel__active-filter-chip-text">
                                <strong>Status do subelemento:</strong> {{ $rotulos }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroStatus')" title="Remover" aria-label="Remover filtro Status do subelemento">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </span>
                    @endif

                    @if(! empty($filtroStatusUnidade))
                        <span class="gs-table-excel__active-filter-chip">
                            <span class="gs-table-excel__active-filter-chip-text">
                                <strong>Status da unidade:</strong> {{ implode(', ', $filtroStatusUnidade) }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroStatusUnidade')" title="Remover" aria-label="Remover filtro Status da unidade">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </span>
                    @endif

                    @if(! empty($filtroFornecedor))
                        <span class="gs-table-excel__active-filter-chip">
                            <span class="gs-table-excel__active-filter-chip-text">
                                <strong>Fornecedor:</strong> {{ implode(', ', $filtroFornecedor) }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroFornecedor')" title="Remover" aria-label="Remover filtro Fornecedor">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </span>
                    @endif

                    @if(! blank($filtroInicioDe) || ! blank($filtroInicioAte))
                        @php
                            $de = $filtroInicioDe ? \Carbon\Carbon::parse($filtroInicioDe)->format('d/m/Y') : null;
                            $ate = $filtroInicioAte ? \Carbon\Carbon::parse($filtroInicioAte)->format('d/m/Y') : null;
                            $valor = $de && $ate ? "$de → $ate" : ($de ? "a partir de $de" : "até $ate");
                        @endphp
                        <span class="gs-table-excel__active-filter-chip">
                            <span class="gs-table-excel__active-filter-chip-text">
                                <strong>Início obra:</strong> {{ $valor }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroInicio')" title="Remover" aria-label="Remover filtro Início obra">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </span>
                    @endif

                    @if(! blank($filtroFimDe) || ! blank($filtroFimAte))
                        @php
                            $de = $filtroFimDe ? \Carbon\Carbon::parse($filtroFimDe)->format('d/m/Y') : null;
                            $ate = $filtroFimAte ? \Carbon\Carbon::parse($filtroFimAte)->format('d/m/Y') : null;
                            $valor = $de && $ate ? "$de → $ate" : ($de ? "a partir de $de" : "até $ate");
                        @endphp
                        <span class="gs-table-excel__active-filter-chip">
                            <span class="gs-table-excel__active-filter-chip-text">
                                <strong>Fim obra:</strong> {{ $valor }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroFim')" title="Remover" aria-label="Remover filtro Fim obra">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </span>
                    @endif

                    @if(! blank($filtroCapexMin) || ! blank($filtroCapexMax))
                        @php
                            $min = filled($filtroCapexMin) ? 'R$ '.number_format((float) $filtroCapexMin, 2, ',', '.') : null;
                            $max = filled($filtroCapexMax) ? 'R$ '.number_format((float) $filtroCapexMax, 2, ',', '.') : null;
                            $valor = $min && $max ? "$min → $max" : ($min ? "≥ $min" : "≤ $max");
                        @endphp
                        <span class="gs-table-excel__active-filter-chip">
                            <span class="gs-table-excel__active-filter-chip-text">
                                <strong>CAPEX:</strong> {{ $valor }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroCapex')" title="Remover" aria-label="Remover filtro CAPEX">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </span>
                    @endif

                    @if(! blank($filtroValorMin) || ! blank($filtroValorMax))
                        @php
                            $min = filled($filtroValorMin) ? 'R$ '.number_format((float) $filtroValorMin, 2, ',', '.') : null;
                            $max = filled($filtroValorMax) ? 'R$ '.number_format((float) $filtroValorMax, 2, ',', '.') : null;
                            $valor = $min && $max ? "$min → $max" : ($min ? "≥ $min" : "≤ $max");
                        @endphp
                        <span class="gs-table-excel__active-filter-chip">
                            <span class="gs-table-excel__active-filter-chip-text">
                                <strong>Valor contratado:</strong> {{ $valor }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroValor')" title="Remover" aria-label="Remover filtro Valor">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </span>
                    @endif

                    <button type="button" class="gs-table-excel__active-filters-clear" wire:click="limparFiltros" title="Limpar todos">
                        Limpar todos
                    </button>
                </div>
            </div>
        @endif

        {{-- Tabela hierárquica --}}
        <div
            x-data="cprStickyMeasure()"
            x-init="init($el)"
            class="flex-1 min-h-0 overflow-y-auto overflow-x-auto cpr-scroll cpr-zoom-target"
            style="--cpr-thead-h: 0px; --cpr-parent-h: 0px;"
        >
        <div>
            <table class="w-full text-sm cpr-main-table" style="table-layout: auto;">
                <colgroup>
                    <col style="width: 3%; min-width: 40px;">
                    <col style="width: 5%; min-width: 60px;">
                    <col style="width: 7%; min-width: 90px;">
                    <col style="width: 8%; min-width: 110px;">
                    <col style="width: 20%; min-width: 250px;">
                    <col style="width: 10%; min-width: 130px;">
                    <col style="width: 9%; min-width: 115px;">
                    <col style="width: 9%; min-width: 115px;">
                    <col style="width: 15%; min-width: 200px;">
                    <col style="width: 7%; min-width: 90px;">
                    <col style="width: 10%; min-width: 130px;">
                </colgroup>
                <thead
                    class="cpr-thead-main sticky top-0 z-40 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 uppercase text-[11px] tracking-wider border-b border-gray-200 dark:border-white/5 shadow-sm"
                >
                    <tr>
                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-900"></th>
                        <th class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900">Ações</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap">Código</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap">Sigla</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900">Unidade</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap">Status unidade</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap">Início obra</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap">Fim obra</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900">Local</th>
                        <th class="px-4 py-3 text-right font-semibold bg-gray-50 dark:bg-gray-900">CAPEX</th>
                        <th class="px-4 py-3 text-right font-semibold bg-gray-50 dark:bg-gray-900">Valor contratado</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($obrasColecao as $obra)
                        @php
                            $itensDaObra = $obra->controlesNotaFiscal->flatMap->itens;
                            $totalItensObra = $itensDaObra->count();
                            $valorContratado = (float) $itensDaObra->sum('valor_global_a');
                            $aberta = in_array($obra->id, $obrasExpandidas, true);
                        @endphp

                        {{-- LINHA-PAI --}}
                        @php
                            $bgPai = $aberta ? 'bg-primary-50 dark:bg-gray-800' : '';
                            $stickyTd = $aberta ? 'cpr-parent-sticky bg-primary-50 dark:bg-gray-800' : '';
                        @endphp
                        <tr
                            wire:key="obra-row-{{ $obra->id }}"
                            @class([
                                'transition-colors',
                                'cpr-parent-row' => $aberta,
                                'hover:bg-gray-50 dark:hover:bg-white/[0.03]' => ! $aberta,
                            ])
                        >
                            <td class="px-4 py-2.5 align-middle {{ $stickyTd }}">
                                <x-filament::input.checkbox
                                    wire:model.live="selecionadas"
                                    value="obra-{{ $obra->id }}"
                                    @change="selecionarObraeItens({{ $obra->id }}, $event.target.checked)"
                                    x-ref="obraCheckbox_{{ $obra->id }}"
                                />
                            </td>
                            <td class="px-4 py-2.5 text-center {{ $stickyTd }}">
                                <a
                                    href="{{ $this->urlViewObra($obra->id) }}"
                                    target="_blank"
                                    title="Visualizar obra"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400 transition-colors"
                                >
                                    @svg('heroicon-o-eye', 'w-4 h-4')
                                </a>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                @if($obra->codigo)
                                    <span class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-100 dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                        {{ $obra->codigo }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                @if($obra->sigla)
                                    <span class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-100 dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                        {{ $obra->sigla }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 max-w-[280px] whitespace-nowrap {{ $stickyTd }}">
                                <button
                                    type="button"
                                    wire:click="toggleObra({{ $obra->id }})"
                                    class="group flex items-center gap-2 text-left font-medium text-gray-800 dark:text-gray-100 w-full min-w-0"
                                >
                                    <span @class([
                                        'inline-flex items-center justify-center w-5 h-5 rounded text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-transform flex-shrink-0',
                                        'rotate-90 text-primary-600 dark:text-primary-400' => $aberta,
                                    ])>
                                        @svg('heroicon-m-chevron-right', 'w-4 h-4')
                                    </span>
                                    <span class="group-hover:text-primary-700 dark:group-hover:text-primary-300 truncate" title="{{ $obra->unidade }}">{{ $obra->unidade }}</span>
                                    @if($totalItensObra > 0)
                                        <span class="inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1.5 text-[11px] font-semibold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300 flex-shrink-0">
                                            {{ $totalItensObra }}
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                @php $corObra = $this->corStatusObra($obra->status ?? null); @endphp
                                <span class="cpr-obra-pill cpr-obra-pill--{{ $corObra }}">
                                    {{ $obra->status ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300 whitespace-nowrap {{ $stickyTd }}">
                                {{ optional($obra->inicio)->translatedFormat('d M, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300 whitespace-nowrap {{ $stickyTd }}">
                                {{ optional($obra->fim)->translatedFormat('d M, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300 max-w-[220px] {{ $stickyTd }}">
                                @if($obra->endereco)
                                    <span class="block truncate text-xs" title="{{ $obra->endereco }}">{{ $obra->endereco }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right text-gray-700 dark:text-gray-200 tabular-nums {{ $stickyTd }}">
                                {{ number_format((float) ($obra->capex ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums {{ $stickyTd }}">
                                <span @class([
                                    'font-medium',
                                    'text-emerald-600 dark:text-emerald-400' => $valorContratado > 0,
                                    'text-gray-400' => $valorContratado <= 0,
                                ])>
                                    {{ number_format($valorContratado, 2, ',', '.') }}
                                </span>
                            </td>
                        </tr>

                        {{-- LINHAS-FILHO --}}
                        @if($aberta)
                            <tr wire:key="obra-detail-{{ $obra->id }}">
                                <td colspan="12" class="p-0">
                                    <div class="relative bg-gray-50/60 dark:bg-white/[0.02] border-t border-b border-primary-200/60 dark:border-primary-500/20">
                                        {{-- Faixa lateral indicando hierarquia --}}
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500/70 dark:bg-primary-400/60"></div>

                                        @if($totalItensObra === 0)
                                            <div class="pl-6 pr-4 py-5 text-sm text-gray-500 dark:text-gray-400 italic flex items-center gap-2">
                                                @svg('heroicon-o-information-circle', 'w-4 h-4')
                                                Nenhum subelemento contratado para esta obra.
                                            </div>
                                            <button
                                                type="button"
                                                wire:click="adicionarSubelemento({{ $obra->id }})"
                                                class="w-full pl-6 pr-3 py-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 border-t border-dashed border-gray-300 dark:border-white/10 hover:bg-primary-50/40 dark:hover:bg-primary-500/5 transition-colors"
                                            >
                                                @svg('heroicon-m-plus', 'w-3.5 h-3.5')
                                                <span>Adicionar subelemento</span>
                                            </button>
                                        @else
                                            <table class="w-full text-sm" style="table-layout: auto; width: 100%; min-width: 1400px;">
                                                <colgroup>
                                                    <col style="width: 2.5%; min-width: 35px;">
                                                    <col style="width: 4%; min-width: 65px;">
                                                    <col style="width: 4%; min-width: 65px;">
                                                    <col style="width: 10%; min-width: 160px;">
                                                    <col style="width: 10%; min-width: 150px;">
                                                    <col style="width: 4%; min-width: 60px;">
                                                    <col style="width: 6%; min-width: 90px;">
                                                    <col style="width: 6%; min-width: 90px;">
                                                    <col style="width: 10%; min-width: 160px;">
                                                    <col style="width: 7%; min-width: 100px;">
                                                    <col style="width: 7%; min-width: 100px;">
                                                    <col style="width: 9%; min-width: 130px;">
                                                    <col style="width: 9%; min-width: 130px;">
                                                    <col style="width: 9%; min-width: 130px;">
                                                    <col style="width: 7%; min-width: 100px;">
                                                </colgroup>
                                                <thead class="cpr-child-thead bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-wider shadow-sm">
                                                    <tr class="border-b border-gray-200/70 dark:border-white/5">
                                                        <th class="pl-6 pr-3 py-2 bg-gray-100 dark:bg-gray-800"></th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">Grupo</th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">A.S.</th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">Escopo</th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">Escopo complementar</th>
                                                        <th class="px-3 py-2 text-right font-semibold bg-gray-100 dark:bg-gray-800">Qtd.</th>
                                                        <th class="px-3 py-2 text-right font-semibold bg-gray-100 dark:bg-gray-800">% Mão de obra</th>
                                                        <th class="px-3 py-2 text-right font-semibold bg-gray-100 dark:bg-gray-800">% Material</th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">Fornecedor</th>
                                                        <th class="px-3 py-2 text-right font-semibold bg-gray-100 dark:bg-gray-800">Valor</th>
                                                        <th class="px-3 py-2 text-right font-semibold bg-gray-100 dark:bg-gray-800">Valor M.O</th>
                                                        <th class="px-3 py-2 text-right font-semibold bg-gray-100 dark:bg-gray-800">Valor MAT.</th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">Status contratação</th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">Data de entrega</th>
                                                        <th class="px-3 py-2 text-left font-semibold bg-gray-100 dark:bg-gray-800">Observação</th>
                                                        <th class="px-3 py-2 text-center font-semibold bg-gray-100 dark:bg-gray-800">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                                    @foreach($obra->controlesNotaFiscal as $controle)
                                                        @foreach($controle->itens as $item)
                                                            @php $statusAtual = $item->status_retrofit ?? 'analisar'; @endphp
                                                            <tr wire:key="item-row-{{ $item->id }}" class="hover:bg-white dark:hover:bg-white/[0.03] transition-colors">
                                                                <td class="pl-6 pr-3 py-2 align-middle">
                                                                    <x-filament::input.checkbox
                                                                        wire:model.live="selecionadas"
                                                                        value="item-{{ $item->id }}"
                                                                        @change="selecionarItemEObra({{ $item->id }}, {{ $obra->id }}, $event.target.checked)"
                                                                        x-ref="itemCheckbox_{{ $item->id }}"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                                                    <span class="block text-xs font-medium leading-5">
                                                                        {{ filled($item->grupo) ? $item->grupo : ($item->asEscopo?->grupo ?? '—') }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                                                    <span class="block text-xs font-medium leading-5 tabular-nums">
                                                                        @php
                                                                            $numeroAsBase = filled($item->numero_as)
                                                                                ? $item->numero_as
                                                                                : ($item->asEscopo?->numero_as ?? '—');
                                                                            $numeroAsExibido = filled($item->numero_complemento)
                                                                                ? $numeroAsBase.'/'.$item->numero_complemento
                                                                                : $numeroAsBase;
                                                                        @endphp
                                                                        {{ $numeroAsExibido }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                                                                    <select
                                                                        wire:change="atualizarEscopoAsItem({{ $item->id }}, $event.target.value)"
                                                                        class="cpr-escopo-select w-full px-2.5 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-[11px] shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    >
                                                                        <option value="">— Selecionar escopo AS —</option>
                                                                        @foreach($escopoAsOptions as $escopoAsId => $rotuloEscopoAs)
                                                                            <option value="{{ $escopoAsId }}" @selected((string) ($item->as_escopo_id ?? '') === (string) $escopoAsId)>
                                                                                {{ $rotuloEscopoAs }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="text"
                                                                        value="{{ $item->escopo_complementar }}"
                                                                        wire:change="atualizarItem({{ $item->id }}, 'escopo_complementar', $event.target.value)"
                                                                        placeholder="Complemento do escopo"
                                                                        class="w-full px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        value="{{ $item->quantidade }}"
                                                                        wire:change="atualizarItem({{ $item->id }}, 'quantidade', $event.target.value)"
                                                                        x-on:input="if ($event.target.value !== '' && Number($event.target.value) < 0) $event.target.value = 0"
                                                                        placeholder="0"
                                                                        class="w-full px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        max="100"
                                                                        value="{{ $item->percentual_faturamento_mao_obra ?? '' }}"
                                                                        x-data="cprPercentualInput({{ (int) $item->id }}, 'mao_obra', {{ (float) ($item->percentual_faturamento_material ?? 0) }})"
                                                                        x-on:change="aoMudarPercentual($event)"
                                                                        x-ref="percentualMaoObra_{{ $item->id }}"
                                                                        placeholder="0"
                                                                        class="w-full px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        max="100"
                                                                        value="{{ $item->percentual_faturamento_material ?? '' }}"
                                                                        x-data="cprPercentualInput({{ (int) $item->id }}, 'material', {{ (float) ($item->percentual_faturamento_mao_obra ?? 0) }})"
                                                                        x-on:change="aoMudarPercentual($event)"
                                                                        x-ref="percentualMaterial_{{ $item->id }}"
                                                                        placeholder="0"
                                                                        class="w-full px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <select
                                                                        wire:change="atualizarItem({{ $item->id }}, 'empresa', $event.target.value)"
                                                                        class="cpr-fornecedor-select w-full px-2.5 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    >
                                                                        <option value="">— Fornecedor —</option>
                                                                        @foreach($construtoraOptions as $nome)
                                                                            <option value="{{ $nome }}" @selected($item->empresa === $nome)>{{ $nome }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <div
                                                                        class="relative"
                                                                        x-data="cprMoedaInput(@js($item->valor_global_a))"
                                                                    >
                                                                        <span class="absolute inset-y-0 left-2 flex items-center text-[11px] text-gray-400 pointer-events-none">R$</span>
                                                                        <input
                                                                            type="text"
                                                                            inputmode="numeric"
                                                                            x-model="display"
                                                                            x-on:input="aoDigitar($event)"
                                                                            x-on:blur="aoSair($event, {{ (int) $item->id }})"
                                                                            class="w-full pl-7 pr-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                        />
                                                                    </div>
                                                                </td>
                                                                <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200 tabular-nums text-xs">
                                                                    <span
                                                                        class="text-gray-600 dark:text-gray-300"
                                                                        x-data="cprCalcularValor({{ (int) $item->id }}, {{ (float) ($item->valor_global_a ?? 0) }})"
                                                                        x-text="exibir()"
                                                                        @update-values.window="atualizar($event)"
                                                                    >
                                                                        R$ 0,00
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200 tabular-nums text-xs">
                                                                    <span
                                                                        class="text-gray-600 dark:text-gray-300"
                                                                        x-data="cprCalcularValorMaterial({{ (int) $item->id }}, {{ (float) ($item->valor_global_a ?? 0) }})"
                                                                        x-text="exibir()"
                                                                        @update-values.window="atualizar($event)"
                                                                    >
                                                                        R$ 0,00
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    @php $corAtual = $this->corStatus($statusAtual); @endphp
                                                                    <div
                                                                        wire:key="status-pill-{{ $item->id }}-{{ $statusAtual }}"
                                                                        class="cpr-pill-dropdown"
                                                                        x-data="{
                                                                            open: false,
                                                                            pos: { top: 0, left: 0, width: 0 },
                                                                            reposition() {
                                                                                const btn = this.$refs.trigger.getBoundingClientRect();
                                                                                this.pos = {
                                                                                    top: btn.bottom + window.scrollY + 4,
                                                                                    left: btn.left + window.scrollX,
                                                                                    width: btn.width,
                                                                                };
                                                                            },
                                                                            toggle() {
                                                                                this.open = !this.open;
                                                                                if (this.open) {
                                                                                    this.$nextTick(() => this.reposition());
                                                                                }
                                                                            },
                                                                        }"
                                                                        x-on:keydown.escape="open = false"
                                                                        x-on:click.away="open = false"
                                                                    >
                                                                        <button
                                                                            type="button"
                                                                            class="cpr-pill"
                                                                            style="{{ $this->estiloStatus($statusAtual) }}"
                                                                            x-ref="trigger"
                                                                            x-on:click.stop="toggle()"
                                                                            aria-haspopup="listbox"
                                                                            :aria-expanded="open.toString()"
                                                                        >
                                                                            <span class="cpr-pill__label">{{ $statusOptions[$statusAtual] ?? $statusOptions['analisar'] }}</span>
                                                                            <svg class="cpr-pill__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                                                <path d="M5.25 7.5L10 12.25L14.75 7.5"/>
                                                                            </svg>
                                                                        </button>

                                                                        <template x-teleport="body">
                                                                            <div
                                                                                class="cpr-pill-menu"
                                                                                x-show="open"
                                                                                x-transition.opacity.duration.120ms
                                                                                x-cloak
                                                                                :style="`top: ${pos.top}px; left: ${pos.left}px; min-width: ${pos.width}px;`"
                                                                            >
                                                                                @foreach($statusOptions as $key => $label)
                                                                                    @php $optionColor = $this->corStatus($key); @endphp
                                                                                    <div class="flex items-center gap-1 group">
                                                                                        <button
                                                                                            type="button"
                                                                                            class="cpr-pill-option flex-1 @if($statusAtual === $key) cpr-pill-option--selected @endif"
                                                                                            x-on:click.stop="
                                                                                                open = false;
                                                                                                $wire.atualizarStatusItem({{ (int) $item->id }}, @js((string) $key));
                                                                                            "
                                                                                        >
                                                                                            <span class="cpr-pill-option__dot" style="background-color: {{ $optionColor }};"></span>
                                                                                            <span class="cpr-pill-option__label">{{ $label }}</span>
                                                                                        </button>
                                                                                        <button
                                                                                            type="button"
                                                                                            x-on:click.stop="
                                                                                                if (confirm('Tem certeza que deseja deletar este status?')) {
                                                                                                    open = false;
                                                                                                    $wire.deletarStatus(@js((string) $key));
                                                                                                }
                                                                                            "
                                                                                            class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors opacity-0 group-hover:opacity-100"
                                                                                            title="Deletar status"
                                                                                        >
                                                                                            @svg('heroicon-m-trash', 'w-4 h-4')
                                                                                        </button>
                                                                                    </div>
                                                                                @endforeach
                                                                                <div class="border-t border-gray-200 dark:border-white/10 my-1"></div>
                                                                                <button
                                                                                    type="button"
                                                                                    class="cpr-pill-option"
                                                                                    x-on:click.stop="
                                                                                        open = false;
                                                                                        $wire.dispatch('openAdicionarStatusModal');
                                                                                    "
                                                                                >
                                                                                    @svg('heroicon-m-plus', 'w-4 h-4 text-gray-400')
                                                                                    <span class="cpr-pill-option__label">Adicionar status</span>
                                                                                </button>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="date"
                                                                        value="{{ optional($item->data_entrega)->format('Y-m-d') }}"
                                                                        wire:change="atualizarItem({{ $item->id }}, 'data_entrega', $event.target.value)"
                                                                        class="w-full px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="text"
                                                                        value="{{ $item->observacoes }}"
                                                                        wire:change="atualizarItem({{ $item->id }}, 'observacoes', $event.target.value)"
                                                                        placeholder="Observação"
                                                                        class="w-full px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <div class="flex items-center justify-center gap-4">
                                                                        <button
                                                                            type="button"
                                                                            title="Excluir subelemento"
                                                                            class="cpr-action-btn inline-flex items-center gap-1 px-3 py-1.5 text-[11px] font-medium text-red-600 dark:text-red-400 whitespace-nowrap"
                                                                            x-on:click="if (!confirm('Excluir este subelemento? Esta ação não pode ser desfeita.')) return; $wire.excluirItemSubelemento({{ $item->id }})"
                                                                        >
                                                                            @svg('heroicon-m-trash', 'h-4 w-4')
                                                                            <span>Deletar</span>
                                                                        </button>

                                                                        <button
                                                                            type="button"
                                                                            title="{{ filled($item->empresa) ? (filled($item->liberado_para_fornecedor_at) ? 'Já liberado para o fornecedor' : 'Liberar para fornecedor') : 'Selecione um fornecedor primeiro' }}"
                                                                            class="cpr-action-btn inline-flex items-center gap-1 px-3 py-1.5 text-[11px] font-medium whitespace-nowrap {{ filled($item->empresa) && blank($item->liberado_para_fornecedor_at) ? 'text-emerald-600 dark:text-emerald-400' : 'cursor-not-allowed text-gray-400 dark:text-gray-600' }}"
                                                                            @disabled(blank($item->empresa) || filled($item->liberado_para_fornecedor_at))
                                                                            wire:click="liberarItemParaFornecedor({{ $item->id }})"
                                                                        >
                                                                            @svg('heroicon-m-paper-airplane', 'h-4 w-4')
                                                                            <span>{{ filled($item->liberado_para_fornecedor_at) ? 'Liberado' : 'Liberar' }}</span>
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endforeach
                                                    <tr wire:key="add-row-{{ $obra->id }}">
                                                        <td colspan="12" class="p-0">
                                                            <button
                                                                type="button"
                                                                wire:click="adicionarSubelemento({{ $obra->id }})"
                                                                class="w-full pl-6 pr-3 py-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 border-t border-dashed border-gray-300 dark:border-white/10 hover:bg-primary-50/40 dark:hover:bg-primary-500/5 transition-colors"
                                                            >
                                                                @svg('heroicon-m-plus', 'w-3.5 h-3.5')
                                                                <span>Adicionar subelemento</span>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-500 dark:text-gray-400">
                                    @svg('heroicon-o-inbox', 'w-10 h-10 text-gray-300 dark:text-gray-600')
                                    <p class="text-sm font-medium">Nenhuma obra encontrada</p>
                                    <p class="text-xs">Ajuste os filtros ou tente outra busca.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        {{-- Rodapé com paginação --}}
        @if($obrasColecao->total() > 0)
            <x-controle-pagination :paginator="$obrasColecao" item-label="obra(s)" />
        @endif
    </div>

    @push('scripts')
        <script>
            window.selecionarObraeItens = function(obraId, checked) {
                const checkboxes = document.querySelectorAll('input[value^="item-"]');
                const obraCheckbox = document.querySelector(`input[value="obra-${obraId}"]`);

                if (!obraCheckbox) return;

                checkboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    if (!row) return;

                    const obraRow = row.closest('tr[wire\\:key^="obra-row-"]');
                    if (!obraRow) return;

                    const rowObraId = obraRow.getAttribute('wire:key')?.match(/obra-row-(\d+)/)?.[1];
                    if (String(rowObraId) === String(obraId)) {
                        checkbox.checked = checked;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            };

            window.selecionarItemEObra = function(itemId, obraId, checked) {
                const obraCheckbox = document.querySelector(`input[value="obra-${obraId}"]`);
                if (!obraCheckbox) return;

                const itemCheckboxes = document.querySelectorAll(`input[value^="item-"]`);
                let todosItensObraSelecionados = true;

                itemCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    if (!row) return;

                    const obraRow = row.closest('tr[wire\\:key^="obra-row-"]');
                    if (!obraRow) return;

                    const rowObraId = obraRow.getAttribute('wire:key')?.match(/obra-row-(\d+)/)?.[1];
                    if (String(rowObraId) === String(obraId) && !checkbox.checked) {
                        todosItensObraSelecionados = false;
                    }
                });

                obraCheckbox.checked = todosItensObraSelecionados;
                obraCheckbox.dispatchEvent(new Event('change', { bubbles: true }));
            };

            document.addEventListener('alpine:init', () => {
                window.cprFormatBRL = function (cents) {
                    if (cents === null || cents === undefined || isNaN(cents)) return '';
                    const valor = (Number(cents) / 100).toFixed(2);
                    const [inteira, decimal] = valor.split('.');
                    const inteiraFmt = inteira.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    return inteiraFmt + ',' + decimal;
                };

                window.cprParseBRL = function (texto) {
                    if (texto === null || texto === undefined || texto === '') return null;
                    const digits = String(texto).replace(/\D/g, '');
                    if (digits === '') return null;
                    return Number(digits) / 100;
                };

                Alpine.data('cprStickyMeasure', () => ({
                    root: null,
                    observer: null,
                    init(el) {
                        this.root = el;
                        const measure = () => this.measure();
                        measure();
                        this.$nextTick(() => measure());
                        this.observer = new ResizeObserver(measure);
                        this.observe();
                        window.addEventListener('resize', measure);
                        if (window.Livewire && Livewire.hook) {
                            Livewire.hook('morph.updated', () => {
                                measure();
                                this.observe();
                            });
                        }
                    },
                    observe() {
                        if (!this.root || !this.observer) return;
                        this.root.querySelectorAll('.cpr-thead-main, .cpr-parent-row').forEach((el) => this.observer.observe(el));
                    },
                    measure() {
                        if (!this.root) return;
                        const mainHead = this.root.querySelector('.cpr-thead-main');
                        const parentRow = this.root.querySelector('.cpr-parent-row');
                        if (mainHead) {
                            this.root.style.setProperty('--cpr-thead-h', mainHead.getBoundingClientRect().height + 'px');
                        }
                        if (parentRow) {
                            this.root.style.setProperty('--cpr-parent-h', parentRow.getBoundingClientRect().height + 'px');
                        } else {
                            this.root.style.setProperty('--cpr-parent-h', '0px');
                        }
                    },
                }));

                Alpine.data('cprZoomControl', () => ({
                    zoom: 1,
                    min: 0.6,
                    max: 1.5,
                    step: 0.1,
                    storageKey: 'cpr-retrofit-zoom',
                    init() {
                        const salvo = parseFloat(localStorage.getItem(this.storageKey));
                        if (!isNaN(salvo) && salvo >= this.min && salvo <= this.max) {
                            this.zoom = salvo;
                        }

                        if (!window.cprAplicarZoomRetrofit) {
                            window.cprAplicarZoomRetrofit = (zoom) => {
                                const valor = Number(zoom);

                                if (Number.isNaN(valor)) {
                                    return;
                                }

                                document.querySelectorAll('.cpr-zoom-target').forEach((el) => {
                                    el.style.zoom = valor;
                                });
                            };
                        }

                        this.aplicar();

                        if (window.Livewire && Livewire.hook && !window.__cprZoomRetrofitHookRegistered) {
                            window.__cprZoomRetrofitHookRegistered = true;

                            Livewire.hook('morph.updated', () => {
                                const salvoAtual = parseFloat(localStorage.getItem(this.storageKey));
                                const zoomAtual = !isNaN(salvoAtual) && salvoAtual >= this.min && salvoAtual <= this.max
                                    ? salvoAtual
                                    : this.zoom;

                                window.cprAplicarZoomRetrofit(zoomAtual);
                            });
                        }
                    },
                    aplicar() {
                        window.cprAplicarZoomRetrofit(this.zoom);
                        localStorage.setItem(this.storageKey, String(this.zoom));
                    },
                    aumentar() {
                        const proximo = Math.min(this.max, Math.round((this.zoom + this.step) * 100) / 100);
                        this.zoom = proximo;
                        this.aplicar();
                    },
                    diminuir() {
                        const proximo = Math.max(this.min, Math.round((this.zoom - this.step) * 100) / 100);
                        this.zoom = proximo;
                        this.aplicar();
                    },
                    resetar() {
                        this.zoom = 1;
                        this.aplicar();
                    },
                }));

                Alpine.data('cprMoedaInput', (valorInicial) => ({
                    display: '',
                    init() {
                        if (valorInicial === null || valorInicial === undefined || valorInicial === '') {
                            this.display = '';
                        } else {
                            const cents = Math.round(Number(valorInicial) * 100);
                            this.display = window.cprFormatBRL(cents);
                        }
                    },
                    aoDigitar(event) {
                        const digits = String(event.target.value).replace(/\D/g, '');
                        if (digits === '') {
                            this.display = '';
                            return;
                        }
                        this.display = window.cprFormatBRL(Number(digits));
                    },
                    aoSair(event, itemId) {
                        const valor = window.cprParseBRL(this.display);
                        this.$wire.atualizarItem(itemId, 'valor_global_a', valor === null ? '' : String(valor));
                        window.dispatchEvent(new CustomEvent('update-values', { detail: { itemId } }));
                    },
                }));

                Alpine.data('cprPercentualInput', (itemId, tipo, percentualComplementar) => ({
                    init() {},
                    aoMudarPercentual(event) {
                        const valor = Number(event.target.value) || 0;
                        const complementar = Math.round((100 - valor) * 100) / 100;
                        const tipoComplementar = tipo === 'mao_obra' ? 'material' : 'mao_obra';
                        const fieldComplementar = tipo === 'mao_obra' ? 'percentual_faturamento_material' : 'percentual_faturamento_mao_obra';

                        this.$wire.atualizarItem(itemId, tipo === 'mao_obra' ? 'percentual_faturamento_mao_obra' : 'percentual_faturamento_material', String(valor));

                        setTimeout(() => {
                            const inputComplementar = document.querySelector(`input[x-ref="percentual${tipo === 'mao_obra' ? 'Material' : 'MaoObra'}_${itemId}"]`);
                            if (inputComplementar) {
                                inputComplementar.value = complementar;
                                this.$wire.atualizarItem(itemId, fieldComplementar, String(complementar));
                            }
                            window.dispatchEvent(new CustomEvent('update-values', { detail: { itemId } }));
                        }, 50);
                    },
                }));

                Alpine.data('cprCalcularValor', (itemId, valorTotal) => ({
                    init() {
                        this.$watch(() => this.exibir(), (novoValor) => {
                            // Re-calcula quando alguma coisa mudar
                        });
                    },
                    exibir() {
                        const inputMaoObra = document.querySelector(`input[x-ref="percentualMaoObra_${itemId}"]`);
                        if (inputMaoObra) {
                            const percentual = Number(inputMaoObra.value) || 0;
                            const valor = valorTotal * (percentual / 100);
                            const cents = Math.round(valor * 100);
                            return 'R$ ' + window.cprFormatBRL(cents);
                        }
                        return 'R$ 0,00';
                    },
                    atualizar(event) {
                        if (event.detail && event.detail.itemId === itemId) {
                            this.$el.textContent = this.exibir();
                        }
                    },
                }));

                Alpine.data('cprCalcularValorMaterial', (itemId, valorTotal) => ({
                    init() {
                        this.$watch(() => this.exibir(), (novoValor) => {
                            // Re-calcula quando alguma coisa mudar
                        });
                    },
                    exibir() {
                        const inputMaterial = document.querySelector(`input[x-ref="percentualMaterial_${itemId}"]`);
                        if (inputMaterial) {
                            const percentual = Number(inputMaterial.value) || 0;
                            const valor = valorTotal * (percentual / 100);
                            const cents = Math.round(valor * 100);
                            return 'R$ ' + window.cprFormatBRL(cents);
                        }
                        return 'R$ 0,00';
                    },
                    atualizar(event) {
                        if (event.detail && event.detail.itemId === itemId) {
                            this.$el.textContent = this.exibir();
                        }
                    },
                }));
            });
        </script>
    @endpush

    {{-- Modal Adicionar Status --}}
    <div
        x-data="{ open: @entangle('abrirModalAdicionarStatus') }"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="open = false"
    >
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Adicionar Status de Contratação</h2>
            </div>

            <form wire:submit.prevent="submeterNovoStatus" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nome do Status
                    </label>
                    <input
                        type="text"
                        wire:model="novoStatusNome"
                        placeholder="Ex: FINALIZADO, PAUSADO, etc"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Cor
                    </label>
                    <input
                        type="color"
                        wire:model="novoStatusCor"
                        value="#10b981"
                        class="w-full h-12 rounded-lg border border-gray-300 dark:border-white/10 cursor-pointer"
                    />
                </div>

                <div class="flex gap-3 justify-end pt-4 border-t border-gray-200 dark:border-white/10">
                    <button
                        type="button"
                        wire:click="$set('abrirModalAdicionarStatus', false)"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 font-medium transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-medium transition-colors"
                    >
                        Criar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-filament::page>
