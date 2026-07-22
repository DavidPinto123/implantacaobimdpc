<x-filament-panels::page>

<style>
.orc-backdrop{position:fixed!important;inset:0!important;z-index:9999!important;display:flex!important;align-items:center!important;justify-content:center!important;background:rgba(0,0,0,.6)!important;padding:1rem!important;}
.orc-box{max-height:92vh!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;}
.orc-box-lg{max-height:95vh!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;}
.orc-header{flex-shrink:0!important;}
.orc-body{overflow-y:auto!important;flex:1 1 0%!important;min-height:0!important;}
.orc-footer{flex-shrink:0!important;}
</style>

    <div class="space-y-4">

        {{-- ── CABEÇALHO ──────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between">
            @if($projetoId)
                <div class="flex items-center gap-3">
                    <button
                        wire:click="voltar"
                        class="inline-flex items-center gap-1 text-sm text-primary-600 dark:text-primary-400 hover:underline"
                    >
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Projetos
                    </button>
                    <span class="text-gray-400 dark:text-gray-600">/</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $projetoNome }}</span>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Selecione um projeto para ver ou criar orçamentos.</p>
            @endif

            @if($projetoId)
            <button
                wire:click="novoOrcamento"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-600 hover:bg-primary-500 text-white transition"
            >
                <x-heroicon-o-plus class="w-4 h-4" />
                Novo Orçamento
            </button>
            @endif
        </div>

        {{-- ── LISTA DE PROJETOS ───────────────────────────────────────────── --}}
        @unless($projetoId)
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Projeto</th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-28">Orçamentos</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-40">Última versão</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->getProjetos() as $projeto)
                    <tr
                        wire:click="selecionarProjeto({{ $projeto->id }}, @js($projeto->nome))"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/60 transition"
                    >
                        <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $projeto->nome }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($projeto->orcamentos_count > 0)
                                <span class="inline-flex items-center justify-center w-7 h-7 text-xs font-bold rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                                    {{ $projeto->orcamentos_count }}
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $projeto->orcamentos_max_data ? \Carbon\Carbon::parse($projeto->orcamentos_max_data)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right pr-3">
                            <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-600">
                            Nenhum projeto encontrado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endunless

        {{-- ── LISTA DE ORÇAMENTOS DO PROJETO ──────────────────────────────── --}}
        @if($projetoId)
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-28">Data</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Nome</th>
                        <th class="text-center px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-24">Categorias</th>
                        <th class="text-right px-4 py-3 font-semibold text-gray-700 dark:text-gray-300 w-32">Total Geral</th>
                        <th class="w-32 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->getOrcamentosDoProjeto() as $orcamento)
                    <tr
                        wire:click="abrirDetalhe({{ $orcamento->id }})"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/60 transition"
                    >
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            {{ $orcamento->data->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $orcamento->nome }}</td>
                        <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">{{ $orcamento->categorias_count }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                            R$ {{ number_format($orcamento->total_geral, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right" wire:click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    wire:click="gerarPdf({{ $orcamento->id }})"
                                    title="Baixar PDF"
                                    class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
                                >
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                </button>
                                <button
                                    wire:click="duplicarOrcamento({{ $orcamento->id }})"
                                    title="Duplicar"
                                    class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
                                >
                                    <x-heroicon-o-document-duplicate class="w-4 h-4" />
                                </button>
                                <button
                                    wire:click="editarOrcamento({{ $orcamento->id }})"
                                    title="Editar"
                                    class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
                                >
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </button>
                                <button
                                    x-on:click="if(confirm('Excluir este orçamento?')) $wire.deletarOrcamento({{ $orcamento->id }})"
                                    title="Excluir"
                                    class="p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500"
                                >
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400 dark:text-gray-600">
                            Nenhum orçamento registrado para este projeto.
                            <button wire:click="novoOrcamento" class="ml-1 text-primary-600 dark:text-primary-400 hover:underline">Criar primeiro orçamento</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── MODAL: DETALHE DO ORÇAMENTO ─────────────────────────────────── --}}
        @if($modalDetalheAberto)
        @php $orcamento = $this->getOrcamentoDetalhe(); @endphp
        @if($orcamento)
        <div class="orc-backdrop">
            <div class="orc-box bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl">

                {{-- Header --}}
                <div class="orc-header flex items-start justify-between px-6 pt-5 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-400 mb-0.5">
                            {{ $orcamento->projeto?->nome ?? 'Sem projeto' }}
                        </p>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $orcamento->nome }}</h2>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <button
                            wire:click="editarOrcamento({{ $orcamento->id }})"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition"
                        >
                            <x-heroicon-o-pencil class="w-3.5 h-3.5" />
                            Editar
                        </button>
                        <button
                            wire:click="fecharDetalhe"
                            class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition"
                        >
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="orc-body px-6 py-5 space-y-6">

                    {{-- Informações --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Data</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $orcamento->data->format('d/m/Y') }}</p>
                        </div>
                        @if($orcamento->nome_mkt)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Nome MKT</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $orcamento->nome_mkt }}</p>
                        </div>
                        @endif
                        @if($orcamento->projeto?->sigla)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Sigla</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $orcamento->projeto->sigla }}</p>
                        </div>
                        @endif
                        @if($orcamento->projeto?->marca)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Marca</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $orcamento->projeto->marca }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Filtros --}}
                    @php $resumo = $this->getResumoDetalhe($orcamento); @endphp
                    <div class="flex flex-wrap items-center gap-3">
                        <select wire:model.live="filtroCategoria"
                            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-1.5 focus:ring-2 focus:ring-primary-500 focus:outline-none">
                            <option value="">Todas as categorias</option>
                            @foreach($orcamento->categorias as $categoria)
                            <option value="{{ $categoria->nome }}">{{ $categoria->nome }}</option>
                            @endforeach
                        </select>
                        <input type="text" wire:model.live.debounce.300ms="filtroBusca"
                            placeholder="Buscar por código ou descrição…"
                            class="flex-1 min-w-[180px] rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm px-3 py-1.5 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
                        @if($filtroCategoria || $filtroBusca)
                        <button wire:click="limparFiltrosDetalhe" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                            Limpar filtros
                        </button>
                        @endif
                    </div>

                    {{-- Orçamento + resumo --}}
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

                        {{-- Tabela agrupada por categoria --}}
                        <div class="lg:col-span-2 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-20">Código</th>
                                        <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300">Item</th>
                                        <th class="text-center px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-14">Un</th>
                                        <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-20">Qtd</th>
                                        <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-24">Mat</th>
                                        <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-24">MO</th>
                                        <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-24">Mat+MO</th>
                                        <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 w-28">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($resumo['grupos'] as $grupo)
                                    <tr class="bg-gray-50/80 dark:bg-gray-800/60">
                                        <td colspan="8" class="px-3 py-1.5 font-semibold text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                            {{ $grupo['categoria']->nome }}
                                        </td>
                                    </tr>
                                    @foreach($grupo['itens'] as $item)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $item->codigo ?: '—' }}</td>
                                        <td class="px-3 py-2 text-gray-900 dark:text-white">{{ $item->descricao }}</td>
                                        <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-400">{{ $item->unidade }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($item->valor_mat, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($item->valor_mo, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($item->valor_mat_mo, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($item->valor_total, 2, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                    <tr class="bg-gray-50/60 dark:bg-gray-800/40 font-semibold">
                                        <td colspan="7" class="px-3 py-1.5 text-right text-xs text-gray-600 dark:text-gray-400">TOTAL DA CATEGORIA</td>
                                        <td class="px-3 py-1.5 text-right text-gray-900 dark:text-white">{{ number_format($grupo['total_geral'], 2, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="px-3 py-8 text-center text-gray-400 dark:text-gray-600">
                                            Nenhum item encontrado para os filtros selecionados.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if($resumo['grupos']->isNotEmpty())
                                <tfoot class="bg-gray-50 dark:bg-gray-800 font-bold">
                                    <tr>
                                        <td colspan="7" class="px-3 py-2 text-right text-gray-900 dark:text-white">TOTAL GERAL</td>
                                        <td class="px-3 py-2 text-right text-gray-900 dark:text-white">{{ number_format($resumo['total_geral'], 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>

                        {{-- Resumo + gráfico --}}
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Itens: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $resumo['total_itens'] }}</span>
                                </p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Total Geral: R$ {{ number_format($resumo['total_geral'], 2, ',', '.') }}
                                </p>
                            </div>

                            @if($resumo['grupos']->isNotEmpty())
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                                    Distribuição por Categoria
                                </h4>
                                @php
                                    $chartSignature = md5(json_encode($resumo['chart_labels']) . json_encode($resumo['chart_series']));
                                @endphp
                                <div
                                    wire:key="orc-chart-{{ $orcamento->id }}-{{ $chartSignature }}"
                                    wire:ignore
                                    x-data="{
                                        chart: null,
                                        init() {
                                            this.renderWhenReady();
                                        },
                                        renderWhenReady(retry = 0) {
                                            if (window.ApexCharts) { this.mountChart(); return; }
                                            const existing = document.getElementById('apexcharts-cdn-js');
                                            if (!existing) {
                                                const script = document.createElement('script');
                                                script.id = 'apexcharts-cdn-js';
                                                script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                                                script.async = true;
                                                script.onload = () => this.mountChart();
                                                document.head.appendChild(script);
                                            }
                                            if (retry > 25) return;
                                            setTimeout(() => this.renderWhenReady(retry + 1), 200);
                                        },
                                        mountChart() {
                                            const labels = @js($resumo['chart_labels']);
                                            const series = @js($resumo['chart_series']);
                                            if (!series.length || !series.some(v => v > 0)) return;

                                            const isDark = document.documentElement.classList.contains('dark');
                                            try { if (this.chart) this.chart.destroy(); } catch(e){}

                                            this.chart = new ApexCharts(this.$refs.chart, {
                                                chart: { type: 'donut', height: 220, background: 'transparent', fontFamily: 'inherit' },
                                                series: series,
                                                labels: labels,
                                                colors: ['#6B7280','#3B82F6','#22C55E','#EF4444','#F59E0B','#8B5CF6','#EC4899','#14B8A6'],
                                                legend: { position: 'bottom', fontSize: '11px', labels: { colors: isDark ? '#D1D5DB' : '#374151' },
                                                    formatter: (name, opts) => name + ' (' + opts.w.globals.seriesPercent[opts.seriesIndex][0].toFixed(1) + '%)' },
                                                tooltip: { theme: isDark ? 'dark' : 'light' },
                                                plotOptions: { pie: { donut: { size: '55%' } } },
                                                dataLabels: { enabled: false },
                                            });
                                            this.chart.render();
                                        },
                                        destroy() { try { if (this.chart) this.chart.destroy(); } catch(e){} }
                                    }"
                                    x-ref="chart"
                                    style="min-height:220px;"
                                ></div>
                            </div>
                            @endif

                            <dl class="space-y-1.5 text-sm border-t border-gray-100 dark:border-gray-800 pt-3">
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Total MAT</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">R$ {{ number_format($resumo['total_mat'], 2, ',', '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-gray-500 dark:text-gray-400">Total MO</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">R$ {{ number_format($resumo['total_mo'], 2, ',', '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-800">
                                    <dt class="font-semibold text-gray-700 dark:text-gray-300">Total Geral</dt>
                                    <dd class="font-bold text-gray-900 dark:text-white">R$ {{ number_format($resumo['total_geral'], 2, ',', '.') }}</dd>
                                </div>
                            </dl>
                        </div>

                    </div>

                </div>{{-- /body --}}

                {{-- Footer --}}
                <div class="orc-footer px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-600">
                        Registrado em {{ $orcamento->created_at->format('d/m/Y \à\s H:i') }}
                        @if($orcamento->criador) por {{ $orcamento->criador->name }} @endif
                    </span>
                    <div class="flex items-center gap-2">
                        <button wire:click="duplicarOrcamento({{ $orcamento->id }})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 transition">
                            <x-heroicon-o-document-duplicate class="w-3.5 h-3.5" />
                            Duplicar
                        </button>
                        <button wire:click="gerarPdf({{ $orcamento->id }})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-600 hover:bg-primary-500 text-white transition">
                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                            Baixar PDF
                        </button>
                    </div>
                </div>

            </div>
        </div>
        @endif
        @endif

        {{-- ── MODAL: FORMULÁRIO (CRIAR / EDITAR) ─────────────────────────── --}}
        @if($modalFormAberto)
        @php
            $inputCls  = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none';
            $inputSmCls = 'w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1.5 text-xs focus:ring-2 focus:ring-primary-500 focus:outline-none';
        @endphp

        <datalist id="categorias-lista">
            @foreach($this->getCategoriasSugeridas() as $sugestao)
            <option value="{{ $sugestao }}">
            @endforeach
        </datalist>

        <div class="orc-backdrop">
            <div class="orc-box-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-5xl">

                {{-- Header fixo --}}
                <div class="orc-header flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $editandoId ? 'Editar Orçamento' : 'Novo Orçamento' }}
                    </h2>
                    <button wire:click="fecharModal" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Corpo rolável --}}
                <div class="orc-body px-6 py-5 space-y-6">

                    {{-- Informações gerais --}}
                    <div class="space-y-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Informações do Orçamento</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Projeto <span class="text-red-500">*</span></label>
                            <select wire:model="formProjetoId" class="{{ $inputCls }}">
                                <option value="">Selecione…</option>
                                @foreach($this->getProjetos() as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                                @endforeach
                            </select>
                            @error('formProjetoId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Orçamento <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="formNome" placeholder="Ex: Estudo orçamento" class="{{ $inputCls }}" />
                                @error('formNome') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="formData" class="{{ $inputCls }}" />
                                @error('formData') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome MKT <span class="text-gray-400 font-normal">(opcional)</span></label>
                            <input type="text" wire:model="formNomeMkt" class="{{ $inputCls }}" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do arquivo Revit <span class="text-gray-400 font-normal">(opcional)</span></label>
                            <input type="text" wire:model="formArquivoRevit" placeholder="Ex: Hospital" class="{{ $inputCls }}" />
                            <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">Usado para localizar os itens que a API do Revit gravou para este arquivo, ao clicar em "Sincronizar Revit".</p>
                        </div>
                    </div>

                    {{-- Categorias e Itens --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Categorias e Itens</h3>
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="sincronizarRevit" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline disabled:opacity-50">
                                    <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                                    Sincronizar Revit
                                </button>
                                <button type="button" wire:click="adicionarCategoria"
                                    class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    <x-heroicon-o-plus class="w-3.5 h-3.5" />
                                    Adicionar categoria
                                </button>
                            </div>
                        </div>

                        @if(count($formCategorias) === 0)
                            <p class="text-xs text-gray-400 dark:text-gray-600 italic">Nenhuma categoria adicionada.</p>
                        @endif

                        <div class="space-y-4">
                            @foreach($formCategorias as $ci => $cat)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 space-y-2">
                                {{-- Nome da categoria --}}
                                <div class="flex gap-2 items-center">
                                    <input
                                        type="text"
                                        wire:model="formCategorias.{{ $ci }}.nome"
                                        list="categorias-lista"
                                        autocomplete="off"
                                        placeholder="Nome da categoria (ex: Paredes, Portas, Janelas…) *"
                                        class="{{ $inputSmCls }} font-medium"
                                    />
                                    <button type="button" wire:click="removerCategoria({{ $ci }})"
                                        class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-400 flex-shrink-0" title="Remover categoria">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>
                                @error("formCategorias.$ci.nome") <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                                {{-- Itens da categoria --}}
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                                <th class="pb-1 pr-1 w-24">Código</th>
                                                <th class="pb-1 pr-1">Descrição</th>
                                                <th class="pb-1 pr-1 w-16">Unid</th>
                                                <th class="pb-1 pr-1 w-20">Qtd</th>
                                                <th class="pb-1 pr-1 w-24">Mat (un.)</th>
                                                <th class="pb-1 pr-1 w-24">MO (un.)</th>
                                                <th class="pb-1 pr-1 w-24 text-right">Total</th>
                                                <th class="pb-1 w-8"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($cat['itens'] ?? []) as $ii => $item)
                                            @php
                                                $qtd   = is_numeric($item['quantidade'] ?? null) ? (float) $item['quantidade'] : 0;
                                                $mat   = is_numeric($item['valor_mat'] ?? null) ? (float) $item['valor_mat'] : 0;
                                                $mo    = is_numeric($item['valor_mo'] ?? null) ? (float) $item['valor_mo'] : 0;
                                                $total = $qtd * ($mat + $mo);
                                            @endphp
                                            <tr class="align-top">
                                                <td class="pb-1.5 pr-1">
                                                    <input type="text" wire:model="formCategorias.{{ $ci }}.itens.{{ $ii }}.codigo" class="{{ $inputSmCls }}" />
                                                </td>
                                                <td class="pb-1.5 pr-1">
                                                    <input type="text" wire:model="formCategorias.{{ $ci }}.itens.{{ $ii }}.descricao" placeholder="Descrição do item *" class="{{ $inputSmCls }}" />
                                                    @error("formCategorias.$ci.itens.$ii.descricao") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                                                </td>
                                                <td class="pb-1.5 pr-1">
                                                    <input type="text" wire:model="formCategorias.{{ $ci }}.itens.{{ $ii }}.unidade" placeholder="un" class="{{ $inputSmCls }}" />
                                                </td>
                                                <td class="pb-1.5 pr-1">
                                                    <input type="number" step="0.001" min="0" wire:model.live="formCategorias.{{ $ci }}.itens.{{ $ii }}.quantidade" class="{{ $inputSmCls }}" />
                                                </td>
                                                <td class="pb-1.5 pr-1">
                                                    <input type="number" step="0.01" min="0" wire:model.live="formCategorias.{{ $ci }}.itens.{{ $ii }}.valor_mat" class="{{ $inputSmCls }}" />
                                                </td>
                                                <td class="pb-1.5 pr-1">
                                                    <input type="number" step="0.01" min="0" wire:model.live="formCategorias.{{ $ci }}.itens.{{ $ii }}.valor_mo" class="{{ $inputSmCls }}" />
                                                </td>
                                                <td class="pb-1.5 pr-1 text-right font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                    {{ number_format($total, 2, ',', '.') }}
                                                </td>
                                                <td class="pb-1.5 text-right">
                                                    <button type="button" wire:click="removerItem({{ $ci }}, {{ $ii }})"
                                                        class="p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-400">
                                                        <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <button type="button" wire:click="adicionarItem({{ $ci }})"
                                    class="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline">
                                    <x-heroicon-o-plus class="w-3.5 h-3.5" />
                                    Adicionar item
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>{{-- /corpo rolável --}}

                {{-- Footer fixo com botões --}}
                <div class="orc-footer flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
                    <button type="button" wire:click="fecharModal"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        Cancelar
                    </button>
                    <button type="button" wire:click="salvar"
                        class="px-5 py-2 text-sm font-medium rounded-lg bg-primary-600 hover:bg-primary-500 text-white transition">
                        {{ $editandoId ? 'Salvar Alterações' : 'Criar Orçamento' }}
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>
</x-filament-panels::page>
