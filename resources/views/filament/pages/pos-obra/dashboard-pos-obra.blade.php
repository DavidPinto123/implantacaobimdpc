<x-filament::page>
@php
    $kpis      = $this->kpis ?? [];
    $sla       = $this->sla ?? [];
    $urgentes  = $this->urgentes ?? [];
    $atrasadas = $this->atrasadas ?? [];
    $atualizacoes = $this->ultimasAtualizacoes ?? [];
    $recentes     = $this->pendenciasRecentes ?? [];

    $taxaSla = $sla['percentual_cumprimento'] ?? 0;
    $slaDash = $taxaSla;
    $slaGap  = 100 - $taxaSla;
    $slaColorClass = $taxaSla >= 80 ? 'po-sla-green' : ($taxaSla >= 50 ? 'po-sla-yellow' : 'po-sla-red');

    $charts = collect($this->charts ?? [])->filter(function($chart){
        $series = $chart['series'] ?? [];
        return count($series) && collect($series)->some(fn($s) =>
            (is_array($s['data'] ?? null) && count($s['data'])) || (!is_array($s) && $s > 0)
        );
    })->values();
@endphp

<div wire:poll.30s="loadData">

{{-- Header --}}
<div class="po-header">
    <div></div>
    @php
        try { $urlNovaPendencia = route('filament.admin.resources.pos-obra.pendencias.create'); }
        catch (\Throwable $e) { $urlNovaPendencia = '#'; }

        $urlPendencia = function (int $id): string {
            try { return route('filament.admin.resources.pos-obra.pendencias.view', $id); }
            catch (\Throwable $e) { return '#'; }
        };
    @endphp
    <div style="display:flex; gap:.5rem;">
        @if($this->modoAtrasadas)
            <button wire:click="voltarDashboard" type="button" class="po-btn-secondary">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Voltar ao Dashboard
            </button>
        @else
            @if(($kpis['atrasadas'] ?? 0) > 0)
                <button wire:click="verAtrasadas" type="button" class="po-btn-danger">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $kpis['atrasadas'] }} Atrasadas
                </button>
            @endif
            <a href="{{ $urlNovaPendencia }}" class="po-btn-primary">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Nova Pendência
            </a>
        @endif
    </div>
</div>


@if($this->modoAtrasadas)
@php $stats = $this->statsAtrasadas; @endphp
{{-- ══════════ MODO ATRASADAS ══════════ --}}

{{-- KPIs --}}
<div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1rem;">
    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-red">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="po-kpi-value po-text-red">{{ $stats['total'] ?? 0 }}</p>
            <p class="po-kpi-label">Total atrasadas</p>
        </div>
    </div>
    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-orange">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            <p class="po-kpi-value po-text-orange">{{ $stats['mediaAtraso'] ?? 0 }}d</p>
            <p class="po-kpi-label">Média de atraso</p>
        </div>
    </div>
    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-red">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="po-kpi-value po-text-red">{{ $stats['maxAtraso'] ?? 0 }}d</p>
            <p class="po-kpi-label">Maior atraso</p>
        </div>
    </div>
    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-indigo">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div>
            <p class="po-kpi-value">{{ count($stats['porConstrutora'] ?? []) }}</p>
            <p class="po-kpi-label">Empresas envolvidas</p>
        </div>
    </div>
</div>

{{-- Gráficos --}}
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1rem;">
    @foreach([
        ['id' => 'atr-construtora', 'title' => 'Por Construtora', 'data' => $stats['porConstrutora'] ?? []],
        ['id' => 'atr-gestor',      'title' => 'Por Gestor',      'data' => $stats['porGestor'] ?? []],
        ['id' => 'atr-disciplina',  'title' => 'Por Disciplina',  'data' => $stats['porDisciplina'] ?? []],
    ] as $chart)
        @if(count($chart['data']) > 0)
        <div class="po-card" style="padding:1rem; position:relative;">
            <h3 class="po-section-title" style="margin:0 0 .75rem;">{{ $chart['title'] }}</h3>
            <div id="{{ $chart['id'] }}" wire:ignore
                 x-data
                 x-init="
                    $nextTick(() => {
                        new ApexCharts($el, {
                            chart: { type: 'bar', height: 220, toolbar: { show: false } },
                            series: [{ name: 'Atrasadas', data: {{ json_encode(array_values($chart['data'])) }} }],
                            xaxis: { categories: {{ json_encode(array_keys($chart['data'])) }} },
                            colors: ['#DC2626'],
                            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                            dataLabels: { enabled: true, style: { fontSize: '11px' } },
                            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                        }).render();
                    })
                 "></div>
        </div>
        @endif
    @endforeach
