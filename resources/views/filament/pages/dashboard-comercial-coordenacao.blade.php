<x-filament::page>
    @php
        $charts = collect($this->charts ?? [])->filter(function ($chart) {
            $series = $chart['series'] ?? [];
            return count($series) && collect($series)->some(fn ($s) =>
                (is_array($s['data'] ?? null) && count($s['data'])) || (!is_array($s) && $s > 0)
            );
        })->values();

        $overdues = collect($this->shellAtrasadoDetalhes ?? []);
        $recentes = collect($this->entradasRecentes ?? []);
        $vtSemAgendamento = collect($this->pontosSolicitadosSemAgendamento ?? []);
    @endphp

    <div>
        <div class="po-header">
            <p class="po-header-sub">Visão consolidada da operação comercial para coordenação.</p>
        </div>

        <div class="po-kpi-grid po-kpi-grid-4">
            <div class="po-card po-kpi-card">
                <div class="po-kpi-icon po-icon-indigo">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                </div>
                <div>
                    <p class="po-kpi-value">{{ $kpis['total'] ?? 0 }}</p>
                    <p class="po-kpi-label">Total</p>
                </div>
            </div>

            <div class="po-card po-kpi-card">
                <div class="po-kpi-icon po-icon-green">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                </div>
                <div>
                    <p class="po-kpi-value po-text-green">{{ $kpis['aprovados'] ?? 0 }}</p>
                    <p class="po-kpi-label">Aprovados</p>
                </div>
            </div>

            <div class="po-card po-kpi-card">
                <div class="po-kpi-icon po-icon-blue">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg>
                </div>
                <div>
                    <p class="po-kpi-value po-text-blue">{{ $kpis['em_validacao'] ?? 0 }}</p>
                    <p class="po-kpi-label">Em validacao</p>
                </div>
            </div>

            <div class="po-card po-kpi-card">
                <div class="po-kpi-icon po-icon-red">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                </div>
                <div>
                    <p class="po-kpi-value po-text-red">{{ $kpis['reprovados'] ?? 0 }}</p>
                    <p class="po-kpi-label">Reprovados</p>
                </div>
            </div>

            <div class="po-card po-kpi-card {{ ($kpis['sem_responsavel'] ?? 0) > 0 ? 'po-card-alert-orange' : '' }}">
                <div class="po-kpi-icon po-icon-orange">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V4H2v16h5"/></svg>
                </div>
                <div>
                    <p class="po-kpi-value po-text-orange">{{ $kpis['sem_responsavel'] ?? 0 }}</p>
                    <p class="po-kpi-label">Sem responsavel</p>
                </div>
            </div>

            <div class="po-card po-kpi-card">
                <div class="po-kpi-icon po-icon-purple">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20h16M6 16l4-4 3 3 5-6"/></svg>
                </div>
                <div>
                    <p class="po-kpi-value po-text-purple">{{ $kpis['imovel_pronto'] ?? 0 }}</p>
                    <p class="po-kpi-label">Imovel pronto</p>
                </div>
            </div>

            <div class="po-card po-kpi-card">
                <div class="po-kpi-icon po-icon-green">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14"/></svg>
                </div>
                <div>
                    <p class="po-kpi-value">{{ $kpis['shell_30'] ?? 0 }}</p>
                    <p class="po-kpi-label">Shell em 30 dias</p>
                </div>
            </div>
        </div>

        <div class="po-card po-overdue-wrap {{ $overdues->isNotEmpty() ? 'po-overdue-alert' : '' }}">
            <div class="po-overdue-head">
                <h3 class="po-overdue-title">Data de shell vencida ({{ $overdues->count() }})</h3>
            </div>

            @if($overdues->isEmpty())
                <p class="po-empty-state">Sem pendencias de shell atrasadas.</p>
            @else
                <div class="po-overdue-list">
                    @foreach($overdues as $item)
                        <a href="{{ $item['url'] }}" class="po-overdue-item">
                            <div class="po-overdue-top">
                                <span class="po-overdue-code">{{ $item['codigo'] }}</span>
                                <span class="po-overdue-date">Venceu {{ $item['vencimento'] }}</span>
                            </div>
                            <p class="po-overdue-name">{{ $item['nome'] }}</p>
                            <p class="po-overdue-meta">{{ $item['responsavel'] }} - {{ $item['dias_atraso'] }} dia(s) em atraso</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if($charts->isNotEmpty())
            <div class="po-chart-grid">
                @foreach($charts as $chart)
                    @include('filament.pages.pos-obra._chart-card', ['chart' => $chart])
                @endforeach
            </div>
        @endif

        <div class="po-stream-grid">
            <div class="po-card po-stream-card">
                <div class="po-stream-head">
                    <h3 class="po-stream-title">Entradas de pontos recentes</h3>
                    <span class="po-stream-badge">{{ $recentes->count() }}</span>
                </div>

                @if($recentes->isEmpty())
                    <p class="po-empty-state">Sem entradas recentes.</p>
                @else
                    <div class="po-stream-list">
                        @foreach($recentes as $item)
                            <a href="{{ $item['url'] }}" class="po-stream-row">
                                <div class="po-stream-top">
                                    <span class="po-stream-code">{{ $item['codigo'] }}</span>
                                    <div class="po-stream-right">
                                        <span class="po-pill po-pill-{{ $item['status_tone'] }}">{{ $item['status_label'] }}</span>
                                        <span class="po-stream-date">{{ $item['criado_em'] }}</span>
                                    </div>
                                </div>
                                <p class="po-stream-name">{{ $item['nome'] }}</p>
                                <p class="po-stream-meta">{{ $item['responsavel'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="po-card po-stream-card">
                <div class="po-stream-head">
                    <h3 class="po-stream-title">Pontos solicitados de VT sem agendamento</h3>
                    <span class="po-stream-badge">{{ $vtSemAgendamento->count() }}</span>
                </div>

                @if($vtSemAgendamento->isEmpty())
                    <p class="po-empty-state">Sem pontos solicitados de VT pendentes de agendamento.</p>
                @else
                    <div class="po-stream-list">
                        @foreach($vtSemAgendamento as $item)
                            <a href="{{ $item['url'] }}" class="po-stream-row">
                                <div class="po-stream-top">
                                    <span class="po-stream-code">{{ $item['codigo'] }}</span>
                                    <div class="po-stream-right">
                                        <span class="po-pill po-pill-{{ $item['status_tone'] }}">{{ $item['status_label'] }}</span>
                                        <span class="po-stream-date">{{ $item['criado_em'] }}</span>
                                    </div>
                                </div>
                                <p class="po-stream-name">{{ $item['nome'] }}</p>
                                <p class="po-stream-meta">{{ $item['responsavel'] }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .po-header { margin-bottom: 1rem; }
        .po-header-sub { font-size: .875rem; margin: 0; color: #6B7280; }

        .po-kpi-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem; margin-bottom: 1rem; }
        .po-kpi-grid-4 { grid-template-columns: repeat(4, 1fr); }

        .po-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            transition: box-shadow .15s;
            margin-bottom: 1rem;
        }
        .dark .po-card { background: #1f2937; border-color: #374151; }

        .po-kpi-card { padding: 1rem; display: flex; align-items: center; gap: 12px; margin-bottom: 0; }
        .po-kpi-card:hover { box-shadow: 0 4px 14px rgba(0, 0, 0, .08); }

        .po-kpi-icon { padding: 8px; border-radius: 8px; flex-shrink: 0; }
        .po-kpi-icon svg { width: 20px; height: 20px; }
        .po-kpi-value { font-size: 1.5rem; font-weight: 700; margin: 0; line-height: 1; }
        .po-kpi-label { font-size: .65rem; color: #9CA3AF; margin: 3px 0 0; text-transform: uppercase; letter-spacing: .04em; }

        .po-icon-indigo  { background: #EEF2FF; color: #6366F1; }
        .po-icon-green   { background: #DCFCE7; color: #16A34A; }
        .po-icon-blue    { background: #DBEAFE; color: #2563EB; }
        .po-icon-red     { background: #FEE2E2; color: #DC2626; }
        .po-icon-orange  { background: #FFEDD5; color: #EA580C; }
        .po-icon-purple  { background: #EDE9FE; color: #7C3AED; }

        .dark .po-icon-indigo  { background: rgba(75,85,99,.35); color: #D1D5DB; }
        .dark .po-icon-green   { background: rgba(22,163,74,.2); color: #86EFAC; }
        .dark .po-icon-blue    { background: rgba(75,85,99,.35); color: #D1D5DB; }
        .dark .po-icon-red     { background: rgba(220,38,38,.2); color: #FCA5A5; }
        .dark .po-icon-orange  { background: rgba(234,88,12,.2); color: #FDBA74; }
        .dark .po-icon-purple  { background: rgba(124,58,237,.2); color: #C4B5FD; }

        .po-text-green  { color: #16A34A; }
        .po-text-blue   { color: #2563EB; }
        .po-text-red    { color: #DC2626; }
        .po-text-orange { color: #EA580C; }
        .po-text-purple { color: #7C3AED; }
        .dark .po-text-green  { color: #86EFAC; }
        .dark .po-text-blue   { color: #D1D5DB; }
        .dark .po-text-red    { color: #FCA5A5; }
        .dark .po-text-orange { color: #FDBA74; }
        .dark .po-text-purple { color: #C4B5FD; }

        .po-card-alert-orange { border-color: #FED7AA !important; }
        .dark .po-card-alert-orange { border-color: rgba(234,88,12,.4) !important; }

        .po-overdue-wrap {
            padding: 1rem;
            border-color: #fecaca;
            background: #fff7f7;
        }
        .dark .po-overdue-wrap {
            border-color: rgba(239, 68, 68, .35);
            background: rgba(127, 29, 29, .2);
        }
        .po-overdue-head { margin-bottom: .75rem; }
        .po-overdue-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .dark .po-overdue-title { color: #fca5a5; }

        .po-overdue-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }
        .po-overdue-item {
            display: block;
            padding: .8rem;
            border: 1px solid #fee2e2;
            border-radius: 10px;
            background: #ffffff;
            text-decoration: none;
        }
        .dark .po-overdue-item {
            border-color: #7f1d1d;
            background: rgba(0, 0, 0, .12);
        }
        .po-overdue-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: .35rem;
        }
        .po-overdue-code { font-size: .95rem; font-weight: 800; color: #dc2626; }
        .po-overdue-date { font-size: .8rem; color: #ef4444; font-weight: 600; }
        .po-overdue-name {
            margin: 0 0 .3rem;
            color: #374151;
            font-size: 1.02rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .po-overdue-name { color: #e5e7eb; }
        .po-overdue-meta { margin: 0; font-size: .85rem; color: #9ca3af; }

        .po-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .po-stream-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .po-stream-card { padding: 0; overflow: hidden; }
        .po-stream-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .95rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .po-stream-head { border-bottom-color: #374151; }
        .po-stream-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .dark .po-stream-title { color: #e5e7eb; }
        .po-stream-badge {
            border-radius: 999px;
            padding: 2px 10px;
            font-size: .78rem;
            font-weight: 700;
            background: #f3f4f6;
            color: #374151;
        }
        .dark .po-stream-badge { background: #374151; color: #f3f4f6; }

        .po-stream-list {
            display: flex;
            flex-direction: column;
            max-height: 420px;
            overflow-y: auto;
        }
        .po-stream-list::-webkit-scrollbar { width: 8px; }
        .po-stream-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 999px;
        }
        .dark .po-stream-list::-webkit-scrollbar-thumb { background: #4b5563; }
        .po-stream-row {
            display: block;
            text-decoration: none;
            padding: .9rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: transparent;
        }
        .po-stream-row:last-child { border-bottom: 0; }
        .dark .po-stream-row { border-bottom-color: #374151; }
        .po-stream-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .3rem;
        }
        .po-stream-right { display: flex; align-items: center; gap: .5rem; }
        .po-stream-code { font-size: .95rem; font-weight: 800; color: #f59e0b; }
        .po-stream-date { font-size: .82rem; color: #9ca3af; }
        .po-stream-name {
            margin: 0 0 .2rem;
            font-size: 1.03rem;
            color: #374151;
        }
        .dark .po-stream-name { color: #e5e7eb; }
        .po-stream-meta { margin: 0; font-size: .9rem; color: #9ca3af; }

        .po-pill {
            border-radius: 999px;
            padding: 3px 10px;
            font-size: .78rem;
            font-weight: 700;
        }
        .po-pill-success { background: #dcfce7; color: #166534; }
        .po-pill-warning { background: #fef3c7; color: #92400e; }
        .po-pill-danger  { background: #fee2e2; color: #991b1b; }
        .po-pill-gray    { background: #e5e7eb; color: #374151; }
        .dark .po-pill-success { background: rgba(22,163,74,.2); color: #86efac; }
        .dark .po-pill-warning { background: rgba(245,158,11,.2); color: #fcd34d; }
        .dark .po-pill-danger  { background: rgba(220,38,38,.2); color: #fca5a5; }
        .dark .po-pill-gray    { background: rgba(107,114,128,.3); color: #d1d5db; }

        .po-empty-state {
            margin: 0;
            padding: 1rem;
            color: #9ca3af;
        }

        @media (max-width: 1280px) { .po-kpi-grid-4 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 1024px) {
            .po-kpi-grid-4 { grid-template-columns: repeat(2, 1fr); }
            .po-chart-grid { grid-template-columns: 1fr; }
            .po-overdue-list { grid-template-columns: 1fr; }
            .po-stream-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .po-kpi-grid-4 { grid-template-columns: 1fr; }
            .po-stream-top {
                flex-direction: column;
                align-items: flex-start;
            }
            .po-stream-right {
                width: 100%;
                justify-content: space-between;
            }
            .po-overdue-top {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</x-filament::page>
