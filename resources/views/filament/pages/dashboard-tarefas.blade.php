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

        <div class="mt-6">
            {{ $this->table }}
        </div>
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