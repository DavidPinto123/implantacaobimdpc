<x-filament-panels::page>
    <style>
        .ct-card {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: .75rem;
            box-shadow: var(--vo-shadow);
            overflow: hidden;
        }

        .ct-filters {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--vo-border);
            flex-wrap: wrap;
            background: var(--vo-bg-subtle);
        }

        @media (max-width: 640px) {
            .ct-filters { padding: 10px; gap: 6px; }
            .ct-filters > div[style*="margin-left:auto"] { margin-left: 0 !important; width: 100%; }
            .ct-header-form { padding: 14px; }
        }

        .ct-filters input[type="text"],
        .ct-filters select {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            color: var(--vo-text);
            border-radius: .375rem;
            padding: 6px 10px;
            font-size: 0.78rem;
            outline: none;
        }

        .vo-btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: 1px solid var(--vo-border);
            color: var(--vo-text-secondary);
            border-radius: .375rem;
            padding: 6px 12px;
            font-size: 0.78rem;
            cursor: pointer;
            transition: border-color .1s, background .1s;
        }

        .vo-btn-outline:hover {
            border-color: var(--vo-text-muted);
            background: var(--vo-bg-subtle);
        }

        .ct-table-wrap { overflow: auto; max-height: 72vh; }

        .ct-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
        }

        .ct-table thead {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .ct-table th {
            background: var(--vo-bg-subtle);
            color: var(--vo-text-faint);
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--vo-border);
            white-space: nowrap;
        }

        .ct-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--vo-border-light);
            color: var(--vo-text-secondary);
            white-space: nowrap;
        }

        .ct-table-row {
            cursor: pointer;
            transition: background 0.12s;
        }

        .ct-table-row:hover { background: var(--vo-bg-subtle); }

        .ct-td-sticky {
            position: sticky;
            left: 0;
            background: var(--vo-bg);
            z-index: 2;
            font-weight: 500;
        }

        .ct-table-row:hover .ct-td-sticky { background: var(--vo-bg-subtle); }

        .ct-th-sticky { position: sticky; left: 0; z-index: 6; }

        .ct-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            background: var(--vo-border-light);
            color: var(--vo-text-secondary);
        }

        .ct-badge-success { background: #dcfce7; color: #166534; }
        .ct-badge-muted { background: var(--vo-border-light); color: var(--vo-text-muted); }
        .ct-badge-accent { background: rgba(251,186,0,.18); color: #8a5a00; }

        .ct-dep-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 0.7rem;
            border-radius: 1rem;
            background: var(--vo-bg-subtle);
            border: 1px dashed var(--vo-border);
            color: var(--vo-text-secondary);
        }

        .ct-empty {
            padding: 48px;
            text-align: center;
            color: var(--vo-text-faint);
            font-size: 0.85rem;
        }

        .ct-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 950;
            padding: 16px;
        }

        .ct-modal {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: .75rem;
            padding: 20px;
            width: 640px;
            max-width: calc(100vw - 32px);
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,.2);
        }

        @media (max-width: 640px) {
            .ct-modal { padding: 16px; }
            .ct-form-grid { grid-template-columns: 1fr; }
            .ct-form-grid .full { grid-column: 1; }
        }

        @media (max-height: 500px) and (orientation: landscape) {
            .ct-modal-overlay { align-items: flex-start; padding: 8px; }
            .ct-modal {
                width: 100%;
                max-width: 760px;
                max-height: calc(100vh - 16px);
                padding: 14px 16px;
            }
            .ct-modal h3 { margin-bottom: 10px; }
            .ct-form-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .ct-form-grid .full { grid-column: 1 / -1; }
        }

        .ct-modal h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--vo-text);
            margin-bottom: 16px;
        }

        .ct-field label,
        .ct-modal label {
            display: block;
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--vo-text-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 6px;
        }

        .ct-field input:not([type="checkbox"]),
        .ct-field select,
        .ct-field textarea,
        .ct-modal input:not([type="checkbox"]),
        .ct-modal select,
        .ct-modal textarea {
            width: 100%;
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            color: var(--vo-text);
            border-radius: .5rem;
            padding: 9px 12px;
            font-size: 0.82rem;
            outline: none;
            transition: border-color .1s, box-shadow .1s;
        }

        .ct-field input:focus,
        .ct-field select:focus,
        .ct-field textarea:focus,
        .ct-modal input:focus,
        .ct-modal select:focus,
        .ct-modal textarea:focus {
            border-color: var(--vo-accent);
            box-shadow: 0 0 0 3px rgba(251,186,0,.15);
        }

        .ct-checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border: 1px solid var(--vo-border);
            border-radius: .5rem;
            background: var(--vo-bg);
            font-size: 0.8rem;
            color: var(--vo-text-secondary);
            cursor: pointer;
        }

        .ct-checkbox-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--vo-accent);
        }

        .ct-modal-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--vo-border);
        }

        .ct-btn-primary {
            background: var(--vo-accent);
            color: #111;
            border: none;
            border-radius: .375rem;
            padding: 7px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
        }

        .ct-btn-danger {
            background: transparent;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            border-radius: .375rem;
            padding: 7px 12px;
            font-size: 0.78rem;
            cursor: pointer;
        }

        .ct-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .ct-form-grid .full { grid-column: 1 / -1; }

        .ct-header-form {
            padding: 20px 24px;
            border-bottom: 1px solid var(--vo-border);
            background: var(--vo-bg);
        }

        .ct-header-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 16px 20px;
            align-items: start;
        }

        .ct-header-grid .span-6 { grid-column: span 6; }
        .ct-header-grid .span-4 { grid-column: span 4; }
        .ct-header-grid .span-3 { grid-column: span 3; }
        .ct-header-grid .span-12 { grid-column: span 12; }

        @media (max-width: 900px) {
            .ct-header-grid .span-6,
            .ct-header-grid .span-4,
            .ct-header-grid .span-3 { grid-column: span 12; }
        }

        .ct-header-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--vo-border-light);
        }

        /* ── Editor de Fases — painel lateral (drawer) ── */
        .ct-editor-shell {
            display: flex;
            flex-direction: row;
            min-height: 60vh;
            width: 100%;
            max-width: 100%;
            overflow: hidden;        /* impede que o Gantt vaze pra fora do card */
            isolation: isolate;      /* novo stacking context p/ position:sticky funcionar dentro */
        }
        .ct-editor-main {
            flex: 1 1 0;
            min-width: 0;            /* permite shrink em flex (sem isso, conteúdo expande) */
            display: flex;
            flex-direction: column;
            overflow: hidden;        /* confina o scroll do Gantt no .ct-gantt-wrap */
        }
        .ct-editor-fases-panel {
            width: 0;
            flex-shrink: 0;
            overflow: hidden;
            transition: width 0.22s ease;
            border-left: 1px solid transparent;
            background: var(--vo-bg);
            display: flex;
            flex-direction: column;
            max-height: 80vh;
        }
        .ct-editor-fases-panel.open {
            width: 560px;
            border-left-color: var(--vo-border);
        }
        @media (max-width: 1100px) {
            .ct-editor-fases-panel.open { width: 480px; }
        }
        .ct-editor-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--vo-border);
            background: var(--vo-bg-subtle);
            flex-shrink: 0;
            gap: 10px;
        }
        .ct-editor-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .ct-ef-card {
            border: 1px solid var(--vo-border);
            border-radius: 0.5rem;
            background: var(--vo-bg);
            overflow: hidden;
        }
        .ct-ef-card-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            background: var(--vo-bg-subtle);
            cursor: pointer;
            user-select: none;
            min-height: 38px;
        }
        .ct-ef-card-head:hover { background: var(--vo-bg-hover, var(--vo-bg-subtle)); }
        .ct-ef-card-body {
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-top: 1px solid var(--vo-border);
        }
        .ct-ef-section-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--vo-text-muted);
            margin-bottom: 4px;
        }
        .ct-ef-dep-row {
            display: grid;
            grid-template-columns: 1fr 100px 60px 22px;
            gap: 4px;
            align-items: center;
        }
        .ct-ef-input {
            padding: 5px 8px;
            border: 1px solid var(--vo-border);
            border-radius: 0.3rem;
            font-size: 0.78rem;
            background: var(--vo-bg);
            color: var(--vo-text);
            width: 100%;
            box-sizing: border-box;
        }
        .ct-ef-input:focus { outline: none; border-color: var(--vo-accent); }
        .ct-ef-btn-ghost {
            background: transparent;
            border: 1px solid var(--vo-border);
            border-radius: 0.3rem;
            cursor: pointer;
            padding: 4px 10px;
            font-size: 0.73rem;
            color: var(--vo-text-secondary);
            white-space: nowrap;
        }
        .ct-ef-btn-ghost:hover { border-color: var(--vo-accent); color: var(--vo-accent); }
        .ct-ef-btn-icon {
            background: transparent;
            border: 1px solid var(--vo-border);
            border-radius: 0.25rem;
            cursor: pointer;
            padding: 2px 5px;
            font-size: 0.65rem;
            line-height: 1.2;
            color: var(--vo-text-secondary);
        }
        .ct-ef-btn-icon:disabled { opacity: 0.25; cursor: not-allowed; }
        .ct-ef-drag-handle {
            cursor: grab;
            color: var(--vo-text-faint);
            padding: 4px 3px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            border-radius: 3px;
        }
        .ct-ef-drag-handle:hover { color: var(--vo-text-secondary); background: var(--vo-bg); }
        .ct-ef-card--dragging { opacity: 0.3; }
        .ct-ef-card--oculta {
            opacity: 0.55;
            filter: grayscale(0.6);
        }
        .ct-ef-card--oculta .ct-ef-card-head {
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 4px,
                color-mix(in srgb, var(--vo-border) 40%, transparent) 4px,
                color-mix(in srgb, var(--vo-border) 40%, transparent) 8px
            );
        }
        .ct-ef-card--over {
            border-color: var(--vo-accent) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--vo-accent) 20%, transparent);
        }
        .ct-ef-btn-remove {
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--vo-text-faint);
            font-size: 1rem;
            line-height: 1;
            padding: 0 3px;
            flex-shrink: 0;
        }
        .ct-ef-btn-remove:hover { color: #ef4444; }

        /* Banner de "alterações não salvas" */
        .ct-dirty-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 9px 16px;
            background: #fef3c7;
            color: #92400e;
            border-bottom: 1px solid #fde68a;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .ct-dirty-banner .ct-dirty-actions { display: flex; gap: 8px; }
        .ct-dirty-banner button {
            border: 1px solid;
            border-radius: .375rem;
            padding: 5px 14px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }
        .ct-dirty-banner .ct-dirty-discard {
            background: transparent;
            color: #92400e;
            border-color: #fbbf24;
        }
        .ct-dirty-banner .ct-dirty-save {
            background: var(--vo-accent);
            color: #111;
            border-color: var(--vo-accent);
        }
        .ct-error-banner {
            padding: 8px 16px;
            background: #fee2e2;
            color: #991b1b;
            border-bottom: 1px solid #fca5a5;
            font-size: 0.75rem;
        }

        /* Gantt simulado dentro do template */
        .ct-gantt-wrap {
            flex: 1 1 0;
            overflow: auto;
            padding: 14px 16px;
            position: relative;       /* âncora do sticky interno */
            max-width: 100%;
            min-width: 0;
        }
        .ct-gantt-table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.74rem;
            width: max-content;
            min-width: 100%;
            table-layout: auto;
        }
        .ct-gantt-table th, .ct-gantt-table td {
            padding: 4px 6px;
            border-right: 1px solid var(--vo-border-light);
            border-bottom: 1px solid var(--vo-border-light);
            white-space: nowrap;
            background: var(--vo-bg);
        }
        .ct-gantt-table th.ct-gantt-fase-col,
        .ct-gantt-table td.ct-gantt-fase-col {
            position: sticky;
            left: 0;
            z-index: 3;
            background: var(--vo-bg);
            font-weight: 600;
            width: 220px;
            min-width: 220px;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 1px 0 0 var(--vo-border-light); /* divisória visível ao rolar */
        }
        .ct-gantt-table thead th.ct-gantt-fase-col { z-index: 5; }
        .ct-gantt-table thead th {
            background: var(--vo-bg-subtle);
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--vo-text-muted);
            text-transform: uppercase;
            letter-spacing: .04em;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .ct-gantt-table thead th.ct-gantt-fase-col { z-index: 4; }
        .ct-gantt-bar {
            display: inline-block;
            min-width: 100%;
            height: 14px;
            border-radius: 3px;
            background: var(--vo-accent);
        }
        .ct-gantt-day-cell {
            width: 22px;
            min-width: 22px;
            text-align: center;
            font-size: 0.6rem;
            color: var(--vo-text-faint);
            position: relative;
            cursor: pointer;
            transition: background 0.08s;
        }
        .ct-gantt-day-cell.ct-gantt-day-weekend {
            background: color-mix(in srgb, var(--vo-bg-subtle) 60%, transparent);
        }
        .ct-gantt-day-cell.ct-gantt-day-active {
            background: color-mix(in srgb, var(--vo-accent) 35%, transparent);
        }
        .ct-gantt-cell-num {
            display: inline-block;
            font-size: 0.58rem;
            font-weight: 700;
            color: #5b3a00;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        /* Linha de contagem absoluta de dias */
        .ct-gantt-abs-cell {
            width: 22px;
            min-width: 22px;
            text-align: center;
            font-size: 0.55rem;
            font-weight: 700;
            color: var(--vo-text-muted);
            background: var(--vo-bg-subtle);
            border-bottom: 2px solid var(--vo-border);
            font-variant-numeric: tabular-nums;
            cursor: pointer;
        }
        .ct-gantt-abs-cell.ct-gantt-day-weekend {
            background: color-mix(in srgb, var(--vo-bg-subtle) 80%, transparent);
        }
        /* Cross-highlight: hover/click destaca linha e coluna */
        .ct-gantt-table tbody tr.ct-gantt-row-on > td {
            background: color-mix(in srgb, var(--vo-accent) 10%, var(--vo-bg));
        }
        .ct-gantt-table tbody tr.ct-gantt-row-on > td.ct-gantt-day-active {
            background: color-mix(in srgb, var(--vo-accent) 50%, transparent);
        }
        .ct-gantt-table tbody tr.ct-gantt-row-on > td.ct-gantt-fase-col {
            background: color-mix(in srgb, var(--vo-accent) 18%, var(--vo-bg));
            font-weight: 700;
        }
        .ct-gantt-table .ct-gantt-col-on {
            background: color-mix(in srgb, var(--vo-accent) 12%, var(--vo-bg)) !important;
        }
        .ct-gantt-table .ct-gantt-day-active.ct-gantt-col-on {
            background: color-mix(in srgb, var(--vo-accent) 55%, transparent) !important;
        }
        .ct-gantt-table .ct-gantt-cell-selected {
            outline: 2px solid var(--vo-accent);
            outline-offset: -2px;
            z-index: 1;
        }
        /* Linha de fase marcada como oculta — mesmo padrão visual do card no painel */
        .ct-gantt-table tbody tr.ct-gantt-row-oculta > td {
            opacity: 0.5;
            filter: grayscale(0.7);
        }
        .ct-gantt-table tbody tr.ct-gantt-row-oculta > td.ct-gantt-fase-col {
            background: repeating-linear-gradient(
                45deg,
                var(--vo-bg),
                var(--vo-bg) 4px,
                color-mix(in srgb, var(--vo-border) 40%, transparent) 4px,
                color-mix(in srgb, var(--vo-border) 40%, transparent) 8px
            );
            color: var(--vo-text-muted);
            font-style: italic;
        }
        .ct-gantt-table tbody tr.ct-gantt-row-oculta > td.ct-gantt-day-active {
            background: color-mix(in srgb, var(--vo-text-muted) 25%, transparent);
        }
        .ct-gantt-anchor-input {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            padding: 6px 12px;
            background: var(--vo-bg-subtle);
            border: 1px solid var(--vo-border);
            border-radius: .375rem;
        }
        .ct-gantt-anchor-input input[type="date"] {
            border: none;
            background: transparent;
            color: var(--vo-text);
            font-size: 0.78rem;
            padding: 0;
            outline: none;
        }
    </style>

    <div class="ct-card vo-theme-cronograma-templates">
        {{-- ===================== MACRO: lista de templates ===================== --}}
        @if(!$modoIndividual)
            <div class="ct-filters">
                <button class="vo-btn-outline" wire:click="novoTemplate">+ Novo template</button>
                <span style="color:var(--vo-text-muted);font-size:0.75rem;">
                    {{ $templates->count() }} template(s)
                </span>
            </div>

            @if($templates->isEmpty())
                <div class="ct-empty">
                    Nenhum template cadastrado. Clique em "Novo template" para começar.
                </div>
            @else
                <div class="ct-table-wrap">
                    <table class="ct-table">
                        <thead>
                            <tr>
                                <th class="ct-th-sticky">Nome</th>
                                <th>Tipo de obra</th>
                                <th>Âncora</th>
                                <th>Variante</th>
                                <th style="text-align:center;">Fases</th>
                                <th style="text-align:center;">Duração total</th>
                                <th style="text-align:center;">Ativo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $tpl)
                                @php
                                    $modoTpl = $tpl->modo_ancora?->value ?? 'posse';
                                    $corModoTpl = $modoTpl === 'posse' ? '#f59e0b' : '#10b981';
                                    $corModoTplBg = $modoTpl === 'posse' ? 'rgba(245,158,11,.12)' : 'rgba(16,185,129,.12)';
                                @endphp
                                <tr class="ct-table-row" wire:click="selecionarTemplate({{ $tpl->id }})">
                                    <td class="ct-td-sticky">
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="display:inline-flex;align-items:center;padding:1px 7px;font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-radius:99px;background:{{ $corModoTplBg }};color:{{ $corModoTpl }};border:1px solid {{ $corModoTpl }};flex-shrink:0;">
                                                {{ strtoupper($modoTpl) }}
                                            </span>
                                            <span>{{ $tpl->nome }}</span>
                                        </div>
                                    </td>
                                    <td><span class="ct-badge">{{ $tpl->tipo_obra->label() }}</span></td>
                                    <td style="font-family:monospace;font-size:0.72rem;color:var(--vo-text-muted);">
                                        {{ $tpl->ancora_campo }}
                                    </td>
                                    <td>
                                        @if($tpl->pareado)
                                            @php
                                                $modoPar = $tpl->pareado->modo_ancora?->value ?? 'posse';
                                                $corPar = $modoPar === 'posse' ? '#f59e0b' : '#10b981';
                                                $corParBg = $modoPar === 'posse' ? 'rgba(245,158,11,.10)' : 'rgba(16,185,129,.10)';
                                            @endphp
                                            <div style="display:inline-flex;align-items:center;gap:6px;padding:3px 8px;border-radius:.375rem;background:{{ $corParBg }};border:1px solid {{ $corPar }};">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="{{ $corPar }}" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                                                <span style="font-size:0.68rem;font-weight:600;color:{{ $corPar }};text-transform:uppercase;letter-spacing:.04em;">{{ strtoupper($modoPar) }}</span>
                                                <span style="font-size:0.7rem;color:var(--vo-text-secondary);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $tpl->pareado->nome }}">
                                                    {{ $tpl->pareado->nome }}
                                                </span>
                                            </div>
                                        @else
                                            <span style="font-size:0.7rem;color:var(--vo-text-faint);font-style:italic;">sem variante</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;font-variant-numeric:tabular-nums;">{{ $tpl->fases_count }}</td>
                                    <td style="text-align:center;font-variant-numeric:tabular-nums;">
                                        @php $dur = $duracoes[$tpl->id] ?? 0; @endphp
                                        @if($dur > 0)
                                            {{ $dur }} d
                                        @else
                                            <span style="color:var(--vo-text-muted);">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($tpl->ativo)
                                            <span class="ct-badge ct-badge-success">Ativo</span>
                                        @else
                                            <span class="ct-badge ct-badge-muted">Inativo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            {{-- ===================== INDIVIDUAL: edição do template ===================== --}}
            <div class="ct-filters">
                <button class="vo-btn-outline" wire:click="voltarParaMacro">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Voltar
                </button>
                <div style="display:flex;align-items:center;gap:10px;padding:6px 14px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--vo-accent)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    <span style="color:var(--vo-text);font-weight:800;font-size:1.15rem;letter-spacing:-0.02em;">
                        {{ $template->nome }}
                    </span>
                    <span class="ct-badge">{{ $template->tipo_obra->label() }}</span>
                    <span class="ct-badge ct-badge-accent" title="Duração total calculada do cronograma">
                        ⏱ {{ $duracaoTotalDias }} dias
                    </span>
                </div>

                <div style="margin-left:auto;display:flex;gap:6px;">
                    <button class="vo-btn-outline" wire:click="duplicarTemplate">Duplicar</button>
                    <button class="vo-btn-outline" wire:click="excluirTemplate"
                            onclick="return confirm('Excluir este template?')"
                            style="color:#b91c1c;border-color:#fca5a5;">
                        Excluir
                    </button>
                </div>
            </div>

            {{-- Seletor de variante POSSE / OBRAS — mesmo padrão visual do card de âncora da página do projeto. --}}
            @php
                $tplModoCor = $tplModoAncora === 'posse' ? '#f59e0b' : '#10b981';
                $tplModoCorBg = $tplModoAncora === 'posse' ? 'rgba(245,158,11,.10)' : 'rgba(16,185,129,.10)';
                $tplModoNome = $tplModoAncora === 'posse' ? 'Posse' : 'Obras';
                $tplDescricaoModo = $tplModoAncora === 'posse'
                    ? 'Variante ancorada na Posse. Cronograma se recalcula quando a data de posse muda — use para o planejamento inicial.'
                    : 'Variante ancorada em Obras. A data de posse não recalcula o cronograma — use durante a execução.';
                $ativoPosseTpl = $tplModoAncora === 'posse';
                $ativoObrasTpl = $tplModoAncora === 'obras';
            @endphp
            <div style="margin-bottom:12px;padding:12px 14px;border:1px solid {{ $tplModoCor }};border-left-width:4px;border-radius:.5rem;background:{{ $tplModoCorBg }};">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                    <span style="font-size:0.72rem;font-weight:700;color:var(--vo-text-secondary);text-transform:uppercase;letter-spacing:.04em;">
                        Variante do template
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:2px 8px;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;border-radius:99px;background:{{ $tplModoCor }};color:#fff;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Editando: {{ $tplModoNome }}
                    </span>
                </div>
                <p style="font-size:0.72rem;color:var(--vo-text-muted);margin:0 0 10px;">
                    {{ $tplDescricaoModo }}
                </p>
                <div style="display:flex;gap:6px;">
                    <button type="button"
                            wire:click="irParaVariante('posse')"
                            @disabled($ativoPosseTpl)
                            title="{{ $ativoPosseTpl ? 'Já está editando a variante POSSE' : 'Alternar para a variante POSSE' }}"
                            style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:7px 10px;font-size:0.75rem;font-weight:600;border-radius:.375rem;cursor:{{ $ativoPosseTpl ? 'default' : 'pointer' }};border:1px solid {{ $ativoPosseTpl ? '#f59e0b' : 'var(--vo-border)' }};{{ $ativoPosseTpl ? 'background:#f59e0b;color:#fff;box-shadow:0 1px 2px rgba(245,158,11,.3);' : 'background:var(--vo-bg);color:var(--vo-text-muted);' }}">
                        @if($ativoPosseTpl)
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                        Variante Posse
                    </button>
                    <button type="button"
                            wire:click="irParaVariante('obras')"
                            @disabled($ativoObrasTpl)
                            title="{{ $ativoObrasTpl ? 'Já está editando a variante OBRAS' : 'Alternar para a variante OBRAS' }}"
                            style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:7px 10px;font-size:0.75rem;font-weight:600;border-radius:.375rem;cursor:{{ $ativoObrasTpl ? 'default' : 'pointer' }};border:1px solid {{ $ativoObrasTpl ? '#10b981' : 'var(--vo-border)' }};{{ $ativoObrasTpl ? 'background:#10b981;color:#fff;box-shadow:0 1px 2px rgba(16,185,129,.3);' : 'background:var(--vo-bg);color:var(--vo-text-muted);' }}">
                        @if($ativoObrasTpl)
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                        Variante Obras
                    </button>
                </div>
                @if(! $tplPareadoId)
                    @php $modoOposto = $tplModoAncora === 'posse' ? 'obras' : 'posse'; @endphp
                    <div style="margin-top:10px;padding-top:10px;border-top:1px dashed {{ $tplModoCor }};display:flex;flex-direction:column;gap:8px;">
                        <div style="font-size:0.65rem;font-weight:700;color:var(--vo-text-muted);text-transform:uppercase;letter-spacing:.05em;">
                            Definir variante pareada
                        </div>
                        <button type="button" wire:click="criarVariantePareada"
                                wire:confirm="Criar variante {{ strtoupper($modoOposto) }} a partir deste template? Fases, dependências e itens serão duplicados."
                                style="width:100%;padding:7px 10px;font-size:0.72rem;font-weight:600;border:1px dashed var(--vo-border);background:transparent;border-radius:.375rem;cursor:pointer;color:var(--vo-text-secondary);display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Criar nova variante {{ strtoupper($modoOposto) }} (duplicar este)
                        </button>
                        @if(isset($candidatosPareamento) && $candidatosPareamento->isNotEmpty())
                            <div style="display:flex;align-items:center;gap:8px;font-size:0.65rem;color:var(--vo-text-muted);text-transform:uppercase;letter-spacing:.05em;">
                                <span style="flex:1;height:1px;background:var(--vo-border);"></span>
                                <span>ou</span>
                                <span style="flex:1;height:1px;background:var(--vo-border);"></span>
                            </div>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <select x-data @change="if ($event.target.value) $wire.parearComTemplate(parseInt($event.target.value))"
                                        style="flex:1;padding:6px 8px;font-size:0.72rem;border:1px solid var(--vo-border);border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);">
                                    <option value="">Parear com template existente ({{ strtoupper($modoOposto) }})…</option>
                                    @foreach($candidatosPareamento as $cand)
                                        <option value="{{ $cand->id }}">{{ $cand->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @else
                    <div style="margin-top:10px;padding-top:10px;border-top:1px dashed {{ $tplModoCor }};display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                        <div style="display:inline-flex;align-items:center;gap:5px;font-size:0.7rem;color:var(--vo-text-muted);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                            Template pareado · pode alternar entre as variantes acima
                        </div>
                        <button type="button" wire:click="desfazerPareamento"
                                wire:confirm="Desfazer o pareamento entre as duas variantes? Os dois templates continuam existindo, mas perdem a relação."
                                style="padding:4px 10px;font-size:0.68rem;font-weight:600;border:1px solid var(--vo-border);background:transparent;border-radius:.375rem;cursor:pointer;color:var(--vo-text-muted);display:inline-flex;align-items:center;gap:5px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Desfazer pareamento
                        </button>
                    </div>
                @endif
            </div>

            {{-- Cabeçalho do template --}}
            <div class="ct-header-form">
                <div class="ct-header-grid">
                    <div class="ct-field span-6">
                        <label>Nome</label>
                        <input type="text" wire:model="tplNome" placeholder="Ex.: Expansão — Progressivo Assinatura">
                    </div>

                    <div class="ct-field span-6">
                        <label>Âncora</label>
                        <select wire:model="tplAncoraCampo">
                            @foreach($ancoraOptions as $valor => $label)
                                <option value="{{ $valor }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ct-field span-6">
                        <label>Tipo de obra</label>
                        <select wire:model="tplTipoObra">
                            @foreach($tipoObraOptions as $opt)
                                <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="span-6" style="display:flex;flex-direction:column;gap:8px;">
                        <label class="ct-checkbox-row">
                            <input type="checkbox" wire:model="tplAtivo">
                            <span>Template ativo</span>
                        </label>
                    </div>

                    <div class="ct-field span-12">
                        <label>Observações</label>
                        <textarea wire:model="tplObservacoes" rows="3" placeholder="Notas livres sobre o propósito deste template..."></textarea>
                    </div>
                </div>

                <div class="ct-header-actions" style="display:flex;gap:8px;">
                    <button class="ct-btn-primary" wire:click="salvarTemplate">Salvar configuração</button>
                </div>
            </div>

            {{-- ===================== Shell: Gantt simulado + Drawer "Editar fases" ===================== --}}
            @include('filament.pages.partials.cronograma-templates-shell', [
                'fases' => $fases,
                'fasesAdicionaveis' => $fasesAdicionaveis,
                'gatilhoOptions' => $gatilhoOptions,
                'tipoDiasOptions' => $tipoDiasOptions,
            ])
        @endif
    </div>


    {{-- ===================== MODAL: importação de template ===================== --}}
</x-filament-panels::page>