</div>

{{-- Urgência (donut) --}}
@if(count($stats['porUrgencia'] ?? []) > 0)
<div style="display:grid; grid-template-columns:1fr 2fr; gap:1rem; margin-bottom:1rem;">
    <div class="po-card" style="padding:1rem; position:relative;">
        <h3 class="po-section-title" style="margin:0 0 .75rem;">Por Urgência</h3>
        <div id="atr-urgencia" wire:ignore
             x-data
             x-init="
                $nextTick(() => {
                    new ApexCharts($el, {
                        chart: { type: 'donut', height: 200 },
                        series: {{ json_encode(array_values($stats['porUrgencia'])) }},
                        labels: {{ json_encode(array_keys($stats['porUrgencia'])) }},
                        colors: ['#22C55E', '#F59E0B', '#DC2626'],
                        legend: { position: 'bottom', fontSize: '12px' },
                        tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
                    }).render();
                })
             "></div>
    </div>

    {{-- Tabela --}}
    <div class="po-card">
        <div style="padding:1rem 1.25rem; border-bottom:1px solid #E5E7EB; display:flex; align-items:center; gap:.75rem;">
            <span style="font-size:1rem; font-weight:700;">Listagem</span>
            <span class="po-badge po-badge-red">{{ count($this->todasAtrasadas) }}</span>
        </div>
        <div style="overflow-x:auto; max-height:400px; overflow-y:auto;">
            <table class="po-table">
                <thead><tr>
                    <th>Código</th><th>Obra</th><th>Descrição</th><th>Urgência</th><th>Gestor</th><th>Construtora</th><th>Prazo</th><th>Atraso</th>
                </tr></thead>
                <tbody>
                    @forelse($this->todasAtrasadas as $p)
                    <tr class="po-table-clickable" onclick="window.location='{{ $p['url'] }}'">
                        <td><span class="po-code po-code-red">{{ $p['codigo'] }}</span></td>
                        <td>{{ $p['obra'] }}</td>
                        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $p['descricao'] }}</td>
                        <td><span class="po-badge {{ $p['urgencia_val'] === 'P3' ? 'po-badge-red' : ($p['urgencia_val'] === 'P2' ? 'po-badge-yellow' : 'po-badge-green') }}">{{ $p['urgencia'] }}</span></td>
                        <td>{{ $p['gestor'] }}</td>
                        <td>{{ $p['construtora'] }}</td>
                        <td>{{ $p['data_termino'] }}</td>
                        <td><span class="po-badge po-badge-red">{{ $p['dias_atraso'] }}d</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="po-empty">Nenhuma pendência atrasada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@else
{{-- ══════════ DASHBOARD NORMAL ══════════ --}}

{{-- KPI Cards --}}
<div class="po-kpi-grid">

    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-indigo">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
            <p class="po-kpi-value">{{ $kpis['total'] ?? 0 }}</p>
            <p class="po-kpi-label">Total</p>
        </div>
    </div>

    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-green">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="po-kpi-value po-text-green">{{ $kpis['terminal'] ?? 0 }}</p>
            <p class="po-kpi-label">Resolvidas</p>
        </div>
    </div>

    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-blue">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="po-kpi-value po-text-blue">{{ $kpis['ativas'] ?? 0 }}</p>
            <p class="po-kpi-label">Ativas</p>
        </div>
    </div>

    <div class="po-card po-kpi-card {{ ($kpis['atrasadas'] ?? 0) > 0 ? 'po-card-alert-red' : '' }}">
        <div class="po-kpi-icon po-icon-red">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="po-kpi-value po-text-red">{{ $kpis['atrasadas'] ?? 0 }}</p>
            <p class="po-kpi-label">Atrasadas</p>
        </div>
    </div>

    <div class="po-card po-kpi-card {{ ($kpis['urgentes'] ?? 0) > 0 ? 'po-card-alert-orange' : '' }}">
        <div class="po-kpi-icon po-icon-orange">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            <p class="po-kpi-value po-text-orange">{{ $kpis['urgentes'] ?? 0 }}</p>
            <p class="po-kpi-label">Urgentes</p>
        </div>
    </div>

    <div class="po-card po-kpi-card">
        <div class="po-kpi-icon po-icon-purple">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div style="flex:1; min-width:0;">
            <p class="po-kpi-value po-text-purple">{{ $kpis['taxa'] ?? 0 }}%</p>
            <p class="po-kpi-label">% Resolvido</p>
            <div class="po-progress-track">
                <div class="po-progress-fill" style="width:{{ min(100, $kpis['taxa'] ?? 0) }}%;"></div>
            </div>
        </div>
    </div>

