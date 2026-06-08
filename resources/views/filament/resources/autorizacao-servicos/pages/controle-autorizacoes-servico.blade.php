<x-filament::page>
    @push('styles')
        <style>
            .cpr-scroll .cpr-thead-main > tr > th {
                position: sticky;
                top: 0;
                z-index: 40;
                background-color: #f9fafb;
                background-clip: padding-box;
            }

            .dark .cpr-scroll .cpr-thead-main > tr > th {
                background-color: #18181b;
            }

            .cpr-scroll .cpr-parent-sticky {
                position: sticky;
                top: var(--cpr-thead-h, 0px);
                z-index: 30;
                background-color: #eff6ff;
                background-clip: padding-box;
            }

            .dark .cpr-scroll .cpr-parent-sticky {
                background-color: #27272a;
            }

            .cpr-scroll .cpr-child-thead > tr > th {
                position: sticky;
                top: calc(var(--cpr-thead-h, 0px) + var(--cpr-parent-h, 0px));
                z-index: 20;
                background-color: #f3f4f6;
                background-clip: padding-box;
            }

            .dark .cpr-scroll .cpr-child-thead > tr > th {
                background-color: #27272a;
            }

            .cpr-scroll table {
                border-collapse: separate;
                border-spacing: 0;
            }

            .cpr-zoom-target {
                zoom: var(--cpr-as-zoom, 1);
                transform-origin: top left;
            }

            .cpr-scroll .cpr-parent-row + tr > td {
                overflow: visible;
            }
            .cpr-hover-row > td {
                transition: background-color 120ms ease;
            }
            .cpr-hover-row:hover > td {
                background-color: #f3f4f6;
            }
            .dark .cpr-hover-row:hover > td {
                background-color: #27272a;
            }
            .cpr-fornecedor-select { color-scheme: light; }
            .dark .cpr-fornecedor-select {
                color-scheme: dark;
                background-color: #2a2a2a !important;
                color: #f3f4f6;
            }
            .cpr-fornecedor-select option {
                background-color: #ffffff;
                color: #111827;
            }
            .dark .cpr-fornecedor-select option {
                background-color: #2a2a2a;
                color: #f3f4f6;
            }
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
            .cpr-row-unsaved > td {
                background-color: #fef9c3;
            }
            .cpr-row-unsaved:hover > td {
                background-color: #fde68a;
            }
            .dark .cpr-row-unsaved > td {
                background-color: rgba(234, 179, 8, 0.14);
            }
            .dark .cpr-row-unsaved:hover > td {
                background-color: rgba(234, 179, 8, 0.28);
            }
            .cpr-row-oi-manual > td {
                background-color: #eff6ff;
            }
            .cpr-row-oi-manual:hover > td {
                background-color: #dbeafe;
            }
            .dark .cpr-row-oi-manual > td {
                background-color: rgba(59, 130, 246, 0.12);
            }
            .dark .cpr-row-oi-manual:hover > td {
                background-color: rgba(59, 130, 246, 0.22);
            }
            .cpr-money-field {
                position: relative;
                display: block;
                width: max-content;
                margin-left: auto;
            }
            .cpr-money-mask {
                box-sizing: border-box;
                width: calc((var(--cpr-money-ch, 4) * 1ch) + 2.75rem);
                max-width: none;
            }
            .cpr-money-field::before {
                content: 'R$';
                position: absolute;
                left: 0.375rem;
                top: 50%;
                transform: translateY(-50%);
                z-index: 1;
                font-size: 0.6875rem;
                font-weight: 600;
                line-height: 1;
                color: #6b7280;
                pointer-events: none;
            }
            .dark .cpr-money-field::before {
                color: #9ca3af;
            }
            .cpr-money-field--oi-manual::before {
                color: #2563eb;
            }
            .dark .cpr-money-field--oi-manual::before {
                color: #60a5fa;
            }
            .cpr-money-display {
                display: inline-flex;
                align-items: baseline;
                justify-content: flex-end;
                gap: 0.25rem;
                white-space: nowrap;
            }
            .cpr-money-display::before {
                content: 'R$';
                font-size: 0.6875rem;
                font-weight: 600;
                color: #6b7280;
            }
            .dark .cpr-money-display::before {
                color: #9ca3af;
            }
            .fi-modal > .fi-modal-close-overlay,
            .fi-modal > .fi-modal-window-ctn {
                z-index: 10020;
            }
        </style>
    @endpush

    @php
        $obrasColecao = $this->obras;
        $podeAtualizarFluxo = $this->podeAtualizarFluxo();
        $podeCriarFluxo = $this->podeCriarFluxo();
        $podeMutarFluxo = $podeAtualizarFluxo || $podeCriarFluxo;
    @endphp

    <div class="rounded-xl border border-gray-200 dark:border-white/5 bg-white dark:bg-gray-900 shadow-sm flex flex-col" style="height: calc(100vh - 10rem)">
        <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-white/5 bg-gray-50/60 dark:bg-white/[0.02]">
            <div class="relative flex-1 min-w-[260px]">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                    @svg('heroicon-m-magnifying-glass', 'w-4 h-4')
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="busca"
                    placeholder="Buscar por código, unidade ou sigla..."
                    class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>

            <div class="gs-table-excel gs-table-excel--page">
                {{ $this->filtrosModalAction }}
            </div>

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
                                <strong>Status do controle:</strong> {{ $rotulos }}
                            </span>
                            <button type="button" class="gs-table-excel__active-filter-chip-remove" wire:click="removerFiltro('filtroStatus')" title="Remover" aria-label="Remover filtro Status do controle">
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
                                <strong>Valor fechado:</strong> {{ $valor }}
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

        <div
            x-data="cprStickyMeasure()"
            x-init="init($el)"
            class="flex-1 min-h-0 overflow-y-auto overflow-x-auto cpr-scroll cpr-zoom-target"
            style="--cpr-thead-h: 0px; --cpr-parent-h: 0px;"
        >
            <div>
                <table class="w-full text-sm" style="table-layout: auto; min-width: 2121px;">
                    <thead
                        class="cpr-thead-main sticky top-0 z-40 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 uppercase text-[11px] tracking-wider border-b border-gray-200 dark:border-white/5 shadow-sm"
                    >
                        <tr>
                            <th rowspan="2" class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900" style="width: 120px;">Ações</th>
                            <th rowspan="2" class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 110px;">Código</th>
                            <th rowspan="2" class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 130px;">Sigla</th>
                            <th rowspan="2" class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900" style="width: 280px;">Unidade</th>
                            <th colspan="3" class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20">Ordem de Investimento - OI</th>
                            <th colspan="3" class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20">Valor Inicial</th>
                            <th colspan="4" class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20">Valor Final</th>
                            <th colspan="4" class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20">Indicadores</th>
                        </tr>
                        <tr>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20" style="width: 125px;">Shell</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 125px;">Recheio</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 120px;">Total</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20" style="width: 125px;">Shell</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 125px;">Recheio</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 120px;">Total</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20" style="width: 125px;">Shell</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 125px;">Recheio</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 155px;">Adicional</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 120px;">Total</th>
                            <th class="px-4 py-2 text-right font-semibold bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-300 dark:border-white/20" style="width: 120px;">Desvio</th>
                            <th class="px-4 py-2 text-right font-semibold whitespace-nowrap bg-gray-50 dark:bg-gray-900" style="width: 100px;">% Desvio</th>
                            <th class="px-4 py-2 text-right font-semibold whitespace-nowrap bg-gray-50 dark:bg-gray-900" style="width: 100px;">% Shell</th>
                            <th class="px-4 py-2 text-right font-semibold whitespace-nowrap bg-gray-50 dark:bg-gray-900" style="width: 115px;">% Adicional</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse($obrasColecao as $obra)
                            @php
                                $resumo = $this->prepararResumo($obra);
                                $itensDaObra = collect($this->itensPrincipais($obra));
                                $itensAdicionais = collect($this->itensAdicionais($obra));
                                $totalItensObra = $itensDaObra->count() + $itensAdicionais->count();
                                $aberta = in_array($obra->id, $obrasExpandidas, true);
                                $sigla = $obra->projeto?->sigla;
                                $stickyTd = $aberta ? 'cpr-parent-sticky bg-primary-50 dark:bg-gray-800' : '';
                            @endphp

                            <tr
                                wire:key="as-obra-row-{{ $obra->id }}"
                                @class([
                                    'cpr-hover-row',
                                    'cpr-parent-row' => $aberta,
                                ])
                            >
                                <td class="px-4 py-2.5 text-center {{ $stickyTd }}">
                                    <div class="flex items-center justify-center gap-1">
                                        <a
                                            href="{{ $this->urlViewObra($obra->id) }}"
                                            target="_blank"
                                            title="Visualizar obra"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400 transition-colors"
                                        >
                                            @svg('heroicon-o-eye', 'w-4 h-4')
                                        </a>
                                        @if($podeAtualizarFluxo)
                                            <button
                                                type="button"
                                                x-on:click="$wire.salvarItensObraComDados({{ $obra->id }}, window.cprAsCollectObraPayload({{ $obra->id }})).then(() => window.cprAsMarkObraSaved({{ $obra->id }}))"
                                                title="Salvar alterações dos escopos da obra"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400 transition-colors"
                                            >
                                                @svg('heroicon-o-document-check', 'w-4 h-4')
                                            </button>
                                            <button
                                                type="button"
                                                x-on:click="$wire.mountAction('sincronizarSimuladorOiObra', { obraId: {{ $obra->id }} })"
                                                title="Importar valores da Simulação OI aprovada para todos os escopos da obra"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400 transition-colors"
                                            >
                                                @svg('heroicon-o-arrow-path', 'w-4 h-4')
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                    @if($obra->codigo)
                                        <span class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-100 dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                            {{ $obra->codigo }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                    @if($sigla)
                                        <span class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-100 dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                            {{ $sigla }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
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

                                <td class="px-3 py-2.5 text-right tabular-nums border-l-2 border-gray-300 dark:border-white/20 {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($resumo['oi_shell'] ?? 0) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($resumo['oi_recheio'] ?? 0) }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-gray-800 dark:text-gray-100 {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($this->totalResumo($obra->id, 'oi')) }}</span>
                                </td>

                                <td class="px-3 py-2.5 text-right tabular-nums border-l-2 border-gray-300 dark:border-white/20 {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($resumo['valor_inicial_shell'] ?? 0) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($resumo['valor_inicial_recheio'] ?? 0) }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-gray-800 dark:text-gray-100 {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($this->totalResumo($obra->id, 'valor_inicial')) }}</span>
                                </td>

                                <td class="px-3 py-2.5 text-right tabular-nums border-l-2 border-gray-300 dark:border-white/20 {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($resumo['valor_final_shell'] ?? 0) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($resumo['valor_final_recheio'] ?? 0) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($resumo['valor_final_adicional'] ?? 0) }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-gray-800 dark:text-gray-100 {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($this->totalResumo($obra->id, 'valor_final')) }}</span>
                                </td>

                                <td class="px-3 py-2.5 text-right tabular-nums border-l-2 border-gray-300 dark:border-white/20 {{ $stickyTd }}">
                                    <span class="cpr-money-display">{{ $this->formatMoeda($this->desvioResumo($obra->id)) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap {{ $stickyTd }}">
                                    {{ $this->formatPercentual($this->percentualDesvioResumo($obra->id)) }}
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap {{ $stickyTd }}">
                                    {{ $this->formatPercentual($this->percentualShellResumo($obra->id)) }}
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap {{ $stickyTd }}">
                                    {{ $this->formatPercentual($this->percentualAdicionalResumo($obra->id)) }}
                                </td>
                            </tr>

                            @if($aberta)
                                <tr wire:key="as-obra-detail-{{ $obra->id }}">
                                    <td colspan="18" class="p-0">
                                        <div class="relative bg-gray-50/60 dark:bg-white/[0.02] border-t border-b border-primary-200/60 dark:border-primary-500/20">
                                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500/70 dark:bg-primary-400/60"></div>

                                            @if($totalItensObra === 0)
                                                <div class="pl-6 pr-4 py-5 text-sm text-gray-500 dark:text-gray-400 italic flex items-center gap-2">
                                                    @svg('heroicon-o-information-circle', 'w-4 h-4')
                                                    Nenhum escopo principal vinculado a esta obra.
                                                </div>
                                            @else
                                                <div
                                                    x-data="{
                                                        selected: [],
                                                        selectableIds() {
                                                            return Array.from(this.$root.querySelectorAll('[data-as-bulk-checkbox]')).map((input) => String(input.value));
                                                        },
                                                        toggleAll(event) {
                                                            this.selected = event.target.checked ? this.selectableIds() : [];
                                                        },
                                                        clearSelection() {
                                                            this.selected = [];
                                                        },
                                                        get allSelected() {
                                                            const ids = this.selectableIds();

                                                            return ids.length > 0 && ids.every((id) => this.selected.includes(id));
                                                        },
                                                    }"
                                                >
                                                    @if($podeAtualizarFluxo)
                                                        <div class="flex items-center gap-3 border-b border-gray-200/70 bg-gray-100/80 px-6 py-2 text-xs dark:border-white/10 dark:bg-gray-800/70">
                                                            <label class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-300">
                                                                <input
                                                                    type="checkbox"
                                                                    x-on:change="toggleAll($event)"
                                                                    x-bind:checked="allSelected"
                                                                    title="Selecionar linhas em rascunho"
                                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-white/20 dark:bg-white/5"
                                                                />
                                                                <span>Selecionar todos</span>
                                                            </label>
                                                            <button
                                                                type="button"
                                                                x-on:click="$wire.removerLinhasRascunhoObra({{ $obra->id }}, selected).then(() => clearSelection())"
                                                                x-bind:disabled="selected.length === 0"
                                                                class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-danger-600 transition-colors hover:bg-danger-50 hover:text-danger-700 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                                                            >
                                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                                                <span>Apagar selecionadas</span>
                                                            </button>
                                                            <span class="text-gray-500 dark:text-gray-400" x-text="selected.length === 1 ? '1 linha selecionada' : `${selected.length} linhas selecionadas`">0 linhas selecionadas</span>
                                                        </div>
                                                    @endif
                                                <table class="w-full text-sm" style="min-width: 2295px;">
                                                    <thead class="cpr-child-thead bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-wider shadow-sm">
                                                        <tr class="border-b border-gray-200/70 dark:border-white/5">
                                                            <th class="px-3 py-2 text-center font-semibold w-[45px] bg-gray-100 dark:bg-gray-800">Sel.</th>
                                                            <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Grupo</th>
                                                            <th class="px-3 py-2 text-left font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">AS</th>
                                                            <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Complemento</th>
                                                            <th class="px-3 py-2 text-left font-semibold min-w-[300px] bg-gray-100 dark:bg-gray-800">Escopo</th>
                                                            <th class="px-3 py-2 text-left font-semibold min-w-[220px] bg-gray-100 dark:bg-gray-800">Escopo Complementar</th>
                                                            <th class="px-3 py-2 text-left font-semibold min-w-[230px] bg-gray-100 dark:bg-gray-800">Fornecedor</th>
                                                            <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Valor Estimado</th>
                                                            <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Valor Fechado</th>
                                                            <th class="px-3 py-2 text-right font-semibold w-[100px] bg-gray-100 dark:bg-gray-800">% M.O.</th>
                                                            <th class="px-3 py-2 text-right font-semibold w-[100px] bg-gray-100 dark:bg-gray-800">% Material</th>
                                                            <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Faturado</th>
                                                            <th class="px-3 py-2 text-right font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Saldo</th>
                                                            <th class="px-3 py-2 text-right font-semibold w-[90px] bg-gray-100 dark:bg-gray-800">% Saldo</th>
                                                            <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Status</th>
                                                            <th class="px-3 py-2 text-left font-semibold w-[280px] bg-gray-100 dark:bg-gray-800">Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody
                                                        class="divide-y divide-gray-100 dark:divide-white/5"
                                                        data-as-obra-detail="{{ $obra->id }}"
                                                        x-data="{}"
                                                    >
                                                        @foreach($itensDaObra as $item)
                                                            @php
                                                                $estadoItem = $this->prepararItem($item);
                                                                $status = $item->autorizacaoServico?->status?->value;
                                                                $asCriada = $item->autorizacaoServico !== null;
                                                                $asStatusCriada = $status === 'criada';
                                                                $asCancelada = $status === 'cancelada';
                                                                $linhaImutavel = in_array($status, ['enviada', 'cancelada'], true);
                                                                $podeCriarAs = ! $asCriada;
                                                                $podeEnviarAs = in_array($status, ['criada', 'em_orcamento'], true);
                                                                $podeCancelarAs = $this->podeCancelarAs($item);
                                                                $linhaRascunhoRemovivel = $this->linhaRascunhoRemovivel($item);
                                                                $numeroComplemento = data_get($estadoItem, 'numero_complemento')
                                                                    ?: $item->autorizacaoServico?->numero_complemento
                                                                    ?: $item->numero_complemento;
                                                                $asEscopoIdEstado = data_get($estadoItem, 'as_escopo_id');
                                                                $opcaoEscopoLocal = filled($asEscopoIdEstado) && ! array_key_exists((int) $asEscopoIdEstado, $asEscopoOptions)
                                                                    ? ($item->asEscopo?->escopo ?? $item->escopo)
                                                                    : null;
                                                                $valorEstimadoManual = $this->valorEstimadoForaSimuladorOi($estadoItem);
                                                                $statusEnum = $item->autorizacaoServico?->status;
                                                                $statusCor = $statusEnum instanceof \App\Enums\AsStatus ? $statusEnum->color() : 'neutral';
                                                                $statusLabel = $statusEnum instanceof \App\Enums\AsStatus ? $statusEnum->label() : 'Rascunho';
                                                            @endphp
                                                            <tr
                                                                wire:key="as-item-row-{{ $item->id }}-{{ optional($item->updated_at)->timestamp ?? 0 }}"
                                                                x-data="{
                                                                    asEscopoId: @js((string) data_get($estadoItem, 'as_escopo_id', '')),
                                                                    numeroComplemento: @js((string) ($numeroComplemento ?? '')),
                                                                    complementoSalvo: @js((string) ($numeroComplemento ?? '')),
                                                                    construtoraId: @js((string) data_get($estadoItem, 'construtora_id', '')),
                                                                    construtoraSalva: @js((string) data_get($estadoItem, 'construtora_id', '')),
                                                                    escopoComplementar: @js((string) data_get($estadoItem, 'escopo_complementar', '')),
                                                                    valorEstimado: @js((string) data_get($estadoItem, 'valor_estimado', '')),
                                                                    valorEstimadoSimulador: @js(data_get($estadoItem, 'valor_estimado_as_simulador')),
                                                                    valorFechado: @js((string) data_get($estadoItem, 'valor_fechado', '')),
                                                                    percentualMaoObra: @js((string) data_get($estadoItem, 'percentual_faturamento_mao_obra', '60')),
                                                                    percentualMaterial: @js((string) data_get($estadoItem, 'percentual_faturamento_material', '40')),
                                                                    rascunho: @js(! $asCriada),
                                                                    dirty: false,
                                                                    asEscopoSalvo: @js((string) ($item->as_escopo_id ?? '')),
                                                                    metadados: @js($asEscopoMetadados),
                                                                    grupoSalvo: @js($item->asEscopo?->grupo ?? '-'),
                                                                    numeroAsSalvo: @js($item->asEscopo?->numero_as ?? $item->numero_as ?? '-'),
                                                                    escopoSalvo: @js($item->asEscopo?->escopo ?? $item->escopo ?? '-'),
                                                                    changed() {
                                                                        this.dirty = true;
                                                                    },
                                                                    normalizePercent(value) {
                                                                        const normalized = String(value ?? '').replace('%', '').replace(/\s/g, '').replace(',', '.');
                                                                        const parsed = Number.parseFloat(normalized);

                                                                        if (Number.isNaN(parsed)) {
                                                                            return 0;
                                                                        }

                                                                        return Math.min(100, Math.max(0, parsed));
                                                                    },
                                                                    formatPercent(value) {
                                                                        return this.normalizePercent(value).toFixed(2).replace('.', ',');
                                                                    },
                                                                    sanitizePercentInput(value) {
                                                                        const normalized = String(value ?? '')
                                                                            .replace(/[^\d,.]/g, '')
                                                                            .replace(/\./g, ',')
                                                                            .replace(/,+/g, ',');
                                                                        const parts = normalized.split(',');
                                                                        const integer = (parts[0] || '').slice(0, 3);
                                                                        const decimal = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;

                                                                        return decimal === null ? integer : `${integer},${decimal}`;
                                                                    },
                                                                    normalizeMoney(value) {
                                                                        let normalized = String(value ?? '').replace(/[^\d,.-]/g, '');

                                                                        if (normalized.includes(',')) {
                                                                            normalized = normalized.replace(/\./g, '').replace(',', '.');
                                                                        }

                                                                        const parsed = Number.parseFloat(normalized);

                                                                        return Number.isNaN(parsed) ? 0 : parsed;
                                                                    },
                                                                    valorEstimadoManual() {
                                                                        if (this.valorEstimadoSimulador === null || this.valorEstimadoSimulador === '') {
                                                                            return false;
                                                                        }

                                                                        return Math.round(this.normalizeMoney(this.valorEstimado) * 100) !== Math.round(this.normalizeMoney(this.valorEstimadoSimulador) * 100);
                                                                    },
                                                                    updateMaoObraPercent() {
                                                                        const maoObra = this.normalizePercent(this.percentualMaoObra);

                                                                        this.percentualMaoObra = this.formatPercent(maoObra);
                                                                        this.percentualMaterial = this.formatPercent(100 - maoObra);
                                                                        this.changed();
                                                                    },
                                                                    updateMaterialPercent() {
                                                                        const material = this.normalizePercent(this.percentualMaterial);

                                                                        this.percentualMaterial = this.formatPercent(material);
                                                                        this.percentualMaoObra = this.formatPercent(100 - material);
                                                                        this.changed();
                                                                    },
                                                                    async applyEscopoDefaults() {
                                                                        const asEscopoId = String(this.asEscopoId || '');
                                                                        const metadados = asEscopoId && this.metadados[asEscopoId]
                                                                            ? this.metadados[asEscopoId]
                                                                            : null;

                                                                        if (metadados) {
                                                                            this.percentualMaoObra = this.formatPercent(metadados.percentual_faturamento_mao_obra_default ?? 60);
                                                                            this.percentualMaterial = this.formatPercent(metadados.percentual_faturamento_material_default ?? (100 - this.normalizePercent(this.percentualMaoObra)));
                                                                        }

                                                                        this.changed();

                                                                        const estado = await $wire.atualizarEscopoItemComComplemento({{ $item->id }}, this.asEscopoId || null);
                                                                        this.asEscopoId = estado.as_escopo_id ? String(estado.as_escopo_id) : '';
                                                                        this.asEscopoSalvo = this.asEscopoId;
                                                                        this.numeroComplemento = estado.numero_complemento || '';
                                                                        this.complementoSalvo = this.numeroComplemento;
                                                                        this.escopoComplementar = estado.escopo_complementar || '';
                                                                    },
                                                                    payload() {
                                                                        return {
                                                                            as_escopo_id: this.asEscopoId || null,
                                                                            numero_complemento: this.numeroComplemento || null,
                                                                            construtora_id: this.construtoraId || null,
                                                                            escopo_complementar: this.escopoComplementar || '',
                                                                            valor_estimado: this.valorEstimado,
                                                                            percentual_faturamento_mao_obra: this.percentualMaoObra,
                                                                            percentual_faturamento_material: this.percentualMaterial,
                                                                        };
                                                                    },
                                                                    get escopoAlterado() {
                                                                        return String(this.asEscopoId || '') !== String(this.asEscopoSalvo || '');
                                                                    },
                                                                    get fornecedorAlterado() {
                                                                        return String(this.construtoraId || '') !== String(this.construtoraSalva || '');
                                                                    },
                                                                    grupo() {
                                                                        const asEscopoId = String(this.asEscopoId || '');

                                                                        return asEscopoId && this.metadados[asEscopoId]
                                                                            ? (this.metadados[asEscopoId].grupo || '-')
                                                                            : this.grupoSalvo;
                                                                    },
                                                                    numeroAs() {
                                                                        const asEscopoId = String(this.asEscopoId || '');

                                                                        return asEscopoId && this.metadados[asEscopoId]
                                                                            ? (this.metadados[asEscopoId].numero_as || '-')
                                                                            : this.numeroAsSalvo;
                                                                    },
                                                                    escopo() {
                                                                        const asEscopoId = String(this.asEscopoId || '');

                                                                        return asEscopoId && this.metadados[asEscopoId]
                                                                            ? (this.metadados[asEscopoId].escopo || '-')
                                                                            : this.escopoSalvo;
                                                                    },
                                                                }"
                                                                data-as-item-row="{{ $item->id }}"
                                                                data-oi-manual="{{ $valorEstimadoManual ? 'true' : 'false' }}"
                                                                x-bind:data-oi-manual="valorEstimadoManual() ? 'true' : 'false'"
                                                                x-bind:class="{ 'cpr-row-unsaved': dirty || escopoAlterado || fornecedorAlterado }"
                                                                @class([
                                                                    'cpr-hover-row',
                                                                    'cpr-row-oi-manual' => $valorEstimadoManual,
                                                                ])
                                                            >
                                                                <td class="px-3 py-2 text-center">
                                                                    @if($podeAtualizarFluxo && $linhaRascunhoRemovivel)
                                                                        <input
                                                                            type="checkbox"
                                                                            value="{{ $item->id }}"
                                                                            x-model="selected"
                                                                            data-as-bulk-checkbox
                                                                            title="Selecionar linha para apagar"
                                                                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-white/20 dark:bg-white/5"
                                                                        />
                                                                    @else
                                                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                                                    <span x-text="grupo()">{{ $this->grupoEscopoLinha($item) }}</span>
                                                                </td>
                                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                                                    <span x-text="numeroAs()">{{ $this->numeroAsEscopoLinha($item) }}</span>
                                                                </td>
                                                                <td class="px-3 py-2 whitespace-nowrap">
                                                                    <span
                                                                        x-show="numeroComplemento"
                                                                        x-text="numeroComplemento"
                                                                        class="inline-flex items-center rounded-md bg-primary-50 dark:bg-primary-500/10 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:text-primary-300"
                                                                    >{{ $numeroComplemento }}</span>
                                                                    <span x-show="! numeroComplemento" class="text-gray-400">-</span>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <select
                                                                        x-model="asEscopoId"
                                                                        x-on:change="applyEscopoDefaults()"
                                                                        @disabled($asCriada)
                                                                        title="{{ $asCriada ? 'Escopo bloqueado após criação da AS' : 'Selecionar escopo' }}"
                                                                        @class([
                                                                            'cpr-fornecedor-select w-full px-2.5 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                                                                            'opacity-70 cursor-not-allowed' => $asCriada,
                                                                        ])
                                                                    >
                                                                        <option value="">Selecionar escopo</option>
                                                                        @if(filled($opcaoEscopoLocal))
                                                                            <option value="{{ $asEscopoIdEstado }}" selected>{{ $opcaoEscopoLocal }}</option>
                                                                        @endif
                                                                        @foreach($asEscopoOptions as $escopoId => $label)
                                                                            <option value="{{ $escopoId }}" @selected((string) data_get($estadoItem, 'as_escopo_id') === (string) $escopoId)>{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        x-show="numeroComplemento"
                                                                        x-cloak
                                                                        type="text"
                                                                        x-model="escopoComplementar"
                                                                        x-on:input="changed()"
                                                                        value="{{ data_get($estadoItem, 'escopo_complementar') }}"
                                                                        @disabled($linhaImutavel)
                                                                        placeholder="Descreva o escopo complementar"
                                                                        title="{{ $linhaImutavel ? 'AS enviada ou cancelada não pode ser editada' : 'Escopo complementar' }}"
                                                                        @class([
                                                                            'w-full px-2.5 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                                                                            'opacity-70 cursor-not-allowed' => $linhaImutavel,
                                                                        ])
                                                                    />
                                                                    <span x-show="! numeroComplemento" class="text-gray-400">-</span>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <select
                                                                        x-model="construtoraId"
                                                                        x-on:change="construtoraId = $event.target.value; changed()"
                                                                        @disabled($linhaImutavel)
                                                                        title="{{ $linhaImutavel ? 'AS enviada ou cancelada não pode ser editada' : 'Selecionar empresa' }}"
                                                                        @class([
                                                                            'cpr-fornecedor-select w-full px-2.5 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                                                                            'opacity-70 cursor-not-allowed' => $linhaImutavel,
                                                                        ])
                                                                    >
                                                                        <option value="">Selecionar empresa</option>
                                                                        @foreach($construtoraOptions as $construtoraId => $nome)
                                                                            <option value="{{ $construtoraId }}" @selected((string) data_get($estadoItem, 'construtora_id') === (string) $construtoraId)>{{ $nome }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <div @class([
                                                                        'cpr-money-field',
                                                                        'cpr-money-field--oi-manual' => $valorEstimadoManual,
                                                                    ])
                                                                    x-bind:class="{ 'cpr-money-field--oi-manual': valorEstimadoManual() }">
                                                                        @php
                                                                            $estimadoSimuladorFilled = filled(data_get($estadoItem, 'valor_estimado_as_simulador'));
                                                                            $estimadoDisabled = $linhaImutavel || $estimadoSimuladorFilled;
                                                                            $estimadoTitulo = $valorEstimadoManual
                                                                                ? 'Valor fora do Simulador OI'
                                                                                : ($estimadoSimuladorFilled
                                                                                    ? 'Valor estimado importado da Simulação OI - somente leitura'
                                                                                    : ($linhaImutavel
                                                                                        ? 'AS enviada ou cancelada não pode ser editada'
                                                                                        : 'Valor estimado'));
                                                                        @endphp
                                                                        <input
                                                                            type="text"
                                                                            inputmode="decimal"
                                                                            x-model="valorEstimado"
                                                                            x-on:input="changed()"
                                                                            value="{{ data_get($estadoItem, 'valor_estimado') }}"
                                                                            @disabled($estimadoDisabled)
                                                                            title="{{ $estimadoTitulo }}"
                                                                            x-bind:title="valorEstimadoManual() ? 'Valor fora do Simulador OI' : @js($estimadoTitulo)"
                                                                            @class([
                                                                                'cpr-money-mask pl-6 pr-1.5 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                                                                                'border-blue-400 bg-blue-50 text-blue-700 font-semibold focus:border-blue-500 focus:ring-blue-500 dark:border-blue-400/70 dark:bg-blue-500/10 dark:text-blue-300' => $valorEstimadoManual,
                                                                                'opacity-70 cursor-not-allowed' => $estimadoDisabled,
                                                                            ])
                                                                            x-bind:class="{ 'border-blue-400 bg-blue-50 text-blue-700 font-semibold focus:border-blue-500 focus:ring-blue-500 dark:border-blue-400/70 dark:bg-blue-500/10 dark:text-blue-300': valorEstimadoManual() }"
                                                                        />
                                                                    </div>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <span class="cpr-money-display" x-text="normalizeMoney(valorFechado).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })">{{ $this->formatMoeda(data_get($estadoItem, 'valor_fechado', 0)) }}</span>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="text"
                                                                        inputmode="decimal"
                                                                        x-model="percentualMaoObra"
                                                                        x-on:input="percentualMaoObra = sanitizePercentInput($event.target.value); changed()"
                                                                        x-on:change="updateMaoObraPercent()"
                                                                        value="{{ data_get($estadoItem, 'percentual_faturamento_mao_obra', 60) }}"
                                                                        @disabled($linhaImutavel)
                                                                        @class([
                                                                            'w-20 px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                                                                            'opacity-70 cursor-not-allowed' => $linhaImutavel,
                                                                        ])
                                                                        title="Percentual de faturamento de mão de obra"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <input
                                                                        type="text"
                                                                        inputmode="decimal"
                                                                        x-model="percentualMaterial"
                                                                        x-on:input="percentualMaterial = sanitizePercentInput($event.target.value); changed()"
                                                                        x-on:change="updateMaterialPercent()"
                                                                        value="{{ data_get($estadoItem, 'percentual_faturamento_material', 40) }}"
                                                                        @disabled($linhaImutavel)
                                                                        @class([
                                                                            'w-20 px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500',
                                                                            'opacity-70 cursor-not-allowed' => $linhaImutavel,
                                                                        ])
                                                                        title="Percentual de faturamento de material"
                                                                    />
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <span data-as-faturado class="cpr-money-display">{{ $this->formatMoeda(data_get($estadoItem, 'faturado', 0)) }}</span>
                                                                </td>
                                                                <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                    <span data-as-saldo class="cpr-money-display">{{ $this->formatMoeda($this->saldoItem($item)) }}</span>
                                                                </td>
                                                                <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                    <span data-as-percentual-saldo>{{ number_format($this->percentualSaldoItem($item), 2, ',', '.') }}%</span>
                                                                </td>
                                                                <td class="px-3 py-2 whitespace-nowrap">
                                                                    <span class="cpr-obra-pill cpr-obra-pill--{{ $statusCor }}">{{ $statusLabel }}</span>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <div class="flex items-center gap-1.5 whitespace-nowrap">
                                                                        @if($linhaRascunhoRemovivel)
                                                                            <button
                                                                                type="button"
                                                                                wire:click="removerLinhaVazia({{ $item->id }})"
                                                                                title="Remover linha"
                                                                                class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-danger-600 hover:text-danger-700 hover:bg-danger-50 dark:hover:bg-danger-500/10 dark:hover:text-danger-400 transition-colors"
                                                                            >
                                                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                                                                <span>Remover linha</span>
                                                                            </button>
                                                                        @endif
                                                                        @if($podeAtualizarFluxo && ! $linhaImutavel)
                                                                            <button type="button" x-on:click="$wire.salvarItemComDados({{ $item->id }}, payload()).then(() => window.cprAsMarkRowSaved($el.closest('[data-as-item-row]')))" title="Salvar linha" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-primary-50 hover:text-primary-700 dark:text-white dark:hover:bg-primary-500/10 dark:hover:text-primary-300">
                                                                                @svg('heroicon-o-document-check', 'w-4 h-4')
                                                                                <span>Salvar linha</span>
                                                                            </button>
                                                                        @endif
                                                                        @if($podeCriarFluxo && $podeCriarAs)
                                                                            <button type="button" x-on:click="$wire.criarAsComDados({{ $item->id }}, payload())" title="Criar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-primary-50 hover:text-primary-700 dark:text-white dark:hover:bg-primary-500/10 dark:hover:text-primary-300">
                                                                                @svg('heroicon-o-document-plus', 'w-4 h-4')
                                                                                <span>Criar AS</span>
                                                                            </button>
                                                                        @endif
                                                                        @if($asCriada && Auth::user()?->can('View:AutorizacaoServico'))
                                                                            <a href="{{ \App\Filament\Resources\AutorizacaoServicos\Pages\EditAutorizacaoServico::getUrl(['record' => $item->autorizacaoServico]) }}" target="_blank" rel="noopener" title="Visualizar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-primary-50 hover:text-primary-700 dark:text-white dark:hover:bg-primary-500/10 dark:hover:text-primary-300">
                                                                                @svg('heroicon-o-eye', 'w-4 h-4')
                                                                                <span>Visualizar AS</span>
                                                                            </a>
                                                                        @endif
                                                                        @if($asCriada && $podeAtualizarFluxo && ! $linhaImutavel)
                                                                            <button type="button" x-on:click="$wire.editarAsComDados({{ $item->id }}, payload())" title="Editar AS e regerar PDF" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-primary-50 hover:text-primary-700 dark:text-white dark:hover:bg-primary-500/10 dark:hover:text-primary-300">
                                                                                @svg('heroicon-o-document-arrow-up', 'w-4 h-4')
                                                                                <span>Editar AS</span>
                                                                            </button>
                                                                        @endif
                                                                        @if($podeAtualizarFluxo)
                                                                            @if($podeEnviarAs)
                                                                                <button type="button" x-on:click="$wire.mountAction('enviarAs', { itemId: {{ $item->id }} })" title="Enviar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700 dark:text-white dark:hover:bg-emerald-500/10 dark:hover:text-emerald-300">
                                                                                    @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                                                                                    <span>Enviar AS</span>
                                                                                </button>
                                                                            @endif
                                                                            @if($podeCancelarAs)
                                                                                <button type="button" x-on:click="$wire.mountAction('cancelarAs', { itemId: {{ $item->id }} })" title="Cancelar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-danger-50 hover:text-danger-700 dark:text-white dark:hover:bg-danger-500/10 dark:hover:text-danger-300">
                                                                                @svg('heroicon-o-x-circle', 'w-4 h-4')
                                                                                <span>Cancelar AS</span>
                                                                                </button>
                                                                            @endif
                                                                        @endif
                                                                        @unless($podeMutarFluxo)
                                                                            <span class="text-xs text-gray-400">-</span>
                                                                        @endunless
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                </div>
                                            @endif

                                            @if($podeAtualizarFluxo)
                                                <button
                                                    type="button"
                                                    wire:click="adicionarLinha({{ $obra->id }})"
                                                    class="w-full pl-6 pr-3 py-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 border-t border-dashed border-gray-300 dark:border-white/10 hover:bg-primary-50/40 dark:hover:bg-primary-500/5 transition-colors"
                                                >
                                                    @svg('heroicon-m-plus', 'w-3.5 h-3.5')
                                                    <span>Adicionar linha</span>
                                                </button>
                                            @endif

                                            @if($itensAdicionais->isNotEmpty())
                                                <div class="border-t border-gray-300 dark:border-white/10 bg-white dark:bg-gray-900">
                                                    <div class="px-6 py-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                        Adicionais somados na linha da obra
                                                    </div>

                                                    <table class="w-full text-sm" style="min-width: 2295px;">
                                                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-wider">
                                                            <tr class="border-y border-gray-200/70 dark:border-white/5">
                                                                <th class="px-3 py-2 text-center font-semibold w-[45px] bg-gray-100 dark:bg-gray-800">Sel.</th>
                                                                <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Grupo</th>
                                                                <th class="px-3 py-2 text-left font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">AS</th>
                                                                <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Complemento</th>
                                                                <th class="px-3 py-2 text-left font-semibold min-w-[300px] bg-gray-100 dark:bg-gray-800">Escopo</th>
                                                                <th class="px-3 py-2 text-left font-semibold min-w-[220px] bg-gray-100 dark:bg-gray-800">Escopo Complementar</th>
                                                                <th class="px-3 py-2 text-left font-semibold min-w-[230px] bg-gray-100 dark:bg-gray-800">Fornecedor</th>
                                                                <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Valor Estimado</th>
                                                                <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Valor Fechado</th>
                                                                <th class="px-3 py-2 text-right font-semibold w-[100px] bg-gray-100 dark:bg-gray-800">% M.O.</th>
                                                                <th class="px-3 py-2 text-right font-semibold w-[100px] bg-gray-100 dark:bg-gray-800">% Material</th>
                                                                <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Faturado</th>
                                                                <th class="px-3 py-2 text-right font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Saldo</th>
                                                                <th class="px-3 py-2 text-right font-semibold w-[90px] bg-gray-100 dark:bg-gray-800">% Saldo</th>
                                                                <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Status</th>
                                                                <th class="px-3 py-2 text-left font-semibold w-[280px] bg-gray-100 dark:bg-gray-800">Ações</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                                            @foreach($itensAdicionais as $auxiliar)
                                                                @php
                                                                    $faturadoAuxiliar = $this->totalNotasAprovadasAuxiliar($auxiliar);
                                                                    $valorFechadoAuxiliar = $this->valorFechadoAuxiliar($auxiliar);
                                                                    $saldoAuxiliar = $this->saldoAuxiliar($auxiliar);
                                                                    $percentualSaldoAuxiliar = $this->percentualSaldoAuxiliar($auxiliar);
                                                                    $podeCriarAsAsa = $this->podeCriarAsAsa($auxiliar);
                                                                    $podeEditarAsAsa = $this->podeEditarAsAsa($auxiliar);
                                                                    $podeEnviarAsa = $this->podeEnviarAsa($auxiliar);
                                                                    $podeVisualizarAsa = $this->podeVisualizarAsa($auxiliar);
                                                                    $podeCancelarAsa = $this->podeCancelarAsa($auxiliar);
                                                                    $asaVinculada = $this->asaParaAuxiliar($auxiliar);
                                                                    $fornecedorAuxiliar = $this->fornecedorAuxiliar($auxiliar);
                                                                    $faturamentoAditivoAuxiliar = $this->faturamentoAditivoAuxiliar($auxiliar);
                                                                @endphp
                                                                <tr wire:key="as-auxiliar-row-{{ $auxiliar->id }}" class="cpr-hover-row">
                                                                    <td class="px-3 py-2 text-center text-gray-300 dark:text-gray-600">-</td>
                                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $auxiliar->grupo ?: '-' }}</td>
                                                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $auxiliar->numero_as ?: '-' }}</td>
                                                                    <td class="px-3 py-2 text-gray-400">-</td>
                                                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $auxiliar->escopo ?: '-' }}</td>
                                                                    <td class="px-3 py-2 text-gray-400">-</td>
                                                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $fornecedorAuxiliar ?: '-' }}</td>
                                                                    <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                        <span class="text-gray-400">-</span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                        <span class="cpr-money-display">{{ $this->formatMoeda($valorFechadoAuxiliar) }}</span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                        <div class="flex flex-col items-end gap-0.5">
                                                                            <span class="cpr-money-display">{{ $this->formatMoeda($faturamentoAditivoAuxiliar['mao_obra']) }}</span>
                                                                            <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $this->formatPercentual($faturamentoAditivoAuxiliar['percentual_mao_obra']) }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                        <div class="flex flex-col items-end gap-0.5">
                                                                            <span class="cpr-money-display">{{ $this->formatMoeda($faturamentoAditivoAuxiliar['material']) }}</span>
                                                                            <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $this->formatPercentual($faturamentoAditivoAuxiliar['percentual_material']) }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                        <span class="cpr-money-display">{{ $this->formatMoeda($faturadoAuxiliar) }}</span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">
                                                                        <span class="cpr-money-display">{{ $this->formatMoeda($saldoAuxiliar) }}</span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-200">{{ $this->formatPercentual($percentualSaldoAuxiliar) }}</td>
                                                                    <td class="px-3 py-2 whitespace-nowrap">
                                                                        @php
                                                                            $asaStatus = $asaVinculada?->status;
                                                                            $statusLabel = $asaStatus instanceof \App\Enums\AsStatus ? $asaStatus->label() : 'Adicional';
                                                                            $statusCor = $asaStatus instanceof \App\Enums\AsStatus ? $asaStatus->color() : 'neutral';
                                                                        @endphp
                                                                        <span class="cpr-obra-pill cpr-obra-pill--{{ $statusCor }}">{{ $statusLabel }}</span>
                                                                    </td>
                                                                    <td class="px-3 py-2">
                                                                        <div class="flex items-center gap-1.5 whitespace-nowrap">
                                                                            @if($podeAtualizarFluxo && $podeCriarAsAsa)
                                                                                <button type="button" wire:click="abrirModalGerarAsAsa({{ $auxiliar->id }})" title="Criar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-amber-50 hover:text-amber-700 dark:text-white dark:hover:bg-amber-500/10 dark:hover:text-amber-300">
                                                                                    @svg('heroicon-o-document-plus', 'w-4 h-4')
                                                                                    <span>Criar AS</span>
                                                                                </button>
                                                                            @else
                                                                                @if($podeAtualizarFluxo && $podeEditarAsAsa)
                                                                                    <button type="button" wire:click="abrirModalGerarAsAsa({{ $auxiliar->id }})" title="Editar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-blue-50 hover:text-blue-700 dark:text-white dark:hover:bg-blue-500/10 dark:hover:text-blue-300">
                                                                                        @svg('heroicon-o-pencil-square', 'w-4 h-4')
                                                                                        <span>Editar AS</span>
                                                                                    </button>
                                                                                @endif
                                                                                @if($podeVisualizarAsa && $asaVinculada)
                                                                                    <a href="{{ \App\Filament\Resources\Asas\Pages\EditAsa::getUrl(['record' => $asaVinculada]) }}" target="_blank" rel="noopener" title="Visualizar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-primary-50 hover:text-primary-700 dark:text-white dark:hover:bg-primary-500/10 dark:hover:text-primary-300">
                                                                                        @svg('heroicon-o-eye', 'w-4 h-4')
                                                                                        <span>Visualizar AS</span>
                                                                                    </a>
                                                                                @endif
                                                                                @if($podeAtualizarFluxo && $podeEnviarAsa)
                                                                                    <button type="button" x-on:click="$wire.mountAction('enviarAsa', { auxiliarId: {{ $auxiliar->id }} })" title="Enviar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-emerald-50 hover:text-emerald-700 dark:text-white dark:hover:bg-emerald-500/10 dark:hover:text-emerald-300">
                                                                                        @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                                                                                        <span>Enviar AS</span>
                                                                                    </button>
                                                                                @endif
                                                                                @if($podeCancelarAsa)
                                                                                    <button type="button" x-on:click="$wire.mountAction('cancelarAsa', { auxiliarId: {{ $auxiliar->id }} })" title="Cancelar AS" class="inline-flex h-7 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-gray-700 transition-colors hover:bg-danger-50 hover:text-danger-700 dark:text-white dark:hover:bg-danger-500/10 dark:hover:text-danger-300">
                                                                                        @svg('heroicon-o-x-circle', 'w-4 h-4')
                                                                                        <span>Cancelar AS</span>
                                                                                    </button>
                                                                                @endif
                                                                                @if(!$podeEditarAsAsa && !$podeEnviarAsa && !$podeVisualizarAsa && !$podeCancelarAsa)
                                                                                    <span class="text-xs text-gray-400">-</span>
                                                                                @endif
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="18" class="px-4 py-12 text-center">
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

        @if($obrasColecao->total() > 0)
            <x-controle-pagination :paginator="$obrasColecao" item-label="obra(s)" />
        @endif
    </div>

    @if($gerarAsModalItemId || $gerarAsModalAuxiliarId)
        <div
            class="fi-modal gs-as-create-modal fixed inset-0 z-[10000] flex items-stretch justify-center overflow-hidden bg-gray-950/60 px-4 py-4"
            x-on:keydown.escape.window="$wire.fecharModalGerarAs()"
        >
            <div class="flex min-h-0 w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white dark:bg-gray-900 shadow-xl ring-1 ring-gray-950/10 dark:ring-white/10">
                <div class="shrink-0 flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $gerarAsModalEdicao ? 'Dados para Editar AS' : 'Dados para Gerar AS' }}</h2>
                    <button
                        type="button"
                        wire:click="fecharModalGerarAs"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        title="Fechar"
                    >
                        @svg('heroicon-o-x-mark', 'w-5 h-5')
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-5 py-5 overscroll-contain">
                    <section>
                        <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Datas estimadas</h3>
                        {{ $this->gerarAsDatasForm }}
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-white/10">
                        <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Condições de pagamento</h3>
                        {{ $this->gerarAsValoresParcelamentoForm }}
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-white/10">
                        <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Descrição do valor</h3>
                        {{ $this->gerarAsDescricaoForm }}
                    </section>

                    <section class="border-t border-gray-200 pt-5 dark:border-white/10">
                        <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">Anexos</h3>
                        {{ $this->gerarAsAnexosForm }}
                    </section>

                    <div class="flex items-center justify-end gap-3">
                        @error('gerarAsParcelas')
                            <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <div class="shrink-0 flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-white/10">
                    <x-filament::button
                        type="button"
                        wire:click="fecharModalGerarAs"
                        color="gray"
                    >
                        Fechar
                    </x-filament::button>
                    <x-filament::button
                        type="button"
                        wire:click="confirmarGeracaoAs"
                        wire:loading.attr="disabled"
                        wire:target="confirmarGeracaoAs"
                        icon="{{ $gerarAsModalModo === 'editar_pdf' ? 'heroicon-o-pencil-square' : 'heroicon-o-document-plus' }}"
                    >
                        {{ $gerarAsModalEdicao ? 'Atualizar AS' : 'Gerar AS' }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            function cprFormatMoneyInput(input) {
                const digits = String(input.value || '').replace(/\D/g, '');

                if (digits === '') {
                    input.value = '';
                    input.style.setProperty('--cpr-money-ch', '1');

                    return;
                }

                const cents = digits.slice(-2).padStart(2, '0');
                const integer = digits.slice(0, -2).replace(/^0+(?=\d)/, '');
                const formattedInteger = (integer === '' ? '0' : integer)
                    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                input.value = `${formattedInteger},${cents}`;
                input.style.setProperty('--cpr-money-ch', String(input.value.length));
            }

            function cprApplyMoneyMasks(root = document) {
                root.querySelectorAll('.cpr-money-mask').forEach((input) => {
                    cprFormatMoneyInput(input);
                });
            }

            function cprScheduleMoneyMasks(root = document) {
                cprApplyMoneyMasks(root);
                window.requestAnimationFrame(() => cprApplyMoneyMasks(root));
                window.setTimeout(() => cprApplyMoneyMasks(root), 100);
            }

            window.cprAsRecalculate = function (root) {
                return;
            };

            window.cprAsCollectObraPayload = function (obraId) {
                const root = document.querySelector(`[data-as-obra-detail="${obraId}"]`);
                const payload = {};

                if (!root || !window.Alpine) {
                    return payload;
                }

                root.querySelectorAll('[data-as-item-row]').forEach((row) => {
                    payload[row.dataset.asItemRow] = Alpine.$data(row).payload();
                });

                return payload;
            };

            window.cprAsMarkRowSaved = function (row) {
                if (!row || !window.Alpine) {
                    return;
                }

                const state = Alpine.$data(row);

                state.dirty = false;
                state.asEscopoSalvo = String(state.asEscopoId || '');
                state.construtoraSalva = String(state.construtoraId || '');
                state.complementoSalvo = String(state.numeroComplemento || '');
            };

            window.cprAsApplyRowState = function (row, estado = {}) {
                if (!row || !window.Alpine || !estado) {
                    return;
                }

                const state = Alpine.$data(row);

                if (Object.prototype.hasOwnProperty.call(estado, 'as_escopo_id')) {
                    state.asEscopoId = estado.as_escopo_id === null ? '' : String(estado.as_escopo_id);
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'construtora_id')) {
                    state.construtoraId = estado.construtora_id === null ? '' : String(estado.construtora_id);
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'numero_complemento')) {
                    state.numeroComplemento = estado.numero_complemento || '';
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'escopo_complementar')) {
                    state.escopoComplementar = estado.escopo_complementar || '';
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'valor_estimado')) {
                    state.valorEstimado = estado.valor_estimado || '0,00';
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'valor_estimado_as_simulador')) {
                    state.valorEstimadoSimulador = estado.valor_estimado_as_simulador;
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'valor_fechado')) {
                    state.valorFechado = estado.valor_fechado || '0,00';
                    row.querySelector('[data-as-valor-fechado]')?.replaceChildren(document.createTextNode(state.valorFechado));
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'percentual_faturamento_mao_obra')) {
                    state.percentualMaoObra = estado.percentual_faturamento_mao_obra || '0';
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'percentual_faturamento_material')) {
                    state.percentualMaterial = estado.percentual_faturamento_material || '0';
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'faturado')) {
                    row.querySelector('[data-as-faturado]')?.replaceChildren(document.createTextNode(estado.faturado || '0,00'));
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'saldo')) {
                    row.querySelector('[data-as-saldo]')?.replaceChildren(document.createTextNode(estado.saldo || '0,00'));
                }

                if (Object.prototype.hasOwnProperty.call(estado, 'percentual_saldo')) {
                    row.querySelector('[data-as-percentual-saldo]')?.replaceChildren(document.createTextNode(estado.percentual_saldo || '0,00%'));
                }

                window.cprAsMarkRowSaved(row);
                cprScheduleMoneyMasks(row);
            };

            window.cprAsMarkObraSaved = function (obraId) {
                const root = document.querySelector(`[data-as-obra-detail="${obraId}"]`);

                if (!root) {
                    return;
                }

                root.querySelectorAll('[data-as-item-row]').forEach((row) => {
                    window.cprAsMarkRowSaved(row);
                });
            };

            if (!window.cprAsLinhaSalvaListenerReady) {
                window.cprAsLinhaSalvaListenerReady = true;

                window.addEventListener('as-linha-salva', (event) => {
                    const itemId = event.detail?.itemId;

                    if (!itemId) {
                        return;
                    }

                    const row = document.querySelector(`[data-as-item-row="${itemId}"]`);

                    window.cprAsApplyRowState(row, event.detail?.estado || {});
                    window.cprAsMarkRowSaved(row);
                });

                window.addEventListener('as-linhas-atualizadas', (event) => {
                    Object.entries(event.detail?.estados || {}).forEach(([itemId, estado]) => {
                        window.cprAsApplyRowState(document.querySelector(`[data-as-item-row="${itemId}"]`), estado);
                    });
                });
            }

            function cprBootMoneyMasks() {
                cprScheduleMoneyMasks();

                if (window.Livewire && Livewire.hook) {
                    Livewire.hook('morph.updated', ({ el }) => cprScheduleMoneyMasks(el));
                }
            }

            function cprBootMoneyMaskListeners() {
                if (window.cprMoneyMaskListenersReady) {
                    return;
                }

                window.cprMoneyMaskListenersReady = true;

                document.body.addEventListener('input', (event) => {
                    const input = event.target;

                    if (!input.matches || !input.matches('.cpr-money-mask')) {
                        return;
                    }

                    cprFormatMoneyInput(input);
                }, true);

                document.body.addEventListener('keydown', (event) => {
                    const input = event.target;

                    if (!input.matches || !input.matches('.cpr-money-mask')) {
                        return;
                    }

                    if (event.ctrlKey || event.metaKey || event.altKey) {
                        return;
                    }

                    const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'Home', 'End', 'ArrowLeft', 'ArrowRight'];

                    if (allowedKeys.includes(event.key) || /^[0-9]$/.test(event.key)) {
                        return;
                    }

                    event.preventDefault();
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    cprBootMoneyMasks();
                    cprBootMoneyMaskListeners();
                }, { once: true });
            } else {
                cprBootMoneyMasks();
                cprBootMoneyMaskListeners();
            }

            document.addEventListener('livewire:navigated', () => cprScheduleMoneyMasks());
            document.addEventListener('livewire:initialized', () => cprScheduleMoneyMasks());

            document.addEventListener('alpine:init', () => {
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
                        const stickyHeight = (el) => el?.offsetHeight || el?.getBoundingClientRect().height || 0;
                        if (mainHead) {
                            this.root.style.setProperty('--cpr-thead-h', stickyHeight(mainHead) + 'px');
                        }
                        if (parentRow) {
                            this.root.style.setProperty('--cpr-parent-h', stickyHeight(parentRow) + 'px');
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
                    storageKey: 'cpr-as-zoom',
                    init() {
                        const salvo = parseFloat(localStorage.getItem(this.storageKey));
                        if (!isNaN(salvo) && salvo >= this.min && salvo <= this.max) {
                            this.zoom = salvo;
                        }

                        if (!window.cprAplicarZoomControleAs) {
                            window.cprAplicarZoomControleAs = (zoom) => {
                                const valor = Number(zoom);

                                if (Number.isNaN(valor)) {
                                    return;
                                }

                                document.documentElement.style.setProperty('--cpr-as-zoom', String(valor));
                            };
                        }

                        this.aplicar();

                        if (window.Livewire && Livewire.hook && !window.__cprAsZoomHookRegistered) {
                            window.__cprAsZoomHookRegistered = true;

                            Livewire.hook('morph.updated', () => {
                                const salvoAtual = parseFloat(localStorage.getItem(this.storageKey));
                                const zoomAtual = !isNaN(salvoAtual) && salvoAtual >= this.min && salvoAtual <= this.max
                                    ? salvoAtual
                                    : this.zoom;

                                window.cprAplicarZoomControleAs(zoomAtual);
                            });
                        }
                    },
                    aplicar() {
                        window.cprAplicarZoomControleAs(this.zoom);
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

            });
        </script>
    @endpush
</x-filament::page>
