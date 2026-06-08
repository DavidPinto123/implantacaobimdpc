<x-filament-panels::page>
    @php
        $sigla = $projeto->nova_sigla ?: $projeto->sigla;
        $statusColors = [
            'Iniciado' => '#9bd55b',
            'Assinado' => '#9bd55b',
            'Em processo' => '#f10000',
        ];
        $needleDeg = -90 + ((int) round(max(0, min(100, (int) ($dashboardPercent ?? 0))) * 1.8));

        $cronPlanTotal = 0;
        $cronRealTotal = 0;
        foreach ($cronograma as $linha) {
            if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $linha['plan'] ?? '')) {
                $cronPlanTotal++;
            }
            if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $linha['real'] ?? '')) {
                $cronRealTotal++;
            }
        }
        $cronDesvio = max(0, $cronPlanTotal - $cronRealTotal);
    @endphp

    <style>
        .fi-main .fi-page { max-width: none !important; }
        .pp-wrap {
            --pp-bg: #f5f7fb;
            --pp-surface: #ffffff;
            --pp-text: #0f172a;
            --pp-muted: #64748b;
            --pp-border: #dbe2ea;
            --pp-primary: #1f4ed8;
            --pp-warning: #f5bf00;
            --pp-danger: #ef4444;
            --pp-success: #86d556;
            color: var(--pp-text);
            width: 100%;
            max-width: none;
            margin: 0;
            background: var(--pp-bg);
            border-radius: 16px;
            padding: 12px;
        }
        .pp-card {
            background: var(--pp-surface);
            border: 1px solid var(--pp-border);
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .04);
        }
        .pp-grid-top { display: grid; grid-template-columns: 1.25fr .95fr; gap: 12px; align-items: stretch; }
        .pp-grid-top > .pp-card { height: 100%; }
        .pp-grid-middle,
        .pp-grid-dates { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 10px; }
        .pp-grid-main-top { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; }
        .pp-grid-bottom { display: grid; grid-template-columns: 1.3fr 1fr; gap: 12px; margin-top: 12px; }
        .pp-label { font-size: 11px; color: var(--pp-primary); font-style: italic; }
        .pp-value { font-size: 26px; font-weight: 800; line-height: 1.15; letter-spacing: -.01em; }
        .pp-value.-small { font-size: 24px; }
        .pp-card-title {
            margin: 0 0 10px;
            font-size: 17px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: .03em;
        }
        .pp-subhelp { margin: -4px 0 10px; color: var(--pp-muted); font-size: 12px; }
        .pp-kv { display: grid; grid-template-columns: 130px 1fr; gap: 10px; margin-bottom: 6px; }
        .pp-kv .pp-label { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; font-style: normal; color: var(--pp-muted); }
        .pp-kv b { font-size: 14px; font-weight: 700; }
        .pp-dados-title { text-align: center; }
        .pp-dados-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px 14px;
        }
        .pp-dados-grid .pp-item.-wide { grid-column: span 2; }
        .pp-dados-grid .pp-item.-full { grid-column: 1 / -1; }
        .pp-item .pp-label {
            font-size: 11px;
            color: var(--pp-primary);
            font-style: italic;
            line-height: 1.25;
            display: block;
            margin-bottom: 2px;
        }
        .pp-item .pp-text {
            font-size: 13px;
            line-height: 1.35;
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }
        .pp-dates-title {
            text-align: center;
            font-size: 18px;
            text-transform: uppercase;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .pp-date-cell { text-align: center; }
        .pp-date-cell .pp-value { font-size: 20px; }
        .pp-date-cell .pp-label { font-size: 12px; margin-top: 2px; color: var(--pp-muted); }
        .pp-top-meta {
            display: grid;
            grid-template-columns: 1.2fr .8fr 1fr;
            border: 1px solid var(--pp-border);
            border-radius: 10px;
            overflow: hidden;
        }
        .pp-top-head {
            display: grid;
            grid-template-columns: 1.2fr .8fr 1fr;
            margin-top: 0;
            margin-bottom: 4px;
        }
        .pp-top-head .pp-label { text-align: center; }
        .pp-top-meta > div {
            border-right: 1px solid var(--pp-border);
            background: #eef2f7;
            text-align: center;
            font-weight: 800;
            font-size: 16px;
            padding: 10px 8px;
        }
        .pp-top-meta > div:last-child { border-right: 0; background: #ffe57a; }
        .pp-top-left-card {
            display: flex;
            align-items: stretch;
            justify-content: center;
        }
        .pp-top-hint {
            color: var(--pp-muted);
            font-style: italic;
            font-size: 11px;
            margin: 6px 0 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pp-status-list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 8px;
        }
        .pp-status-head {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 8px;
            margin-top: 12px;
            margin-bottom: 10px;
        }
        .pp-status-head .pp-label {
            text-transform: uppercase;
            font-style: normal;
            font-size: 10px;
            letter-spacing: .05em;
            color: var(--pp-muted);
        }
        .pp-status-item {
            text-align: center;
            padding: 9px 6px;
            border: 1px solid var(--pp-border);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            background: #f8fafc;
        }
        .pp-status-item.-on { background: var(--pp-status-bg, var(--pp-success)); border-color: transparent; color: #111827; }
        .pp-tabs {
            margin-top: 12px;
            display: flex;
            border: 1px solid var(--pp-border);
            border-radius: 12px;
            overflow-x: auto;
            background: #fff;
        }
        .pp-tab {
            flex: 1;
            text-align: center;
            white-space: nowrap;
            padding: 9px 12px;
            font-size: 13px;
            color: var(--pp-muted);
            border-right: 1px solid var(--pp-border);
        }
        .pp-tab:last-child { border-right: 0; }
        .pp-tab.-active { background: var(--pp-warning); color: #111827; font-weight: 700; }
        .pp-gauge-wrap { display: grid; grid-template-columns: 320px 1fr; align-items: center; gap: 14px; }
        .pp-speedometer {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }
        .pp-speedometer-meta {
            margin-top: 8px;
            text-align: center;
            line-height: 1.2;
        }
        .pp-speedometer-percent {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
        }
        .pp-speedometer-caption {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }
        .pp-speedometer svg { width: 100%; height: auto; display: block; }
        .pp-speedometer .dial-base { stroke: #e2e8f0; stroke-width: 22; fill: none; stroke-linecap: round; }
        .pp-speedometer .seg-red { stroke: #ef4444; stroke-width: 22; fill: none; stroke-linecap: butt; stroke-dasharray: 14 86; stroke-dashoffset: 0; }
        .pp-speedometer .seg-orange { stroke: #f59e0b; stroke-width: 22; fill: none; stroke-linecap: butt; stroke-dasharray: 20 80; stroke-dashoffset: -14; }
        .pp-speedometer .seg-yellow { stroke: #facc15; stroke-width: 22; fill: none; stroke-linecap: butt; stroke-dasharray: 20 80; stroke-dashoffset: -34; }
        .pp-speedometer .seg-lime { stroke: #c7db9d; stroke-width: 22; fill: none; stroke-linecap: butt; stroke-dasharray: 20 80; stroke-dashoffset: -54; }
        .pp-speedometer .seg-green { stroke: #a8c97c; stroke-width: 22; fill: none; stroke-linecap: butt; stroke-dasharray: 26 74; stroke-dashoffset: -74; }
        .pp-speedometer .needle {
            stroke: #1f2937;
            stroke-width: 3;
            stroke-linecap: round;
            transform-origin: 120px 124px;
            transform: rotate(var(--needle, -90deg));
        }
        .pp-speedometer .needle-dot { fill: #111827; }
        .pp-dashboard-lines { display: grid; gap: 8px; }
        .pp-dashboard-line { font-size: 15px; font-weight: 700; }
        .pp-doc-badge {
            margin-top: 6px;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 8px;
            border-radius: 999px;
            color: #fff;
            background: var(--pp-danger);
        }
        .pp-doc-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }
        .pp-crono-summary {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .pp-crono-summary div {
            border: 1px solid var(--pp-border);
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            color: var(--pp-muted);
        }
        .pp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .pp-table th, .pp-table td { border: 1px solid var(--pp-border); padding: 8px; text-align: left; }
        .pp-table th { background: #fff7d1; color: #475569; font-size: 12px; text-transform: uppercase; }
        .pp-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .pp-right-stack { display: grid; gap: 10px; }
        .pp-team { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .pp-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #1e3a8a;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .pp-file-preview {
            width: 100%;
            min-height: 72px;
            border: 1px dashed var(--pp-border);
            border-radius: 10px;
            background: #f8fafc;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 12px;
            padding: 8px;
        }
        .pp-kv-right {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 8px;
            font-size: 13px;
            margin-bottom: 6px;
            align-items: baseline;
        }
        .pp-kv-right b { font-weight: 600; color: #334155; }
        .pp-proj-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .pp-proj-table td, .pp-proj-table th { border: 1px solid var(--pp-border); padding: 6px 8px; }
        .pp-proj-table th { background: #f8fafc; text-align: center; font-size: 11px; text-transform: uppercase; }
        .pp-muted { color: var(--pp-muted); font-size: 12px; }

        .dark .pp-wrap {
            --pp-bg: #0b1220;
            --pp-surface: #0f172a;
            --pp-text: #e2e8f0;
            --pp-muted: #94a3b8;
            --pp-border: #233042;
            --pp-primary: #93c5fd;
        }
        .dark .pp-top-meta > div { background: #172235; }
        .dark .pp-top-meta > div:last-child { background: #eab308; color: #111827; }
        .dark .pp-status-item { background: #111c30; color: #e2e8f0; }
        .dark .pp-speedometer .dial-base { stroke: #243246; }
        .dark .pp-speedometer-percent { color: #e2e8f0; }
        .dark .pp-speedometer-caption { color: #94a3b8; }
        .dark .pp-tab { background: #0f172a; }
        .dark .pp-file-preview { background: #0b1220; }
        .dark .pp-table th { background: #16223a; color: #cbd5e1; }
        .dark .pp-proj-table th { background: #16223a; color: #cbd5e1; }
        .dark .pp-item .pp-text { color: #e2e8f0; }

        @media (max-width: 1180px) {
            .pp-grid-top, .pp-grid-main-top, .pp-grid-bottom, .pp-mini-grid { grid-template-columns: 1fr; }
            .pp-grid-middle, .pp-grid-dates { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .pp-gauge-wrap { grid-template-columns: 1fr; }
            .pp-tabs { border-radius: 10px; }
            .pp-dados-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .pp-item .pp-text { font-size: 14px; }
            .pp-dados-grid .pp-item.-wide,
            .pp-dados-grid .pp-item.-full { grid-column: 1 / -1; }
        }
    </style>

    <div class="pp-wrap">
        <div class="pp-grid-top">
            <div class="pp-card pp-top-left-card">
                <div class="pp-grid-middle" style="margin-top:0; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items:center;">
                    <div>
                        <div class="pp-label">Codigo</div>
                        <div class="pp-value -small">{{ $projeto->codigo ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="pp-label">Sigla</div>
                        <div class="pp-value -small">{{ $sigla ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="pp-label">Unidade</div>
                        <div class="pp-value -small">{{ $projeto->nome ?: '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="pp-card">
                <div class="pp-top-head">
                    <div class="pp-label">PIPELINE</div>
                    <div class="pp-label">ANO</div>
                    <div class="pp-label">Marca</div>
                </div>
                <div class="pp-top-meta">
                    <div>{{ $projeto->pipeline ?: 'PIPELINE' }}</div>
                    <div>{{ $projeto->ano_inauguracao ?: now()->year }}</div>
                    <div>{{ strtoupper($projeto->marca ?: 'DPC') }}</div>
                </div>
                <div class="pp-status-head">
                    <div class="pp-label">Status do Projeto</div>
                    <div class="pp-label">Status do Contrato</div>
                    <div class="pp-label">Status do Projeto</div>
                </div>
                <div class="pp-status-list">
                    @foreach ($statusLabels as $label)
                        <div class="pp-status-item {{ $statusAtivo === $label ? '-on' : '' }}" @if($statusAtivo === $label) style="--pp-status-bg: {{ $statusColors[$label] ?? '#9bd55b' }}" @endif>{{ strtoupper($label) }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="pp-card" style="margin-top:12px;">
            <div class="pp-dates-title">Datas</div>
            <div class="pp-grid-dates" style="margin-top:0;">
                @foreach ($timeline as $item)
                    <div class="pp-date-cell">
                        <div class="pp-value">{{ $item['value'] }}</div>
                        <div class="pp-label">{{ $item['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="pp-grid-main-top">
            <div class="pp-card">
                @php
                    $cnpj = $projeto->controlePedido?->cnpj ?? '-';
                    $produtos = collect([
                        $projeto->marca,
                        $projeto->tipo_de_loja,
                    ])->filter()->implode(' | ');
                @endphp
                <div class="pp-card-title pp-dados-title">Dados</div>
                <div class="pp-dados-grid">
                    <div class="pp-item -wide">
                        <span class="pp-label">Endereço</span>
                        <div class="pp-text">{{ $endereco ?: '-' }}</div>
                    </div>
                    <div class="pp-item -wide">
                        <span class="pp-label">Link / PIN Earth</span>
                        <div class="pp-text">{{ $projeto->pin_google ?: '-' }}</div>
                    </div>
                    <div class="pp-item -full">
                        <span class="pp-label">CNPJ</span>
                        <div class="pp-text">{{ $cnpj }}</div>
                    </div>

                    <div class="pp-item">
                        <span class="pp-label">Tipo de imóvel</span>
                        <div class="pp-text">{{ $projeto->tipo_imovel ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">Construção</span>
                        <div class="pp-text">{{ $projeto->empreendimento ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">Locação</span>
                        <div class="pp-text">{{ $projeto->locacao ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">Obs. Estac.</span>
                        <div class="pp-text">{{ $projeto->obs_aluguel ?: '-' }}</div>
                    </div>

                    <div class="pp-item">
                        <span class="pp-label">Contrato m²</span>
                        <div class="pp-text">{{ $projeto->metro_contrato ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">Layout m²</span>
                        <div class="pp-text">{{ $projeto->metro_layout_util ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">Estac.</span>
                        <div class="pp-text">{{ $projeto->n_vagas_livres ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">Tier</span>
                        <div class="pp-text">{{ $projeto->tier ?: '-' }}</div>
                    </div>

                    <div class="pp-item -wide">
                        <span class="pp-label">Produtos (Conforme atualização do Check List)</span>
                        <div class="pp-text">{{ $produtos ?: '-' }}</div>
                    </div>
                    <div class="pp-item -wide">
                        <span class="pp-label">Set Equipamentos (PP, P, M, G, Personalizado)</span>
                        <div class="pp-text">{{ $projeto->set_equipamentos ?: '-' }}</div>
                    </div>

                    <div class="pp-item">
                        <span class="pp-label">Capex Previsto</span>
                        <div class="pp-text">{{ $projeto->capex_aprovado_diretoria_valor ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">COC</span>
                        <div class="pp-text">{{ $projeto->coc_aprovado ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label"># Alunos</span>
                        <div class="pp-text">{{ $projeto->potencial_alunos ?: '-' }}</div>
                    </div>
                    <div class="pp-item">
                        <span class="pp-label">Contato</span>
                        <div class="pp-text">{{ $projeto->nome_contato ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="pp-card">
                <div class="pp-card-title">Dashboard</div>
                <div class="pp-subhelp">% de avanço do Projeto Geral</div>
                <div class="pp-gauge-wrap">
                    <div class="pp-speedometer" style="--needle: {{ $needleDeg }}deg;">
                        <svg viewBox="0 0 240 150" aria-label="Velocímetro de progresso">
                            <path d="M24 124 A96 96 0 0 1 216 124" pathLength="100" class="dial-base"></path>
                            <path d="M24 124 A96 96 0 0 1 216 124" pathLength="100" class="seg-red"></path>
                            <path d="M24 124 A96 96 0 0 1 216 124" pathLength="100" class="seg-orange"></path>
                            <path d="M24 124 A96 96 0 0 1 216 124" pathLength="100" class="seg-yellow"></path>
                            <path d="M24 124 A96 96 0 0 1 216 124" pathLength="100" class="seg-lime"></path>
                            <path d="M24 124 A96 96 0 0 1 216 124" pathLength="100" class="seg-green"></path>
                            <line x1="120" y1="124" x2="120" y2="46" class="needle"></line>
                            <circle cx="120" cy="124" r="5.5" class="needle-dot"></circle>
                        </svg>
                        <div class="pp-speedometer-meta">
                            <div class="pp-speedometer-percent">{{ (int) ($dashboardPercent ?? 0) }}%</div>
                            <div class="pp-speedometer-caption">Progresso</div>
                        </div>
                    </div>
                    <div class="pp-dashboard-lines">
                        <div class="pp-dashboard-line"><span>{{ $kpis['contratacao'] ?? 0 }}% Contratação</span></div>
                        <div class="pp-dashboard-line"><span>{{ $kpis['obras'] ?? 0 }}% Obras</span></div>
                        <div class="pp-dashboard-line"><span>{{ $kpis['implantacao'] ?? 0 }}% Implantação</span></div>
                        <div>
                            <div class="pp-doc-title">Documentação</div>
                            <span class="pp-doc-badge">{{ $kpis['documentacao'] ?? 'PENDENCIA COMERCIAL' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pp-tabs">
            <div class="pp-tab -active">Resumo</div>
            <div class="pp-tab">Cronograma</div>
            <div class="pp-tab">Dados</div>
            <div class="pp-tab">Projetos</div>
            <div class="pp-tab">Pre Obras</div>
            <div class="pp-tab">Orcamentos</div>
            <div class="pp-tab">Obra</div>
            <div class="pp-tab">Pos Obra</div>
            <div class="pp-tab">Financeiro</div>
            <div class="pp-tab">Legalizacao</div>
        </div>

        <div class="pp-grid-bottom">
            <div class="pp-card">
                <div class="pp-card-title">Cronograma</div>
                <div class="pp-subhelp">Para cada fase: comentário, anexo, status e comparativo de prazo.</div>
                <div class="pp-crono-summary">
                    <div><span>Prazo Planejado</span><span>{{ $cronPlanTotal }}</span></div>
                    <div><span>Prazo Real</span><span>{{ $cronRealTotal }}</span></div>
                    <div><span>Desvio</span><span>{{ $cronDesvio }}</span></div>
                </div>
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Etapa</th>
                            <th>Prazo planejado</th>
                            <th>Prazo real</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cronograma as $item)
                            <tr>
                                <td>{{ $item['etapa'] }}</td>
                                <td>{{ $item['plan'] }}</td>
                                <td>{{ $item['real'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pp-right-stack">
                <div class="pp-card">
                    <div class="pp-mini-grid">
                        <div>
                            <div class="pp-card-title">Squad</div>
                            <div class="pp-team">
                                @forelse ($squad as $user)
                                    <div class="pp-avatar" title="{{ $user->name }}">{{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}</div>
                                @empty
                                    <span class="pp-muted">Sem squad vinculado.</span>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <div class="pp-card-title">Contrato</div>
                            <div class="pp-file-preview">
                                @if ($contratoArquivo)
                                    {{ basename($contratoArquivo) }}
                                @else
                                    Nenhum contrato anexado.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pp-card">
                    <div class="pp-card-title">Status Posse / IO</div>
                    @foreach ($statusPosseIo as $campo => $valor)
                        <div class="pp-kv-right">
                            <span style="font-weight:700;">{{ $campo }}</span>
                            <b>{{ $valor ?: '-' }}</b>
                        </div>
                    @endforeach
                </div>

                <div class="pp-card">
                    <div class="pp-card-title">Projetos</div>
                    @foreach ($statusProjetos as $campo => $valor)
                        <div class="pp-kv-right">
                            <span style="font-weight:700;">{{ $campo }}</span>
                            <b>{{ $valor }}</b>
                        </div>
                    @endforeach
                    <table class="pp-proj-table" style="margin-top:8px;">
                        <thead>
                            <tr>
                                <th colspan="2">Análise de Projetos Recebidos do Proprietário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Data recebimento dos projetos</td>
                                <td>{{ $projeto->proj_real_reuniao_start?->format('d/m/Y') ?? '--/--/----' }}</td>
                            </tr>
                            <tr>
                                <td>Status do processo</td>
                                <td>{{ $projeto->proj_status ?: 'Em análise' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="pp-muted" style="margin-top:8px;">Ajustes de análise seguem o checklist técnico.</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
