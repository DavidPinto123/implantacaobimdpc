@php
    use Illuminate\Support\Str;

    $dataInicial = $this->data['data_inicial'] ?? null;
    $dataFinal = $this->data['data_final'] ?? null;
    $assignedTo = $this->data['assigned_to'] ?? null;
    $total = $this->getFilteredTasksQuery()->count();

    $responsavel = null;

    if ($assignedTo) {
        $responsavel = \App\Models\User::find($assignedTo)?->name;
    }
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        {{-- Toggle de visualização: Tabela | Kanban --}}
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <div style="display:flex;gap:2px;background:var(--fi-bg,#f9fafb);border:1px solid #e5e7eb;border-radius:0.5rem;padding:2px;">
                <button wire:click="$set('visualizacao','tabela')"
                        style="padding:5px 14px;border:none;border-radius:0.375rem;cursor:pointer;font-size:0.78rem;font-family:inherit;{{ $visualizacao === 'tabela' ? 'background:#f59e0b;color:#111;font-weight:700;' : 'background:transparent;color:#6b7280;' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
                    Tabela
                </button>
                <button wire:click="$set('visualizacao','kanban')"
                        style="padding:5px 14px;border:none;border-radius:0.375rem;cursor:pointer;font-size:0.78rem;font-family:inherit;{{ $visualizacao === 'kanban' ? 'background:#f59e0b;color:#111;font-weight:700;' : 'background:transparent;color:#6b7280;' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="13" rx="1"/><rect x="17" y="3" width="5" height="16" rx="1"/></svg>
                    Kanban
                </button>
            </div>
            @if($visualizacao === 'kanban')
            <div style="display:flex;align-items:center;gap:5px;margin-left:6px;">
                <span style="font-size:0.72rem;color:#9ca3af;font-weight:600;">Agrupar:</span>
                <button wire:click="$set('kanbanAgrupamento','status')"
                        style="font-size:0.72rem;padding:3px 10px;border-radius:1rem;cursor:pointer;font-family:inherit;{{ $kanbanAgrupamento === 'status' ? 'background:#3b82f6;color:#fff;border:1px solid #3b82f6;font-weight:700;' : 'background:transparent;color:#6b7280;border:1px solid #e5e7eb;' }}">
                    Status
                </button>
                <button wire:click="$set('kanbanAgrupamento','profissional')"
                        style="font-size:0.72rem;padding:3px 10px;border-radius:1rem;cursor:pointer;font-family:inherit;{{ $kanbanAgrupamento === 'profissional' ? 'background:#3b82f6;color:#fff;border:1px solid #3b82f6;font-weight:700;' : 'background:transparent;color:#6b7280;border:1px solid #e5e7eb;' }}">
                    Profissional
                </button>
            </div>
            @endif
        </div>

        <style>
    .cards-tarefas-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    @media (min-width: 768px) {
        .cards-tarefas-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1200px) {
        .cards-tarefas-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
    }

    .card-dashboard {
        padding: 0.75rem;
    }

    .card-dashboard-label {
        font-size: 0.85rem;
    }

    .card-dashboard-value {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .stat-futuras {
    background-color: #ffffff !important;
    }

    .dark .stat-futuras {
        border: 1px solid #ffffff !important;
        background-color: #000 !important;
    }
        </style>

        <div class="mt-6 cards-tarefas-grid">
            @foreach ($this->getCards() as $card)
                <div class="card-dashboard stat-{{ Str::slug($card['label']) }}">
                    <div class="card-dashboard-label">
                        {{ $card['label'] }}
                    </div>

                    <div class="card-dashboard-value">
                        {{ $card['value'] }}
                    </div>
                </div>
            @endforeach
        </div>

        @php
            $charts = collect($this->charts ?? [])->filter(function ($chart) {
                $series = $chart['series'] ?? [];

                if (! count($series)) {
                    return false;
                }

                if (in_array($chart['type'] ?? '', ['donut', 'pie'])) {
                    return true;
                }

                return collect($series)->some(
                    fn ($s) => (is_array($s['data'] ?? null) && count($s['data']))
                        || (! is_array($s) && $s > 0)
                );
            })->values();

            $chartsGrid = $charts->filter(fn ($chart) => ($chart['id'] ?? '') !== 'tarefas-por-usuario');
            $chartUsuario = $charts->first(fn ($chart) => ($chart['id'] ?? '') === 'tarefas-por-usuario');
        @endphp

        @if ($charts->isNotEmpty())

            @if ($chartUsuario)
                @php
                    $signature = md5(json_encode($chartUsuario['series'] ?? []) . json_encode($chartUsuario['labels'] ?? []));
                    $wireKey = 'chart-' . ($chartUsuario['id'] ?? \Illuminate\Support\Str::random(6)) . '-' . $signature . '-' . now()->timestamp;
                @endphp

                <div class="mt-6">
                    <div wire:key="{{ $wireKey }}" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow relative overflow-hidden w-full">
                        <h3 class="text-lg font-semibold mb-2 text-black dark:text-white">
                            {{ $chartUsuario['title'] ?? '' }}
                        </h3>

                        <div
                            x-data="{
                                chart: null,
                                init() {
                                    const series = @js($chartUsuario['series'] ?? []);
                                    const hasData = series.length && series.some(function (s) {
                                        return Array.isArray(s['data']) ? s['data'].length > 0 : s > 0;
                                    });

                                    if (!hasData) return;

                                    try { if (this.chart) this.chart.destroy(); } catch (e) {}

                                    const isDark = document.documentElement.classList.contains('dark');

                                    let options = {
                                        chart: {
                                            type: '{{ $chartUsuario['type'] ?? 'bar' }}',
                                            width: '100%',
                                            height: 350,
                                            background: 'transparent',
                                            toolbar: {
                                                show: true,
                                                tools: {
                                                    download: true,
                                                    selection: false,
                                                    zoom: false,
                                                    zoomin: false,
                                                    zoomout: false,
                                                    pan: false,
                                                    reset: false,
                                                }
                                            },
                                            animations: { enabled: true }
                                        },
                                        series: series,
                                        tooltip: {
                                            theme: isDark ? 'dark' : 'light'
                                        },
                                        legend: {
                                            position: 'bottom',
                                            labels: { colors: isDark ? '#E5E7EB' : '#111827' }
                                        },
                                        xaxis: {
                                            categories: @js($chartUsuario['labels'] ?? []),
                                            labels: {
                                                rotate: -90,
                                                style: {
                                                    colors: isDark ? '#E5E7EB' : '#111827',
                                                    fontSize: '10px',
                                                    fontWeight: '400'
                                                }
                                            },
                                            axisBorder: { color: isDark ? '#6B7280' : '#111827' },
                                            axisTicks: { color: isDark ? '#6B7280' : '#111827' }
                                        },
                                        yaxis: {
                                            labels: {
                                                show: true,
                                                style: {
                                                    colors: isDark ? '#E5E7EB' : '#111827',
                                                    fontSize: '11px',
                                                    fontWeight: '400'
                                                }
                                            }
                                        },
                                        dataLabels: {
                                            enabled: true,
                                            offsetY: -10,
                                            style: { colors: [isDark ? '#E5E7EB' : '#111827'] },
                                            formatter: (val) => val === 0 ? '' : val
                                        },
                                        plotOptions: {
                                            bar: {
                                                horizontal: false,
                                                borderRadius: 3,
                                                columnWidth: '40%',
                                            }
                                        },
                                        colors: ['#3B82F6'],
                                        responsive: [
                                            {
                                                breakpoint: 768,
                                                options: {
                                                    chart: { height: 300 },
                                                    plotOptions: { bar: { columnWidth: '70%' } },
                                                    xaxis: { labels: { rotate: -45, style: { fontSize: '8px' } } },
                                                    yaxis: { labels: { style: { fontSize: '8px' } } },
                                                    dataLabels: { style: { fontSize: '8px' } }
                                                }
                                            }
                                        ]
                                    };

                                    this.chart = new ApexCharts(this.$refs.chart, options);
                                    this.$nextTick(() => {
                                        requestAnimationFrame(() => {
                                            try { this.chart.render(); } catch (e) {}
                                        });
                                    });

                                    const observer = new MutationObserver(() => this.updateColors());
                                    observer.observe(document.documentElement, {
                                        attributes: true,
                                        attributeFilter: ['class']
                                    });
                                    this._observer = observer;

                                    window.addEventListener('resize', () => {
                                        try { this.chart.updateOptions({}); } catch (e) {}
                                    });
                                },
                                isDark() {
                                    return document.documentElement.classList.contains('dark');
                                },
                                updateColors() {
                                    const dark = this.isDark();
                                    if (!this.chart) return;

                                    this.chart.updateOptions({
                                        xaxis: {
                                            labels: { style: { colors: dark ? '#E5E7EB' : '#111827' } },
                                            axisBorder: { color: dark ? '#6B7280' : '#111827' },
                                            axisTicks: { color: dark ? '#6B7280' : '#111827' }
                                        },
                                        yaxis: {
                                            labels: { style: { colors: dark ? '#E5E7EB' : '#111827' } }
                                        },
                                        dataLabels: {
                                            style: { colors: [dark ? '#E5E7EB' : '#111827'] }
                                        },
                                        tooltip: { theme: dark ? 'dark' : 'light' },
                                        legend: { labels: { colors: dark ? '#E5E7EB' : '#111827' } }
                                    });
                                },
                                destroy() {
                                    try { if (this.chart) this.chart.destroy(); } catch (e) {}
                                    try { if (this._observer) this._observer.disconnect(); } catch (e) {}
                                }
                            }"
                            x-ref="chart"
                            class="w-full"
                            style="height: 350px;"
                        ></div>
                    </div>
                </div>
            @endif

            @if ($chartsGrid->isNotEmpty())
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach ($chartsGrid as $chart)
                        @php
                            $signature = md5(json_encode($chart['series'] ?? []) . json_encode($chart['labels'] ?? []));
                            $wireKey = 'chart-' . ($chart['id'] ?? \Illuminate\Support\Str::random(6)) . '-' . $signature . '-' . now()->timestamp;
                        @endphp

                        <div wire:key="{{ $wireKey }}" class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow relative overflow-hidden">
                            <h3 class="text-lg font-semibold mb-2 text-black dark:text-white">
                                {{ $chart['title'] ?? '' }}
                            </h3>

                            <div
                                x-data="{
                                    chart: null,
                                    init() {
                                        const series = @js($chart['series'] ?? []);
                                        const hasData = series.length && series.some(function (s) {
                                            return Array.isArray(s['data']) ? s['data'].length > 0 : s > 0;
                                        });

                                        if (!hasData) return;

                                        try { if (this.chart) this.chart.destroy(); } catch (e) {}

                                        const isDark = document.documentElement.classList.contains('dark');

                                        let options = {
                                            chart: {
                                                type: '{{ $chart['type'] ?? 'bar' }}',
                                                width: '100%',
                                                height: 350,
                                                background: 'transparent',
                                                toolbar: {
                                                    show: true,
                                                    tools: {
                                                        download: true,
                                                        selection: false,
                                                        zoom: false,
                                                        zoomin: false,
                                                        zoomout: false,
                                                        pan: false,
                                                        reset: false,
                                                    }
                                                },
                                                animations: { enabled: true }
                                            },
                                            series: series,
                                            tooltip: {
                                                theme: isDark ? 'dark' : 'light'
                                            },
                                            legend: {
                                                position: 'bottom',
                                                labels: { colors: isDark ? '#E5E7EB' : '#111827' }
                                            },
                                            xaxis: {
                                                categories: @js($chart['labels'] ?? []),
                                                labels: {
                                                    style: {
                                                        colors: isDark ? '#E5E7EB' : '#111827',
                                                        fontSize: '10px',
                                                        fontWeight: '400'
                                                    },
                                                    rotate: 0
                                                },
                                                axisBorder: { color: isDark ? '#6B7280' : '#111827' },
                                                axisTicks: { color: isDark ? '#6B7280' : '#111827' }
                                            },
                                            yaxis: {
                                                labels: {
                                                    style: {
                                                        colors: isDark ? '#E5E7EB' : '#111827',
                                                        fontSize: '10px',
                                                        fontWeight: '400'
                                                    }
                                                }
                                            },
                                            dataLabels: {
                                                enabled: true,
                                                offsetY: -10,
                                                style: { colors: [isDark ? '#E5E7EB' : '#111827'] },
                                                formatter: (val) => val === 0 ? '' : val
                                            },
                                            plotOptions: {},
                                            responsive: [
                                                {
                                                    breakpoint: 768,
                                                    options: {
                                                        chart: { height: 300 },
                                                        plotOptions: { bar: { columnWidth: '70%' } },
                                                        xaxis: { labels: { rotate: -45, style: { fontSize: '8px' } } },
                                                        yaxis: { labels: { style: { fontSize: '8px' } } },
                                                        dataLabels: { style: { fontSize: '8px' } }
                                                    }
                                                }
                                            ]
                                        };

                                        if (options.chart.type === 'donut' || options.chart.type === 'pie') {
                                            options.labels = @js($chart['labels'] ?? []);

                                            if ('{{ $chart['id'] }}' === 'tarefas-por-status') {
                                                options.colors = [
                                                    '#ffcc00',
                                                    '#0062ff',
                                                    '#009765',
                                                    '#bebebe',
                                                ];
                                            }

                                            if ('{{ $chart['id'] }}' === 'tarefas-atrasadas-por-status') {
                                                options.colors = [
                                                    '#991B1B',
                                                    '#EF4444',
                                                ];
                                            }

                                            options.plotOptions = { pie: { donut: { size: '65%' } } };

                                            options.legend.formatter = (seriesName, opts) =>
                                                seriesName + ' (' + opts.w.globals.series[opts.seriesIndex] + ')';

                                            options.dataLabels = {
                                                enabled: true,
                                                formatter: function (val, opts) {
                                                    return opts.w.config.series[opts.seriesIndex];
                                                }
                                            };
                                        } else if (options.chart.type === 'bar') {
                                            options.plotOptions = {
                                                bar: {
                                                    borderRadius: 6,
                                                    columnWidth: '50%'
                                                }
                                            };
                                        }

                                        this.chart = new ApexCharts(this.$refs.chart, options);
                                        this.$nextTick(() => {
                                            requestAnimationFrame(() => {
                                                try { this.chart.render(); } catch (e) {}
                                            });
                                        });

                                        const observer = new MutationObserver(() => this.updateColors());
                                        observer.observe(document.documentElement, {
                                            attributes: true,
                                            attributeFilter: ['class']
                                        });
                                        this._observer = observer;

                                        window.addEventListener('resize', () => {
                                            try { this.chart.updateOptions({}); } catch (e) {}
                                        });
                                    },
                                    isDark() {
                                        return document.documentElement.classList.contains('dark');
                                    },
                                    updateColors() {
                                        const dark = this.isDark();
                                        if (!this.chart) return;

                                        this.chart.updateOptions({
                                            xaxis: {
                                                labels: { style: { colors: dark ? '#E5E7EB' : '#111827' } },
                                                axisBorder: { color: dark ? '#6B7280' : '#111827' },
                                                axisTicks: { color: dark ? '#6B7280' : '#111827' }
                                            },
                                            yaxis: {
                                                labels: { style: { colors: dark ? '#E5E7EB' : '#111827' } }
                                            },
                                            dataLabels: {
                                                style: { colors: [dark ? '#E5E7EB' : '#111827'] }
                                            },
                                            tooltip: { theme: dark ? 'dark' : 'light' },
                                            legend: { labels: { colors: dark ? '#E5E7EB' : '#111827' } }
                                        });
                                    },
                                    destroy() {
                                        try { if (this.chart) this.chart.destroy(); } catch (e) {}
                                        try { if (this._observer) this._observer.disconnect(); } catch (e) {}
                                    }
                                }"
                                x-ref="chart"
                                class="w-full"
                                style="height: 350px;"
                            ></div>
                        </div>
                    @endforeach
                </div>
            @endif

            

        @endif

        <div class="flex flex-col gap-1 px-4 py-3">
            <div class="text-sm text-gray-500">
                Período Selecionado:
                <strong>
                    {{ $dataInicial ? \Carbon\Carbon::parse($dataInicial)->format('d/m/Y') : '-' }}
                    até
                    {{ $dataFinal ? \Carbon\Carbon::parse($dataFinal)->format('d/m/Y') : '-' }}
                </strong>
            </div>

            <div class="text-sm text-gray-500">
                Responsável:
                <strong>{{ $responsavel ?? 'Todos' }}</strong>
            </div>

            <div class="text-sm font-semibold">
                Total de tarefas: {{ $total }}
            </div>
        </div>

        @if($visualizacao === 'kanban')
        {{-- ── KANBAN DE TAREFAS ─────────────────────────────────────── --}}
        @php
            $tkCores = [
                'pendente'     => '#f59e0b',
                'em_andamento' => '#3b82f6',
                'concluida'    => '#22c55e',
                'cancelada'    => '#9ca3af',
            ];
            $tkLabels = [
                'pendente'     => 'Pendente',
                'em_andamento' => 'Em andamento',
                'concluida'    => 'Concluída',
                'cancelada'    => 'Cancelada',
            ];
            $tkTarefas  = $this->getKanbanTarefas();
            $tkUsuarios = $this->getKanbanUsuarios();
        @endphp
        <style>
            .tk-kanban-board { display:flex;gap:12px;overflow-x:auto;padding:4px 0 16px;align-items:flex-start; }
            .tk-kanban-col { flex-shrink:0;width:250px;border-radius:.75rem;background:var(--fi-bg,#f9fafb);border:1px solid #e5e7eb;overflow:hidden; }
            .dark .tk-kanban-col { background:#18181b;border-color:#3f3f46; }
            .tk-kanban-col-header { display:flex;justify-content:space-between;align-items:center;padding:10px 12px; }
            .tk-kanban-count { border-radius:1rem;padding:1px 8px;font-size:.65rem;font-weight:700; }
            .tk-kanban-cards { padding:8px;display:flex;flex-direction:column;gap:8px;min-height:60px;transition:background .15s; }
            .tk-kanban-drop-target { background:rgba(0,0,0,.05); }
            .tk-kanban-card { border-radius:.5rem;padding:11px 12px;cursor:grab;user-select:none;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:transform .12s,opacity .12s; }
            .tk-kanban-card:active { cursor:grabbing;opacity:.85;transform:scale(.98); }
            .tk-kanban-card-nome { font-weight:700;font-size:0.78rem;color:#fff;line-height:1.3;margin-bottom:5px; }
            .tk-kanban-card-resp { font-size:0.66rem;color:rgba(255,255,255,.9);font-weight:600;display:flex;align-items:center;gap:3px; }
            .tk-kanban-card-resp::before { content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.6);flex-shrink:0; }
            .tk-kanban-card-datas { font-size:0.63rem;color:rgba(255,255,255,.78);margin-top:5px; }
            .tk-kanban-card-status { font-size:0.6rem;color:rgba(255,255,255,.7);margin-top:3px;font-style:italic; }
            .tk-kanban-empty { text-align:center;padding:18px;font-size:.72rem;color:#9ca3af; }
        </style>

        @if($kanbanAgrupamento === 'status')
        <div class="tk-kanban-board" x-data="{ draggingId: null, draggingStatus: null }">
            @foreach(['pendente','em_andamento','concluida','cancelada'] as $tkStatus)
                @php
                    $tkCor      = $tkCores[$tkStatus];
                    $tkLabel    = $tkLabels[$tkStatus];
                    $tkCards    = $tkTarefas->get($tkStatus, collect());
                @endphp
                <div class="tk-kanban-col"
                     @dragover.prevent="draggingStatus = '{{ $tkStatus }}'"
                     @drop.prevent="if (draggingId !== null) { $wire.moverTarefaKanban(draggingId, '{{ $tkStatus }}'); draggingId = null; draggingStatus = null; }">
                    <div class="tk-kanban-col-header" style="background:{{ $tkCor }}22;border-bottom:3px solid {{ $tkCor }};">
                        <span style="color:{{ $tkCor }};font-weight:800;font-size:0.78rem;text-transform:uppercase;letter-spacing:.04em;">{{ $tkLabel }}</span>
                        <span class="tk-kanban-count" style="background:{{ $tkCor }};color:#fff;">{{ $tkCards->count() }}</span>
                    </div>
                    <div class="tk-kanban-cards" :class="draggingStatus === '{{ $tkStatus }}' ? 'tk-kanban-drop-target' : ''">
                        @foreach($tkCards as $tkCard)
                            <div class="tk-kanban-card" style="background:{{ $tkCor }};"
                                 draggable="true"
                                 @dragstart="draggingId = {{ $tkCard->id }}; draggingStatus = '{{ $tkStatus }}'"
                                 @dragend="draggingId = null; draggingStatus = null">
                                <div class="tk-kanban-card-nome">{{ $tkCard->title }}</div>
                                @if($tkCard->responsavel)
                                    <div class="tk-kanban-card-resp">{{ Str::before($tkCard->responsavel->name, ' ') }}</div>
                                @endif
                                @if($tkCard->termino_programado)
                                    <div class="tk-kanban-card-datas">
                                        @if($tkCard->inicio) {{ $tkCard->inicio->format('d/m') }} → @endif
                                        {{ $tkCard->termino_programado->format('d/m/y') }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        @if($tkCards->isEmpty())
                            <div class="tk-kanban-empty">Sem tarefas</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @else
        {{-- KANBAN POR PROFISSIONAL --}}
        @php $tkPorUsuario = $tkTarefas; @endphp
        <div class="tk-kanban-board" x-data="{ draggingId: null, draggingUserId: null }">
            {{-- Sem Responsável --}}
            @php $tkSemResp = $tkPorUsuario->get(0, collect()); @endphp
            <div class="tk-kanban-col"
                 @dragover.prevent="draggingUserId = 0"
                 @drop.prevent="if (draggingId !== null) { $wire.moverTarefaResponsavel(draggingId, null); draggingId = null; draggingUserId = null; }">
                <div class="tk-kanban-col-header" style="background:#6b728022;border-bottom:3px solid #6b7280;">
                    <span style="color:#6b7280;font-weight:800;font-size:0.78rem;text-transform:uppercase;letter-spacing:.04em;">Sem Responsável</span>
                    <span class="tk-kanban-count" style="background:#6b7280;color:#fff;">{{ $tkSemResp->count() }}</span>
                </div>
                <div class="tk-kanban-cards" :class="draggingUserId === 0 ? 'tk-kanban-drop-target' : ''">
                    @foreach($tkSemResp as $tkCard)
                        @php $tkCor = $tkCores[$tkCard->status] ?? '#9ca3af'; @endphp
                        <div class="tk-kanban-card" style="background:{{ $tkCor }};"
                             draggable="true"
                             @dragstart="draggingId = {{ $tkCard->id }}; draggingUserId = 0"
                             @dragend="draggingId = null; draggingUserId = null">
                            <div class="tk-kanban-card-nome">{{ $tkCard->title }}</div>
                            <div class="tk-kanban-card-status">{{ $tkLabels[$tkCard->status] ?? $tkCard->status }}</div>
                            @if($tkCard->termino_programado)
                                <div class="tk-kanban-card-datas">Prazo: {{ $tkCard->termino_programado->format('d/m/y') }}</div>
                            @endif
                        </div>
                    @endforeach
                    @if($tkSemResp->isEmpty())
                        <div class="tk-kanban-empty">Sem tarefas</div>
                    @endif
                </div>
            </div>
            {{-- Por usuário --}}
            @foreach($tkUsuarios as $tkUser)
                @php $tkUserCards = $tkPorUsuario->get($tkUser->id, collect()); @endphp
                <div class="tk-kanban-col"
                     @dragover.prevent="draggingUserId = {{ $tkUser->id }}"
                     @drop.prevent="if (draggingId !== null) { $wire.moverTarefaResponsavel(draggingId, '{{ $tkUser->id }}'); draggingId = null; draggingUserId = null; }">
                    <div class="tk-kanban-col-header" style="background:#3b82f622;border-bottom:3px solid #3b82f6;">
                        <span style="color:#3b82f6;font-weight:800;font-size:0.78rem;text-transform:uppercase;letter-spacing:.04em;" title="{{ $tkUser->name }}">{{ Str::before($tkUser->name, ' ') }}</span>
                        <span class="tk-kanban-count" style="background:#3b82f6;color:#fff;">{{ $tkUserCards->count() }}</span>
                    </div>
                    <div class="tk-kanban-cards" :class="draggingUserId === {{ $tkUser->id }} ? 'tk-kanban-drop-target' : ''">
                        @foreach($tkUserCards as $tkCard)
                            @php $tkCor = $tkCores[$tkCard->status] ?? '#9ca3af'; @endphp
                            <div class="tk-kanban-card" style="background:{{ $tkCor }};"
                                 draggable="true"
                                 @dragstart="draggingId = {{ $tkCard->id }}; draggingUserId = {{ $tkUser->id }}"
                                 @dragend="draggingId = null; draggingUserId = null">
                                <div class="tk-kanban-card-nome">{{ $tkCard->title }}</div>
                                <div class="tk-kanban-card-status">{{ $tkLabels[$tkCard->status] ?? $tkCard->status }}</div>
                                @if($tkCard->termino_programado)
                                    <div class="tk-kanban-card-datas">Prazo: {{ $tkCard->termino_programado->format('d/m/y') }}</div>
                                @endif
                            </div>
                        @endforeach
                        @if($tkUserCards->isEmpty())
                            <div class="tk-kanban-empty">Sem tarefas</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @endif
        @else
        <div class="mt-6">
            {{ $this->table }}
        </div>
        @endif
    </div>

    <style>
        .apexcharts-toolbar {
            position: absolute !important;
            top: -30px !important;
            right: 50px !important;
            z-index: 2 !important;
            background-color: #fff !important;
            color: #111827 !important;
        }

        .apexcharts-menu {
            z-index: 8 !important;
        }

        .dark .apexcharts-toolbar {
            background-color: #27272a !important;
            color: #1f2937 !important;
        }

        .dark .apexcharts-menu {
            background-color: #27272a !important;
            color: #E5E7EB !important;
        }

        .apexcharts-toolbar button {
            color: inherit !important;
        }

        @media (max-width: 768px) {
            .apexcharts-toolbar {
                top: -40px !important;
                right: 10px !important;
                transform: scale(0.85);
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    
</x-filament-panels::page>