</div>

{{-- SLA + Urgentes --}}
<div class="po-two-col" style="margin-bottom:1rem;">

    <div class="po-card" style="padding:1.25rem;">
        <h3 class="po-section-title" style="margin:0 0 1rem;">
            <svg style="width:14px;height:14px;color:#EAB308;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Cumprimento de SLA
        </h3>
        <div style="display:flex; align-items:center; gap:1.5rem;">
            <div style="position:relative; width:88px; height:88px; flex-shrink:0;">
                <svg style="width:88px;height:88px;transform:rotate(-90deg);" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="2.5" class="po-sla-track"/>
                    <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="2.5"
                        stroke-dasharray="{{ $slaDash }} {{ $slaGap }}"
                        stroke-dashoffset="0" stroke-linecap="round"
                        class="{{ $slaColorClass }}"/>
                </svg>
                <span class="po-sla-pct">{{ $taxaSla }}%</span>
            </div>
            <div style="flex:1; display:grid; grid-template-columns:repeat(3,1fr); gap:8px; text-align:center;">
                <div>
                    <p class="po-stat-value">{{ $sla['total_ativos'] ?? 0 }}</p>
                    <p class="po-stat-label">Ativos</p>
                </div>
                <div>
                    <p class="po-stat-value po-text-green">{{ $sla['dentro_prazo'] ?? 0 }}</p>
                    <p class="po-stat-label">No prazo</p>
                </div>
                <div>
                    <p class="po-stat-value po-text-red">{{ $sla['fora_prazo'] ?? 0 }}</p>
                    <p class="po-stat-label">Vencido</p>
                </div>
            </div>
        </div>
    </div>

    <div class="po-alert-orange" style="padding:1.25rem;">
        <h3 class="po-alert-title-orange" style="margin:0 0 0.75rem;">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Pendências Urgentes ({{ count($urgentes) }})
        </h3>
        @forelse($urgentes as $p)
        <a href="{{ $urlPendencia($p['id']) }}" class="po-alert-item po-list-link" style="margin-bottom:6px;text-decoration:none;">
            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                <span class="po-code po-code-orange">{{ $p['codigo'] }}</span>
                <span class="po-text-muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.8rem;">{{ $p['descricao'] }}</span>
            </div>
            <span class="po-text-xs-muted" style="flex-shrink:0;">{{ $p['obra'] }}</span>
        </a>
        @empty
        <p class="po-empty">Nenhuma pendência urgente</p>
        @endforelse
    </div>

</div>

{{-- Atrasadas --}}
@if(count($atrasadas) > 0)
<div class="po-alert-red" style="padding:1.25rem; margin-bottom:1rem;">
    <h3 class="po-alert-title-red" style="margin:0 0 0.75rem;">
        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        Pendências Atrasadas ({{ count($atrasadas) }})
    </h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:8px;">
        @foreach($atrasadas as $p)
        <a href="{{ $urlPendencia($p['id']) }}" class="po-alert-item po-alert-item-col po-list-link" style="text-decoration:none;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span class="po-code po-code-red">{{ $p['codigo'] }}</span>
                <span class="po-text-red" style="font-size:0.7rem;font-weight:500;">Venceu {{ $p['data_termino'] }}</span>
            </div>
            <span class="po-text-muted" style="font-size:0.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p['descricao'] }}</span>
            <span class="po-text-xs-muted">{{ $p['obra'] }} — {{ $p['gestor'] }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Gráficos --}}
@if($charts->isNotEmpty())
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem;">
    @foreach($charts->take(3) as $chart)
        @include('filament.pages.pos-obra._chart-card', ['chart' => $chart])
    @endforeach
</div>
@if($charts->count() > 3)
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
    @foreach($charts->slice(3) as $chart)
        @include('filament.pages.pos-obra._chart-card', ['chart' => $chart])
    @endforeach
</div>
@endif
@endif

