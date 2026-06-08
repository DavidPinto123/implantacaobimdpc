<x-filament::page>
    <div wire:poll.60s="loadAgenda">
        <div class="agv-header">
            <p class="agv-subtitle">Agenda de visitas técnicas com visão mensal e detalhamento por dia.</p>
        </div>

        <div class="agv-grid">
            <div class="agv-card">
                <div class="agv-card-head">
                    <h3 class="agv-title">Calendario VT ({{ ucfirst($mesLabel) }})</h3>
                    <span class="agv-muted">Agendado em</span>
                </div>

                <div class="agv-calendar-shell">
                    <div class="agv-weekdays">
                        <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sab</span><span>Dom</span>
                    </div>

                    <div class="agv-calendar-grid">
                        @foreach($calendarWeeks as $week)
                            @foreach($week as $cell)
                                @if(!$cell)
                                    <div class="agv-day agv-day-empty"></div>
                                @else
                                    <button
                                        type="button"
                                        wire:click="selecionarData('{{ $cell['date'] }}')"
                                        class="agv-day {{ $cell['is_today'] ? 'agv-day-today' : '' }} {{ $cell['is_selected'] ? 'agv-day-selected' : '' }}"
                                    >
                                        <div class="agv-day-head">
                                            <span class="agv-day-number">{{ $cell['day'] }}</span>
                                            @if(($cell['total'] ?? 0) > 0)
                                                <span class="agv-day-count">{{ $cell['total'] }}</span>
                                            @endif
                                        </div>
                                        @if(($cell['total'] ?? 0) > 0)
                                            <div class="agv-day-items">
                                                @foreach($cell['items'] as $item)
                                                    <div class="agv-item">
                                                        <span class="agv-item-code">{{ $item['codigo'] }}</span>
                                                        <span class="agv-item-name">{{ \Illuminate\Support\Str::limit($item['ponto'], 10, '...') }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </button>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="agv-side">
                <div class="agv-card agv-side-card">
                    <div class="agv-card-head">
                        <h3 class="agv-title">Agenda do dia</h3>
                        <span class="agv-badge">{{ count($agendaDoDia) }}</span>
                    </div>
                    @if($dataSelecionada)
                        <div class="agv-selected-date">
                            Dia selecionado: {{ \Carbon\Carbon::parse($dataSelecionada)->format('d/m/Y') }}
                        </div>
                    @endif
                    <div class="agv-list">
                        @forelse($agendaDoDia as $item)
                            <button
                                type="button"
                                class="agv-row"
                                @if(!empty($item['projeto_id'])) wire:click="abrirSidebarPonto({{ $item['projeto_id'] }})" @endif
                            >
                                <div class="agv-row-top">
                                    <span class="agv-code">{{ $item['codigo'] }}</span>
                                    <span class="agv-time">{{ $item['data'] }}</span>
                                </div>
                                <p class="agv-name">{{ $item['ponto'] }}</p>
                            </button>
                        @empty
                            <p class="agv-empty">Sem visitas tecnicas para o dia selecionado.</p>
                        @endforelse
                    </div>
                </div>

                <div class="agv-card agv-side-card">
                    <div class="agv-card-head">
                        <h3 class="agv-title">Agenda do mes</h3>
                        <span class="agv-badge">{{ count($agendaDoMes) }}</span>
                    </div>
                    <div class="agv-list agv-list-month">
                        @forelse($agendaDoMes as $item)
                            <button
                                type="button"
                                class="agv-row"
                                @if(!empty($item['projeto_id'])) wire:click="abrirSidebarPonto({{ $item['projeto_id'] }})" @endif
                            >
                                <div class="agv-row-top">
                                    <span class="agv-code">{{ $item['codigo'] }}</span>
                                    <span class="agv-time">{{ $item['data'] }}</span>
                                </div>
                                <p class="agv-name">{{ $item['ponto'] }}</p>
                            </button>
                        @empty
                            <p class="agv-empty">Sem visitas tecnicas agendadas no mes.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @if($sidebarOpen && $sidebarPonto)
            <div class="agv-overlay" wire:click="fecharSidebarPonto"></div>
            <aside class="agv-sidebar">
                <div class="agv-sidebar-head">
                    <h3 class="agv-title" style="margin:0;">Visualizacao rapida</h3>
                    <button type="button" wire:click="fecharSidebarPonto" class="agv-close-btn">x</button>
                </div>

                <div class="agv-sidebar-body">
                    <div class="agv-kv">
                        <span>Codigo</span>
                        <strong>{{ $sidebarPonto['codigo'] }}</strong>
                    </div>
                    <div class="agv-kv">
                        <span>Ponto</span>
                        <strong>{{ $sidebarPonto['nome'] }}</strong>
                    </div>
                    <div class="agv-kv">
                        <span>Marca</span>
                        <strong>{{ $sidebarPonto['marca'] }}</strong>
                    </div>
                    <div class="agv-kv">
                        <span>Status comite</span>
                        <strong>{{ $sidebarPonto['status_comite'] }}</strong>
                    </div>
                    <div class="agv-kv">
                        <span>Responsavel</span>
                        <strong>{{ $sidebarPonto['responsavel'] }}</strong>
                    </div>
                    <div class="agv-kv">
                        <span>Cidade/UF</span>
                        <strong>{{ $sidebarPonto['cidade_uf'] }}</strong>
                    </div>
                    <div class="agv-kv">
                        <span>Agendamento VT</span>
                        <strong>{{ $sidebarPonto['agendamento_vt'] }}</strong>
                    </div>
                    <div class="agv-kv">
                        <span>Endereco</span>
                        <strong>{{ $sidebarPonto['endereco'] }}</strong>
                    </div>

                    <div class="agv-sidebar-actions">
                        <a href="{{ $sidebarPonto['visualizar_url'] }}" class="agv-btn agv-btn-secondary">Abrir visualizacao completa</a>
                        <a href="{{ $sidebarPonto['editar_url'] }}" class="agv-btn agv-btn-primary">Editar ponto</a>
                    </div>
                </div>
            </aside>
        @endif
    </div>

    <style>
        .agv-header { margin-bottom: .75rem; }
        .agv-subtitle { margin: 0; font-size: .875rem; color: #6B7280; }

        .agv-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 1rem;
            align-items: start;
        }
        .agv-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 1rem;
        }
        .dark .agv-card { background: #2A2D31; border-color: #4B5563; }
        .agv-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .75rem;
            gap: .5rem;
        }
        .agv-title {
            margin: 0;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #374151;
        }
        .dark .agv-title { color: #E5E7EB; }
        .agv-muted { font-size: .78rem; color: #6B7280; }

        .agv-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: .35rem;
            margin-bottom: .35rem;
        }
        .agv-weekdays span {
            text-align: center;
            font-size: .68rem;
            font-weight: 700;
            color: #9CA3AF;
            text-transform: uppercase;
        }
        .agv-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: .35rem;
        }
        .agv-day {
            min-height: 92px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: .35rem;
            background: #fff;
            text-align: left;
            cursor: pointer;
        }
        .dark .agv-day { background: #1F2328; border-color: #4B5563; }
        .agv-day-empty {
            border-style: dashed;
            opacity: .45;
            cursor: default;
        }
        .agv-day-today { border-color: #FBBA00; }
        .agv-day-selected {
            border-color: #F59E0B;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, .42) inset;
            background: #FFF7ED;
        }
        .dark .agv-day-selected {
            background: rgba(245, 158, 11, .12);
            border-color: #FCD34D;
        }
        .agv-day-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .2rem;
        }
        .agv-day-number { font-size: .74rem; font-weight: 700; color: #1F2937; }
        .dark .agv-day-number { color: #E5E7EB; }
        .agv-day-count {
            font-size: .62rem;
            font-weight: 700;
            color: #111827;
            background: #FBBA00;
            border-radius: 999px;
            padding: 0 .35rem;
            line-height: 1.2rem;
            min-width: 1.2rem;
            text-align: center;
        }
        .agv-day-items { display: flex; flex-direction: column; gap: 2px; }
        .agv-item {
            display: flex;
            gap: 4px;
            align-items: center;
            min-width: 0;
            background: #F9FAFB;
            border-radius: 4px;
            padding: 2px 4px;
        }
        .dark .agv-item { background: #30353B; }
        .agv-item-code { font-size: .62rem; font-weight: 700; color: #EA580C; flex-shrink: 0; }
        .agv-item-name {
            font-size: .62rem;
            color: #6B7280;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .agv-item-name { color: #C4CAD1; }

        .agv-side {
            display: grid;
            grid-template-rows: auto auto;
            gap: 1rem;
        }
        .agv-side-card { padding: 0; overflow: hidden; }
        .agv-side-card .agv-card-head { padding: .85rem 1rem .35rem; margin-bottom: 0; }
        .agv-selected-date {
            margin: 0 .95rem .35rem;
            padding: .4rem .55rem;
            border-radius: 8px;
            background: #EFF6FF;
            color: #1E40AF;
            font-size: .74rem;
            font-weight: 600;
        }
        .dark .agv-selected-date {
            background: rgba(107, 114, 128, .28);
            color: #E5E7EB;
        }
        .agv-badge {
            padding: 2px 8px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            background: #FEF3C7;
            color: #92400E;
        }
        .dark .agv-badge { background: rgba(245, 158, 11, .2); color: #FCD34D; }
        .agv-list {
            max-height: 300px;
            overflow-y: auto;
            padding: .35rem .5rem .5rem;
        }
        .agv-list-month { max-height: 380px; }
        .agv-row {
            display: block;
            width: 100%;
            border: 0;
            text-align: left;
            padding: .5rem .6rem;
            border-left: 3px solid #FBBA00;
            border-radius: 8px;
            background: #FFFBEB;
            text-decoration: none;
            margin-bottom: .45rem;
            cursor: pointer;
        }
        .dark .agv-row { background: rgba(115, 115, 115, .18); }
        .agv-row-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-bottom: 4px;
        }
        .agv-code { font-size: .75rem; font-weight: 700; color: #EA580C; }
        .agv-time { font-size: .72rem; color: #9CA3AF; }
        .agv-name {
            margin: 0;
            font-size: .82rem;
            color: #374151;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dark .agv-name { color: #E5E7EB; }
        .agv-empty { margin: 0; padding: 1rem; text-align: center; font-size: .85rem; color: #9CA3AF; }

        .agv-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .35);
            z-index: 40;
        }
        .agv-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            width: min(460px, 96vw);
            background: #ffffff;
            border-left: 1px solid #e5e7eb;
            box-shadow: -10px 0 30px rgba(15, 23, 42, .2);
            z-index: 50;
            display: flex;
            flex-direction: column;
        }
        .dark .agv-sidebar {
            background: #23262B;
            border-left-color: #4B5563;
        }
        .agv-sidebar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .9rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark .agv-sidebar-head { border-bottom-color: #4B5563; }
        .agv-close-btn {
            border: 0;
            background: #f1f5f9;
            color: #0f172a;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }
        .dark .agv-close-btn {
            background: #3A3F46;
            color: #F3F4F6;
        }
        .agv-sidebar-body {
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }
        .agv-kv {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            padding: .55rem .7rem;
        }
        .dark .agv-kv {
            border-color: #4B5563;
            background: #2E3339;
        }
        .agv-kv span {
            display: block;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: 2px;
        }
        .agv-kv strong {
            font-size: .84rem;
            color: #0f172a;
            word-break: break-word;
        }
        .dark .agv-kv strong { color: #F3F4F6; }
        .agv-sidebar-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: .5rem;
            margin-top: .45rem;
        }
        .agv-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 700;
            border-radius: 10px;
            padding: .6rem .75rem;
        }
        .agv-btn-primary {
            background: #fbbc04;
            color: #111827;
            border: 1px solid #d39b00;
        }
        .agv-btn-secondary {
            background: #f8fafc;
            color: #1e293b;
            border: 1px solid #e2e8f0;
        }
        .dark .agv-btn-secondary {
            background: #2E3339;
            color: #F3F4F6;
            border-color: #4B5563;
        }

        .agv-calendar-shell {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            padding-bottom: .25rem;
            width: 100%;
            max-width: 100%;
        }
        .agv-calendar-shell::-webkit-scrollbar {
            height: 8px;
        }
        .agv-calendar-shell::-webkit-scrollbar-thumb {
            background: #4B5563;
            border-radius: 999px;
        }

        @media (max-width: 1200px) {
            .agv-grid { grid-template-columns: 1fr; }
            .agv-side { grid-template-columns: 1fr 1fr; grid-template-rows: none; }
        }
        @media (max-width: 768px) {
            .agv-side { grid-template-columns: 1fr; }
            .agv-card {
                padding: .85rem;
            }
            .agv-day {
                min-height: 62px;
            }
            .agv-weekdays,
            .agv-calendar-grid {
                min-width: 0;
            }
            .agv-day-items {
                display: none;
            }
            .agv-title {
                font-size: .74rem;
            }
            .agv-calendar-shell {
                overflow-x: hidden;
            }
            .fi-page-header-actions {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: .5rem;
            }
            .fi-page-header-actions > * {
                flex: 1 1 calc(50% - .25rem);
            }
        }
    </style>
</x-filament::page>
