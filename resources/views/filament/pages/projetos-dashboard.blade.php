<x-filament::page>
    <div class="mb-4 relative z-9">
        {{ $this->form }}
    </div>

    @php
        $charts = collect($this->charts ?? [])->filter(function($chart){
            $series = $chart['series'] ?? [];
            return count($series) && collect($series)->some(fn($s) => (is_array($s['data'] ?? null) && count($s['data'])) || (!is_array($s) && $s > 0));
        })->values();
    @endphp

    @if($charts->isEmpty())
        <div class="text-center text-gray-500">
            Nenhum dado disponível para os filtros selecionados.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($charts as $chart)
                @php
                    $signature = md5(json_encode($chart['series'] ?? []) . json_encode($chart['labels'] ?? []));
                    $wireKey = 'chart-' . ($chart['id'] ?? Str::random(6)) . '-' . $signature . '-' . now()->timestamp;
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
                                const hasData = series.length && series.some(function(s){
                                    return Array.isArray(s['data']) ? s['data'].length > 0 : s > 0;
                                });
                                if(!hasData) return;

                                try { if(this.chart) this.chart.destroy(); } catch(e){}

                                const isDark = document.documentElement.classList.contains('dark');

                                let options = {
                                    chart: {
                                        type: '{{ $chart['type'] ?? 'bar' }}',
                                        height: 350,
                                        background: 'transparent',
                                        toolbar: { show: true, tools: {
                                            download: true,
                                            selection: false,
                                            zoom: false,
                                            zoomin: false,
                                            zoomout: false,
                                            pan: false,
                                            reset: false
                                        } },
                                        animations: { enabled: true }
                                    },
                                    series: series,
                                    tooltip: {
                                        theme: isDark ? 'dark' : 'light',
                                        y: { formatter: (val) => {
                                            @if(isset($chart['currency']) && $chart['currency'] === 'BRL')
                                                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
                                            @elseif(str_contains(strtolower($chart['title'] ?? ''), '%'))
                                                return val + '%';
                                            @else
                                                return val;
                                            @endif
                                        }}
                                    },
                                    legend: { position: 'bottom', labels: { colors: isDark ? '#E5E7EB' : '#111827' } },
                                    xaxis: {
                                        categories: @js($chart['labels'] ?? []),
                                        labels: { 
                                            style: { colors: isDark ? '#E5E7EB' : '#111827', fontSize: '10px', fontWeight: '400' },
                                            rotate: 0
                                        },
                                        axisBorder: { color: isDark ? '#6B7280' : '#111827' },
                                        axisTicks: { color: isDark ? '#6B7280' : '#111827' }
                                    },
                                    yaxis: { labels: { style: { colors: isDark ? '#E5E7EB' : '#111827', fontSize: '10px', fontWeight: '400' } } },
                                    dataLabels: { enabled:true, offsetY:-10, style:{ colors: [isDark ? '#E5E7EB' : '#111827'] }, formatter: (val) => {
                                        if(val===0) return '';
                                        @if(isset($chart['currency']) && $chart['currency'] === 'BRL')
                                            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
                                        @elseif(str_contains(strtolower($chart['title'] ?? ''), '%'))
                                            return val + '%';
                                        @else
                                            return val;
                                        @endif
                                    }},
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

                                if(options.chart.type === 'donut' || options.chart.type === 'pie') {
                                    options.labels = @js($chart['labels'] ?? []);
                                    options.colors = ['#6B7280','#3B82F6','#10B981','#EF4444','#F59E0B','#8B5CF6'];
                                    options.plotOptions = { pie: { donut: { size:'65%' } } };
                                    options.legend.formatter = (seriesName, opts) => seriesName + ' (' + opts.w.globals.series[opts.seriesIndex] + ')';
                                    options.dataLabels = { enabled:true };
                                } else if(options.chart.type === 'bar') {
                                    options.plotOptions = { bar: { borderRadius:6, columnWidth:'50%' } };
                                }

                                this.chart = new ApexCharts(this.$refs.chart, options);
                                this.$nextTick(() => { requestAnimationFrame(() => { try { this.chart.render(); } catch(e){} }); });

                                const observer = new MutationObserver(() => this.updateColors());
                                observer.observe(document.documentElement, { attributes:true, attributeFilter:['class'] });
                                this._observer = observer;

                                window.addEventListener('resize', () => { try { this.chart.updateOptions({}); } catch(e){} });
                            },
                            isDark(){ return document.documentElement.classList.contains('dark'); },
                            updateColors(){
                                const dark = this.isDark();
                                if(!this.chart) return;
                                this.chart.updateOptions({
                                    xaxis: { labels:{ style:{ colors: dark ? '#E5E7EB':'#111827' } }, axisBorder:{ color: dark ? '#6B7280':'#111827' }, axisTicks:{ color: dark ? '#6B7280':'#111827' } },
                                    yaxis: { labels:{ style:{ colors: dark ? '#E5E7EB':'#111827' } } },
                                    dataLabels: { style:{ colors:[dark ? '#E5E7EB':'#111827'] } },
                                    tooltip:{ theme: dark?'dark':'light' },
                                    legend:{ labels:{ colors: dark?'#E5E7EB':'#111827' } }
                                });
                            },
                            destroy(){
                                try{ if(this.chart) this.chart.destroy(); } catch(e){}
                                try{ if(this._observer) this._observer.disconnect(); } catch(e){}
                            }
                        }"
                        x-ref="chart"
                        class="w-full"
                        style="height:350px">
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <style>
        .form-wrapper { position: relative; z-index: 9; }
        .apexcharts-toolbar { position: absolute !important; top: -30px !important; right: 50px !important; z-index: 2 !important; background-color:#fff !important; color:#111827 !important; }
        .apexcharts-menu { z-index: 8 !important; }
        .dark .apexcharts-toolbar { background-color:#27272a !important; color:#1f2937 !important; }
        .dark .apexcharts-menu { background-color:#27272a !important; color:#E5E7EB !important; }
        .apexcharts-toolbar button { color: inherit !important; }
        @media (max-width: 768px) {
            .apexcharts-toolbar {
                top: -40px !important;
                right: 10px !important; /* encosta na direita da div */
                transform: scale(0.85); /* diminui um pouco pra caber melhor */
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</x-filament::page>