{{-- Listas --}}
<div class="po-two-col">

    <div class="po-card" style="overflow:hidden;">
        <div class="po-list-header">
            <h3 class="po-section-title" style="margin:0;">
                <svg style="width:14px;height:14px;color:#FBBA00;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Últimas Atualizações
            </h3>
        </div>
        @forelse($atualizacoes as $a)
        <a href="{{ $urlPendencia($a['pendencia_id']) }}" class="po-list-row po-list-link" style="display:block;text-decoration:none;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <span class="po-code" style="color:#FBBA00;">{{ $a['codigo'] }}</span>
                <span class="po-text-xs-muted">{{ $a['data'] }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                <span class="po-badge po-badge-gray">{{ $a['status_anterior'] }}</span>
                <span class="po-text-xs-muted">→</span>
                <span class="po-badge po-badge-green">{{ $a['status_novo'] }}</span>
                <span class="po-text-xs-muted">por {{ $a['usuario'] }}</span>
            </div>
            @if($a['comentario'])
            <p class="po-text-xs-muted" style="margin:3px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $a['comentario'] }}</p>
            @endif
        </a>
        @empty
        <p class="po-empty">Nenhuma atualização registrada.</p>
        @endforelse
    </div>

    <div class="po-card" style="overflow:hidden;">
        <div class="po-list-header">
            <h3 class="po-section-title" style="margin:0;">
                <svg style="width:14px;height:14px;color:#FBBA00;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Pendências Recentes
            </h3>
        </div>
        @forelse($recentes as $p)
        <a href="{{ $urlPendencia($p['id']) }}" class="po-list-row po-list-link" style="display:block;text-decoration:none;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <span class="po-code" style="color:#FBBA00;">{{ $p['codigo'] }}</span>
                <div style="display:flex;gap:4px;">
                    @php
                        $urgClass = match($p['urgencia_val'] ?? '') {
                            'P3' => 'po-badge-red', 'P2' => 'po-badge-orange', default => 'po-badge-green',
                        };
                    @endphp
                    <span class="po-badge {{ $urgClass }}">{{ $p['urgencia'] }}</span>
                    <span class="po-badge po-badge-gray">{{ $p['status'] }}</span>
                </div>
            </div>
            <p class="po-text-muted" style="margin:0;font-size:0.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p['descricao'] }}</p>
            <p class="po-text-xs-muted" style="margin:3px 0 0;">{{ $p['obra'] }} — {{ $p['data'] }}</p>
        </a>
        @empty
        <p class="po-empty">Nenhuma pendência registrada.</p>
        @endforelse
    </div>

</div>

@endif
</div>{{-- /wire:poll --}}

<style>
/* ── Layout ──────────────────────────────────────────── */
.po-header           { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; }
.po-header-title     { font-size:1.4rem; font-weight:700; margin:0; }
.po-header-sub       { font-size:0.875rem; margin:.25rem 0 0; color:#6B7280; }
.po-two-col          { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
.po-kpi-grid         { display:grid; grid-template-columns:repeat(6,1fr); gap:1rem; margin-bottom:1rem; }

/* ── Base card ───────────────────────────────────────── */
.po-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: box-shadow .15s;
}
.dark .po-card {
    background: #1f2937;
    border-color: #374151;
}
.po-kpi-card { padding:1rem; display:flex; align-items:center; gap:12px; }
.po-kpi-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.08); }

