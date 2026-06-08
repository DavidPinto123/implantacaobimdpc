<x-filament::page>
    @php
    $kpiIcons = [
    'total' => '
    <svg viewBox="0 0 24 24" fill="none" class="dc-kpi-svg" xmlns="http://www.w3.org/2000/svg">
        <rect x="5" y="4" width="14" height="16" rx="3" stroke="currentColor" stroke-width="1.8" />
        <path d="M9 8H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        <path d="M9 12H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        <path d="M9 16H13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </svg>
    ',
    'aprovados' => '
    <svg viewBox="0 0 24 24" fill="none" class="dc-kpi-svg" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
        <path d="M9 12.5L11 14.5L15 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    ',
    'em_validacao' => '
    <svg viewBox="0 0 24 24" fill="none" class="dc-kpi-svg" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
        <path d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    ',
    'imovel_pronto' => '
    <svg viewBox="0 0 24 24" fill="none" class="dc-kpi-svg" xmlns="http://www.w3.org/2000/svg">
        <path d="M4 20H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        <path d="M6 20V9L12 5L18 9V20" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
        <path d="M10 13H14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </svg>
    ',
    'multiusuario' => '
    <svg viewBox="0 0 24 24" fill="none" class="dc-kpi-svg" xmlns="http://www.w3.org/2000/svg">
        <circle cx="9" cy="10" r="3" stroke="currentColor" stroke-width="1.8" />
        <circle cx="16" cy="11" r="2.5" stroke="currentColor" stroke-width="1.8" />
        <path d="M4.5 18C5.3 15.8 7.1 14.5 9.5 14.5C11.9 14.5 13.7 15.8 14.5 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        <path d="M14.5 17.5C15 16.2 16.1 15.3 17.6 15.1C18.8 14.9 20 15.4 20.8 16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </svg>
    ',
    'shell_30' => '
    <svg viewBox="0 0 24 24" fill="none" class="dc-kpi-svg" xmlns="http://www.w3.org/2000/svg">
        <rect x="5" y="6" width="14" height="13" rx="3" stroke="currentColor" stroke-width="1.8" />
        <path d="M8 4V8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        <path d="M16 4V8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        <path d="M5 10H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </svg>
    ',
    ];
    @endphp

    <div wire:poll.30s="loadData">
        <div class="dc-header">
            <div>
                <p class="dc-header-sub">
                    Visão rápida dos pontos cadastrados e acompanhamento da operação comercial.
                </p>
            </div>
        </div>

        <div class="dc-kpi-grid">
            <div class="dc-kpi-card">
                <div class="dc-kpi-icon-wrap dc-kpi-indigo">
                    {!! $kpiIcons['total'] !!}
                </div>
                <div>
                    <p class="dc-kpi-value">{{ $kpis['total'] ?? 0 }}</p>
                    <p class="dc-kpi-label">Total de pontos</p>
                </div>
            </div>

            <div class="dc-kpi-card">
                <div class="dc-kpi-icon-wrap dc-kpi-green">
                    {!! $kpiIcons['aprovados'] !!}
                </div>
                <div>
                    <p class="dc-kpi-value dc-text-green">{{ $kpis['aprovados'] ?? 0 }}</p>
                    <p class="dc-kpi-label">Aprovados</p>
                </div>
            </div>

            <div class="dc-kpi-card">
                <div class="dc-kpi-icon-wrap dc-kpi-blue">
                    {!! $kpiIcons['em_validacao'] !!}
                </div>
                <div>
                    <p class="dc-kpi-value dc-text-blue">{{ $kpis['em_validacao'] ?? 0 }}</p>
                    <p class="dc-kpi-label">Em validação</p>
                </div>
            </div>

            <div class="dc-kpi-card">
                <div class="dc-kpi-icon-wrap dc-kpi-red">
                    {!! $kpiIcons['imovel_pronto'] !!}
                </div>
                <div>
                    <p class="dc-kpi-value dc-text-red">{{ $kpis['imovel_pronto'] ?? 0 }}</p>
                    <p class="dc-kpi-label">Imóvel pronto</p>
                </div>
            </div>

            <div class="dc-kpi-card">
                <div class="dc-kpi-icon-wrap dc-kpi-orange">
                    {!! $kpiIcons['multiusuario'] !!}
                </div>
                <div>
                    <p class="dc-kpi-value dc-text-orange">{{ $kpis['multiusuario'] ?? 0 }}</p>
                    <p class="dc-kpi-label">Multiusuário</p>
                </div>
            </div>

            <div class="dc-kpi-card">
                <div class="dc-kpi-icon-wrap dc-kpi-purple">
                    {!! $kpiIcons['shell_30'] !!}
                </div>
                <div>
                    <p class="dc-kpi-value dc-text-purple">{{ $kpis['shell_30'] ?? 0 }}</p>
                    <p class="dc-kpi-label">Shell em 30 dias</p>
                </div>
            </div>
        </div>

        @php
            $meta = $metaSemanal ?? [];
            $metaPercent = (int) ($meta['percentual'] ?? 0);
            $metaGap = 100 - $metaPercent;
            $metaColorClass = $metaPercent >= 80 ? 'dc-sla-green' : ($metaPercent >= 50 ? 'dc-sla-yellow' : 'dc-sla-red');
        @endphp

        @if(!empty($meta))
        <div class="dc-sla-card">
            <div class="dc-sla-title-row">
                <h3 class="dc-section-title title-black" style="margin:0;">
                    <svg style="width:14px;height:14px;color:#FBBA00;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Cumprimento semanal da meta ({{ $meta['inicio'] ?? '--' }} a {{ $meta['fim'] ?? '--' }})
                </h3>
                <span class="dc-sla-sub">Meta: {{ $meta['meta'] ?? 10 }} pontos por usuario</span>
            </div>

            <div class="dc-sla-grid">
                <div class="dc-sla-donut-wrap">
                    <svg class="dc-sla-donut" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="2.7" class="dc-sla-track" />
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="2.7"
                            stroke-dasharray="{{ $metaPercent }} {{ $metaGap }}"
                            stroke-dashoffset="0"
                            stroke-linecap="round"
                            class="{{ $metaColorClass }}" />
                    </svg>
                    <span class="dc-sla-pct">{{ $metaPercent }}%</span>
                </div>

                <div class="dc-sla-stats">
                    <div>
                        <p class="dc-sla-value">{{ $meta['aprovados'] ?? 0 }}</p>
                        <p class="dc-sla-label">Aprovados</p>
                    </div>
                    <div>
                        <p class="dc-sla-value dc-text-green">{{ $meta['em_validacao'] ?? 0 }}</p>
                        <p class="dc-sla-label">Em validação</p>
                    </div>
                    <div>
                        <p class="dc-sla-value dc-text-red">{{ $meta['reprovados'] ?? 0 }}</p>
                        <p class="dc-sla-label">Reprovado</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="dc-table-section">
            <div class="dc-list-header">
                <h3 class="dc-section-title" style="margin: 0;">
                    <svg style="width:14px;height:14px;color:#FBBA00;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                    </svg>
                    Pontos cadastrados
                </h3>
            </div>

            <div class="dc-filament-table-native">
                {{ $this->table }}
            </div>
        </div>
    </div>

    <style>
        .dc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .dc-header-title {
            font-size: 1.85rem;
            font-weight: 800;
            margin: 0;
            color: var(--gray-950);
        }

        .dark .dc-header-title {
            color: #fff;
        }

        .dc-header-sub {
            font-size: .9rem;
            margin: -1.7rem 0 0;
            color: #64748B;
        }

        .dark .dc-header-sub {
            color: #94A3B8;
        }

        .dc-kpi-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .dc-kpi-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem;
            border-radius: 16px;
            border: 1px solid #d39b00;
            background: #fbbc04;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .10);
        }

        .dark .dc-kpi-card {
            background: #fbbc04;
            border-color: #d39b00;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .18);
        }

        .dc-kpi-value {
            font-size: 1.9rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
            color: #111111 !important;
        }

        .dark .dc-kpi-value {
            color: #111111 !important;
        }

        .dc-kpi-label {
            font-size: .72rem;
            color: #111111 !important;
            margin: 4px 0 0;
            text-transform: uppercase;
            letter-spacing: .05em;
            opacity: .9;
        }

        .dark .dc-kpi-label {
            color: #111111 !important;
        }

        .dc-kpi-icon-wrap {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(0, 0, 0, .08) !important;
            color: #111111 !important;
        }

        .dark .dc-kpi-icon-wrap {
            background: rgba(0, 0, 0, .08) !important;
            color: #111111 !important;
        }

        .dc-kpi-svg {
            width: 24px;
            height: 24px;
            display: block;
            color: #111111 !important;
        }

        .dc-kpi-indigo,
        .dc-kpi-green,
        .dc-kpi-blue,
        .dc-kpi-red,
        .dc-kpi-orange,
        .dc-kpi-purple {
            background: rgba(0, 0, 0, .08) !important;
            color: #111111 !important;
        }

        .dark .dc-kpi-indigo,
        .dark .dc-kpi-green,
        .dark .dc-kpi-blue,
        .dark .dc-kpi-red,
        .dark .dc-kpi-orange,
        .dark .dc-kpi-purple {
            background: rgba(0, 0, 0, .08) !important;
            color: #111111 !important;
        }

        .dc-text-green,
        .dc-text-blue,
        .dc-text-red,
        .dc-text-orange,
        .dc-text-purple {
            color: #111111 !important;
        }

        .dark .dc-text-green,
        .dark .dc-text-blue,
        .dark .dc-text-red,
        .dark .dc-text-orange,
        .dark .dc-text-purple {
            color: #111111 !important;
        }

        .dc-table-section {
            margin-top: 1rem;
        }

        .dc-sla-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem 1.2rem;
            margin-bottom: 1rem;
            background: #ffffff;
        }
        .dark .dc-sla-card {
            background: #fbbc04;
            border-color: #d39b00;
        }
        .dc-sla-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .9rem;
            flex-wrap: wrap;
        }
        .dc-sla-sub {
            font-size: .75rem;
            color: #94a3b8;
        }
        .dark .dc-sla-sub{
            color: #111111 ;
        }
        .dc-sla-grid {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 1rem;
            align-items: center;
            margin-bottom: .75rem;
        }
        .dc-sla-donut-wrap {
            position: relative;
            width: 110px;
            height: 110px;
        }
        .dc-sla-donut {
            width: 110px;
            height: 110px;
            transform: rotate(-90deg);
        }
        .dc-sla-track { stroke: #e5e7eb; }
        .dark .dc-sla-track { stroke: #374151; }
        .dc-sla-green { stroke: #22c55e; }
        .dc-sla-yellow { stroke: #eab308; }
        .dc-sla-red { stroke: #ef4444; }
        .dc-sla-pct {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
        }
        .dark .dc-sla-pct { color: #f8fafc; }
        .dc-sla-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .75rem;
            text-align: center;
        }
        .dc-sla-value {
            margin: 0;
            font-size: 2rem;
            line-height: 1;
            font-weight: 700;
            color: #111827;
        }
        .dark .dc-sla-value { color: #111111; }
        .dc-sla-label {
            margin: .35rem 0 0;
            color: #111111;
        }

        .dc-list-header {
            margin-bottom: .75rem;
        }

        .dc-section-title {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dark .dc-section-title {
            color: #ffffff;
        }

        .dark .title-black{
            color: #111111;
        }

        .dc-filament-table-native {
            padding: 0;
        }

        @media (max-width: 1280px) {
            .dc-kpi-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .dc-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .dc-sla-grid {
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
            }

            .dc-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .dc-kpi-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</x-filament::page>
