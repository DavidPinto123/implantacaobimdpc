{{-- Wrapper custom da tabela de ImportacaoNotaFiscais: delega a tabela ao template padrão do Filament e adiciona o modal custom de filtros. --}}

<div
    x-data="{
        infFiltersOpen: false,
        infObserver: null,
        init() {
            this.$nextTick(() => this.ensureTriggerInToolbar());
            this.infObserver = new MutationObserver(() => this.ensureTriggerInToolbar());
            this.infObserver.observe(this.$root, { childList: true, subtree: true });
        },
        destroy() {
            if (this.infObserver) {
                this.infObserver.disconnect();
                this.infObserver = null;
            }
        },
        ensureTriggerInToolbar() {
            const wrapper = this.$root;
            const source = wrapper.querySelector(':scope > .inf-filters-trigger-btn');
            const toolbar = wrapper.querySelector('.fi-ta-header-toolbar');
            if (! source || ! toolbar) return;
            if (toolbar.querySelector('.inf-filters-trigger-btn')) return;

            const colMgr = toolbar.querySelector('.fi-ta-col-manager-dropdown, [class*=\'col-manager\']');
            const clone = source.cloneNode(true);
            clone.addEventListener('click', () => this.infFiltersOpen = true);
            if (colMgr) {
                colMgr.parentNode.insertBefore(clone, colMgr);
            } else {
                toolbar.appendChild(clone);
            }
        },
    }"
    class="inf-table-wrapper"