/* ── KPI ─────────────────────────────────────────────── */
.po-kpi-icon     { padding:8px; border-radius:8px; flex-shrink:0; }
.po-kpi-icon svg { width:20px; height:20px; }
.po-kpi-value    { font-size:1.5rem; font-weight:700; margin:0; line-height:1; }
.po-kpi-label    { font-size:.65rem; color:#9CA3AF; margin:3px 0 0; text-transform:uppercase; letter-spacing:.04em; }

/* icon backgrounds - light */
.po-icon-indigo  { background:#EEF2FF; color:#6366F1; }
.po-icon-green   { background:#DCFCE7; color:#16A34A; }
.po-icon-blue    { background:#DBEAFE; color:#2563EB; }
.po-icon-red     { background:#FEE2E2; color:#DC2626; }
.po-icon-orange  { background:#FFEDD5; color:#EA580C; }
.po-icon-purple  { background:#EDE9FE; color:#7C3AED; }

/* icon backgrounds - dark */
.dark .po-icon-indigo  { background:rgba(99,102,241,.2);  color:#A5B4FC; }
.dark .po-icon-green   { background:rgba(22,163,74,.2);   color:#86EFAC; }
.dark .po-icon-blue    { background:rgba(37,99,235,.2);   color:#93C5FD; }
.dark .po-icon-red     { background:rgba(220,38,38,.2);   color:#FCA5A5; }
.dark .po-icon-orange  { background:rgba(234,88,12,.2);   color:#FDBA74; }
.dark .po-icon-purple  { background:rgba(124,58,237,.2);  color:#C4B5FD; }

/* text colors */
.po-text-green  { color:#16A34A; }
.po-text-blue   { color:#2563EB; }
.po-text-red    { color:#DC2626; }
.po-text-orange { color:#EA580C; }
.po-text-purple { color:#7C3AED; }
.dark .po-text-green  { color:#86EFAC; }
.dark .po-text-blue   { color:#93C5FD; }
.dark .po-text-red    { color:#FCA5A5; }
.dark .po-text-orange { color:#FDBA74; }
.dark .po-text-purple { color:#C4B5FD; }

/* alert card variants */
.po-card-alert-red    { border-color:#FECACA !important; }
.po-card-alert-orange { border-color:#FED7AA !important; }
.dark .po-card-alert-red    { border-color:rgba(220,38,38,.4) !important; }
.dark .po-card-alert-orange { border-color:rgba(234,88,12,.4) !important; }

/* ── Progress bar ────────────────────────────────────── */
.po-progress-track { width:100%; background:#E5E7EB; border-radius:99px; height:4px; margin-top:6px; }
.po-progress-fill  { background:#7C3AED; height:4px; border-radius:99px; }
.dark .po-progress-track { background:#374151; }

/* ── SLA ─────────────────────────────────────────────── */
.po-sla-track   { stroke:#E5E7EB; }
.dark .po-sla-track { stroke:#374151; }
.po-sla-green   { stroke:#22C55E; }
.po-sla-yellow  { stroke:#EAB308; }
.po-sla-red     { stroke:#EF4444; }
.po-sla-pct     { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:1rem; font-weight:700; }
.po-stat-value  { font-size:1.5rem; font-weight:700; margin:0; }
.po-stat-label  { font-size:.7rem; color:#9CA3AF; margin:2px 0 0; }

/* ── Alert sections ──────────────────────────────────── */
.po-alert-orange {
    background: #FFF7ED;
    border: 1px solid #FED7AA;
    border-radius: 12px;
}
.po-alert-red {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    border-radius: 12px;
}
.dark .po-alert-orange { background:rgba(234,88,12,.1);  border-color:rgba(234,88,12,.3); }
.dark .po-alert-red    { background:rgba(220,38,38,.1);  border-color:rgba(220,38,38,.3); }

.po-alert-title-orange { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#C2410C; display:flex; align-items:center; gap:6px; }
.po-alert-title-red    { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#B91C1C; display:flex; align-items:center; gap:6px; }
.dark .po-alert-title-orange { color:#FDBA74; }
.dark .po-alert-title-red    { color:#FCA5A5; }

.po-alert-item {
    background: rgba(255,255,255,.8);
    border-radius: 8px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.po-alert-item-col { flex-direction:column; align-items:stretch; }
.dark .po-alert-item { background:rgba(255,255,255,.05); }

/* ── Lists ───────────────────────────────────────────── */
.po-list-header { padding:.875rem 1.25rem; border-bottom:1px solid rgba(107,114,128,.15); }
.po-list-row    { padding:10px 1.25rem; border-bottom:1px solid rgba(107,114,128,.1); }
.po-list-row:last-child { border-bottom:none; }
.po-list-link   { cursor:pointer; transition:background .12s; }
.po-list-link:hover { background:rgba(107,114,128,.07) !important; }
.dark .po-list-link:hover { background:rgba(255,255,255,.05) !important; }

/* ── Badges ──────────────────────────────────────────── */
.po-badge       { padding:2px 7px; border-radius:4px; font-size:.7rem; font-weight:500; }
.po-badge-gray  { background:#F3F4F6; color:#374151; }
.po-badge-green { background:#DCFCE7; color:#166534; }
.po-badge-red   { background:#FEE2E2; color:#991B1B; }
.po-badge-orange{ background:#FFEDD5; color:#9A3412; }
.dark .po-badge-gray   { background:#374151; color:#D1D5DB; }
.dark .po-badge-green  { background:rgba(22,163,74,.25);  color:#86EFAC; }
.dark .po-badge-red    { background:rgba(220,38,38,.25);  color:#FCA5A5; }
.dark .po-badge-orange { background:rgba(234,88,12,.25);  color:#FDBA74; }

/* ── Typography ──────────────────────────────────────── */
.po-section-title   { font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; display:flex; align-items:center; gap:6px; color:#374151; }
.dark .po-section-title { color:#E5E7EB; }
.po-text-muted      { color:#6B7280; }
.dark .po-text-muted { color:#9CA3AF; }
.po-text-xs-muted   { font-size:.7rem; color:#9CA3AF; }
.dark .po-text-xs-muted { color:#6B7280; }
.po-code            { font-family:monospace; font-size:.75rem; font-weight:600; }
.po-code-orange     { color:#EA580C; }
.po-code-red        { color:#DC2626; }
.dark .po-code-orange { color:#FDBA74; }
.dark .po-code-red    { color:#FCA5A5; }
.po-empty           { font-size:.875rem; color:#9CA3AF; text-align:center; padding:1.5rem; }

/* ── Button ──────────────────────────────────────────── */
.po-btn-primary {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; background:#FBBA00; color:#000;
    border-radius:8px; font-size:.875rem; font-weight:600;
    text-decoration:none; transition:background .15s;
}
.po-btn-primary:hover { background:#E0A800; }
.po-btn-danger {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; background:#DC2626; color:#fff;
    border-radius:8px; font-size:.875rem; font-weight:600;
    text-decoration:none; transition:background .15s;
}
.po-btn-danger:hover { background:#B91C1C; }
.po-btn-secondary {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; background:#ffffff; color:#374151;
    border:1px solid #D1D5DB; border-radius:8px; font-size:.875rem; font-weight:600;
    text-decoration:none; transition:all .15s; cursor:pointer;
}
.po-btn-secondary:hover { background:#F9FAFB; border-color:#9CA3AF; }
.dark .po-btn-secondary { background:#374151; border-color:#4B5563; color:#D1D5DB; }
.dark .po-btn-secondary:hover { background:#4B5563; }

/* ── Table (atrasadas) ──────────────────────────────── */
.po-table { width:100%; border-collapse:collapse; font-size:.8125rem; }
.po-table th {
    text-align:left; padding:.6rem 1rem; font-weight:600; color:#6B7280;
    border-bottom:2px solid #E5E7EB; white-space:nowrap; font-size:.75rem; text-transform:uppercase; letter-spacing:.03em;
}
.dark .po-table th { color:#9CA3AF; border-bottom-color:#374151; }
.po-table td { padding:.6rem 1rem; border-bottom:1px solid #F3F4F6; color:#111827; }
.dark .po-table td { border-bottom-color:#374151; color:#F3F4F6; }
.po-table tr:hover td { background:#F9FAFB; }
.dark .po-table tr:hover td { background:rgba(255,255,255,.03); }
.po-table-clickable { cursor:pointer; }
.po-table-clickable:hover td { background:#FEF2F2 !important; }
.dark .po-table-clickable:hover td { background:rgba(220,38,38,.08) !important; }
.po-badge { display:inline-block; padding:.1rem .5rem; border-radius:1rem; font-size:.7rem; font-weight:600; white-space:nowrap; }
.po-badge-red { background:#FEE2E2; color:#991B1B; }
.po-badge-yellow { background:#FEF3C7; color:#92400E; }
.po-badge-green { background:#DCFCE7; color:#166534; }
.dark .po-badge-red { background:rgba(220,38,38,.15); color:#FCA5A5; }
.dark .po-badge-yellow { background:rgba(245,158,11,.15); color:#FCD34D; }
.dark .po-badge-green { background:rgba(34,197,94,.15); color:#86EFAC; }

/* ── Responsive ──────────────────────────────────────── */
@media (max-width:1280px) { .po-kpi-grid { grid-template-columns:repeat(3,1fr); } }
@media (max-width:768px)  { .po-kpi-grid,.po-two-col { grid-template-columns:repeat(2,1fr); } }
@media (max-width:480px)  { .po-kpi-grid { grid-template-columns:repeat(2,1fr); } }

/* ── ApexCharts ──────────────────────────────────────── */
.apexcharts-toolbar { position:absolute!important; top:-28px!important; right:0!important; z-index:2!important; }
.apexcharts-menu    { z-index:8!important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</x-filament::page>
