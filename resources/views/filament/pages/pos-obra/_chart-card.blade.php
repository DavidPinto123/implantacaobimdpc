@php
    $isHorizontal = $chart['horizontal'] ?? false;
    $isPercent    = $chart['percent'] ?? false;
    $chartHeight  = $isHorizontal ? max(280, count($chart['labels'] ?? []) * 28 + 60) : 280;
    $signature    = md5(json_encode($chart['series'] ?? []) . json_encode($chart['labels'] ?? []));
    $wireKey      = 'chart-' . ($chart['id'] ?? \Illuminate\Support\Str::random(6)) . '-' . $signature;
@endphp

<div wire:key="{{ $wireKey }}"
     class="po-card" style="padding:1.25rem; overflow:hidden;">

    <h3 class="po-section-title" style="font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin:0 0 1rem;">
        {{ $chart['title'] ?? '' }}
    </h3>

    <div
        x-data="{
            chart: null,
            _obs: null,
            _retryTimer: null,
            _sizeObs: null,
            _type: 'bar',
            init() {
                this.renderWhenReady();
            },
            renderWhenReady(retry = 0) {
                if (window.ApexCharts) {
                    this.mountChart();
                    return;
                }

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
                clearTimeout(this._retryTimer);
                this._retryTimer = setTimeout(() => this.renderWhenReady(retry + 1), 200);
            },
            mountChart() {
                const series     = @js($chart['series'] ?? []);
                const labels     = @js($chart['labels'] ?? []);
                const type       = @js($chart['type'] ?? 'bar');
                const horizontal = @js($isHorizontal);
                const isPercent  = @js($isPercent);
                const height     = @js($chartHeight);

                const hasData = series.length && series.some(function(s){
                    return Array.isArray(s['data']) ? s['data'].length > 0 : s > 0;
                });
                if(!hasData) return;
                this._type = type;

                try { if(this.chart) this.chart.destroy(); } catch(e){}

                const isDark   = document.documentElement.classList.contains('dark');
                const txtColor = isDark ? '#D1D5DB' : '#374151';
                const gridColor= isDark ? '#374151' : '#F3F4F6';

                const numFmt = (val) => {
                    if(val === null || val === undefined || val === 0) return '';
                    return isPercent ? val + '%' : val;
                };

                let options = {
                    chart: {
                        type: type,
                        height: height,
                        background: 'transparent',
                        toolbar: { show: false },
                        animations: { enabled: true, speed: 300 },
                        fontFamily: 'inherit',
                    },
                    series: series,
                    colors: ['#22C55E','#6366F1','#F59E0B','#EF4444','#8B5CF6','#14B8A6','#EC4899','#6B7280'],
                    tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: (v) => isPercent ? v+'%' : v } },
                    legend: { position: 'bottom', labels: { colors: txtColor }, fontSize: '12px' },
                    grid: { borderColor: gridColor, strokeDashArray: 4 },
                    dataLabels: { enabled: false },
                    plotOptions: {},
                    stroke: { width: 2 },
                    responsive: [{ breakpoint: 768, options: { chart: { height: Math.round(height*.8) } } }]
                };

                if(type === 'donut' || type === 'pie') {
                    options.labels = labels;
                    options.colors = ['#6B7280','#3B82F6','#22C55E','#EF4444','#F59E0B','#8B5CF6','#EC4899','#14B8A6'];
                    options.plotOptions = { pie: { donut: { size: '55%' } } };
                    options.dataLabels = { enabled: false };
                    options.legend.formatter = (name, opts) => name + ' (' + opts.w.globals.series[opts.seriesIndex] + ')';
                } else if(type === 'bar') {
                    options.xaxis = {
                        categories: labels,
                        labels: { style: { colors: txtColor, fontSize: '11px' }, rotate: horizontal ? 0 : -30 },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    };
                    options.yaxis = { labels: { style: { colors: txtColor, fontSize: '11px' }, formatter: numFmt } };
                    options.plotOptions = {
                        bar: { horizontal: horizontal, borderRadius: 4, columnWidth: '55%', barHeight: '65%' }
                    };
                } else if(type === 'line') {
                    options.xaxis = { categories: labels, labels: { style: { colors: txtColor, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } };
                    options.yaxis = { labels: { style: { colors: txtColor, fontSize: '11px' } } };
                    options.stroke = { curve: 'smooth', width: 2 };
                    options.markers = { size: 4 };
                    options.fill = { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .3, opacityTo: 0, stops: [0,90,100] } };
                }

                // If the chart container still has no width (Filament SPA/nav transition),
                // postpone render a bit to avoid creating a blank SVG.
                if ((this.$refs.chart?.offsetWidth ?? 0) < 16) {
                    clearTimeout(this._retryTimer);
                    this._retryTimer = setTimeout(() => this.mountChart(), 180);
                    return;
                }

                this.chart = new ApexCharts(this.$refs.chart, options);
                this.$nextTick(() => requestAnimationFrame(() => {
                    try {
                        this.chart.render().then(() => {
                            // Force one resize tick so Apex recalculates layout consistently.
                            window.dispatchEvent(new Event('resize'));
                        }).catch(() => {});
                    } catch(e){}
                }));

                // Safety net: if rendered empty, retry once automatically.
                clearTimeout(this._retryTimer);
                this._retryTimer = setTimeout(() => {
                    const hasSvg = !!this.$refs.chart?.querySelector('svg');
                    if (!hasSvg) {
                        this.mountChart();
                    }
                }, 900);

                if (!this._obs) {
                    const obs = new MutationObserver(() => this.syncTheme());
                    obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                    this._obs = obs;
                }

                if (!this._sizeObs && this.$refs.chart) {
                    this._sizeObs = new ResizeObserver(() => {
                        if (!this.chart) return;
                        const width = this.$refs.chart?.offsetWidth ?? 0;
                        if (width > 16) {
                            try { this.chart.updateOptions({}, false, true); } catch(e){}
                        }
                    });
                    this._sizeObs.observe(this.$refs.chart);
                }
            },
            syncTheme() {
                const dark = document.documentElement.classList.contains('dark');
                const txt  = dark ? '#D1D5DB' : '#374151';
                const grid = dark ? '#374151' : '#F3F4F6';
                if(!this.chart) return;
                const baseOptions = {
                    grid: { borderColor: grid },
                    tooltip: { theme: dark ? 'dark' : 'light' },
                    legend: { labels: { colors: txt } },
                };

                // Donut/pie does not use xaxis/yaxis and Apex may throw internally
                // if we send cartesian options to non-cartesian charts.
                if (this._type === 'bar' || this._type === 'line') {
                    baseOptions.xaxis = { labels: { style: { colors: txt } } };
                    baseOptions.yaxis = { labels: { style: { colors: txt } } };
                }

                try {
                    this.chart.updateOptions(baseOptions, false, false);
                } catch(e){}
            },
            destroy() {
                try { if(this.chart) this.chart.destroy(); } catch(e){}
                try { if(this._obs) this._obs.disconnect(); } catch(e){}
                try { if(this._sizeObs) this._sizeObs.disconnect(); } catch(e){}
                try { if(this._retryTimer) clearTimeout(this._retryTimer); } catch(e){}
            }
        }"
        wire:ignore
        x-ref="chart"
        style="min-height:{{ $chartHeight }}px; width:100%;"
    ></div>
</div>