>
    @include('filament-tables::index')

    {{-- Source do botão: fica escondido, é clonado via JS para dentro da toolbar --}}
    <button type="button"
            class="inf-filters-trigger-btn fi-icon-btn fi-icon-btn-size-md"
            title="Filtros"
    >
        <span class="fi-icon-btn-icon-ctn" style="position: relative;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fi-icon-btn-icon" style="width: 1.25rem; height: 1.25rem;">
                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
            </svg>
        </span>
    </button>

    {{-- Modal custom de filtros --}}
    <template x-teleport="body">
        <div x-show="infFiltersOpen"
             x-transition.opacity.duration.150ms
             class="fi-colmgr-overlay"
             @click.self="infFiltersOpen = false"
             @keydown.escape.window="infFiltersOpen = false"
             style="display:none;">

            <div class="fi-colmgr-modal" @click.stop>

                <div class="fi-colmgr-head">
                    <span>Filtros</span>
                    <div class="fi-colmgr-head-actions">
                        <button type="button"
                                @click="$wire.call('resetTableFiltersForm'); infFiltersOpen = false;"
                                class="fi-colmgr-reset-btn">
                            Limpar Filtros
                        </button>
                        <button type="button" @click="infFiltersOpen = false" class="fi-colmgr-close-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>

                <div class="fi-colmgr-body">
                    @php
                        $infUnidadeOptions = \App\Models\Obras::query()
                            ->orderBy('codigo')
                            ->orderBy('unidade')
                            ->get(['id', 'codigo', 'unidade'])
                            ->mapWithKeys(function (\App\Models\Obras $obra): array {
                                $codigo = trim((string) ($obra->codigo ?? ''));
                                $unidade = trim((string) ($obra->unidade ?? ''));
                                $label = trim(($codigo !== '' ? ($codigo.' - ') : '').$unidade);

                                return [
                                    $obra->id => ($label !== '' ? $label : ('Obra #'.$obra->id)),
                                ];
                            })
                            ->all();

                        $infFilterGroups = [
                            'Unidade' => [
                                [
                                    'name' => 'unidade',
                                    'label' => 'Unidade',
                                    'type' => 'select',
                                    'wireKey' => 'tableFilters.unidade.value',
                                    'options' => $infUnidadeOptions,
                                ],
                            ],
                            'Classificação' => [
                                [
                                    'name' => 'tipo_medicao',
                                    'label' => 'Tipo de medição',
                                    'type' => 'select',
                                    'wireKey' => 'tableFilters.tipo_medicao.value',
                                    'options' => [
                                        'mao_obra' => 'Mão de Obra',
                                        'material' => 'Material',
                                        'transporte' => 'Transporte',
                                    ],
                                ],
                                [
                                    'name' => 'status',
                                    'label' => 'Status',
                                    'type' => 'select',
                                    'wireKey' => 'tableFilters.status.value',
                                    'options' => \App\Models\ControleNotaFiscalNota::getStatusOptions(),
                                ],
                                [
                                    'name' => 'instrucoes_pagamento',
                                    'label' => 'Instruções de pagamento',
                                    'type' => 'select',
                                    'wireKey' => 'tableFilters.instrucoes_pagamento.value',
                                    'options' => [
                                        'pix' => 'PIX',
                                        'dados_bancarios' => 'Dados Bancários',
                                        'boleto_bancario' => 'Boleto Bancário',
                                    ],
                                ],
                            ],
                            'Fornecedor / Anexo' => [
                                [
                                    'name' => 'construtora',
                                    'label' => 'Fornecedor',
                                    'type' => 'select',
                                    'wireKey' => 'tableFilters.fornecedor.value',
                                    'options' => \App\Models\Construtora::query()
                                        ->orderBy('nome')
                                        ->pluck('nome', 'id')
                                        ->all(),
                                ],
                                [
                                    'name' => 'arquivo_path',
                                    'label' => 'Com anexo',
                                    'type' => 'select',
                                    'wireKey' => 'tableFilters.arquivo_path.value',
                                    'options' => [
                                        '1' => 'Sim',
                                        '0' => 'Não',
                                    ],
                                ],
                            ],
                            'Datas' => [
                                [
                                    'name' => 'emissao',
                                    'label' => 'Emissão',
                                    'type' => 'date_range',
                                    'fromKey' => 'tableFilters.emissao.from',
                                    'untilKey' => 'tableFilters.emissao.until',
                                ],
                                [
                                    'name' => 'envio',
                                    'label' => 'Envio da nota',
                                    'type' => 'date_range',
                                    'fromKey' => 'tableFilters.envio.from',
                                    'untilKey' => 'tableFilters.envio.until',
                                ],
                            ],
                        ];
                    @endphp

                    @foreach ($infFilterGroups as $groupLabel => $groupFilters)
                        <div class="fi-colmgr-group">
                            <div class="fi-colmgr-group-head">
                                <div class="fi-colmgr-group-head-inner">
                                    <span class="fi-colmgr-group-label">{{ $groupLabel }}</span>
                                </div>
                            </div>
                            <div class="fi-colmgr-group-items">
                                @foreach ($groupFilters as $filter)
                                    <div class="fi-filtmgr-row">
                                        <span class="fi-filtmgr-label">{{ $filter['label'] }}</span>
                                        <div class="fi-filtmgr-control">
                                            @if ($filter['type'] === 'select')
                                                <select
                                                    wire:model.live="{{ $filter['wireKey'] }}"
                                                    class="fi-filtmgr-select"
                                                >
                                                    <option value="">Todos</option>
                                                    @foreach ($filter['options'] as $optValue => $optLabel)
                                                        <option value="{{ $optValue }}">{{ $optLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($filter['type'] === 'date_range')
                                                <div class="fi-filtmgr-date-group">
                                                    <input
                                                        type="date"
                                                        wire:model.live.debounce.800ms="{{ $filter['fromKey'] }}"
                                                        class="fi-filtmgr-date"
                                                        placeholder="De"
                                                    />
                                                    <span class="fi-filtmgr-date-sep">-</span>
                                                    <input
                                                        type="date"
                                                        wire:model.live.debounce.800ms="{{ $filter['untilKey'] }}"
                                                        class="fi-filtmgr-date"
                                                        placeholder="Até"
                                                    />
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
                    <button type="button" @click="infFiltersOpen = false" class="fi-colmgr-btn fi-colmgr-btn-close">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
