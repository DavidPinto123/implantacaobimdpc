<x-filament-panels::page>
    @php
        $controlesColecao = $this->controles;
    @endphp

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
                    placeholder="Buscar por unidade, sigla ou código..."
                    class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>

            <div class="gs-table-excel gs-table-excel--page">
                {{ $this->filtrosModalAction }}
            </div>

            <div
                class="flex items-center gap-1 ml-auto rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 shadow-sm px-1 py-0.5"
                x-data="cnfZoomControl()"
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

            @if(count($selecionadas) > 0)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300 px-2.5 py-1 text-xs font-medium">
                        @svg('heroicon-m-check-circle', 'w-3.5 h-3.5')
                        {{ count($selecionadas) }} selecionado(s)
                    </span>
                    <x-filament::button size="sm" color="primary" icon="heroicon-m-arrow-down-tray" wire:click="executarAcaoEmMassa('exportar')">
                        Exportar
                    </x-filament::button>
                    <x-filament::button size="sm" color="gray" icon="heroicon-m-x-mark" wire:click="executarAcaoEmMassa('limpar_selecao')">
                        Limpar
                    </x-filament::button>
                </div>
            @endif
        </div>

        {{-- Active filters --}}
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
            x-data="cnfStickyMeasure()"
            x-init="init($el)"
            class="flex-1 min-h-0 overflow-y-auto overflow-x-auto cnf-scroll cnf-zoom-target"
            style="--cnf-thead-h: 0px; --cnf-parent-h: 0px;"
        >
        <div>
            <table class="w-full text-sm" style="table-layout: fixed; min-width: 1380px;">
                <thead class="cnf-thead-main sticky top-0 z-40 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 uppercase text-[11px] tracking-wider border-b border-gray-200 dark:border-white/5 shadow-sm">
                    <tr>
                        <th class="px-4 py-3 bg-gray-50 dark:bg-gray-900" style="width: 44px;"></th>
                        <th class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900" style="width: 64px;">Ações</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 120px;">Código</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 140px;">Sigla</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900" style="width: 280px;">Unidade</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 170px;">Status</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 130px;">Data base</th>
                        <th class="px-4 py-3 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 130px;">CAPEX</th>
                        <th class="px-4 py-3 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 150px;">Valor</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($controlesColecao as $controle)
                        @php
                            $obra = $controle->obra;
                            $itensControle = $controle->itens;
                            $totalItensControle = $itensControle->count();
                            $valorContratado = (float) $itensControle->sum('valor_global_a');
                            $aberta = in_array($controle->id, $controlesExpandidos, true);
                            $statusControle = $controle->status;
                            $corStatus = $this->corStatus($statusControle);
                            $bgPai = $aberta ? 'bg-primary-50 dark:bg-gray-800' : '';
                            $stickyTd = $aberta ? 'cnf-parent-sticky bg-primary-50 dark:bg-gray-800' : '';
                        @endphp

                        {{-- LINHA-PAI --}}
                        <tr
                            wire:key="ctrl-row-{{ $controle->id }}"
                            @class([
                                'transition-colors',
                                'cnf-parent-row' => $aberta,
                                'hover:bg-gray-50 dark:hover:bg-white/[0.03]' => ! $aberta,
                            ])
                        >
                            <td class="px-4 py-2.5 align-middle {{ $stickyTd }}">
                                <x-filament::input.checkbox
                                    wire:model.live="selecionadas"
                                    value="ctrl-{{ $controle->id }}"
                                />
                            </td>
                            <td class="px-4 py-2.5 text-center {{ $stickyTd }}">
                                <a
                                    href="{{ $this->urlEditarControle($controle->id) }}"
                                    title="Visualizar controle"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400 transition-colors"
                                >
                                    @svg('heroicon-o-eye', 'w-4 h-4')
                                </a>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                @if($obra && $obra->codigo)
                                    <span class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-100 dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                        {{ $obra->codigo }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                @php $sigla = $obra->sigla ?? $controle->sigla; @endphp
                                @if($sigla)
                                    <span class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-100 dark:bg-white/5 px-2 py-0.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                                        {{ $sigla }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 max-w-[280px] whitespace-nowrap {{ $stickyTd }}">
                                @php $unidade = $obra->unidade ?? $controle->unidade; @endphp
                                <button
                                    type="button"
                                    wire:click="toggleControle({{ $controle->id }})"
                                    class="group flex items-center gap-2 text-left font-medium text-gray-800 dark:text-gray-100 w-full min-w-0"
                                >
                                    <span @class([
                                        'inline-flex items-center justify-center w-5 h-5 rounded text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-transform flex-shrink-0',
                                        'rotate-90 text-primary-600 dark:text-primary-400' => $aberta,
                                    ])>
                                        @svg('heroicon-m-chevron-right', 'w-4 h-4')
                                    </span>
                                    <span class="group-hover:text-primary-700 dark:group-hover:text-primary-300 truncate" title="{{ $unidade }}">{{ $unidade ?: '—' }}</span>
                                    @if($totalItensControle > 0)
                                        <span class="inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1.5 text-[11px] font-semibold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300 flex-shrink-0">
                                            {{ $totalItensControle }}
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap {{ $stickyTd }}">
                                <span class="cnf-status-pill cnf-status-pill--{{ $corStatus }}">
                                    {{ $statusOptions[$statusControle] ?? '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300 whitespace-nowrap {{ $stickyTd }}">
                                {{ optional($controle->data_base)->translatedFormat('d M, Y') ?? '—' }}
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
                            <tr wire:key="ctrl-detail-{{ $controle->id }}">
                                <td colspan="9" class="p-0">
                                    <div class="relative bg-gray-50/60 dark:bg-white/[0.02] border-t border-b border-primary-200/60 dark:border-primary-500/20">
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500/70 dark:bg-primary-400/60"></div>

                                        @if($totalItensControle === 0)
                                            <div class="pl-6 pr-4 py-5 text-sm text-gray-500 dark:text-gray-400 italic flex items-center gap-2">
                                                @svg('heroicon-o-information-circle', 'w-4 h-4')
                                                Nenhum item cadastrado neste controle.
                                            </div>
                                        @else
                                            <table class="w-full text-sm">
                                                <thead class="cnf-child-thead bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-wider shadow-sm">
                                                    <tr class="border-b border-gray-200/70 dark:border-white/5">
                                                        <th class="pl-6 pr-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Grupo</th>
                                                        <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">A.S.</th>
                                                        <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Complemento</th>
                                                        <th class="px-3 py-2 text-left font-semibold min-w-[260px] bg-gray-100 dark:bg-gray-800">Nome do escopo</th>
                                                        <th class="px-3 py-2 text-left font-semibold min-w-[220px] bg-gray-100 dark:bg-gray-800">Escopo Complementar</th>
                                                        <th class="px-3 py-2 text-left font-semibold min-w-[180px] bg-gray-100 dark:bg-gray-800">Empresa</th>
                                                        <th class="px-3 py-2 text-right font-semibold w-[100px] bg-gray-100 dark:bg-gray-800">% Mão de obra</th>
                                                        <th class="px-3 py-2 text-right font-semibold w-[100px] bg-gray-100 dark:bg-gray-800">% Material</th>
                                                        <th class="px-3 py-2 text-right font-semibold w-[140px] bg-gray-100 dark:bg-gray-800">Valor global</th>
                                                        <th class="px-3 py-2 text-right font-semibold w-[140px] bg-gray-100 dark:bg-gray-800">Total medido</th>
                                                        <th class="px-3 py-2 text-right font-semibold w-[140px] bg-gray-100 dark:bg-gray-800">Saldo geral</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                                    @foreach($itensControle as $item)
                                                        @php
                                                            $numeroAs = trim((string) ($item->numero_as ?? ''));
                                                            $numeroComplemento = trim((string) ($item->numero_complemento ?? ''));

                                                            if (str_contains($numeroAs, '/')) {
                                                                [$numeroAsBase, $complementoEmbutido] = array_pad(explode('/', $numeroAs, 2), 2, '');
                                                                $numeroAs = trim($numeroAsBase);

                                                                if ($numeroComplemento === '') {
                                                                    $numeroComplemento = trim($complementoEmbutido);
                                                                }
                                                            }
                                                        @endphp
                                                        <tr wire:key="item-row-{{ $item->id }}" class="hover:bg-white dark:hover:bg-white/[0.03] transition-colors">
                                                            <td class="pl-6 pr-3 py-2 text-gray-700 dark:text-gray-200 text-xs">
                                                                {{ $item->grupo ?: '—' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200 text-xs font-mono">
                                                                {{ $numeroAs !== '' ? $numeroAs : '—' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200 text-xs whitespace-nowrap">
                                                                @if($numeroComplemento !== '')
                                                                    <span class="inline-flex items-center rounded-md bg-primary-50 dark:bg-primary-500/10 px-2 py-0.5 text-xs font-semibold text-primary-700 dark:text-primary-300">
                                                                        {{ $numeroComplemento }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-gray-800 dark:text-gray-100 text-xs">
                                                                <div class="flex items-start gap-2">
                                                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-primary-500/70 flex-shrink-0 mt-1.5"></span>
                                                                    <div class="min-w-0">
                                                                        <div class="font-medium truncate" title="{{ $item->escopo }}">{{ $item->escopo ?: '—' }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200 text-xs">
                                                                <span class="block truncate" title="{{ $item->escopo_complementar }}">
                                                                    {{ filled($item->escopo_complementar) ? $item->escopo_complementar : '—' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200 text-xs">
                                                                {{ $item->empresa ?: '—' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200 text-xs tabular-nums">
                                                                @if($item->percentual_faturamento_mao_obra !== null)
                                                                    {{ number_format((float) $item->percentual_faturamento_mao_obra, 2, ',', '.') }}%
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200 text-xs tabular-nums">
                                                                @if($item->percentual_faturamento_material !== null)
                                                                    {{ number_format((float) $item->percentual_faturamento_material, 2, ',', '.') }}%
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100 text-xs tabular-nums font-medium">
                                                                {{ number_format((float) ($item->valor_global_a ?? 0), 2, ',', '.') }}
                                                            </td>
                                                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200 text-xs tabular-nums">
                                                                {{ number_format((float) ($item->valor_acumulado_medido ?? 0), 2, ',', '.') }}
                                                            </td>
                                                            <td class="px-3 py-2 text-right text-xs tabular-nums">
                                                                @php $saldo = (float) ($item->saldo ?? 0); @endphp
                                                                <span @class([
                                                                    'font-medium',
                                                                    'text-emerald-600 dark:text-emerald-400' => $saldo > 0,
                                                                    'text-gray-500' => $saldo == 0,
                                                                    'text-rose-600 dark:text-rose-400' => $saldo < 0,
                                                                ])>
                                                                    {{ number_format($saldo, 2, ',', '.') }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-500 dark:text-gray-400">
                                    @svg('heroicon-o-inbox', 'w-10 h-10 text-gray-300 dark:text-gray-600')
                                    <p class="text-sm font-medium">Nenhum controle encontrado</p>
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
        @if($controlesColecao->total() > 0)
            <x-controle-pagination :paginator="$controlesColecao" item-label="controle(s)" />
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('cnfStickyMeasure', () => ({
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
                        this.root.querySelectorAll('.cnf-thead-main, .cnf-parent-row').forEach((el) => this.observer.observe(el));
                    },
                    measure() {
                        if (!this.root) return;
                        const mainHead = this.root.querySelector('.cnf-thead-main');
                        const parentRow = this.root.querySelector('.cnf-parent-row');
                        const stickyHeight = (el) => el?.offsetHeight || el?.getBoundingClientRect().height || 0;
                        if (mainHead) {
                            this.root.style.setProperty('--cnf-thead-h', stickyHeight(mainHead) + 'px');
                        }
                        if (parentRow) {
                            this.root.style.setProperty('--cnf-parent-h', stickyHeight(parentRow) + 'px');
                        } else {
                            this.root.style.setProperty('--cnf-parent-h', '0px');
                        }
                    },
                }));

                Alpine.data('cnfZoomControl', () => ({
                    zoom: 1,
                    min: 0.6,
                    max: 1.5,
                    step: 0.1,
                    storageKey: 'cnf-list-zoom',
                    init() {
                        const salvo = parseFloat(localStorage.getItem(this.storageKey));
                        if (!isNaN(salvo) && salvo >= this.min && salvo <= this.max) {
                            this.zoom = salvo;
                        }

                        if (!window.cnfAplicarZoomListaNotaFiscal) {
                            window.cnfAplicarZoomListaNotaFiscal = (zoom) => {
                                const valor = Number(zoom);

                                if (Number.isNaN(valor)) {
                                    return;
                                }

                                document.documentElement.style.setProperty('--cnf-zoom', String(valor));
                            };
                        }

                        this.aplicar();

                        if (window.Livewire && Livewire.hook && !window.__cnfZoomListaHookRegistered) {
                            window.__cnfZoomListaHookRegistered = true;

                            Livewire.hook('morph.updated', () => {
                                const salvoAtual = parseFloat(localStorage.getItem(this.storageKey));
                                const zoomAtual = !isNaN(salvoAtual) && salvoAtual >= this.min && salvoAtual <= this.max
                                    ? salvoAtual
                                    : this.zoom;

                                window.cnfAplicarZoomListaNotaFiscal(zoomAtual);
                            });
                        }
                    },
                    aplicar() {
                        window.cnfAplicarZoomListaNotaFiscal(this.zoom);
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
</x-filament-panels::page>
