<x-filament::page wire:key="entrega-contratual-page">
    @push('styles')
        <style>
            .ec-scroll .ec-thead-main > tr > th {
                position: sticky;
                top: 0;
                z-index: 40;
            }

            .ec-scroll .ec-parent-sticky {
                position: sticky;
                top: var(--ec-thead-h, 0px);
                z-index: 30;
            }

            .ec-scroll .ec-child-thead > tr > th {
                position: sticky;
                top: calc(var(--ec-thead-h, 0px) + var(--ec-parent-h, 0px));
                z-index: 20;
            }

            .ec-desc-textarea {
                min-height: 4.5rem;
                resize: none;
                line-height: 1.35;
                white-space: pre-wrap;
                overflow-wrap: anywhere;
            }

            .ec-pill-dropdown {
                position: relative;
                display: inline-block;
                width: 100%;
            }

            .ec-pill {
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
            .ec-pill:hover { filter: brightness(1.05); }
            .ec-pill:active { transform: translateY(1px); }
            .ec-pill__label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .ec-pill__chevron { width: 12px; height: 12px; opacity: .9; flex-shrink: 0; }

            .ec-pill--entregue     { background: #16a34a; }
            .ec-pill--parcial      { background: #f59e0b; color: #3b2900; }
            .ec-pill--nao-entregue { background: #ef4444; }

            .ec-pill-menu {
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
            .dark .ec-pill-menu {
                background: #1f2937;
                border-color: rgba(255, 255, 255, 0.08);
            }
            .ec-pill-option {
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
            .dark .ec-pill-option { color: #e5e7eb; }
            .ec-pill-option:hover { background: rgba(0, 0, 0, 0.05); }
            .dark .ec-pill-option:hover { background: rgba(255, 255, 255, 0.06); }
            .ec-pill-option--selected { background: rgba(99, 102, 241, 0.08); }
            .dark .ec-pill-option--selected { background: rgba(99, 102, 241, 0.16); }

            .ec-pill-option__dot {
                width: 10px;
                height: 10px;
                border-radius: 9999px;
                flex-shrink: 0;
            }
            .ec-pill-option__dot[data-color="entregue"]     { background: #16a34a; }
            .ec-pill-option__dot[data-color="parcial"]      { background: #f59e0b; }
            .ec-pill-option__dot[data-color="nao-entregue"] { background: #ef4444; }

            .ec-pill-option__label { flex: 1; white-space: nowrap; }
        </style>
    @endpush

    @php
        $obrasColecao = $this->obras;
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
                    placeholder="Buscar por código, sigla ou unidade..."
                    class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>

            <div class="ml-auto flex items-center gap-2">
                @if(count($entregasSelecionadas) > 0)
                    <button
                        type="button"
                        wire:click="removerEntregasSelecionadas"
                        wire:confirm="Remover {{ count($entregasSelecionadas) }} entrega(s) selecionada(s)?"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-danger-600 hover:bg-danger-700 text-white text-sm font-medium shadow-sm transition-colors"
                    >
                        @svg('heroicon-m-trash', 'w-4 h-4')
                        <span>Excluir {{ count($entregasSelecionadas) }} selecionada(s)</span>
                    </button>
                @endif
                {{ $this->colarTabelaAction }}
                {{ $this->adicionarObraAction }}
            </div>

            <div
                class="flex items-center gap-1 rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 shadow-sm px-1 py-0.5"
                x-data="ecZoomControl()"
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

        <div
            x-data="ecStickyMeasure()"
            x-init="init($el)"
            class="flex-1 min-h-0 overflow-y-auto overflow-x-auto ec-scroll ec-zoom-target"
            style="--ec-thead-h: 0px; --ec-parent-h: 0px;"
        >
            <table class="w-full text-sm" style="table-layout: fixed; min-width: 1100px;">
                <thead class="ec-thead-main sticky top-0 z-40 bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 uppercase text-[11px] tracking-wider border-b border-gray-200 dark:border-white/5 shadow-sm">
                    <tr>
                        <th class="px-3 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900" style="width: 40px;"></th>
                        <th class="px-4 py-3 text-center font-semibold bg-gray-50 dark:bg-gray-900" style="width: 56px;">Ações</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 96px;">Código</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900 whitespace-nowrap" style="width: 104px;">Sigla</th>
                        <th class="px-4 py-3 text-left font-semibold bg-gray-50 dark:bg-gray-900" style="width: 240px;">Unidade</th>
                        <th class="px-4 py-3 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 150px;">Custo c/ contrato</th>
                        <th class="px-4 py-3 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 150px;">Custo s/ contrato</th>
                        <th class="px-4 py-3 text-right font-semibold bg-gray-50 dark:bg-gray-900" style="width: 150px;">Total</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($obrasColecao as $obra)
                        @php
                            $entregas = $this->entregasDaObra($obra->id);
                            $totalEntregas = (int) ($obra->entregas_contratuais_count ?? $entregas->count());
                            $custoContrato    = (float) ($obra->entregas_custo_contrato    ?? $entregas->sum('custo_contrato'));
                            $custoSemContrato = (float) ($obra->entregas_custo_sem_contrato ?? $entregas->sum('custo_sem_contrato'));
                            $custoTotal       = $custoContrato + $custoSemContrato;
                            $aberta = in_array($obra->id, $obrasExpandidas, true);
                            $stickyTd = $aberta ? 'ec-parent-sticky bg-primary-50 dark:bg-gray-800' : '';
                            $idsObraPai = $entregas->pluck('id')->map(fn ($id) => (int) $id)->all();
                            $obraTotalmenteSelecionada = ! empty($idsObraPai)
                                && empty(array_diff($idsObraPai, $entregasSelecionadas));
                        @endphp

                        <tr
                            wire:key="obra-row-{{ $obra->id }}"
                            @class([
                                'transition-colors',
                                'ec-parent-row' => $aberta,
                                'hover:bg-gray-50 dark:hover:bg-white/[0.03]' => ! $aberta,
                            ])
                        >
                            <td class="px-3 py-2.5 text-center {{ $stickyTd }}">
                                @if($totalEntregas > 0)
                                    <input
                                        type="checkbox"
                                        wire:click="alternarSelecaoObra({{ $obra->id }})"
                                        @checked($obraTotalmenteSelecionada)
                                        class="fi-checkbox-input rounded border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-primary-600 focus:ring-primary-600"
                                        title="Selecionar todas as entregas desta obra"
                                    />
                                @endif
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
                            <td class="px-4 py-2.5 {{ $stickyTd }}">
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
                                    <span class="group-hover:text-primary-700 dark:hover:text-primary-300 truncate" title="{{ $obra->unidade }}">
                                        {{ $obra->unidade ?? '—' }}
                                    </span>
                                    @if($totalEntregas > 0)
                                        <span class="inline-flex items-center justify-center min-w-[1.4rem] h-5 px-1.5 text-[11px] font-semibold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300 flex-shrink-0">
                                            {{ $totalEntregas }}
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-700 dark:text-gray-200 {{ $stickyTd }}">
                                R$ {{ number_format($custoContrato, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-700 dark:text-gray-200 {{ $stickyTd }}">
                                R$ {{ number_format($custoSemContrato, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-right tabular-nums font-bold text-primary-700 dark:text-primary-300 {{ $stickyTd }}">
                                R$ {{ number_format($custoTotal, 2, ',', '.') }}
                            </td>
                        </tr>

                        @if($aberta)
                            <tr wire:key="obra-detail-{{ $obra->id }}">
                                <td colspan="8" class="p-0">
                                    <div class="relative bg-gray-50/60 dark:bg-white/[0.02] border-t border-b border-primary-200/60 dark:border-primary-500/20">
                                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500/70 dark:bg-primary-400/60"></div>

                                        <table class="w-full text-sm">
                                            <thead class="ec-child-thead bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-wider shadow-sm">
                                                <tr class="border-b border-gray-200/70 dark:border-white/5">
                                                    <th class="pl-6 pr-2 py-2 text-center font-semibold w-[40px] bg-gray-100 dark:bg-gray-800">
                                                        @php
                                                            $idsDaObra = $entregas->pluck('id')->map(fn ($id) => (int) $id)->all();
                                                            $todasSelecionadas = ! empty($idsDaObra)
                                                                && empty(array_diff($idsDaObra, $entregasSelecionadas));
                                                        @endphp
                                                        <input
                                                            type="checkbox"
                                                            wire:click="alternarSelecaoObra({{ $obra->id }})"
                                                            @checked($todasSelecionadas)
                                                            class="fi-checkbox-input rounded border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-primary-600 focus:ring-primary-600"
                                                            title="Selecionar/desmarcar todas desta obra"
                                                        />
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-semibold w-[150px] bg-gray-100 dark:bg-gray-800">Tipo</th>
                                                    <th class="px-3 py-2 text-left font-semibold w-[150px] bg-gray-100 dark:bg-gray-800">Entrega contratual</th>
                                                    <th class="px-3 py-2 text-left font-semibold w-[350px] bg-gray-100 dark:bg-gray-800">Descrição da entrega</th>
                                                    <th class="px-3 py-2 text-left font-semibold w-[350px] bg-gray-100 dark:bg-gray-800">Descrição do existente</th>
                                                    <th class="px-3 py-2 text-center font-semibold w-[110px] bg-gray-100 dark:bg-gray-800">Previsto em contrato?</th>
                                                    <th class="px-3 py-2 text-left font-semibold w-[150px] bg-gray-100 dark:bg-gray-800">Status</th>
                                                    <th class="px-3 py-2 text-left font-semibold w-[120px] bg-gray-100 dark:bg-gray-800">Data de entrega</th>
                                                    <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Custo c/ contrato</th>
                                                    <th class="px-3 py-2 text-right font-semibold w-[130px] bg-gray-100 dark:bg-gray-800">Custo s/ contrato</th>
                                                    <th class="px-3 py-2 text-left font-semibold w-[250px] bg-gray-100 dark:bg-gray-800">Observações</th>
                                                    <th class="px-3 py-2 text-center font-semibold w-[60px] bg-gray-100 dark:bg-gray-800"></th>
                                                </tr>
                                            </thead>
                                            <tbody wire:key="obra-entregas-{{ $obra->id }}" class="divide-y divide-gray-100 dark:divide-white/5">
                                                @foreach($entregas as $entrega)
                                                    @php
                                                        $statusSlug = $entrega->status;
                                                        $statusCor = $this->corStatus($statusSlug);
                                                        $statusRotulo = $statusOptions[$statusSlug] ?? $statusSlug;

                                                        $previstoSlug = $entrega->previsto_status
                                                            ?? ((bool) $entrega->previsto_em_contrato ? 'previsto_sim' : 'previsto_nao');
                                                        $previstoCor = $this->corPrevisto($previstoSlug);
                                                        $previstoRotulo = $previstoOptions[$previstoSlug] ?? $previstoSlug;
                                                        $tipoCustoAtual = $this->tipoCustoDoPrevisto($previstoSlug);

                                                        $habilitaCustoContrato = $tipoCustoAtual === 'contrato';
                                                        $habilitaCustoSemContrato = $tipoCustoAtual === 'sem_contrato';
                                                    @endphp
                                                    <tr wire:key="entrega-row-{{ $entrega->id }}" class="hover:bg-white dark:hover:bg-white/[0.03] transition-colors">
                                                        <td class="pl-6 pr-2 py-2 text-center">
                                                            <input
                                                                type="checkbox"
                                                                value="{{ $entrega->id }}"
                                                                wire:model.live="entregasSelecionadas"
                                                                class="fi-checkbox-input rounded border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-primary-600 focus:ring-primary-600"
                                                            />
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <input
                                                                type="text"
                                                                value="{{ $entrega->tipo }}"
                                                                wire:change="atualizarEntrega({{ $entrega->id }}, 'tipo', $event.target.value)"
                                                                placeholder="Tipo"
                                                                class="w-full px-2 py-1 rounded-md border-transparent hover:border-gray-300 dark:hover:border-white/10 focus:border-primary-500 bg-transparent hover:bg-white dark:hover:bg-white/5 focus:bg-white dark:focus:bg-white/5 text-gray-600 dark:text-gray-400 text-xs shadow-none focus:ring-1 focus:ring-primary-500 transition-colors truncate"
                                                            />
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <div class="flex items-center gap-2">
                                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-primary-500/70 flex-shrink-0"></span>
                                                                <input
                                                                    type="text"
                                                                    value="{{ $entrega->entrega }}"
                                                                    wire:change="atualizarEntrega({{ $entrega->id }}, 'entrega', $event.target.value)"
                                                                    placeholder="Nome da entrega"
                                                                    class="w-full px-2 py-1 rounded-md border-transparent hover:border-gray-300 dark:hover:border-white/10 focus:border-primary-500 bg-transparent hover:bg-white dark:hover:bg-white/5 focus:bg-white dark:focus:bg-white/5 text-gray-900 dark:text-gray-100 text-xs font-medium shadow-none focus:ring-1 focus:ring-primary-500 transition-colors truncate"
                                                                />
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <textarea
                                                                rows="3"
                                                                wire:change="atualizarEntrega({{ $entrega->id }}, 'descricao_entrega', $event.target.value)"
                                                                placeholder="Descrição da entrega"
                                                                class="ec-desc-textarea w-full px-2 py-1.5 rounded-md border-transparent hover:border-gray-300 dark:hover:border-white/10 focus:border-primary-500 bg-transparent hover:bg-white dark:hover:bg-white/5 focus:bg-white dark:focus:bg-white/5 text-gray-700 dark:text-gray-300 text-xs shadow-none focus:ring-1 focus:ring-primary-500 transition-colors"
                                                            >{{ $entrega->descricao_entrega }}</textarea>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <textarea
                                                                rows="3"
                                                                wire:change="atualizarEntrega({{ $entrega->id }}, 'descricao_existente', $event.target.value)"
                                                                placeholder="Descrição do existente"
                                                                class="ec-desc-textarea w-full px-2 py-1.5 rounded-md border-transparent hover:border-gray-300 dark:hover:border-white/10 focus:border-primary-500 bg-transparent hover:bg-white dark:hover:bg-white/5 focus:bg-white dark:focus:bg-white/5 text-gray-700 dark:text-gray-300 text-xs shadow-none focus:ring-1 focus:ring-primary-500 transition-colors"
                                                            >{{ $entrega->descricao_existente }}</textarea>
                                                        </td>
                                                        <td class="px-3 py-2 text-center">
                                                            <div
                                                                wire:key="entrega-previsto-menu-{{ $entrega->id }}-{{ $previstoSlug }}"
                                                                class="ec-pill-dropdown"
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
                                                                        if (this.open) this.$nextTick(() => this.reposition());
                                                                    },
                                                                }"
                                                                x-on:keydown.escape="open = false"
                                                                x-on:click.away="open = false"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="ec-pill"
                                                                    style="background-color: {{ $previstoCor }};"
                                                                    x-ref="trigger"
                                                                    x-on:click.stop="toggle()"
                                                                    aria-haspopup="listbox"
                                                                    :aria-expanded="open.toString()"
                                                                >
                                                                    <span class="ec-pill__label">{{ $previstoRotulo }}</span>
                                                                    <svg class="ec-pill__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path d="M5.25 7.5L10 12.25L14.75 7.5"/>
                                                                    </svg>
                                                                </button>
                                                                <template x-teleport="body">
                                                                    <div
                                                                        class="ec-pill-menu"
                                                                        x-show="open"
                                                                        x-transition.opacity.duration.120ms
                                                                        x-cloak
                                                                        :style="`top: ${pos.top}px; left: ${pos.left}px; min-width: ${pos.width}px;`"
                                                                    >
                                                                        @foreach($previstoOptions as $pKey => $pLabel)
                                                                            @php
                                                                                $pCor = $previstoColors[$pKey] ?? '#6b7280';
                                                                                $pProt = $previstoProtected[$pKey] ?? false;
                                                                                $pTipo = $previstoTipoCusto[$pKey] ?? null;
                                                                                $custoAtualOposto = match ($pTipo) {
                                                                                    'contrato' => (float) $entrega->custo_sem_contrato,
                                                                                    'sem_contrato' => (float) $entrega->custo_contrato,
                                                                                    'nenhum' => (float) $entrega->custo_contrato + (float) $entrega->custo_sem_contrato,
                                                                                    default => 0.0,
                                                                                };
                                                                            @endphp
                                                                            <div class="flex items-center gap-1 group">
                                                                                <button
                                                                                    type="button"
                                                                                    wire:key="entrega-previsto-option-{{ $entrega->id }}-{{ $pKey }}"
                                                                                    class="ec-pill-option flex-1 @if($previstoSlug === $pKey) ec-pill-option--selected @endif"
                                                                                    @click.stop="open = false; ecMostrarConfirmacaoPrevisto({{ (int) $entrega->id }}, @js((string) $pKey), {{ (float) $custoAtualOposto }}, $wire);"
                                                                                >
                                                                                    <span class="ec-pill-option__dot" style="background-color: {{ $pCor }};"></span>
                                                                                    <span class="ec-pill-option__label">{{ $pLabel }}</span>
                                                                                </button>
                                                                                @if(! $pProt)
                                                                                    <button
                                                                                        type="button"
                                                                                        x-on:click.stop="if (confirm('Deletar este status?')) { open = false; $wire.deletarPrevisto(@js((string) $pKey)); }"
                                                                                        class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors opacity-0 group-hover:opacity-100"
                                                                                        title="Deletar status"
                                                                                    >
                                                                                        @svg('heroicon-m-trash', 'w-4 h-4')
                                                                                    </button>
                                                                                @endif
                                                                            </div>
                                                                        @endforeach
                                                                        @if($this->podeGerenciarStatus())
                                                                            <div class="border-t border-gray-200 dark:border-white/10 my-1"></div>
                                                                            <button
                                                                                type="button"
                                                                                class="ec-pill-option"
                                                                                x-on:click.stop="open = false; $wire.dispatch('openAdicionarPrevistoModal');"
                                                                            >
                                                                                @svg('heroicon-m-plus', 'w-4 h-4 text-gray-400')
                                                                                <span class="ec-pill-option__label">Adicionar status</span>
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <div
                                                                wire:key="entrega-status-menu-{{ $entrega->id }}-{{ $statusSlug }}"
                                                                class="ec-pill-dropdown"
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
                                                                        if (this.open) this.$nextTick(() => this.reposition());
                                                                    },
                                                                }"
                                                                x-on:keydown.escape="open = false"
                                                                x-on:click.away="open = false"
                                                            >
                                                                <button
                                                                    type="button"
                                                                    class="ec-pill"
                                                                    style="background-color: {{ $statusCor }};"
                                                                    x-ref="trigger"
                                                                    x-on:click.stop="toggle()"
                                                                    aria-haspopup="listbox"
                                                                    :aria-expanded="open.toString()"
                                                                >
                                                                    <span class="ec-pill__label">{{ $statusRotulo }}</span>
                                                                    <svg class="ec-pill__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path d="M5.25 7.5L10 12.25L14.75 7.5"/>
                                                                    </svg>
                                                                </button>
                                                                <template x-teleport="body">
                                                                    <div
                                                                        class="ec-pill-menu"
                                                                        x-show="open"
                                                                        x-transition.opacity.duration.120ms
                                                                        x-cloak
                                                                        :style="`top: ${pos.top}px; left: ${pos.left}px; min-width: ${pos.width}px;`"
                                                                    >
                                                                        @foreach($statusOptions as $key => $label)
                                                                            @php
                                                                                $optionColor = $statusColors[$key] ?? '#6b7280';
                                                                                $optionProt = $statusProtected[$key] ?? false;
                                                                            @endphp
                                                                            <div class="flex items-center gap-1 group">
                                                                                <button
                                                                                    type="button"
                                                                                    wire:key="entrega-status-option-{{ $entrega->id }}-{{ $key }}"
                                                                                    class="ec-pill-option flex-1 @if($statusSlug === $key) ec-pill-option--selected @endif"
                                                                                    x-on:click.stop="open = false; $wire.atualizarEntrega({{ (int) $entrega->id }}, 'status', @js((string) $key));"
                                                                                >
                                                                                    <span class="ec-pill-option__dot" style="background-color: {{ $optionColor }};"></span>
                                                                                    <span class="ec-pill-option__label">{{ $label }}</span>
                                                                                </button>
                                                                                @if(! $optionProt)
                                                                                    <button
                                                                                        type="button"
                                                                                        x-on:click.stop="if (confirm('Deletar este status?')) { open = false; $wire.deletarStatus(@js((string) $key)); }"
                                                                                        class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors opacity-0 group-hover:opacity-100"
                                                                                        title="Deletar status"
                                                                                    >
                                                                                        @svg('heroicon-m-trash', 'w-4 h-4')
                                                                                    </button>
                                                                                @endif
                                                                            </div>
                                                                        @endforeach
                                                                        @if($this->podeGerenciarStatus())
                                                                            <div class="border-t border-gray-200 dark:border-white/10 my-1"></div>
                                                                            <button
                                                                                type="button"
                                                                                class="ec-pill-option"
                                                                                x-on:click.stop="open = false; $wire.dispatch('openAdicionarStatusModal');"
                                                                            >
                                                                                @svg('heroicon-m-plus', 'w-4 h-4 text-gray-400')
                                                                                <span class="ec-pill-option__label">Adicionar status</span>
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            @php
                                                                $semData = $entrega->status === 'nao_entregue';
                                                                $dataValor = optional($entrega->data_entrega)->format('Y-m-d');
                                                            @endphp
                                                            @if($semData)
                                                                <span class="inline-flex items-center w-full px-2 py-1.5 rounded-md border border-dashed border-gray-300 dark:border-white/10 bg-gray-50 dark:bg-white/[0.02] text-gray-400 dark:text-gray-500 text-xs italic" title="Status 'Não entregue' não possui data">
                                                                    Não possui data
                                                                </span>
                                                            @else
                                                                <input
                                                                    type="date"
                                                                    value="{{ $dataValor }}"
                                                                    wire:change="atualizarEntrega({{ $entrega->id }}, 'data_entrega', $event.target.value)"
                                                                    class="w-full px-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                />
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 {{ ! $habilitaCustoContrato ? 'opacity-40 pointer-events-none select-none' : '' }}">
                                                            <div
                                                                wire:key="custo-contrato-{{ $entrega->id }}-{{ $previstoSlug }}-{{ (float) $entrega->custo_contrato }}"
                                                                class="relative"
                                                                x-data="ecMoedaInput(@js((float) $entrega->custo_contrato))"
                                                            >
                                                                <span class="absolute inset-y-0 left-2 flex items-center text-[11px] text-gray-400 pointer-events-none">R$</span>
                                                                <input
                                                                    type="text"
                                                                    inputmode="numeric"
                                                                    x-model="display"
                                                                    x-on:input="aoDigitar($event)"
                                                                    x-on:blur="$wire.atualizarEntrega({{ (int) $entrega->id }}, 'custo_contrato', ecParseBRL(display) ?? 0)"
                                                                    {{ ! $habilitaCustoContrato ? 'tabindex="-1" disabled' : '' }}
                                                                    class="w-full pl-7 pr-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                />
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2 {{ ! $habilitaCustoSemContrato ? 'opacity-40 pointer-events-none select-none' : '' }}">
                                                            <div
                                                                wire:key="custo-sem-contrato-{{ $entrega->id }}-{{ $previstoSlug }}-{{ (float) $entrega->custo_sem_contrato }}"
                                                                class="relative"
                                                                x-data="ecMoedaInput(@js((float) $entrega->custo_sem_contrato))"
                                                            >
                                                                <span class="absolute inset-y-0 left-2 flex items-center text-[11px] text-gray-400 pointer-events-none">R$</span>
                                                                <input
                                                                    type="text"
                                                                    inputmode="numeric"
                                                                    x-model="display"
                                                                    x-on:input="aoDigitar($event)"
                                                                    x-on:blur="$wire.atualizarEntrega({{ (int) $entrega->id }}, 'custo_sem_contrato', ecParseBRL(display) ?? 0)"
                                                                    {{ ! $habilitaCustoSemContrato ? 'tabindex="-1" disabled' : '' }}
                                                                    class="w-full pl-7 pr-2 py-1.5 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs text-right tabular-nums shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                                                                />
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <textarea
                                                                rows="3"
                                                                wire:change="atualizarEntrega({{ $entrega->id }}, 'observacoes', $event.target.value)"
                                                                placeholder="Observações"
                                                                class="ec-desc-textarea w-full px-2 py-1.5 rounded-md border-transparent hover:border-gray-300 dark:hover:border-white/10 focus:border-primary-500 bg-transparent hover:bg-white dark:hover:bg-white/5 focus:bg-white dark:focus:bg-white/5 text-gray-700 dark:text-gray-300 text-xs shadow-none focus:ring-1 focus:ring-primary-500 transition-colors"
                                                            >{{ $entrega->observacoes }}</textarea>
                                                        </td>
                                                        <td class="px-3 py-2 text-center">
                                                            <button
                                                                type="button"
                                                                wire:click="removerEntrega({{ $entrega->id }})"
                                                                wire:confirm="Remover esta entrega?"
                                                                title="Remover entrega"
                                                                class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 dark:hover:text-red-400 transition-colors"
                                                            >
                                                                @svg('heroicon-m-trash', 'w-4 h-4')
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                <tr wire:key="add-row-{{ $obra->id }}">
                                                    <td colspan="12" class="p-0">
                                                        <button
                                                            type="button"
                                                            wire:click="adicionarEntrega({{ $obra->id }})"
                                                            class="w-full pl-6 pr-3 py-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 border-t border-dashed border-gray-300 dark:border-white/10 hover:bg-primary-50/40 dark:hover:bg-primary-500/5 transition-colors"
                                                        >
                                                            @svg('heroicon-m-plus', 'w-3.5 h-3.5')
                                                            <span>Adicionar entrega</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-500 dark:text-gray-400">
                                    @svg('heroicon-o-inbox', 'w-10 h-10 text-gray-300 dark:text-gray-600')
                                    <p class="text-sm font-medium">Nenhuma obra cadastrada na pré-obra</p>
                                    <p class="text-xs">Use o botão "Adicionar obra" no topo para começar.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        @if($obrasColecao->total() > 0)
            <x-controle-pagination :paginator="$obrasColecao" item-label="obra(s)" />
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:navigated', () => {
                // Recarregar a página quando entrega é atualizada
            });

            document.addEventListener('alpine:init', () => {
                window.ecFormatBRL = function (cents) {
                    if (cents === null || cents === undefined || isNaN(cents)) return '';
                    const valor = (Number(cents) / 100).toFixed(2);
                    const [inteira, decimal] = valor.split('.');
                    const inteiraFmt = inteira.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    return inteiraFmt + ',' + decimal;
                };

                window.ecParseBRL = function (texto) {
                    if (texto === null || texto === undefined || texto === '') return null;
                    const digits = String(texto).replace(/\D/g, '');
                    if (digits === '') return null;
                    return Number(digits) / 100;
                };

                window.ecMostrarConfirmacaoPrevisto = function (entregaId, novoSlug, valorOposto, wire) {
                    // valorOposto = soma de valores que serão zerados pela troca; se 0, aplica direto.
                    if (!valorOposto || valorOposto <= 0) {
                        wire.dispatch('confirmarMudancaPrevisto', { entregaId, novoSlug });
                        return;
                    }

                    const valorFormatado = window.ecFormatBRL(Math.round(valorOposto * 100));

                    if (!document.getElementById('ec-modal-confirmacao')) {
                        const modal = document.createElement('div');
                        modal.id = 'ec-modal-confirmacao';
                        modal.innerHTML = `
                            <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;">
                                <div style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);" class="dark:bg-gray-900">
                                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--primary-600);" class="dark:text-primary-400">Confirmar alteração?</h3>
                                    <p style="color: #666; margin-bottom: 16px; line-height: 1.5;" class="dark:text-gray-300">
                                        Existe R$ <strong id="ec-valor-atual"></strong> em campos de custo que serão zerados ao mudar o status.
                                    </p>
                                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                                        <button id="ec-btn-cancelar" style="padding: 8px 16px; border: 1px solid #ddd; border-radius: 6px; background: white; cursor: pointer; font-weight: 500;">Cancelar</button>
                                        <button id="ec-btn-confirmar" style="padding: 8px 16px; border: none; border-radius: 6px; background: var(--primary-600); color: white; cursor: pointer; font-weight: 500;">Confirmar</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                    }

                    const modalEl = document.getElementById('ec-modal-confirmacao');
                    document.getElementById('ec-valor-atual').textContent = valorFormatado;

                    modalEl.style.display = 'flex';

                    document.getElementById('ec-btn-cancelar').onclick = () => {
                        modalEl.style.display = 'none';
                    };

                    document.getElementById('ec-btn-confirmar').onclick = () => {
                        modalEl.style.display = 'none';
                        wire.dispatch('confirmarMudancaPrevisto', { entregaId, novoSlug });
                    };
                };

                Alpine.data('ecStickyMeasure', () => ({
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
                        this.root.querySelectorAll('.ec-thead-main, .ec-parent-row').forEach((el) => this.observer.observe(el));
                    },
                    measure() {
                        if (!this.root) return;
                        const mainHead = this.root.querySelector('.ec-thead-main');
                        const parentRow = this.root.querySelector('.ec-parent-row');
                        if (mainHead) {
                            this.root.style.setProperty('--ec-thead-h', mainHead.getBoundingClientRect().height + 'px');
                        }
                        if (parentRow) {
                            this.root.style.setProperty('--ec-parent-h', parentRow.getBoundingClientRect().height + 'px');
                        } else {
                            this.root.style.setProperty('--ec-parent-h', '0px');
                        }
                    },
                }));

                Alpine.data('ecZoomControl', () => ({
                    zoom: 1,
                    min: 0.6,
                    max: 1.5,
                    step: 0.1,
                    storageKey: 'ec-entrega-contratual-zoom',
                    init() {
                        const salvo = parseFloat(localStorage.getItem(this.storageKey));
                        if (!isNaN(salvo) && salvo >= this.min && salvo <= this.max) {
                            this.zoom = salvo;
                        }

                        if (!window.ecAplicarZoomEntregaContratual) {
                            window.ecAplicarZoomEntregaContratual = (zoom) => {
                                const valor = Number(zoom);

                                if (Number.isNaN(valor)) {
                                    return;
                                }

                                document.querySelectorAll('.ec-zoom-target').forEach((el) => {
                                    el.style.zoom = valor;
                                });
                            };
                        }

                        this.aplicar();

                        if (window.Livewire && Livewire.hook && !window.__ecZoomEntregaHookRegistered) {
                            window.__ecZoomEntregaHookRegistered = true;

                            Livewire.hook('morph.updated', () => {
                                const salvoAtual = parseFloat(localStorage.getItem(this.storageKey));
                                const zoomAtual = !isNaN(salvoAtual) && salvoAtual >= this.min && salvoAtual <= this.max
                                    ? salvoAtual
                                    : this.zoom;

                                window.ecAplicarZoomEntregaContratual(zoomAtual);
                            });
                        }
                    },
                    aplicar() {
                        window.ecAplicarZoomEntregaContratual(this.zoom);
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

                Alpine.data('ecMoedaInput', (valorInicial) => ({
                    display: '',
                    init() {
                        if (valorInicial === null || valorInicial === undefined || valorInicial === '') {
                            this.display = '';
                        } else {
                            const cents = Math.round(Number(valorInicial) * 100);
                            this.display = window.ecFormatBRL(cents);
                        }
                    },
                    aoDigitar(event) {
                        const digits = String(event.target.value).replace(/\D/g, '');
                        if (digits === '') {
                            this.display = '';
                            return;
                        }
                        this.display = window.ecFormatBRL(Number(digits));
                    },
                    aoSair(event, entregaId) {
                        const valor = window.ecParseBRL(this.display);
                        this.$wire.atualizarEntrega(entregaId, 'custo_estimado', valor === null ? '' : String(valor));
                    },
                }));
            });
        </script>
    @endpush

    {{-- Modal: adicionar status (contexto Status da entrega) --}}
    <div
        x-data="{ open: @entangle('abrirModalAdicionarStatus') }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="open = false"
    >
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Adicionar status (Status da entrega)</h2>
            </div>
            <form wire:submit.prevent="submeterNovoStatus" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome do status</label>
                    <input
                        type="text"
                        wire:model="novoStatusNome"
                        placeholder="Ex: ENTREGUE COM RESSALVA, EM ANÁLISE"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cor</label>
                    <input
                        type="color"
                        wire:model="novoStatusCor"
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

    {{-- Modal: adicionar status (contexto Previsto em contrato) --}}
    <div
        x-data="{ open: @entangle('abrirModalAdicionarPrevisto') }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="open = false"
    >
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/10">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Adicionar status (Previsto em contrato?)</h2>
            </div>
            <form wire:submit.prevent="submeterNovoPrevisto" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nome do status</label>
                    <input
                        type="text"
                        wire:model="novoPrevistoNome"
                        placeholder="Ex: EM ANÁLISE, A REVISAR"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cor</label>
                    <input
                        type="color"
                        wire:model="novoPrevistoCor"
                        class="w-full h-12 rounded-lg border border-gray-300 dark:border-white/10 cursor-pointer"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Comportamento do custo</label>
                    <select
                        wire:model="novoPrevistoTipoCusto"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                    >
                        <option value="contrato">Habilita "Custo c/ contrato"</option>
                        <option value="sem_contrato">Habilita "Custo s/ contrato"</option>
                        <option value="nenhum">Não habilita nenhum custo</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Define qual coluna de custo fica ativa quando este status estiver selecionado.</p>
                </div>
                <div class="flex gap-3 justify-end pt-4 border-t border-gray-200 dark:border-white/10">
                    <button
                        type="button"
                        wire:click="$set('abrirModalAdicionarPrevisto', false)"
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

