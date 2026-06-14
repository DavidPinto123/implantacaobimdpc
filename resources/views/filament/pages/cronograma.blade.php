<x-filament-panels::page>
    <style>
        :root {
            --cr-nao-iniciado: #6b7280;
            --cr-em-andamento: #3b82f6;
            --cr-concluido: #22c55e;
            --cr-atrasado: #ef4444;
            --cr-bloqueado: #eab308;
            --cr-previsto: rgba(0,0,0,0.08);
            --cr-previsto-border: rgba(0,0,0,0.15);
            --cr-today: #ef4444;
            --cr-row-height: 40px;
        }
        .dark {
            --cr-nao-iniciado: #6b7280;
            --cr-em-andamento: #4a9eff;
            --cr-concluido: #2dd67c;
            --cr-atrasado: #ff4d6a;
            --cr-bloqueado: #f5ba00;
            --cr-previsto: rgba(255,255,255,0.08);
            --cr-previsto-border: rgba(255,255,255,0.2);
            --cr-today: #ff4d6a;
        }

        .cr-card {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 0.75rem;
            overflow: visible;
            box-shadow: var(--vo-shadow);
        }

        .cr-filters {
            display: flex;
            gap: 10px;
            padding: 10px 16px;
            background: var(--vo-bg);
            border-bottom: 1px solid var(--vo-border);
            flex-wrap: wrap;
            align-items: center;
            position: sticky;
            top: 4rem;
            z-index: 15;
        }

        .cr-filters select,
        .cr-filters input[type="text"] {
            background: var(--vo-bg-subtle);
            color: var(--vo-text);
            border: 1px solid var(--vo-border);
            border-radius: 0.5rem;
            padding: 7px 12px;
            font-size: 0.78rem;
            min-width: 140px;
            font-family: inherit;
        }

        .cr-filters select:focus,
        .cr-filters input:focus {
            outline: none;
            border-color: var(--vo-accent);
            box-shadow: 0 0 0 3px rgba(251,186,0,.2);
        }

        .cr-container {
            display: flex;
            overflow-x: auto;
        }

        .cr-left {
            flex-shrink: 0;
            width: var(--cr-left-w, 540px);
            border-right: 1px solid var(--vo-border);
            background: var(--vo-bg);
            position: sticky;
            left: 0;
            z-index: 5;
            transition: width 0.18s ease;
        }
        .cr-left .cr-col-fase { width: 360px; min-width: 360px; max-width: 360px; flex-shrink: 0; display:flex; align-items:center; gap:8px; padding:0 16px; border-right:1px solid var(--vo-border-light); box-sizing:border-box; overflow:visible; }
        .cr-left .cr-col-deps { flex-shrink:0; width:180px; min-width:180px; display:flex; align-items:center; padding:0 10px; gap:4px; overflow:hidden; }
        .cr-left .cr-col-deps-inner { display:flex; flex-wrap:wrap; gap:3px; max-height:calc(var(--cr-row-height) - 8px); overflow:hidden; }
        .cr-dep-pill { display:inline-flex; align-items:center; padding:2px 6px; font-size:0.6rem; border-radius:1rem; background:var(--vo-bg-subtle); border:1px dashed var(--vo-border); color:var(--vo-text-secondary); white-space:nowrap; }
        .cr-dep-pill.cr-dep-ancora { border-style:solid; border-color:var(--vo-accent); }
        .cr-deps-wrap { position:relative; display:flex; align-items:center; gap:3px; min-width:0; }
        .cr-deps-tooltip { display:none; position:absolute; left:0; top:100%; margin-top:4px; z-index:50; background:var(--vo-bg); border:1px solid var(--vo-border); border-radius:.5rem; padding:6px 10px; box-shadow:0 4px 12px rgba(0,0,0,.15); min-width:180px; max-width:320px; }
        .cr-deps-wrap:hover .cr-deps-tooltip { display:flex; flex-direction:column; gap:3px; }

        .cr-right {
            flex-shrink: 0;
            position: relative;
        }

        .cr-header-left {
            height: 44px;
            display: flex;
            align-items: center;
            padding: 0 16px;
            font-weight: 700;
            font-size: 0.65rem;
            color: var(--vo-text-faint);
            text-transform: uppercase;
            letter-spacing: .08em;
            background: var(--vo-bg);
            border-bottom: 1px solid var(--vo-border);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .cr-header-right {
            height: 44px;
            display: flex;
            position: sticky;
            top: 0;
            z-index: 5;
            background: var(--vo-bg);
            border-bottom: 1px solid var(--vo-border);
        }

        .cr-month {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--vo-text-faint);
            text-transform: uppercase;
            letter-spacing: .05em;
            border-right: 1px solid var(--vo-border-light);
            white-space: nowrap;
        }

        .cr-row-left {
            height: var(--cr-row-height);
            display: flex;
            align-items: stretch;
            padding: 0;
            font-size: 0.78rem;
            color: var(--vo-text-secondary);
            border-bottom: 1px solid var(--vo-border-light);
            cursor: pointer;
            transition: background 0.15s;
            gap: 0;
        }

        .cr-row-left:hover { background: var(--vo-bg-subtle); }

        .cr-row-left .marco-badge {
            width: 8px;
            height: 8px;
            transform: rotate(45deg);
            flex-shrink: 0;
        }

        .cr-row-left .atraso-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--cr-atrasado, #ef4444);
            flex-shrink: 0;
            box-shadow: 0 0 0 1px rgba(239,68,68,.25);
        }

        .cr-row-left .fase-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1;
            min-width: 0;
        }

        .cr-row-left .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .cr-row-right {
            height: var(--cr-row-height);
            position: relative;
            border-bottom: 1px solid var(--vo-border-light);
        }

        .cr-bar {
            position: absolute;
            top: 8px;
            height: 24px;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.15s;
            z-index: 2;
            min-width: 4px;
        }

        .cr-bar:hover {
            opacity: 0.85;
            transform: scaleY(1.1);
        }

        .cr-bar-previsto {
            background: var(--cr-previsto);
            border: 1px dashed var(--cr-previsto-border);
            z-index: 1;
        }

        .cr-bar-marco {
            width: 16px !important;
            height: 16px !important;
            top: 12px;
            transform: rotate(45deg);
            border-radius: 2px;
            min-width: 16px;
        }

        .cr-bar-marco:hover { transform: rotate(45deg) scale(1.2); }

        .cr-today-line {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--cr-today);
            z-index: 3;
            pointer-events: none;
        }

        .cr-today-label {
            position: absolute;
            top: 2px;
            transform: translateX(-50%);
            font-size: 0.6rem;
            color: var(--cr-today);
            font-weight: 700;
            white-space: nowrap;
            z-index: 4;
        }

        .cr-legend {
            display: flex;
            gap: 16px;
            padding: 10px 16px;
            background: var(--vo-bg);
            border-top: 1px solid var(--vo-border);
            flex-wrap: wrap;
        }

        .cr-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            color: var(--vo-text-muted);
        }

        .cr-legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        .cr-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: var(--vo-bg);
            border-top: 1px solid var(--vo-border);
        }

        .cr-pagination span {
            color: var(--vo-text-muted);
            font-size: 0.78rem;
        }

        .cr-obra-header {
            padding: 0;
            background: var(--vo-bg);
            border-bottom: 1px solid var(--vo-border);
        }

        .cr-obra-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 20px;
            background: var(--vo-accent);
            color: #111;
        }
        .dark .cr-obra-header-top {
            background: rgba(251,186,0,.85);
        }

        .cr-obra-title {
            font-size: 0.95rem;
            font-weight: 700;
        }

        .cr-obra-meta {
            font-size: 0.73rem;
            opacity: 0.7;
            margin-top: 2px;
        }

        .cr-obra-progress {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cr-obra-progress-bar {
            width: 120px;
            height: 8px;
            background: rgba(0,0,0,.15);
            border-radius: 4px;
            overflow: hidden;
        }

        .cr-obra-progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }

        .cr-obra-progress-text {
            font-size: 0.85rem;
            font-weight: 700;
        }

        .cr-obra-header-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px 20px;
            padding: 14px 20px;
        }

        .cr-detail-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .cr-detail-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--vo-text-faint);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .cr-detail-value {
            font-size: 0.78rem;
            color: var(--vo-text);
            font-weight: 500;
        }

        .cr-stats-row {
            display: flex;
            gap: 16px;
            padding: 10px 20px;
            border-top: 1px solid var(--vo-border-light);
            flex-wrap: wrap;
        }

        .cr-stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.73rem;
            color: var(--vo-text-muted);
        }

        .cr-stat-number {
            font-weight: 700;
            font-size: 0.85rem;
        }

        .vo-btn-outline {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            font-size: 0.75rem;
            padding: 7px 16px;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            color: var(--vo-text-secondary);
            white-space: nowrap;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: border-color 0.15s;
        }

        .vo-btn-outline:hover { border-color: var(--vo-text-muted); }

        .vo-btn-accent {
            background: var(--vo-accent);
            border: none;
            font-size: 0.75rem;
            padding: 7px 16px;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            color: #111;
            white-space: nowrap;
            font-family: inherit;
            transition: opacity 0.15s;
        }
        .vo-btn-accent:hover { opacity: 0.9; }

        .cr-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cr-modal {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 0.75rem;
            padding: 24px;
            width: 480px;
            max-width: 95vw;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
        }
        .dark .cr-modal {
            box-shadow: 0 8px 30px rgba(0,0,0,.5);
        }

        .cr-modal h3 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--vo-text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--vo-accent);
        }

        .cr-modal label {
            display: block;
            font-size: 0.65rem;
            color: var(--vo-text-faint);
            margin-bottom: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .cr-modal input:not([type="range"]),
        .cr-modal select,
        .cr-modal textarea {
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
            background: var(--vo-bg-subtle);
            color: var(--vo-text);
            border: 1px solid var(--vo-border);
            border-radius: 0.5rem;
            padding: 8px 12px;
            font-size: 0.78rem;
            margin-bottom: 14px;
            font-family: inherit;
        }

        .cr-modal input:not([type="range"]):focus,
        .cr-modal select:focus,
        .cr-modal textarea:focus {
            outline: none;
            border-color: var(--vo-accent);
            box-shadow: 0 0 0 3px rgba(251,186,0,.2);
        }

        @media (max-width: 640px) {
            .cr-modal { padding: 16px; width: calc(100vw - 24px); }
            .cr-modal-grid-2 { grid-template-columns: 1fr !important; }
            .cr-modal-actions { flex-wrap: wrap; }
            .cr-modal-actions button { flex: 1 1 auto; }
        }

        /* Mobile landscape: viewport baixo, modal precisa ocupar largura total e
           rolar internamente sem cortar conteudo. */
        @media (max-height: 500px) and (orientation: landscape) {
            .cr-modal-overlay { align-items: flex-start; padding: 8px; }
            .cr-modal {
                width: 100%;
                max-width: 720px;
                max-height: calc(100vh - 16px);
                padding: 14px 16px;
            }
            .cr-modal h3 { margin-bottom: 10px; padding-bottom: 6px; font-size: 0.85rem; }
            .cr-modal label { margin-bottom: 2px; }
            .cr-modal input:not([type="range"]),
            .cr-modal select,
            .cr-modal textarea { margin-bottom: 8px; padding: 6px 10px; }
            .cr-modal-grid-2 { grid-template-columns: 1fr 1fr !important; gap: 8px !important; }
        }

        .cr-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 12px;
        }

        .cr-percentual {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .cr-percentual input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 4px;
            outline: none;
            border: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
            background: linear-gradient(to right, var(--vo-accent) var(--pct, 0%), var(--vo-border) var(--pct, 0%));
        }

        .cr-percentual input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--vo-accent);
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,.25);
        }

        .cr-percentual input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--vo-accent);
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,.25);
        }

        .cr-percentual input[type="range"]::-webkit-slider-runnable-track {
            height: 8px;
            border-radius: 4px;
            background: transparent;
        }

        .cr-percentual input[type="range"]::-moz-range-track {
            height: 8px;
            border-radius: 4px;
            background: transparent;
        }

        .cr-percentual span {
            font-size: 0.78rem;
            color: var(--vo-text);
            font-weight: 600;
            min-width: 40px;
            text-align: right;
        }

        .cr-empty {
            padding: 60px 20px;
            text-align: center;
            color: var(--vo-text-faint);
            font-size: 0.85rem;
        }

        .cr-status-tag {
            display: inline-block;
            font-size: 0.65rem;
            padding: 2px 10px;
            border-radius: 1rem;
            font-weight: 600;
        }

        .cr-fase-list {
            padding: 0 20px 16px;
        }

        .cr-fase-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--vo-border-light);
            cursor: pointer;
            transition: background 0.12s;
        }

        .cr-fase-row:hover {
            background: var(--vo-bg-subtle);
            margin: 0 -20px;
            padding: 10px 20px;
        }

        .cr-fase-row:last-child { border-bottom: none; }

        .cr-fase-name {
            font-size: 0.78rem;
            color: var(--vo-text-secondary);
            width: 180px;
            flex-shrink: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cr-fase-bar-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .cr-fase-dates {
            font-size: 0.65rem;
            color: var(--vo-text-faint);
            white-space: nowrap;
            flex-shrink: 0;
            font-variant-numeric: tabular-nums;
        }

        .cr-status-badge {
            display: inline-block;
            font-size: 0.6rem;
            font-weight: 600;
            color: #fff;
            padding: 2px 8px;
            border-radius: 1rem;
            white-space: nowrap;
            min-width: 80px;
            text-align: center;
            flex-shrink: 0;
        }

        .cr-status-badge-sm {
            display: inline-block;
            font-size: 0.55rem;
            font-weight: 600;
            color: #fff;
            padding: 1px 6px;
            border-radius: 1rem;
            white-space: nowrap;
            line-height: 1.4;
        }

        .cr-zoom-controls {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .cr-obra-selector {
            position: relative;
        }

        .cr-obra-search {
            background: var(--vo-bg-subtle);
            color: var(--vo-text);
            border: 1px solid var(--vo-border);
            border-radius: 0.5rem;
            padding: 7px 12px;
            font-size: 0.78rem;
            width: 260px;
            font-family: inherit;
        }

        .cr-obra-search:focus {
            outline: none;
            border-color: var(--vo-accent);
            box-shadow: 0 0 0 3px rgba(251,186,0,.2);
        }

        .cr-obra-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            width: 320px;
            max-height: 280px;
            overflow-y: auto;
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 0.5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            z-index: 50;
            margin-top: 4px;
        }
        .dark .cr-obra-dropdown {
            box-shadow: 0 8px 24px rgba(0,0,0,.5);
        }

        .cr-obra-option {
            padding: 8px 12px;
            font-size: 0.78rem;
            color: var(--vo-text-secondary);
            cursor: pointer;
            transition: background 0.1s;
            border-bottom: 1px solid var(--vo-border-light);
        }

        .cr-obra-option:last-child { border-bottom: none; }

        .cr-obra-option:hover {
            background: var(--vo-bg-subtle);
        }

        .cr-obra-option-active {
            background: rgba(251,186,0,.1);
            font-weight: 600;
            color: var(--vo-text);
        }

        /* Tabela de resumo macro */
        .cr-table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            /* max-height calculado via JS (ganttChart.init) — faz o thead ficar sticky
               relativo a este scroll container, ignorando a hierarquia do Filament */
            max-height: var(--cr-table-max-h, calc(100dvh - 200px));
        }

        .cr-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.78rem;
        }

        .cr-table th {
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
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .cr-table td {
            padding: 8px 12px;
            border-bottom: 1px solid var(--vo-border-light);
            color: var(--vo-text-secondary);
            white-space: nowrap;
        }

        .cr-table-row {
            cursor: pointer;
            transition: background 0.12s;
        }

        .cr-table-row:hover {
            background: var(--vo-bg-subtle);
        }

        .cr-td-sticky {
            position: sticky;
            left: 0;
            background: var(--vo-bg);
            z-index: 2;
        }

        .cr-table-row:hover .cr-td-sticky {
            background: var(--vo-bg-subtle);
        }

        .cr-th-sticky {
            position: sticky;
            left: 0;
            top: 0;
            z-index: 7;
            background: var(--vo-bg-subtle);
        }

        .cr-col-fase {
            width: var(--cr-fase-col-width, 220px);
            min-width: 180px;
            max-width: 600px;
        }

        /* Tabela detalhada: auto layout para a coluna FASE crescer com o conteúdo.
           Demais colunas têm largura explícita via CSS vars e não expandem. */
        .cr-table-detalhada {
            table-layout: auto;
            min-width: calc(var(--cr-fase-col-width, 220px) + 1228px);
        }
        .cr-table-detalhada col.cr-col-fase {
            width: var(--cr-fase-col-width, 220px);
        }
        .cr-table-detalhada .cr-subitem-titulo-inline {
            min-width: 0;
            width: 100%;
        }

        .cr-col-status {
            min-width: 110px;
            max-width: 130px;
        }

        /* ─── Multi-seleção e batch ──────────────────────────────────── */
        .cr-sel-check {
            width: 14px; height: 14px; cursor: pointer; flex-shrink: 0; accent-color: var(--vo-accent);
        }
        .cr-subitem-tr.cr-selected,
        .cr-table-row.cr-selected {
            background: color-mix(in srgb, var(--vo-accent) 10%, transparent) !important;
        }
        .cr-batch-toolbar {
            position: sticky;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 30;
            background: var(--vo-bg);
            border-top: 2px solid var(--vo-accent);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            box-shadow: 0 -4px 16px rgba(0,0,0,.15);
        }
        .cr-batch-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--vo-text);
            margin-right: 4px;
        }
        .cr-batch-btn {
            padding: 4px 12px;
            font-size: 0.72rem;
            font-weight: 600;
            border-radius: .3rem;
            border: 1px solid var(--vo-border);
            background: var(--vo-bg-subtle);
            color: var(--vo-text);
            cursor: pointer;
            white-space: nowrap;
        }
        .cr-batch-btn:hover { background: var(--vo-bg-hover, var(--vo-bg-subtle)); }
        .cr-batch-btn--danger { border-color: #ef4444; color: #ef4444; }
        .cr-batch-btn--danger:hover { background: rgba(239,68,68,.1); }
        .cr-batch-btn--primary { border-color: var(--vo-accent); color: var(--vo-accent); }
        .cr-batch-btn--cancel { color: var(--vo-text-faint); }
        .cr-batch-sep { width: 1px; height: 20px; background: var(--vo-border); margin: 0 4px; }

        /* Drag-drop subitens */
        .cr-subitem-tr[draggable="true"] { cursor: grab; }
        .cr-subitem-tr[draggable="true"]:active { cursor: grabbing; }
        .cr-item-dragging { opacity: 0.4; }
        .cr-item-dragover td:first-child { border-top: 2px solid var(--vo-accent) !important; }
        .cr-fase-dragover-target td { background: color-mix(in srgb, var(--vo-accent) 8%, transparent) !important; }
        .cr-drag-handle {
            cursor: grab;
            color: var(--vo-text-faint);
            padding: 0 3px;
            flex-shrink: 0;
            font-size: 0.7rem;
            line-height: 1;
            user-select: none;
        }
        .cr-drag-handle:active { cursor: grabbing; }

        /* Drag-drop de fases */
        .cr-drag-handle-fase {
            cursor: grab;
            color: var(--vo-text-faint);
            padding: 0 3px;
            font-size: 1rem;
            flex-shrink: 0;
            line-height: 1;
            user-select: none;
            opacity: 0.5;
        }
        .cr-drag-handle-fase:hover { opacity: 1; color: var(--vo-text); }
        .cr-drag-handle-fase:active { cursor: grabbing; }
        .cr-table-row.cr-fase-dragging td { opacity: 0.35; }
        .cr-table-row.cr-fase-reorder-target td { border-top: 2px solid var(--vo-accent) !important; }

        /* Modal overlay genérico para batch */
        .cr-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(0,0,0,.45);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cr-modal-box {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: .6rem;
            padding: 20px 24px;
            min-width: 300px;
            max-width: 480px;
            box-shadow: 0 8px 32px rgba(0,0,0,.25);
        }
        .cr-modal-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--vo-text);
            margin-bottom: 14px;
        }
        .cr-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 16px;
        }

        .cr-fase-resize-handle {
            position: absolute;
            right: -7px;
            top: 0;
            bottom: 0;
            width: 14px;
            cursor: col-resize;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--vo-text-muted);
            user-select: none;
            z-index: 10;
            background: transparent;
            transition: background-color .15s, color .15s;
        }
        .cr-fase-resize-handle::before {
            content: "";
            display: block;
            width: 2px;
            height: 60%;
            background: currentColor;
            box-shadow: 3px 0 0 currentColor;
            border-radius: 1px;
        }
        .cr-fase-resize-handle:hover,
        .cr-fase-resizing .cr-fase-resize-handle {
            color: var(--vo-accent);
            background: color-mix(in srgb, var(--vo-accent) 12%, transparent);
        }
        .cr-fase-resizing,
        .cr-fase-resizing * { cursor: col-resize !important; user-select: none !important; }

        /* STATUS: apenas sticky vertical (top) no cabeçalho; dados scrollam normalmente */
        th.cr-col-status {
            left: auto;
        }
        td.cr-col-status {
            position: static;
            z-index: auto;
            background: inherit;
            box-shadow: none;
        }

        @media (min-width: 900px) {
            thead .cr-col-status {
                z-index: 6;
                background: var(--vo-bg-subtle);
            }

            .cr-table-row:hover .cr-col-status { background: var(--vo-bg-subtle); }
        }

        .cr-td-unidade {
            font-weight: 600;
            color: var(--vo-text);
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cr-td-center {
            text-align: center;
        }

        .cr-td-date {
            font-variant-numeric: tabular-nums;
            color: var(--vo-text-muted);
            font-size: 0.73rem;
        }

        .cr-uf-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--vo-text-faint);
            background: var(--vo-border-light);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .cr-status-pill {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            color: #fff;
            padding: 3px 10px;
            border-radius: 1rem;
            white-space: nowrap;
        }

        .cr-status-dropdown {
            position: relative;
            display: inline-block;
        }
        .cr-status-trigger {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.65rem;
            font-weight: 600;
            color: #fff;
            padding: 3px 8px 3px 6px;
            border-radius: 1rem;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            transition: filter .15s;
            line-height: 1.3;
        }
        .cr-status-trigger:hover { filter: brightness(1.15); }
        .cr-status-trigger .cr-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255,255,255,.45);
            flex-shrink: 0;
        }
        .cr-status-trigger .cr-status-chevron {
            width: 10px;
            height: 10px;
            opacity: .6;
            margin-left: 2px;
            transition: transform .15s;
            flex-shrink: 0;
        }
        .cr-status-trigger[aria-expanded="true"] .cr-status-chevron {
            transform: rotate(180deg);
        }
        .cr-status-menu {
            position: fixed;
            z-index: 99999;
            min-width: 170px;
            max-height: 260px;
            overflow-y: auto;
            background: var(--vo-bg, #fff);
            border: 1px solid var(--vo-border, #e5e7eb);
            border-radius: .5rem;
            box-shadow: 0 10px 32px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.08);
            padding: 4px;
        }
        .cr-status-option {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 6px 10px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 0.72rem;
            color: var(--vo-text, #333);
            border-radius: .375rem;
            text-align: left;
            line-height: 1.3;
        }
        .cr-status-option:hover {
            background: var(--vo-bg-subtle, #f5f5f5);
        }
        .cr-status-option.cr-status-active {
            background: var(--vo-bg-subtle, #f0f0f0);
            font-weight: 600;
        }
        .cr-status-option .cr-opt-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .cr-status-sm .cr-status-trigger {
            font-size: 0.58rem;
            padding: 1px 6px 1px 5px;
        }
        .cr-status-sm .cr-status-dot { width: 6px; height: 6px; }
        .cr-status-sm .cr-status-chevron { width: 8px; height: 8px; }

        .cr-versoes-panel {
            width: 280px;
            flex-shrink: 0;
            border-left: 1px solid var(--vo-border);
            background: var(--vo-bg);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .cr-versoes-header {
            padding: 12px 14px;
            border-bottom: 1px solid var(--vo-border);
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--vo-text);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cr-versoes-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }
        .cr-versao-item {
            padding: 10px 12px;
            border-radius: .5rem;
            cursor: pointer;
            transition: background .15s;
            border: 1px solid transparent;
            margin-bottom: 4px;
        }
        .cr-versao-item:hover {
            background: var(--vo-bg-subtle);
        }
        .cr-versao-item.cr-versao-ativa {
            background: rgba(251,186,0,.1);
            border-color: var(--vo-accent);
        }
        .cr-versao-item.cr-versao-atual {
            background: rgba(45,214,124,.08);
            border-color: #2dd67c;
        }
        .cr-historico-banner {
            padding: 8px 16px;
            background: rgba(251,186,0,.1);
            border-bottom: 2px solid var(--vo-accent);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .cr-progress-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cr-progress-track {
            flex: 1;
            height: 6px;
            background: var(--vo-border-light);
            border-radius: 3px;
            overflow: hidden;
            min-width: 80px;
        }

        .cr-progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }

        .cr-inline-date {
            border: 1px solid transparent;
            border-radius: 4px;
            background: transparent;
            color: var(--vo-text-secondary);
            font-size: 0.72rem;
            font-variant-numeric: tabular-nums;
            padding: 2px 4px;
            width: 108px;
            cursor: pointer;
        }

        .cr-date-copy-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            padding: 0;
            border: 1px solid transparent;
            border-radius: 3px;
            background: transparent;
            color: var(--vo-text-faint);
            cursor: pointer;
            font-size: 0.78rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .cr-date-copy-btn:hover:not(:disabled) {
            background: var(--vo-bg-subtle);
            border-color: var(--vo-border);
            color: var(--vo-accent);
        }

        .cr-date-copy-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .cr-inline-date:hover,
        .cr-inline-date:focus {
            border-color: var(--vo-border);
            background: var(--vo-bg-subtle);
            color: var(--vo-text);
            outline: none;
        }

        .cr-inline-date::-webkit-calendar-picker-indicator {
            opacity: 0.45;
            cursor: pointer;
        }

        .dark .cr-inline-date::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.55;
        }

        .cr-inline-nome-projeto {
            border: 1px solid transparent;
            border-radius: 4px;
            background: transparent;
            color: inherit;
            font: inherit;
            padding: 2px 6px;
            min-width: 280px;
            max-width: 100%;
            cursor: text;
        }

        .cr-inline-nome-projeto:hover {
            border-color: var(--vo-border);
            background: var(--vo-bg-subtle);
        }

        .cr-inline-nome-projeto:focus {
            border-color: var(--vo-accent);
            background: var(--vo-bg);
            outline: none;
        }

        .cr-farol {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
            flex-shrink: 0;
        }

        /* ---------- Linha colorida por farol ---------- */
        /* Aplicado tanto no Gantt (cr-row-left + cr-cal-row) quanto no Resumo (<tr>). */
        .cr-row-verde,
        .cr-fase-linha-verde > td,
        .cr-fase-linha-verde {
            background: rgba(45, 214, 124, 0.10) !important;
        }
        .cr-row-verde {
            box-shadow: inset 3px 0 0 var(--cr-concluido, #2dd67c);
        }

        .cr-row-amarelo,
        .cr-fase-linha-amarelo > td,
        .cr-fase-linha-amarelo {
            background: rgba(245, 186, 0, 0.14) !important;
        }
        .cr-row-amarelo {
            box-shadow: inset 3px 0 0 #f5ba00;
        }

        .cr-row-vermelho,
        .cr-fase-linha-vermelho > td,
        .cr-fase-linha-vermelho {
            background: rgba(255, 77, 106, 0.12) !important;
        }
        .cr-row-vermelho {
            box-shadow: inset 3px 0 0 var(--cr-atrasado, #ff4d6a);
        }

        /* Mantém hover dando um leve escurecimento sobre a cor do farol */
        .cr-fase-linha-verde:hover > td,
        .cr-fase-linha-amarelo:hover > td,
        .cr-fase-linha-vermelho:hover > td {
            filter: brightness(0.96);
        }

        /* Colunas sticky em linhas coloridas precisam de fundo opaco — caso
           contrário o conteúdo das colunas roladas vaza através delas. */
        .cr-fase-linha-verde > td.cr-td-sticky,
        .cr-fase-linha-verde > td.cr-col-status {
            background:
                linear-gradient(rgba(45, 214, 124, 0.10), rgba(45, 214, 124, 0.10)),
                var(--vo-bg) !important;
        }
        .cr-fase-linha-amarelo > td.cr-td-sticky,
        .cr-fase-linha-amarelo > td.cr-col-status {
            background:
                linear-gradient(rgba(245, 186, 0, 0.14), rgba(245, 186, 0, 0.14)),
                var(--vo-bg) !important;
        }
        .cr-fase-linha-vermelho > td.cr-td-sticky,
        .cr-fase-linha-vermelho > td.cr-col-status {
            background:
                linear-gradient(rgba(255, 77, 106, 0.12), rgba(255, 77, 106, 0.12)),
                var(--vo-bg) !important;
        }

        .cr-pct-inline {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 10px;
            background: var(--vo-bg-subtle);
            color: var(--vo-text-secondary);
            margin-left: 6px;
        }

        .cr-checklist-btn {
            background: transparent;
            border: 1px solid var(--vo-border);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.7rem;
            color: var(--vo-text-secondary);
            cursor: pointer;
            margin-left: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .cr-checklist-btn:hover {
            background: var(--vo-bg-subtle);
            color: var(--vo-text);
        }

        .cr-checklist-btn-open {
            background: var(--vo-accent);
            color: #111;
            border-color: var(--vo-accent);
        }

        /* Linhas de subitem no Gantt — mesma altura que uma cr-row-left */
        .cr-subitem-gantt-row {
            background: var(--vo-bg-subtle);
            border-top: 1px dashed var(--vo-border-light);
        }

        .cr-subitem-gantt-row-spacer {
            height: var(--cr-row-height);
            background: var(--vo-bg-subtle);
            border-top: 1px dashed var(--vo-border-light);
        }

        .cr-subitem-add-row {
            opacity: 0.85;
        }

        /* Gantt: colunas extras (Status, %, Planejado, Realizado) no painel esquerdo */
        .cr-gantt-col {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            height: var(--cr-row-height);
            padding: 0 10px;
            border-left: 1px solid var(--vo-border-light);
            font-size: 0.73rem;
            color: var(--vo-text-secondary);
            white-space: nowrap;
            overflow: hidden;
            box-sizing: border-box;
        }
        .cr-gantt-col-header {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--vo-text-faint);
            height: auto;
            align-items: flex-end;
            padding-bottom: 8px;
        }
        .cr-gantt-col-status { width: 120px; min-width: 120px; }
        .cr-gantt-col-pct    { width: 70px;  min-width: 70px;  justify-content: center; }
        .cr-gantt-col-plan   { width: 190px; min-width: 190px; gap: 3px; }
        .cr-gantt-col-real   { width: 190px; min-width: 190px; gap: 3px; }
        /* Linha de subitem no calendário (cr-right) */
        .cr-subitem-cal-row {
            display: flex;
            height: var(--cr-row-height);
            background: var(--vo-bg-subtle);
            border-top: 1px dashed var(--vo-border-light);
            position: relative;
        }

        .cr-subitens-header {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--vo-text-secondary);
            margin-bottom: 4px;
        }

        .cr-subitens-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            overflow-y: auto;
        }

        .cr-subitem-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            border: 1px solid var(--vo-border-light);
            border-radius: 4px;
            background: var(--vo-bg);
        }

        .cr-subitem-row input[type="checkbox"] {
            width: 14px;
            height: 14px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .cr-subitem-label {
            flex: 1;
            font-size: 0.72rem;
            color: var(--vo-text);
        }

        .cr-subitem-done {
            text-decoration: line-through;
            color: var(--vo-text-muted);
        }

        .cr-subitem-remove {
            background: transparent;
            border: none;
            color: var(--vo-text-faint);
            cursor: pointer;
            padding: 0 6px;
            font-size: 1rem;
            line-height: 1;
        }

        .cr-subitem-remove:hover {
            color: var(--cr-atrasado, #ff4d6a);
        }

        .cr-subitens-empty {
            font-size: 0.7rem;
            color: var(--vo-text-faint);
            text-align: center;
            padding: 12px;
        }

        .cr-subitens-add {
            display: flex;
            gap: 6px;
            padding-top: 4px;
            border-top: 1px dashed var(--vo-border-light);
        }

        .cr-subitens-add input[type="text"] {
            flex: 1;
            padding: 5px 8px;
            border: 1px solid var(--vo-border);
            border-radius: 4px;
            font-size: 0.72rem;
            background: var(--vo-bg);
            color: var(--vo-text);
        }

        .cr-subitens-add button {
            padding: 5px 12px;
            background: var(--vo-accent);
            color: #111;
            border: none;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
        }

        /* Linhas de subitem dentro da tabela resumida */
        .cr-subitem-tr,
        .cr-subitem-add-tr {
            background: var(--vo-bg-subtle);
        }

        /* Farol de completude dos subitens */
        .cr-subitem-vermelho { background: rgba(255, 77, 106, 0.12) !important; }
        .cr-subitem-vermelho td { background: rgba(255, 77, 106, 0.12) !important; }
        .cr-subitem-vermelho td.cr-td-sticky { background: linear-gradient(rgba(255,77,106,.12),rgba(255,77,106,.12)), var(--vo-bg) !important; }
        .cr-subitem-vermelho:hover { filter: brightness(0.96); }

        .cr-subitem-azul { background: rgba(59, 130, 246, 0.10) !important; }
        .cr-subitem-azul td { background: rgba(59, 130, 246, 0.10) !important; }
        .cr-subitem-azul td.cr-td-sticky { background: linear-gradient(rgba(59,130,246,.10),rgba(59,130,246,.10)), var(--vo-bg) !important; }
        .cr-subitem-azul:hover { filter: brightness(0.96); }

        .cr-subitem-tr:hover {
            background: var(--vo-bg-subtle);
            filter: brightness(0.97);
        }

        .cr-subitem-tr td,
        .cr-subitem-add-tr td {
            border-top: 1px dashed var(--vo-border-light);
            font-size: 0.7rem;
        }

        .cr-subitem-tree {
            color: var(--vo-text-faint);
            font-family: monospace;
            width: 10px;
            flex-shrink: 0;
        }

        .cr-subitem-titulo-inline {
            flex: 1;
            border: 1px solid transparent;
            background: transparent;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.72rem;
            color: var(--vo-text);
            min-width: 0;
            width: 100%;
            resize: none;
            overflow: hidden;
            line-height: 1.35;
            font-family: inherit;
            box-sizing: border-box;
            word-break: break-word;
            overflow-wrap: anywhere;
            max-height: calc(1.35em * 2 + 6px);
        }

        .cr-subitem-titulo-inline:hover {
            border-color: var(--vo-border);
            background: var(--vo-bg);
        }

        .cr-subitem-titulo-inline:focus {
            border-color: var(--vo-border);
            background: var(--vo-bg);
            outline: none;
            max-height: 320px;
            overflow: auto;
            position: relative;
            z-index: 10;
        }

        .cr-subitem-titulo-inline.cr-subitem-done {
            text-decoration: line-through;
            color: var(--vo-text-muted);
        }

        .cr-subitem-obs-inline {
            width: 100%;
            border: 1px solid transparent;
            background: transparent;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            color: var(--vo-text-secondary);
            resize: none;
            overflow: hidden;
            line-height: 1.35;
            font-family: inherit;
            box-sizing: border-box;
            word-break: break-word;
            overflow-wrap: anywhere;
            max-height: calc(1.35em * 2 + 6px);
        }

        .cr-subitem-obs-inline:hover {
            border-color: var(--vo-border);
            background: var(--vo-bg);
            color: var(--vo-text);
        }

        .cr-subitem-obs-inline:focus {
            border-color: var(--vo-border);
            background: var(--vo-bg);
            outline: none;
            color: var(--vo-text);
            max-height: 320px;
            overflow: auto;
            position: relative;
            z-index: 10;
        }

        .cr-subitem-dep-select {
            width: 100%;
            max-width: 170px;
            min-width: 92px;
            border: 1px solid var(--vo-border);
            background: var(--vo-bg);
            color: var(--vo-text-secondary);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.68rem;
        }

        .cr-subitem-dep-select:focus {
            border-color: var(--vo-accent);
            color: var(--vo-text);
            outline: none;
        }

        .cr-subitem-dep-trigger,
        .cr-subitem-dep-gap {
            border: 1px solid var(--vo-border);
            background: var(--vo-bg);
            color: var(--vo-text-secondary);
            border-radius: 4px;
            padding: 2px 4px;
            font-size: 0.65rem;
        }

        .cr-subitem-dep-trigger {
            width: 58px;
        }

        .cr-subitem-dep-gap {
            width: 42px;
        }

        .cr-subitem-status-badge {
            display: inline-block;
            font-size: 0.62rem;
            font-weight: 600;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .cr-subitem-add-btn {
            padding: 3px 10px;
            background: var(--vo-accent);
            color: #111;
            border: none;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 600;
            cursor: pointer;
        }

        .cr-subitem-child-btn {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 4px;
            color: var(--vo-text-secondary);
            cursor: pointer;
            font-size: 0.75rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .cr-subitem-child-btn:hover {
            background: var(--vo-accent);
            border-color: var(--vo-accent);
            color: #111;
        }

        .cr-subitem-child-btn-deps {
            background: var(--vo-accent);
            border-color: var(--vo-accent);
            color: #111;
            font-weight: 700;
        }

        .cr-progress-pct {
            font-size: 0.73rem;
            font-weight: 700;
            color: var(--vo-text);
            min-width: 32px;
            text-align: right;
        }

        .cr-fases-count {
            font-size: 0.73rem;
            color: var(--vo-text-muted);
            font-variant-numeric: tabular-nums;
        }

        .cr-atrasadas-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--cr-atrasado);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
        }

        /* =========================================
           Gantt calendário dia-a-dia (novo layout)
           ========================================= */
        .cr-cal .cr-header-left { height: 76px; align-items: flex-end; padding-bottom: 8px; }
        .cr-cal-right { }
        .cr-cal-header {
            background: var(--vo-bg);
            border-bottom: 1px solid var(--vo-border);
        }
        .cr-cal-header-row { display: flex; }
        .cr-cal-header-mes {
            height: 22px;
            border-bottom: 1px solid var(--vo-border-light);
        }
        .cr-cal-header-mes-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.62rem;
            font-weight: 700;
            color: var(--vo-text-muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            border-right: 1px solid var(--vo-border-light);
            white-space: nowrap;
            overflow: hidden;
        }
        .cr-cal-header-dia { height: 28px; }
        .cr-cal-header-dow { height: 26px; border-bottom: 1px solid var(--vo-border); }
        .cr-cal-day-cell {
            flex-shrink: 0;
            width: var(--cr-ppd);
            min-width: var(--cr-ppd);
            max-width: var(--cr-ppd);
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid var(--vo-border-light);
            font-size: 0.6rem;
            color: var(--vo-text-secondary);
            font-variant-numeric: tabular-nums;
            overflow: hidden;
        }
        .cr-cal-day-cell .cr-day-num { font-weight: 600; }
        .cr-cal-day-cell .cr-dow-letter {
            color: var(--vo-text-faint);
            font-size: 0.56rem;
            text-transform: uppercase;
        }
        .cr-cal-day-cell.cr-weekend { background: rgba(148,148,148,.08); }
        .cr-cal-day-cell.cr-hoje-col { background: rgba(251,186,0,.12); }
        .cr-cal-day-cell { cursor: pointer; transition: background-color .12s ease; }
        .cr-cal-day-cell:hover,
        .cr-cal-day-cell.cr-col-highlighted { background: rgba(251,186,0,.20); }
        .cr-cal-row {
            height: var(--cr-row-height);
            display: flex;
            border-bottom: 1px solid var(--vo-border-light);
            cursor: pointer;
            position: relative;
        }
        .cr-ancora-marker {
            position: absolute;
            top: 50%;
            width: 12px;
            height: 12px;
            background: #fbba00;
            transform: translate(-50%, -50%) rotate(45deg);
            border: 1px solid #b45309;
            z-index: 3;
            pointer-events: none;
        }
        .cr-cal-row.cr-row-ancora { background: rgba(251,186,0,.06); }
        .cr-cal-row.cr-row-highlighted { background: rgba(251,186,0,.16) !important; }
        .cr-left .cr-row-left.cr-row-ancora { background: rgba(251,186,0,.08); }
        .cr-left .cr-row-left.cr-row-ancora::before {
            content: '⚓';
            font-size: 0.7rem;
            margin-right: 4px;
            color: #b45309;
        }
        .cr-left .cr-row-left.cr-row-highlighted { background: rgba(251,186,0,.2) !important; }
        .cr-cal-cell {
            flex-shrink: 0;
            width: var(--cr-ppd);
            min-width: var(--cr-ppd);
            max-width: var(--cr-ppd);
            height: calc(var(--cr-row-height) - 10px);
            margin-top: 5px;
            margin-bottom: 5px;
            border-right: 1px solid var(--vo-border-light);
            box-sizing: border-box;
            position: relative;
            cursor: pointer;
            transition: background-color .12s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.56rem;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        .cr-cell-num { font-weight: 600; }
        .cr-cal-cell.cr-cell-real .cr-cell-num,
        .cr-cal-cell.cr-cell-atraso .cr-cell-num { color: #fff; }
        .cr-cal-cell.cr-weekend { background: rgba(148,148,148,.06); }
        .cr-cal-cell.cr-hoje-col { background: rgba(251,186,0,.08); }
        .cr-cal-cell.cr-col-highlighted { background: rgba(251,186,0,.14); }
        .cr-cal-cell:hover { background: rgba(251,186,0,.10); }
        .cr-cal-cell.cr-cell-prev {
            border: 1px dashed var(--vo-text-muted);
            background: transparent;
            border-radius: 2px;
        }
        .cr-cal-cell.cr-cell-real {
            background: var(--cr-concluido);
            border: 1px solid var(--cr-concluido);
            border-radius: 2px;
        }
        .cr-cal-cell.cr-cell-atraso {
            background: var(--cr-atrasado);
            border: 1px solid var(--cr-atrasado);
            border-radius: 2px;
        }

        /* ── Indicador de carregamento (Livewire) ── */
        .cr-loading-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            overflow: hidden;
            z-index: 1100;
            background: color-mix(in srgb, var(--vo-accent) 14%, transparent);
            pointer-events: none;
        }
        .cr-loading-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 35%;
            height: 100%;
            background: linear-gradient(90deg, transparent, var(--vo-accent), transparent);
            animation: cr-loading-slide 1.1s ease-in-out infinite;
        }
        @keyframes cr-loading-slide {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(290%); }
        }
        .cr-loading-overlay {
            position: absolute;
            inset: 0;
            background: color-mix(in srgb, var(--vo-text) 6%, transparent);
            backdrop-filter: blur(0.5px);
            pointer-events: none;
            z-index: 1090;
            opacity: 0;
            animation: cr-loading-fade-in 0.18s ease-out forwards;
        }
        @keyframes cr-loading-fade-in {
            to { opacity: 1; }
        }

        /* Alerta visual "piscante" para deadlines críticos (ex.: SUFRAMA 60d
           antes da inauguração). Aplicar via classe .cr-piscando no badge,
           farol ou data da fase quando faltam <=60 dias para a data. */
        .cr-piscando {
            animation: cr-piscar 1.4s ease-in-out infinite;
        }
        @keyframes cr-piscar {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        /* Fullscreen overlay fallback */
        .cr-container.cr-fullscreen-fallback,
        .cr-container:fullscreen,
        .vo-theme-cronograma.cr-fullscreen-fallback,
        .vo-theme-cronograma:fullscreen {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1000;
            background: var(--vo-bg);
            overflow: auto;
        }
        /* Quando o container externo está em tela cheia, o card interno deve
           ocupar a altura disponível para que o Gantt mantenha o scroll
           próprio em vez de jogar tudo para o scroll da página. */
        .vo-theme-cronograma.cr-fullscreen-fallback > .cr-card,
        .vo-theme-cronograma:fullscreen > .cr-card {
            height: 100vh;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .cr-left { width: 320px; }
            .cr-left .cr-col-fase { width: 150px; padding: 0 8px; }
            .cr-cal .cr-header-left { font-size: 0.55rem; padding: 0 8px; }
            .cr-row-left { font-size: 0.7rem; }
        }

        /* ── Editor de Fases – painel lateral ── */
        .cr-editor-fases-panel,
        .cr-alterar-datas-panel {
            width: 0;
            flex-shrink: 0;
            overflow: hidden;
            transition: width 0.22s ease;
            border-left: 1px solid transparent;
            background: var(--vo-bg);
            display: flex;
            flex-direction: column;
        }
        .cr-editor-fases-panel.open {
            width: 560px;
            border-left-color: var(--vo-border);
        }
        .cr-alterar-datas-panel.open {
            width: 720px;
            border-left-color: var(--vo-border);
        }
        .cr-alterar-datas-body {
            flex: 1;
            overflow-y: auto;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .cr-alterar-datas-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
        }
        .cr-alterar-datas-table thead th {
            position: sticky;
            top: 0;
            background: var(--vo-bg);
            text-align: left;
            padding: 10px 8px;
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--vo-text-muted);
            border-bottom: 1px solid var(--vo-border);
            z-index: 2;
        }
        .cr-alterar-datas-table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid var(--vo-border-light);
            vertical-align: top;
        }
        .cr-editor-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--vo-border);
            background: var(--vo-bg-subtle);
            flex-shrink: 0;
            gap: 10px;
        }
        .cr-editor-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        /* card de fase */
        .cr-ef-card {
            border: 1px solid var(--vo-border);
            border-radius: 0.5rem;
            background: var(--vo-bg);
            overflow: hidden;
        }
        .cr-ef-card-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            background: var(--vo-bg-subtle);
            cursor: pointer;
            user-select: none;
            min-height: 38px;
        }
        .cr-ef-card-head:hover { background: var(--vo-bg-hover, var(--vo-bg-subtle)); }
        .cr-ef-card-body {
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-top: 1px solid var(--vo-border);
        }
        /* label de seção dentro do corpo */
        .cr-ef-section-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--vo-text-muted);
            margin-bottom: 4px;
        }
        /* linha de dependência de fase */
        .cr-ef-dep-row {
            display: grid;
            grid-template-columns: 1fr 100px 60px 22px;
            gap: 4px;
            align-items: center;
        }
        /* inputs genéricos do editor */
        .cr-ef-input {
            padding: 5px 8px;
            border: 1px solid var(--vo-border);
            border-radius: 0.3rem;
            font-size: 0.78rem;
            background: var(--vo-bg);
            color: var(--vo-text);
            width: 100%;
            box-sizing: border-box;
        }
        .cr-ef-input:focus { outline: none; border-color: var(--vo-accent); }
        /* botões pequenos */
        .cr-ef-btn-ghost {
            background: transparent;
            border: 1px solid var(--vo-border);
            border-radius: 0.3rem;
            cursor: pointer;
            padding: 4px 10px;
            font-size: 0.73rem;
            color: var(--vo-text-secondary);
            white-space: nowrap;
        }
        .cr-ef-btn-ghost:hover { border-color: var(--vo-accent); color: var(--vo-accent); }
        .cr-ef-btn-icon {
            background: transparent;
            border: 1px solid var(--vo-border);
            border-radius: 0.25rem;
            cursor: pointer;
            padding: 2px 5px;
            font-size: 0.65rem;
            line-height: 1.2;
            color: var(--vo-text-secondary);
        }
        .cr-ef-btn-icon:disabled { opacity: 0.25; cursor: not-allowed; }
        /* drag-and-drop do editor de fases */
        .cr-ef-drag-handle {
            cursor: grab;
            color: var(--vo-text-faint);
            padding: 4px 3px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            border-radius: 3px;
            transition: color .1s, background .1s;
        }
        .cr-ef-drag-handle:hover { color: var(--vo-text-secondary); background: var(--vo-bg); }
        .cr-ef-card--dragging { opacity: 0.3; }
        .cr-ef-card--nao-aplica {
            opacity: 0.55;
            filter: grayscale(0.6);
        }
        .cr-ef-card--nao-aplica .cr-ef-card-head {
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 4px,
                color-mix(in srgb, var(--vo-border) 40%, transparent) 4px,
                color-mix(in srgb, var(--vo-border) 40%, transparent) 8px
            );
        }
        .cr-ef-card--over {
            border-color: var(--vo-accent) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--vo-accent) 20%, transparent);
        }
        .cr-ef-btn-remove {
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--vo-text-faint);
            font-size: 1rem;
            line-height: 1;
            padding: 0 3px;
            flex-shrink: 0;
        }
        .cr-ef-btn-remove:hover { color: #ef4444; }
        /* linha de subitem */
        .cr-ef-sub-row {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 3px 0;
        }
        .cr-ef-sub-title {
            flex: 1;
            border: none;
            border-bottom: 1px solid transparent;
            background: transparent;
            font-size: 0.8rem;
            color: var(--vo-text);
            padding: 1px 3px;
            outline: none;
            min-width: 0;
        }
        .cr-ef-sub-title:focus { border-bottom-color: var(--vo-accent); }
        /* bloco expansível de sub-detalhes */
        .cr-ef-sub-detail {
            margin-left: 20px;
            margin-bottom: 4px;
            padding: 8px 10px;
            border-left: 2px solid var(--vo-border);
            background: var(--vo-bg-subtle);
            border-radius: 0 0.3rem 0.3rem 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        /* linha de dep de subitem */
        .cr-ef-subdep-row {
            display: grid;
            grid-template-columns: 1fr 88px 54px 20px;
            gap: 3px;
            align-items: center;
        }

        /* ── Gerenciamento de colunas ── */
        .cr-col-panel {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 210px;
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 0.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
            z-index: 60;
            padding: 8px 0;
        }
        .cr-col-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 14px 8px;
            border-bottom: 1px solid var(--vo-border);
            margin-bottom: 4px;
        }
        .cr-col-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 14px;
            cursor: pointer;
            font-size: 0.78rem;
            color: var(--vo-text);
            user-select: none;
            transition: background .1s;
        }
        .cr-col-option:hover { background: var(--vo-bg-subtle); }
        .cr-col-option input[type="checkbox"] {
            width: 13px;
            height: 13px;
            cursor: pointer;
            accent-color: var(--vo-accent);
            flex-shrink: 0;
        }

        /* ── Chips de filtros ativos ── */
        .cr-filter-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 8px 16px;
            border-bottom: 1px solid var(--vo-border);
            align-items: center;
            background: var(--vo-bg);
        }
        .cr-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 8px 3px 10px;
            background: color-mix(in srgb, var(--vo-accent) 10%, transparent);
            color: var(--vo-accent);
            border: 1px solid color-mix(in srgb, var(--vo-accent) 25%, transparent);
            border-radius: 99px;
            font-size: 0.71rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .cr-filter-chip button {
            background: none;
            border: none;
            cursor: pointer;
            color: currentColor;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
            opacity: 0.6;
            display: flex;
            align-items: center;
        }
        .cr-filter-chip button:hover { opacity: 1; }
        .cr-filter-chips-clear {
            font-size: 0.71rem;
            color: var(--vo-text-muted);
            background: none;
            border: 1px solid var(--vo-border);
            border-radius: 99px;
            cursor: pointer;
            padding: 3px 10px;
            font-family: inherit;
        }
        .cr-filter-chips-clear:hover { color: var(--cr-atrasado); border-color: var(--cr-atrasado); }

        /* ======================================================
           Design Refresh — fontes maiores, badge, cores de fase
           ====================================================== */

        /* Badge circular com o número da fase */
        .cr-fase-num-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            min-width: 26px;
            border-radius: 50%;
            font-size: 0.72rem;
            font-weight: 700;
            flex-shrink: 0;
            letter-spacing: 0;
            box-shadow: 0 1px 4px rgba(0,0,0,.28);
        }

        /* Status badge — maior e mais legível */
        .cr-status-trigger {
            font-size: 0.78rem !important;
            padding: 6px 14px 6px 10px !important;
            border-radius: 2rem !important;
            letter-spacing: .01em !important;
        }

        /* Tabela detalhada — fonte geral maior */
        .cr-table { font-size: 0.875rem !important; }
        .cr-table td { padding: 11px 12px !important; }
        .cr-table th {
            font-size: 0.72rem !important;
            padding: 12px 12px !important;
        }

        /* Subitens — fonte maior */
        .cr-subitem-tr td,
        .cr-subitem-add-tr td {
            font-size: 0.78rem !important;
            padding: 9px 10px !important;
        }
        .cr-subitem-status-badge {
            font-size: 0.7rem !important;
            padding: 3px 10px !important;
        }
        .cr-subitem-titulo-inline {
            font-size: 0.8rem !important;
        }

        /* Gantt — fonte maior */
        .cr-row-left { font-size: 0.84rem !important; }
        @media (max-width: 768px) { .cr-row-left { font-size: 0.72rem !important; } }

        /* Linhas de fase — fundo mais proeminente */
        .cr-fase-linha-verde > td,
        .cr-fase-linha-verde { background: rgba(45, 214, 124, 0.20) !important; }
        .cr-fase-linha-verde > td.cr-td-sticky,
        .cr-fase-linha-verde > td.cr-col-status {
            background: linear-gradient(rgba(45,214,124,0.20),rgba(45,214,124,0.20)), var(--vo-bg) !important;
        }

        .cr-fase-linha-amarelo > td,
        .cr-fase-linha-amarelo { background: rgba(245, 186, 0, 0.26) !important; }
        .cr-fase-linha-amarelo > td.cr-td-sticky,
        .cr-fase-linha-amarelo > td.cr-col-status {
            background: linear-gradient(rgba(245,186,0,0.26),rgba(245,186,0,0.26)), var(--vo-bg) !important;
        }

        .cr-fase-linha-vermelho > td,
        .cr-fase-linha-vermelho { background: rgba(255, 77, 106, 0.22) !important; }
        .cr-fase-linha-vermelho > td.cr-td-sticky,
        .cr-fase-linha-vermelho > td.cr-col-status {
            background: linear-gradient(rgba(255,77,106,0.22),rgba(255,77,106,0.22)), var(--vo-bg) !important;
        }

        /* Menu lateral do Filament — fonte maior */
        .fi-sidebar-item-label { font-size: 0.92rem !important; }
        .fi-sidebar-group-label { font-size: 0.8rem !important; }

        /* ====== Toolbar da tabela detalhada ====== */
        .cr-tbl-toolbar {
            display: flex; gap: 8px; padding: 10px 16px;
            border-bottom: 1px solid var(--vo-border);
            align-items: center; flex-wrap: wrap; background: var(--vo-bg);
        }
        .cr-tbl-search-wrap { position: relative; flex: 1; min-width: 140px; max-width: 240px; }
        .cr-tbl-search-icon {
            position: absolute; left: 9px; top: 50%; transform: translateY(-50%);
            color: var(--vo-text-faint); pointer-events: none;
        }
        .cr-tbl-search {
            width: 100%; border: 1px solid var(--vo-border); border-radius: .5rem;
            background: var(--vo-bg-subtle); color: var(--vo-text);
            font-size: 0.8rem; padding: 6px 10px 6px 30px; font-family: inherit; box-sizing: border-box;
        }
        .cr-tbl-search:focus { outline: none; border-color: var(--vo-accent); }
        .cr-tbl-filter {
            border: 1px solid var(--vo-border); border-radius: .5rem;
            background: var(--vo-bg-subtle); color: var(--vo-text);
            font-size: 0.8rem; padding: 6px 10px; font-family: inherit;
        }
        .cr-tbl-cols-btn {
            display: inline-flex; align-items: center; gap: 6px;
            border: 1px solid var(--vo-border); border-radius: .5rem;
            background: var(--vo-bg-subtle); color: var(--vo-text-secondary);
            font-size: 0.8rem; padding: 6px 12px; cursor: pointer; white-space: nowrap;
        }
        .cr-tbl-cols-btn:hover { background: var(--vo-bg); border-color: var(--vo-accent); color: var(--vo-text); }
        .cr-tbl-cols-panel {
            position: absolute; top: 100%; right: 0; margin-top: 6px;
            background: var(--vo-bg); border: 1px solid var(--vo-border); border-radius: .5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,.18); padding: 6px; min-width: 180px; z-index: 50;
        }
        .dark .cr-tbl-cols-panel { box-shadow: 0 8px 24px rgba(0,0,0,.5); }
        .cr-tbl-cols-item {
            display: flex; align-items: center; gap: 8px; padding: 6px 8px;
            border-radius: .3rem; cursor: pointer; font-size: 0.82rem;
            color: var(--vo-text-secondary); user-select: none;
        }
        .cr-tbl-cols-item:hover { background: var(--vo-bg-subtle); }
        .cr-tbl-cols-item input[type="checkbox"] { cursor: pointer; accent-color: var(--vo-accent); }

        /* ====== Resize handle genérico para th ====== */
        th.cr-th-resizable { position: relative; }
        th.cr-th-resizable .cr-th-rhandle {
            position: absolute; right: -5px; top: 0; bottom: 0; width: 10px;
            cursor: col-resize; z-index: 10; display: flex; align-items: center;
            justify-content: center; color: transparent; transition: color .15s;
        }
        th.cr-th-resizable .cr-th-rhandle::before {
            content: ""; display: block; width: 2px; height: 55%;
            background: currentColor; border-radius: 1px;
        }
        th.cr-th-resizable:hover .cr-th-rhandle,
        .cr-fase-resizing .cr-th-rhandle { color: var(--vo-accent); }

        /* ====== Col-status: largura dinâmica ====== */
        .cr-col-status { width: var(--cr-col-status-w, 155px); min-width: var(--cr-col-status-w, 155px); }
    </style>

    <div class="vo-theme-cronograma" style="display:flex;gap:0;overflow:clip;border-radius:0.75rem;border:1px solid var(--vo-border);box-shadow:var(--vo-shadow);align-self:flex-start;width:100%;position:relative;">
        {{-- Indicadores de carregamento Livewire (com delay pra evitar piscar) --}}
        <div class="cr-loading-bar" wire:loading.delay></div>
        <div class="cr-loading-overlay" wire:loading.delay.longer></div>
    <div x-data="ganttChart()" class="cr-card" x-cloak wire:key="cr-card-{{ $projetoSelecionado ?? 'macro' }}-{{ $renderKey }}" style="flex:1;min-width:0;border:none;border-radius:0;box-shadow:none;">
        {{-- Filtros --}}
        <div class="cr-filters">
            @if($modoIndividual)
                <button class="vo-btn-outline" wire:click="voltarParaMacro">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Voltar
                </button>

                <div x-data="{
                        open: false,
                        search: '',
                        projetos: @js($projetosDisponiveis ?? []),
                        get filtered() {
                            if (!this.search) return this.projetos.slice(0, 50);
                            const term = this.search.toLowerCase();
                            return this.projetos.filter(o => o.label.toLowerCase().includes(term)).slice(0, 50);
                        }
                     }"
                     class="cr-obra-selector" @click.outside="open = false">
                    <input type="text"
                           x-model="search"
                           @focus="open = true"
                           @input="open = true"
                           placeholder="Trocar projeto..."
                           class="cr-obra-search">
                    <div x-show="open && filtered.length > 0" x-cloak class="cr-obra-dropdown">
                        <template x-for="item in filtered" :key="item.id">
                            <div class="cr-obra-option"
                                 :class="{ 'cr-obra-option-active': item.id == {{ $projetoSelecionado }} }"
                                 @click="open = false; search = ''; $wire.selecionarProjeto(item.id)"
                                 x-text="item.label">
                            </div>
                        </template>
                    </div>
                </div>

                @if($templateAplicadoNoProjeto ?? null)
                    <div style="display:flex;align-items:center;gap:8px;padding:5px 12px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vo-accent)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        <span style="font-weight:700;font-size:.78rem;color:var(--vo-text);letter-spacing:-0.01em;">{{ $templateAplicadoNoProjeto->nome }}</span>
                    </div>
                @endif

<button class="vo-btn-outline" wire:click="abrirEditorFases"
                        title="{{ ($templateAplicadoNoProjeto ?? null) ? 'Template: '.$templateAplicadoNoProjeto->nome : 'Editar fases' }}"
                        style="{{ $mostrarEditorFases ? 'background:var(--vo-accent);color:#111;border-color:var(--vo-accent);' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="10" y2="18"/><circle cx="19" cy="15" r="3"/><line x1="21.5" y1="17.5" x2="23" y2="19"/></svg>
                    Editar fases
                </button>

                <button class="vo-btn-outline" wire:click="abrirModalDatas"
                        style="{{ $mostrarModalDatas ? 'background:var(--vo-accent);color:#111;border-color:var(--vo-accent);' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M12 14l2 2 4-4"/></svg>
                    Alterar datas
                </button>

                <div style="display:flex;gap:2px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:0.5rem;padding:2px;">
                    <button class="vo-btn-outline" wire:click="$set('visualizacao', 'gantt')" style="padding:5px 10px;border:none;border-radius:0.375rem;{{ $visualizacao === 'gantt' ? 'background:var(--vo-accent);color:#111;' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><rect x="7" y="10" width="8" height="3" rx="1"/><rect x="7" y="5" width="12" height="3" rx="1"/><rect x="7" y="15" width="5" height="3" rx="1"/></svg>
                    </button>
                    <button class="vo-btn-outline" wire:click="$set('visualizacao', 'barras')" style="padding:5px 10px;border:none;border-radius:0.375rem;{{ $visualizacao === 'barras' ? 'background:var(--vo-accent);color:#111;' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
                    </button>
                </div>

                @if($visualizacao === 'gantt')
                <div style="position:relative;" @click.outside="ganttColPanel = false">
                    <button type="button" class="vo-btn-outline"
                            @click="ganttColPanel = !ganttColPanel"
                            style="padding:5px 10px;display:inline-flex;align-items:center;gap:5px;font-size:0.75rem;"
                            :style="ganttColPanel ? 'background:var(--vo-accent);color:#111;border-color:var(--vo-accent);' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Colunas
                    </button>
                    <div x-show="ganttColPanel" x-cloak
                         style="position:absolute;top:calc(100% + 6px);left:0;z-index:60;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.5rem;padding:10px 14px;min-width:160px;box-shadow:0 8px 24px rgba(0,0,0,.12);display:flex;flex-direction:column;gap:8px;">
                        <span style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--vo-text-faint);margin-bottom:2px;">Colunas do Gantt</span>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.78rem;color:var(--vo-text-secondary);">
                            <input type="checkbox" x-model="ganttCols.status"> Status
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.78rem;color:var(--vo-text-secondary);">
                            <input type="checkbox" x-model="ganttCols.pct"> Percentual
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.78rem;color:var(--vo-text-secondary);">
                            <input type="checkbox" x-model="ganttCols.planejado"> Planejado
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.78rem;color:var(--vo-text-secondary);">
                            <input type="checkbox" x-model="ganttCols.realizado"> Realizado
                        </label>
                    </div>
                </div>
                @endif

                <button class="vo-btn-outline" wire:click="abrirHistorico" title="Histórico de alterações"
                        style="padding:5px 10px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Histórico
                </button>

                <button class="vo-btn-outline" wire:click="abrirComentariosGlobal" title="Comentários do projeto"
                        style="padding:5px 10px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    Comentários
                </button>

                <button class="vo-btn-outline" wire:click="toggleVersoes" title="Histórico de versões"
                        style="padding:5px 10px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;{{ $mostrarVersoes ? 'background:var(--vo-accent);color:#111;' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><path d="M3.05 11a9 9 0 1 1 .5 4"/><path d="M3 16v-5h5"/></svg>
                    Versões
                </button>

                <button class="vo-btn-outline" wire:click="sincronizarTarefasDoProjetoAtual" title="Criar tarefas faltantes para responsáveis e revisores já atribuídos"
                        style="padding:5px 10px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Sincronizar tarefas
                </button>

                <button class="vo-btn-outline" wire:click="$set('mostrarModalNovaFase', true)"
                        title="Inserir nova fase no planejamento"
                        style="padding:5px 10px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Adicionar fase
                </button>

            @else
                <input type="text" placeholder="Buscar projeto..." wire:model.live.debounce.300ms="busca">

                <select wire:model.live="filtroEstado">
                    <option value="">Todos os Estados</option>
                    @foreach($estadosDisponiveis as $estado)
                        <option value="{{ $estado->id }}">{{ $estado->uf }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtroStatusObra">
                    <option value="">Status do Projeto</option>
                    @foreach($statusProjetoOptions as $statusProjeto)
                        <option value="{{ $statusProjeto }}">{{ $statusProjeto }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtroStatus">
                    <option value="">Status Cronograma</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtroPeriodo">
                    <option value="">Todo o Período</option>
                    <optgroup label="Passado">
                        <option value="ultimo_mes">Último mês</option>
                        <option value="ultimos_3_meses">Últimos 3 meses</option>
                        <option value="ultimos_6_meses">Últimos 6 meses</option>
                    </optgroup>
                    <optgroup label="Futuro">
                        <option value="proximo_mes">Próximo mês</option>
                        <option value="proximos_3_meses">Próximos 3 meses</option>
                        <option value="proximos_6_meses">Próximos 6 meses</option>
                    </optgroup>
                </select>

                <select wire:model.live="filtroTemplate">
                    <option value="">Todos os Templates</option>
                    <option value="com_template">Com template</option>
                    <option value="sem_template">Sem template</option>
                </select>

                @if($busca || $filtroEstado || $filtroStatusObra || $filtroStatus || $filtroPeriodo || $filtroTemplate)
                    <button type="button" wire:click="limparFiltrosMacro"
                            style="background:transparent;border:1px solid var(--vo-border);border-radius:.5rem;cursor:pointer;color:var(--vo-text-muted);font-size:0.72rem;padding:6px 10px;white-space:nowrap;font-family:inherit;line-height:1;">
                        Limpar filtros
                    </button>
                @endif

                <a href="{{ \App\Filament\Resources\ProjetoResource::getUrl('create') }}"
                   class="vo-btn-outline"
                   style="padding:5px 12px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;text-decoration:none;color:inherit;font-weight:600;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo projeto
                </a>
                <button type="button"
                        wire:click="$set('mostrarModalNovoPlanejamento', true)"
                        class="vo-btn-outline"
                        style="padding:5px 12px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;font-weight:600;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Novo planejamento
                </button>

                <button type="button" class="vo-btn-outline" wire:click="abrirHistoricoGlobal"
                        title="Histórico de alterações de todos os projetos"
                        style="margin-left:auto;padding:5px 10px;display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Histórico
                </button>

                <div style="position:relative;" @click.outside="painelColunas = false">
                    <button type="button" class="vo-btn-outline"
                            @click="painelColunas = !painelColunas"
                            :style="colunasOcultas.length ? 'border-color:var(--vo-accent);' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                        Colunas
                        <span x-show="colunasOcultas.length > 0" x-cloak
                              style="background:var(--vo-accent);color:#111;border-radius:99px;font-size:.6rem;font-weight:700;padding:1px 5px;margin-left:2px;"
                              x-text="colunasOcultas.length"></span>
                    </button>
                    <div x-show="painelColunas" x-cloak class="cr-col-panel">
                        <div class="cr-col-panel-header">
                            <span style="font-size:.68rem;font-weight:700;color:var(--vo-text-muted);text-transform:uppercase;letter-spacing:.05em;">Colunas visíveis</span>
                            <button type="button" @click="resetarColunas()"
                                    style="font-size:.68rem;color:var(--vo-accent);background:none;border:none;cursor:pointer;padding:0;font-family:inherit;">
                                Mostrar todas
                            </button>
                        </div>
                        @foreach([
                            'marca'          => 'Marca',
                            'uf'             => 'UF',
                            'template'       => 'Template',
                            'status_projeto' => 'Status do Projeto',
                            'cronograma'     => 'Cronograma',
                            'progresso'      => 'Progresso',
                            'fases'          => 'Fases',
                            'atrasadas'      => 'Atrasadas',
                            'inicio'         => 'Início',
                            'termino'        => 'Término',
                            'duracao'        => 'Duração',
                        ] as $colKey => $colLabel)
                            <label class="cr-col-option">
                                <input type="checkbox"
                                       :checked="mostrarColuna('{{ $colKey }}')"
                                       @change="toggleColuna('{{ $colKey }}')">
                                {{ $colLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($modoIndividual && $visualizacao === 'gantt')
                <div class="cr-zoom-controls">
                    <button class="vo-btn-outline" @click="zoomOut" title="Zoom Out" style="padding:7px 10px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </button>
                    <span style="font-size:0.7rem;color:var(--vo-text-muted);min-width:36px;text-align:center" x-text="Math.round(zoomLevel * 100) + '%'"></span>
                    <button class="vo-btn-outline" @click="zoomIn" title="Zoom In" style="padding:7px 10px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    </button>
                    <button class="vo-btn-outline" @click="zoomReset" title="Resetar Zoom" style="padding:7px 10px;font-size:0.65rem;">
                        1:1
                    </button>
                    <button class="vo-btn-outline" @click="toggleFullscreen" title="Tela cheia" style="padding:7px 10px;margin-left:4px;">
                        <svg x-show="!isFullscreen" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h6M4 4v6M20 20h-6M20 20v-6M20 4h-6M20 4v6M4 20h6M4 20v-6"/>
                        </svg>
                        <svg x-show="isFullscreen" x-cloak xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 4v6H3M21 10h-6V4M9 20v-6H3M21 14h-6v6"/>
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        {{-- Banner de modo historico --}}
        @if(($versaoAtiva ?? null) && $modoIndividual)
            <div class="cr-historico-banner">
                <div style="display:flex;align-items:center;gap:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vo-accent)" stroke-width="2"><path d="M12 8v4l3 3"/><path d="M3.05 11a9 9 0 1 1 .5 4"/><path d="M3 16v-5h5"/></svg>
                    <div>
                        <span style="font-size:0.78rem;font-weight:600;color:var(--vo-text);">Visualizando versão histórica</span>
                        <span style="font-size:0.72rem;color:var(--vo-text-muted);margin-left:8px;">
                            {{ \Carbon\Carbon::parse($versaoAtiva)->format('d/m/Y H:i') }}
                        </span>
                    </div>
                </div>
                <button type="button" class="vo-btn-accent" wire:click="voltarVersaoAtual" style="padding:5px 14px;font-size:0.75rem;">
                    Voltar ao atual
                </button>
            </div>
        @endif

        @if($modoIndividual && isset($projeto))
            @php $obraProjeto = $projeto->obras->first(); @endphp
            <div class="cr-obra-header">
                <div class="cr-obra-header-top">
                    <div>
                        @php
                            $templateAtivo = $projeto->cronogramaFases->firstWhere('cronograma_template_id')?->template;
                        @endphp
                        <div class="cr-obra-title">
                            <span>{{ $projeto->nome ?? 'Projeto #'.$projeto->id }}</span>
                            @if($templateAtivo)
                                <span style="color:var(--vo-text-faint);font-weight:400;font-size:0.9rem;margin:0 4px;">·</span>
                                <span style="font-size:0.8rem;font-weight:500;color:var(--vo-text-muted);">{{ $templateAtivo->nome }}</span>
                            @else
                                <span style="color:var(--vo-text-faint);font-weight:400;font-size:0.9rem;margin:0 4px;">·</span>
                                <span style="font-size:0.75rem;font-weight:500;color:var(--vo-text-faint);font-style:italic;">Sem template aplicado</span>
                            @endif
                        </div>
                        @php
                            $planInicio = $fases->whereNotNull('data_prevista_inicio')->min('data_prevista_inicio');
                            $planFim    = $fases->whereNotNull('data_prevista_fim')->max('data_prevista_fim');
                            $planDias   = ($planInicio && $planFim)
                                ? \Carbon\Carbon::parse($planInicio)->diffInDays(\Carbon\Carbon::parse($planFim)) + 1
                                : null;
                        @endphp
                        <div class="cr-obra-meta" style="display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
                            @if($projeto->codigo)<span>{{ $projeto->codigo }}</span>@endif
                            @if($projeto->estado)<span>&bull; {{ $projeto->estado->uf }}</span>@endif
                            @if($projeto->marca)<span>&bull; {{ $projeto->marca }}</span>@endif
                            @if($planInicio || $planFim)
                                @if($projeto->codigo || $projeto->estado || $projeto->marca)
                                    <span style="opacity:.4;">|</span>
                                @endif
                                @if($planInicio)
                                    <span style="font-size:0.7rem;">{{ \Carbon\Carbon::parse($planInicio)->format('d/m/Y') }}</span>
                                @endif
                                @if($planFim)
                                    <span style="opacity:.5;font-size:0.7rem;">—</span>
                                    <span style="font-size:0.7rem;">{{ \Carbon\Carbon::parse($planFim)->format('d/m/Y') }}</span>
                                @endif
                                @if($planDias)
                                    <span style="padding:1px 7px;background:rgba(0,0,0,.15);border-radius:99px;font-size:0.67rem;font-weight:700;">{{ $planDias }} dias</span>
                                @endif
                            @endif
                        </div>
                        @if($projeto->suframaPendente())
                            @php $diasInaug = $projeto->diasParaInauguracao(); @endphp
                            <div class="cr-suframa-pisca"
                                 style="display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:4px 10px;border-radius:.5rem;border:1px solid #ef4444;background:rgba(239,68,68,.08);color:#b91c1c;font-size:.7rem;font-weight:700;animation:cr-pisca 1.2s ease-in-out infinite;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                SUFRAMA — decisão pendente: faltam {{ $diasInaug }} dia(s) para a inauguração
                            </div>
                            <style>
                                @keyframes cr-pisca {
                                    0%, 100% { opacity: 1; }
                                    50% { opacity: 0.45; }
                                }
                            </style>
                        @endif
                    </div>
                    <div class="cr-obra-progress">
                        <div class="cr-obra-progress-bar">
                            <div class="cr-obra-progress-fill" style="width: {{ $percentualGeral }}%; background: {{ $percentualGeral == 100 ? '#166534' : ($fasesAtrasadas > 0 ? '#dc2626' : '#111') }}"></div>
                        </div>
                        @php
                            $pctCor = match(true) {
                                $percentualGeral === 100            => '#16a34a',
                                $fasesAtrasadas > 0                 => '#dc2626',
                                $percentualGeral >= 70              => '#16a34a',
                                $percentualGeral >= 40              => '#d97706',
                                default                             => '#dc2626',
                            };
                        @endphp
                        <span class="cr-obra-progress-text" style="color:{{ $pctCor }}">{{ $percentualGeral }}%</span>
                    </div>
                </div>

                <div class="cr-obra-header-details">
                    @if($projeto->status)
                        <div class="cr-detail-item">
                            <span class="cr-detail-label">Status</span>
                            <span class="cr-detail-value">{{ $projeto->status }}</span>
                        </div>
                    @endif

                    @if($projeto->responsavelCom)
                        <div class="cr-detail-item">
                            <span class="cr-detail-label">Comercial</span>
                            <span class="cr-detail-value">{{ $projeto->responsavelCom->name }}</span>
                        </div>
                    @endif

                    @if($projeto->cad_plan_inicio)
                        <div class="cr-detail-item">
                            <span class="cr-detail-label">Início de Projeto</span>
                            <span class="cr-detail-value">{{ \Carbon\Carbon::parse($projeto->cad_plan_inicio)->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if($projeto->data_posse)
                        <div class="cr-detail-item">
                            <span class="cr-detail-label">Posse</span>
                            <span class="cr-detail-value">{{ \Carbon\Carbon::parse($projeto->data_posse)->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if($obraProjeto?->inicio)
                        <div class="cr-detail-item">
                            <span class="cr-detail-label">Início Obra</span>
                            <span class="cr-detail-value">{{ $obraProjeto->inicio->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if($obraProjeto?->fim)
                        <div class="cr-detail-item">
                            <span class="cr-detail-label">Fim Obra</span>
                            <span class="cr-detail-value">{{ $obraProjeto->fim->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    @if($projeto->inauguracao)
                        <div class="cr-detail-item">
                            <span class="cr-detail-label">Inauguração</span>
                            <span class="cr-detail-value">{{ \Carbon\Carbon::parse($projeto->inauguracao)->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </div>

                <div class="cr-stats-row">
                    <div class="cr-stat">
                        <span class="cr-stat-number" style="color: var(--cr-concluido)">{{ $fasesConcluidas }}</span>
                        <span>Concluídas</span>
                    </div>
                    <div class="cr-stat">
                        <span class="cr-stat-number" style="color: var(--cr-em-andamento)">{{ $fasesEmAndamento }}</span>
                        <span>Em Andamento</span>
                    </div>
                    <div class="cr-stat">
                        <span class="cr-stat-number" style="color: var(--cr-atrasado)">{{ $fasesAtrasadas }}</span>
                        <span>Atrasadas</span>
                    </div>
                    <div class="cr-stat">
                        <span class="cr-stat-number" style="color: var(--vo-text-muted)">{{ $totalFases - $fasesConcluidas - $fasesEmAndamento - $fasesAtrasadas }}</span>
                        <span>Não Iniciadas</span>
                    </div>
                    <div class="cr-stat">
                        <span class="cr-stat-number" style="color: var(--vo-text)">{{ $duracaoTotalDias }}</span>
                        <span>Duração Total (dias)</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Banner de limites operacionais (PR 5 — reunião 07/05) --}}
        @if($modoIndividual && isset($projeto) && isset($limitesResumo))
            @php
                $limitesViolados = collect([
                    $limitesResumo['briefing_obras'] ?? null,
                    $limitesResumo['inicio_posse'] ?? null,
                ])->filter(fn ($l) => $l && ($l['violado'] ?? false));
            @endphp
            @if($limitesViolados->isNotEmpty())
                <div style="margin:0 16px 12px;padding:10px 14px;border:1px solid #fbbf24;background:#fffbeb;border-radius:.5rem;display:flex;align-items:flex-start;gap:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" style="flex-shrink:0;margin-top:2px;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <div style="flex:1;">
                        <div style="font-size:0.78rem;font-weight:700;color:#92400e;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em;">
                            Limites operacionais violados
                        </div>
                        <ul style="margin:0;padding-left:14px;font-size:0.78rem;color:#92400e;line-height:1.5;">
                            @foreach($limitesViolados as $lim)
                                <li>{{ $lim['mensagem'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endif

        @php
            $items = $modoIndividual && isset($fases) ? $fases : ($projetos ?? collect());
            $hasData = $items->isNotEmpty();
        @endphp

        @if(!$hasData)
            <div class="cr-empty">Nenhum projeto encontrado.</div>
        @elseif(!$modoIndividual)
            {{-- VISAO MACRO: Tabela de resumo --}}
            @php
                $chipsAtivos = [];
                if ($busca) {
                    $chipsAtivos[] = ['campo' => 'busca', 'label' => 'Busca', 'valor' => '"'.$busca.'"'];
                }
                if ($filtroEstado) {
                    $estadoChip = ($estadosDisponiveis ?? collect())->firstWhere('id', $filtroEstado);
                    $chipsAtivos[] = ['campo' => 'filtroEstado', 'label' => 'Estado', 'valor' => $estadoChip?->uf ?? $filtroEstado];
                }
                if ($filtroStatusObra) {
                    $chipsAtivos[] = ['campo' => 'filtroStatusObra', 'label' => 'Status Projeto', 'valor' => $filtroStatusObra];
                }
                if ($filtroStatus) {
                    $statusChipEnum = collect($statusOptions ?? [])->first(fn($s) => $s->value === $filtroStatus);
                    $chipsAtivos[] = ['campo' => 'filtroStatus', 'label' => 'Cronograma', 'valor' => $statusChipEnum?->label() ?? $filtroStatus];
                }
                if ($filtroPeriodo) {
                    $periodoLabelsMap = [
                        'ultimo_mes' => 'Último mês', 'ultimos_3_meses' => 'Últimos 3 meses',
                        'ultimos_6_meses' => 'Últimos 6 meses', 'proximo_mes' => 'Próximo mês',
                        'proximos_3_meses' => 'Próximos 3 meses', 'proximos_6_meses' => 'Próximos 6 meses',
                    ];
                    $chipsAtivos[] = ['campo' => 'filtroPeriodo', 'label' => 'Período', 'valor' => $periodoLabelsMap[$filtroPeriodo] ?? $filtroPeriodo];
                }
                if ($filtroTemplate) {
                    $chipsAtivos[] = ['campo' => 'filtroTemplate', 'label' => 'Template', 'valor' => $filtroTemplate === 'com_template' ? 'Com template' : 'Sem template'];
                }
            @endphp
            @if(count($chipsAtivos) > 0)
                <div class="cr-filter-chips">
                    @foreach($chipsAtivos as $chip)
                        <span class="cr-filter-chip">
                            <span style="opacity:.65;margin-right:1px;">{{ $chip['label'] }}:</span>
                            {{ $chip['valor'] }}
                            <button wire:click="$set('{{ $chip['campo'] }}', '')" title="Remover filtro">×</button>
                        </span>
                    @endforeach
                    @if(count($chipsAtivos) > 1)
                        <button type="button" wire:click="limparFiltrosMacro" class="cr-filter-chips-clear">
                            Limpar tudo ×
                        </button>
                    @endif
                </div>
            @endif

            <div class="cr-table-wrap">
                <table class="cr-table">
                    <thead>
                        <tr>
                            <th class="cr-th-sticky">Código</th>
                            <th>Nome</th>
                            <th x-show="mostrarColuna('marca')">Marca</th>
                            <th x-show="mostrarColuna('uf')">UF</th>
                            <th x-show="mostrarColuna('template')">Template</th>
                            <th x-show="mostrarColuna('status_projeto')">Status</th>
                            <th x-show="mostrarColuna('cronograma')">Cronograma</th>
                            <th x-show="mostrarColuna('progresso')" style="min-width:180px">Progresso</th>
                            <th x-show="mostrarColuna('fases')">Fases</th>
                            <th x-show="mostrarColuna('atrasadas')">Atrasadas</th>
                            <th x-show="mostrarColuna('inicio')">Início</th>
                            <th x-show="mostrarColuna('termino')">Término</th>
                            <th x-show="mostrarColuna('duracao')">Duração</th>
                            <th style="width:48px;text-align:center;">Hist.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projetos as $projetoItem)
                            @php
                                $limMin = now()->subYears(5);
                                $limMax = now()->addYears(5);
                                $fasesProjeto = $projetoItem->cronogramaFases;
                                $totalF = $fasesProjeto->count();
                                $conclF = $fasesProjeto->where('status.value', 'concluido')->count();
                                $atrasF = $fasesProjeto->where('status.value', 'atrasado')->count();
                                $andamF = $fasesProjeto->where('status.value', 'em_andamento')->count();
                                $pctF = $totalF > 0 ? (int) round($fasesProjeto->avg('percentual_conclusao')) : 0;

                                $datasValidas = fn($col) => $fasesProjeto->pluck($col)->filter()->filter(fn($d) => $d->gte($limMin) && $d->lte($limMax));
                                $primeiraData = $datasValidas('data_prevista_inicio')->min();
                                $ultimaData = $datasValidas('data_prevista_fim')->max();
                                $duracao = ($primeiraData && $ultimaData) ? $primeiraData->diffInDays($ultimaData) + 1 : null;

                                $statusColor = match(true) {
                                    $atrasF > 0 => 'var(--cr-atrasado)',
                                    $conclF === $totalF && $totalF > 0 => 'var(--cr-concluido)',
                                    $pctF === 100 => 'var(--cr-concluido)',
                                    $andamF > 0 => 'var(--cr-em-andamento)',
                                    default => 'var(--cr-nao-iniciado)',
                                };
                            @endphp
                            <tr class="cr-table-row" wire:click="selecionarProjeto({{ $projetoItem->id }})">
                                <td class="cr-td-sticky" style="font-size:0.73rem;color:var(--vo-text-muted);font-variant-numeric:tabular-nums;">
                                    {{ $projetoItem->codigo ?? '-' }}
                                </td>
                                <td class="cr-td-unidade">
                                    {{ $projetoItem->nome ?? 'Projeto #'.$projetoItem->id }}
                                </td>
                                <td x-show="mostrarColuna('marca')" style="font-size:0.73rem;color:var(--vo-text-secondary)">
                                    {{ $projetoItem->marca ?? '-' }}
                                </td>
                                <td x-show="mostrarColuna('uf')" class="cr-td-center">
                                    <span class="cr-uf-badge">{{ $projetoItem->estado?->uf ?? '-' }}</span>
                                </td>
                                <td x-show="mostrarColuna('template')">
                                    @php $templateMacro = $projetoItem->cronogramaFases->first(fn($f) => $f->cronograma_template_id)?->template; @endphp
                                    @if($templateMacro)
                                        <span style="font-size:0.72rem;color:var(--vo-text-secondary);white-space:nowrap;">{{ $templateMacro->nome }}</span>
                                    @else
                                        <span style="font-size:0.72rem;color:var(--vo-text-faint);font-style:italic;">—</span>
                                    @endif
                                </td>
                                <td x-show="mostrarColuna('status_projeto')">
                                    <span style="font-size:0.73rem;color:var(--vo-text-secondary)">{{ $projetoItem->status ?? '-' }}</span>
                                </td>
                                <td x-show="mostrarColuna('cronograma')">
                                    <span class="cr-status-pill" style="background: {{ $statusColor }}">
                                        @if($atrasF > 0)
                                            Atrasado
                                        @elseif(($conclF === $totalF && $totalF > 0) || $pctF === 100)
                                            Concluído
                                        @elseif($andamF > 0)
                                            Em Andamento
                                        @else
                                            Não Iniciado
                                        @endif
                                    </span>
                                </td>
                                <td x-show="mostrarColuna('progresso')">
                                    <div class="cr-progress-cell">
                                        <div class="cr-progress-track">
                                            <div class="cr-progress-fill" style="width: {{ $pctF }}%; background: {{ $statusColor }}"></div>
                                        </div>
                                        <span class="cr-progress-pct">{{ $pctF }}%</span>
                                    </div>
                                </td>
                                <td x-show="mostrarColuna('fases')" class="cr-td-center">
                                    <span class="cr-fases-count">{{ $conclF }}/{{ $totalF }}</span>
                                </td>
                                <td x-show="mostrarColuna('atrasadas')" class="cr-td-center">
                                    @if($atrasF > 0)
                                        <span class="cr-atrasadas-badge">{{ $atrasF }}</span>
                                    @else
                                        <span style="color:var(--vo-text-faint)">0</span>
                                    @endif
                                </td>
                                <td x-show="mostrarColuna('inicio')" class="cr-td-date">
                                    {{ $primeiraData?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td x-show="mostrarColuna('termino')" class="cr-td-date">
                                    {{ $ultimaData?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td x-show="mostrarColuna('duracao')" class="cr-td-center">
                                    @if($duracao !== null)
                                        {{ $duracao }} dias
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="text-align:center;white-space:nowrap;" wire:click.stop>
                                    <button type="button"
                                            wire:click.stop="duplicarProjeto({{ $projetoItem->id }})"
                                            title="Duplicar este planejamento"
                                            style="padding:3px 5px;border:1px solid var(--vo-border);background:transparent;border-radius:.25rem;cursor:pointer;color:var(--vo-text-muted);line-height:1;margin-right:3px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    </button>
                                    <button type="button"
                                            wire:click.stop="confirmarExcluirPlanejamento({{ $projetoItem->id }})"
                                            title="Excluir este planejamento"
                                            style="padding:3px 5px;border:1px solid var(--vo-border);background:transparent;border-radius:.25rem;cursor:pointer;color:var(--vo-text-muted);line-height:1;margin-right:3px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                    <button type="button"
                                            wire:click.stop="abrirHistoricoProjeto({{ $projetoItem->id }})"
                                            title="Histórico de alterações deste projeto"
                                            style="padding:3px 5px;border:1px solid var(--vo-border);background:transparent;border-radius:.25rem;cursor:pointer;color:var(--vo-text-muted);line-height:1;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(isset($totalPaginas) && $totalPaginas > 1)
                <div class="cr-pagination">
                    <button class="vo-btn-outline" wire:click="irParaPagina({{ $paginaAtual - 1 }})" @if($paginaAtual <= 1) disabled @endif style="@if($paginaAtual <= 1) opacity:0.4;cursor:not-allowed @endif">
                        Anterior
                    </button>
                    <span>{{ $paginaAtual }} / {{ $totalPaginas }}</span>
                    <button class="vo-btn-outline" wire:click="irParaPagina({{ $paginaAtual + 1 }})" @if($paginaAtual >= $totalPaginas) disabled @endif style="@if($paginaAtual >= $totalPaginas) opacity:0.4;cursor:not-allowed @endif">
                        Seguinte
                    </button>
                </div>
            @endif
        @else
            @if($visualizacao === 'gantt')
            {{-- VISAO INDIVIDUAL: Gantt calendário dia-a-dia --}}
            @php
                $inicioTimeline = $timeline['inicio'];
                $diasTimeline = $timeline['dias'];
                $totalDiasTl = count($diasTimeline);

                // Agrupa os dias por mês para montar a linha de mês com colspan implícito.
                $mesesAgrupados = [];
                foreach ($diasTimeline as $idx => $d) {
                    if (! isset($mesesAgrupados[$d['mesLabel']])) {
                        $mesesAgrupados[$d['mesLabel']] = ['label' => $d['mesLabel'], 'dias' => 0];
                    }
                    $mesesAgrupados[$d['mesLabel']]['dias']++;
                }

                $limMin = now()->subYears(5);
                $limMax = now()->addYears(5);
                $dataValida = fn($d) => $d && $d->gte($limMin) && $d->lte($limMax);

                // Pré-calcula mapa de células por fase: para cada dia, qual o status visual.
                $mapaFases = [];
                foreach ($fases as $fase) {
                    $prevI = $dataValida($fase->data_prevista_inicio) ? $fase->data_prevista_inicio->toDateString() : null;
                    $prevF = $dataValida($fase->data_prevista_fim) ? $fase->data_prevista_fim->toDateString() : null;
                    $realI = $dataValida($fase->data_realizada_inicio) ? $fase->data_realizada_inicio->toDateString() : null;
                    $realF = $dataValida($fase->data_realizada_fim) ? $fase->data_realizada_fim->toDateString() : null;
                    $hojeStr = now()->toDateString();
                    $concluido = \App\Services\CronogramaTemplateService::bloqueadoRecalculo($fase->status);
                    $emAndamento = $fase->status === \App\Enums\StatusCronograma::EM_ANDAMENTO;
                    $atrasado = $fase->status === \App\Enums\StatusCronograma::ATRASADO;
                    $isAncora = (bool) ($fase->templateFase?->is_ancora);
                    $cor = $fase->status->color();

                    $ultrapassouPrazo = $prevF && $prevF < $hojeStr && ! $concluido;
                    $concluidoComAtraso = $concluido && $realF && $prevF && $realF > $prevF;
                    $estaAtrasada = $atrasado || $ultrapassouPrazo || $concluidoComAtraso;

                    $mapaFases[$fase->id] = [
                        'fase' => $fase,
                        'isAncora' => $isAncora,
                        'cor' => $cor,
                        'prevI' => $prevI, 'prevF' => $prevF,
                        'realI' => $realI, 'realF' => $realF,
                        'concluido' => $concluido,
                        'emAndamento' => $emAndamento,
                        'atrasado' => $atrasado,
                        'estaAtrasada' => $estaAtrasada,
                    ];
                }
            @endphp

            <div class="cr-container cr-cal" x-ref="ganttContainer">
                <div class="cr-left" x-ref="ganttLeft" :style="`--cr-left-w: ${ganttLeftW}`">
                    <div class="cr-header-left" style="height:76px;display:flex;align-items:stretch;padding:0;">
                        <div class="cr-col-fase" style="align-items:flex-end;padding-bottom:8px;">
                            <span>Fase</span>
                            <button type="button"
                                    @click="mostrarDeps = !mostrarDeps"
                                    :title="mostrarDeps ? 'Ocultar coluna de dependências' : 'Mostrar coluna de dependências'"
                                    style="margin-left:8px;padding:2px 6px;font-size:0.65rem;border:1px solid var(--vo-border);border-radius:.25rem;background:var(--vo-bg-subtle);cursor:pointer;color:var(--vo-text-muted);"
                                    x-text="mostrarDeps ? 'Ocultar deps' : 'Mostrar deps'"></button>
                        </div>
                        <div class="cr-gantt-col cr-gantt-col-header cr-gantt-col-status" x-show="ganttCols.status" x-cloak>Status</div>
                        <div class="cr-gantt-col cr-gantt-col-header cr-gantt-col-pct"    x-show="ganttCols.pct"      x-cloak>%</div>
                        <div class="cr-gantt-col cr-gantt-col-header cr-gantt-col-plan"   x-show="ganttCols.planejado" x-cloak>Planejado</div>
                        <div class="cr-gantt-col cr-gantt-col-header cr-gantt-col-real"   x-show="ganttCols.realizado" x-cloak>Realizado</div>
                        <div class="cr-col-deps" x-show="mostrarDeps" x-cloak style="align-items:flex-end;padding-bottom:8px;">Dependências</div>
                    </div>
                    @foreach($fases as $fase)
                        @php
                            $regraLeft = $fase->regraEfetiva();
                            $depsLeft = [];
                            if ($regraLeft) {
                                foreach ($regraLeft->dependencias as $dep) {
                                    $depEnumL = $dep->depende_de_fase instanceof \App\Enums\FaseCronograma
                                        ? $dep->depende_de_fase
                                        : \App\Enums\FaseCronograma::tryFrom((string) $dep->depende_de_fase);
                                    if (! $depEnumL) continue;
                                    $gatEnumL = $dep->gatilho instanceof \App\Enums\GatilhoTemplateFase
                                        ? $dep->gatilho
                                        : \App\Enums\GatilhoTemplateFase::tryFrom((string) $dep->gatilho);
                                    $gatTxtL = $gatEnumL?->labelCurto() ?? 'fim';
                                    $gapL = (int) $dep->gap_dias;
                                    $gapTxtL = $gapL === 0 ? '' : ($gapL > 0 ? ' +'.$gapL.'d' : ' '.$gapL.'d');
                                    $depsLeft[] = $depEnumL->label().' ('.$gatTxtL.')'.$gapTxtL;
                                }
                            }
                            $isAncoraLeft = (bool) ($fase->templateFase?->is_ancora);
                        @endphp
                        @php $farolRow = $fase->farol; $qtdItensRow = $fase->itens->count(); @endphp
                        <div class="cr-row-left {{ ($mapaFases[$fase->id]['isAncora'] ?? false) ? 'cr-row-ancora' : '' }} {{ $farolRow !== 'neutro' ? 'cr-row-'.$farolRow : '' }}"
                             @click="highlightRow({{ $fase->id }}); if ({{ $qtdItensRow }}) $wire.alternarExpansaoFase({{ $fase->id }})"
                             :class="{ 'cr-row-highlighted': highlightedRow === {{ $fase->id }} }"
                             style="{{ $qtdItensRow > 0 ? 'cursor:pointer;' : '' }}">
                            <div class="cr-col-fase">
                                @php
                                    $farol = $fase->farol;
                                    $farolCor = match ($farol) {
                                        'vermelho' => 'var(--cr-atrasado, #ff4d6a)',
                                        'amarelo' => '#f5ba00',
                                        'verde' => 'var(--cr-concluido, #2dd67c)',
                                        default => 'transparent',
                                    };
                                    $farolTitulo = match ($farol) {
                                        'vermelho' => 'Atraso crítico',
                                        'amarelo' => 'Leve atraso',
                                        'verde' => 'Em dia',
                                        default => 'Sem indicador',
                                    };
                                @endphp
                                @if($farol !== 'neutro')
                                    <span class="cr-farol" title="{{ $farolTitulo }}" style="background:{{ $farolCor }}"></span>
                                @endif
                                <span class="fase-label" title="{{ $fase->label_exibicao }}">{{ $fase->label_exibicao }}</span>
                                @if($fase->bloqueada_pos_contrato)
                                    <span class="cr-fase-cadeado" title="Fase bloqueada após assinatura do contrato"
                                          style="display:inline-flex;align-items:center;color:#92400e;flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    </span>
                                @endif
                                @if($fase->percentual_conclusao > 0 && $fase->percentual_conclusao < 100)
                                    <span class="cr-pct-inline" title="Percentual de conclusão">{{ $fase->percentual_conclusao }}%</span>
                                @endif
                                @php
                                    $qtdItens = $fase->itens->count();
                                    $faseExpandida = in_array($fase->id, $fasesExpandidas, true);
                                @endphp
                                <button type="button" class="cr-checklist-btn {{ $faseExpandida ? 'cr-checklist-btn-open' : '' }}"
                                        wire:click.stop="alternarExpansaoFase({{ $fase->id }})"
                                        title="{{ $faseExpandida ? 'Recolher subitens' : 'Expandir subitens' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>
                                    {{ $qtdItens > 0 ? $qtdItens : '+' }}
                                </button>
                                <div class="cr-status-dropdown cr-status-sm" x-data="{ open: false, pos: {top:0,left:0}, reposition() { const r = this.$refs.trigger.getBoundingClientRect(); this.pos = {top: r.bottom + 4, left: r.left}; } }" @click.stop @click.outside="open = false">
                                    <button type="button" class="cr-status-trigger" style="background:{{ $fase->status->color() }}" x-ref="trigger"
                                            @click="reposition(); open = !open" :aria-expanded="open">
                                        <span class="cr-status-dot"></span>
                                        {{ $fase->status->label() }}
                                        <svg class="cr-status-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/></svg>
                                    </button>
                                    <template x-teleport="body">
                                    <div class="cr-status-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms :style="`top:${pos.top}px;left:${pos.left}px`" @click.outside="open = false">
                                        @foreach($fase->fase->statusDisponiveis() as $s)
                                            <button type="button"
                                                    class="cr-status-option {{ $fase->status === $s ? 'cr-status-active' : '' }}"
                                                    wire:click="alterarStatusFase({{ $fase->id }}, '{{ $s->value }}')"
                                                    @click="open = false">
                                                <span class="cr-opt-dot" style="background:{{ $s->color() }}"></span>
                                                {{ $s->label() }}
                                            </button>
                                        @endforeach
                                    </div>
                                    </template>
                                </div>
                            </div>
                            @php
                                $mLeft = $mapaFases[$fase->id] ?? [];
                                $gPrevI = $mLeft['prevI'] ?? null;
                                $gPrevF = $mLeft['prevF'] ?? null;
                                $gRealI = $mLeft['realI'] ?? null;
                                $gRealF = $mLeft['realF'] ?? null;
                            @endphp
                            <div class="cr-gantt-col cr-gantt-col-status" x-show="ganttCols.status" x-cloak>
                                <span class="cr-status-pill" style="background:{{ $fase->status->color() }};font-size:0.6rem;padding:2px 8px;">
                                    {{ $fase->status->label() }}
                                </span>
                            </div>
                            <div class="cr-gantt-col cr-gantt-col-pct" x-show="ganttCols.pct" x-cloak
                                 style="font-weight:700;font-size:0.72rem;color:{{ $fase->percentual_conclusao >= 100 ? 'var(--cr-concluido)' : ($fase->percentual_conclusao > 0 ? 'var(--cr-em-andamento)' : 'var(--vo-text-faint)') }}">
                                {{ $fase->percentual_conclusao }}%
                            </div>
                            <div class="cr-gantt-col cr-gantt-col-plan" x-show="ganttCols.planejado" x-cloak
                                 style="font-variant-numeric:tabular-nums;font-size:0.7rem;">
                                @if($gPrevI && $gPrevF)
                                    {{ \Carbon\Carbon::parse($gPrevI)->format('d/m/y') }}
                                    <span style="color:var(--vo-text-faint);">–</span>
                                    {{ \Carbon\Carbon::parse($gPrevF)->format('d/m/y') }}
                                @else
                                    <span style="color:var(--vo-text-faint);">—</span>
                                @endif
                            </div>
                            <div class="cr-gantt-col cr-gantt-col-real" x-show="ganttCols.realizado" x-cloak
                                 style="font-variant-numeric:tabular-nums;font-size:0.7rem;">
                                @if($gRealI && $gRealF)
                                    {{ \Carbon\Carbon::parse($gRealI)->format('d/m/y') }}
                                    <span style="color:var(--vo-text-faint);">–</span>
                                    {{ \Carbon\Carbon::parse($gRealF)->format('d/m/y') }}
                                @elseif($gRealI)
                                    {{ \Carbon\Carbon::parse($gRealI)->format('d/m/y') }}
                                    <span style="color:var(--vo-text-faint);">– em curso</span>
                                @else
                                    <span style="color:var(--vo-text-faint);">—</span>
                                @endif
                            </div>
                            <div class="cr-col-deps" x-show="mostrarDeps" x-cloak>
                                @if(! $fase->cronograma_template_id)
                                    <span style="color:var(--vo-text-faint);font-size:0.65rem;">—</span>
                                @else
                                    @if($isAncoraLeft)
                                        <span class="cr-dep-pill cr-dep-ancora">⚓ Âncora</span>
                                    @endif
                                    @if(!empty($depsLeft))
                                        <div class="cr-deps-wrap">
                                            <span class="cr-dep-pill">← {{ $depsLeft[0] }}</span>
                                            @if(count($depsLeft) > 1)
                                                <span class="cr-dep-pill" style="cursor:help;">+{{ count($depsLeft) - 1 }}</span>
                                                <div class="cr-deps-tooltip">
                                                    @foreach($depsLeft as $depTxt)
                                                        <span class="cr-dep-pill">← {{ $depTxt }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @elseif(!$isAncoraLeft)
                                        <span style="color:var(--vo-text-faint);font-size:0.65rem;">—</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @if(in_array($fase->id, $fasesExpandidas, true))
                            @foreach($fase->itens->whereNull('parent_id')->sortBy('ordem') as $item)
                                @include('filament.pages.cronograma-subitem-gantt', ['item' => $item, 'depth' => 0, 'fasesDependencia' => $fases])
                            @endforeach
                            <div class="cr-row-left cr-subitem-gantt-row cr-subitem-add-row">
                                <div class="cr-col-fase">
                                    <span class="cr-subitem-tree">+</span>
                                    <input type="text"
                                           wire:model="novoSubitemTitulos.{{ $fase->id }}"
                                           wire:keydown.enter.prevent="adicionarSubitem({{ $fase->id }})"
                                           placeholder="Adicionar novo subitem…"
                                           class="cr-subitem-titulo-inline">
                                    <button type="button" wire:click="adicionarSubitem({{ $fase->id }})" class="cr-subitem-add-btn">
                                        Adicionar
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="cr-right cr-cal-right" x-ref="ganttRight"
                     :style="'--cr-ppd: ' + ppd + 'px'">

                    {{-- Header calendário: linha de mês + dia + dia-da-semana --}}
                    <div class="cr-cal-header" :style="'width: calc({{ $totalDiasTl }} * var(--cr-ppd)); min-width: 100%;'">
                        <div class="cr-cal-header-row cr-cal-header-mes">
                            @foreach($mesesAgrupados as $mes)
                                <div class="cr-cal-header-mes-cell" :style="'width: calc({{ $mes['dias'] }} * var(--cr-ppd))'">
                                    <span x-show="labelVisivel({{ $mes['dias'] }}, 0)">{{ $mes['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="cr-cal-header-row cr-cal-header-dia">
                            @foreach($diasTimeline as $idx => $d)
                                <div class="cr-cal-day-cell {{ $d['isWeekend'] ? 'cr-weekend' : '' }} {{ $d['isHoje'] ? 'cr-hoje-col' : '' }}"
                                     :class="highlightedCol === {{ $idx }} ? 'cr-col-highlighted' : ''"
                                     @click="toggleCol({{ $idx }})"
                                     title="{{ \Carbon\Carbon::parse($d['data'])->format('d/m/Y') }}">
                                    <span class="cr-day-num">{{ $d['dia'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="cr-cal-header-row cr-cal-header-dow">
                            @foreach($diasTimeline as $idx => $d)
                                <div class="cr-cal-day-cell {{ $d['isWeekend'] ? 'cr-weekend' : '' }} {{ $d['isHoje'] ? 'cr-hoje-col' : '' }}"
                                     :class="highlightedCol === {{ $idx }} ? 'cr-col-highlighted' : ''"
                                     @click="toggleCol({{ $idx }})">
                                    <span class="cr-dow-letter">{{ $d['dow'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Corpo: linha de células por fase --}}
                    <div :style="'position: relative; width: calc({{ $totalDiasTl }} * var(--cr-ppd)); min-width: 100%'">
                        @if($timeline['diasHoje'] >= 0 && $timeline['diasHoje'] <= $totalDiasTl)
                            <div class="cr-today-line" :style="'left: calc({{ $timeline['diasHoje'] }} * var(--cr-ppd) + (var(--cr-ppd) / 2))'">
                                <div class="cr-today-label">Hoje</div>
                            </div>
                        @endif

                        @foreach($fases as $fase)
                            @php
                                $m = $mapaFases[$fase->id]; $contadorPrev = 0;
                                $ancoraIdx = null;
                                if ($m['isAncora'] && $m['prevI']) {
                                    $ancoraIdx = (int) $timeline['inicio']->diffInDays(\Carbon\Carbon::parse($m['prevI']));
                                }
                            @endphp
                            @php $farolCal = $fase->farol; @endphp
                            <div class="cr-cal-row {{ $m['isAncora'] ? 'cr-row-ancora' : '' }} {{ $farolCal !== 'neutro' ? 'cr-row-'.$farolCal : '' }}"
                                 @click="highlightRow({{ $fase->id }})"
                                 :class="{ 'cr-row-highlighted': highlightedRow === {{ $fase->id }} }">
                                @if($ancoraIdx !== null)
                                    <div class="cr-ancora-marker"
                                         :style="'left: calc({{ $ancoraIdx }} * var(--cr-ppd) + (var(--cr-ppd) / 2))'"
                                         title="Âncora do template"></div>
                                @endif
                                @foreach($diasTimeline as $idx => $d)
                                    @php
                                        $dataDia = $d['data'];
                                        $inPrev = $m['prevI'] && $m['prevF'] && $dataDia >= $m['prevI'] && $dataDia <= $m['prevF'];
                                        $inReal = $m['realI'] && $m['realF'] && $dataDia >= $m['realI'] && $dataDia <= $m['realF'];
                                        $temReal = $m['realI'] && $m['realF'];
                                        $classe = 'cr-cal-cell';
                                        if ($d['isWeekend']) $classe .= ' cr-weekend';
                                        if ($d['isHoje']) $classe .= ' cr-hoje-col';

                                        if ($temReal) {
                                            // Fase com datas realizadas: verde = previsto E realizado;
                                            // vermelho = realizado fora do previsto (atraso); tracejado = previsto não realizado.
                                            if ($inPrev && $inReal) {
                                                $classe .= ' cr-cell-real';
                                            } elseif ($inReal) {
                                                $classe .= ' cr-cell-atraso';
                                            } elseif ($inPrev) {
                                                $classe .= ' cr-cell-prev';
                                            }
                                        } elseif ($inPrev && $m['atrasado']) {
                                            $classe .= ' cr-cell-atraso';
                                        } elseif ($inPrev && $m['concluido']) {
                                            // Fase concluída sem datas realizadas registradas — fallback verde no previsto.
                                            $classe .= ' cr-cell-real';
                                        } elseif ($inPrev) {
                                            $classe .= ' cr-cell-prev';
                                        }
                                        $bgStyle = '';
                                        $numero = null;
                                        if (str_contains($classe, 'cr-cell-real')) {
                                            $bgStyle = "background:{$m['cor']};border-color:{$m['cor']};";
                                            $contadorPrev++;
                                            $numero = $contadorPrev;
                                        } elseif (str_contains($classe, 'cr-cell-atraso')) {
                                            $contadorPrev++;
                                            $numero = $contadorPrev;
                                        } elseif (str_contains($classe, 'cr-cell-prev')) {
                                            $bgStyle = "border-color:{$m['cor']};color:{$m['cor']};";
                                            $contadorPrev++;
                                            $numero = $contadorPrev;
                                        }
                                    @endphp
                                    <div class="{{ $classe }}"
                                         :class="highlightedCol === {{ $idx }} ? 'cr-col-highlighted' : ''"
                                         @click.stop="toggleCol({{ $idx }})"
                                         style="{{ $bgStyle }}"
                                         title="{{ $fase->label_exibicao }} — {{ \Carbon\Carbon::parse($d['data'])->format('d/m/Y') }}">
                                        @if($numero !== null)<span class="cr-cell-num">{{ $numero }}</span>@endif
                                    </div>
                                @endforeach
                            </div>
                            @if(in_array($fase->id, $fasesExpandidas, true))
                                @foreach($fase->itens->whereNull('parent_id')->sortBy('ordem') as $item)
                                    @php
                                        $si_pI = $item->data_prevista_inicio  ? \Carbon\Carbon::parse($item->data_prevista_inicio)->format('Y-m-d')  : null;
                                        $si_pF = $item->data_prevista_fim     ? \Carbon\Carbon::parse($item->data_prevista_fim)->format('Y-m-d')     : null;
                                        $si_rI = $item->data_realizada_inicio ? \Carbon\Carbon::parse($item->data_realizada_inicio)->format('Y-m-d') : null;
                                        $si_rF = $item->data_realizada_fim    ? \Carbon\Carbon::parse($item->data_realizada_fim)->format('Y-m-d')    : null;
                                        $si_tR = $si_rI && $si_rF;
                                    @endphp
                                    <div class="cr-subitem-cal-row" wire:key="scr-{{ $item->id }}">
                                        @foreach($diasTimeline as $idx => $d)
                                            @php
                                                $dd=$d['data']; $inP=$si_pI&&$si_pF&&$dd>=$si_pI&&$dd<=$si_pF; $inR=$si_rI&&$si_rF&&$dd>=$si_rI&&$dd<=$si_rF;
                                                $cls='cr-cal-cell';
                                                if($d['isWeekend'])$cls.=' cr-weekend'; if($d['isHoje'])$cls.=' cr-hoje-col';
                                                if($si_tR){if($inP&&$inR)$cls.=' cr-cell-real';elseif($inR)$cls.=' cr-cell-atraso';elseif($inP)$cls.=' cr-cell-prev';}
                                                elseif($inP){$cls.=$item->recebido?' cr-cell-real':' cr-cell-prev';}
                                            @endphp
                                            <div class="{{ $cls }}" :class="highlightedCol==={{ $idx }}?'cr-col-highlighted':''" @click.stop="toggleCol({{ $idx }})" title="{{ $item->titulo }} — {{ \Carbon\Carbon::parse($d['data'])->format('d/m/Y') }}"></div>
                                        @endforeach
                                    </div>
                                    @if($expandindoFilhosDeItemId === $item->id)
                                        <div class="cr-subitem-gantt-row-spacer"></div>
                                    @endif
                                    @foreach($item->children as $child)
                                        @php
                                            $sc_pI = $child->data_prevista_inicio  ? \Carbon\Carbon::parse($child->data_prevista_inicio)->format('Y-m-d')  : null;
                                            $sc_pF = $child->data_prevista_fim     ? \Carbon\Carbon::parse($child->data_prevista_fim)->format('Y-m-d')     : null;
                                            $sc_rI = $child->data_realizada_inicio ? \Carbon\Carbon::parse($child->data_realizada_inicio)->format('Y-m-d') : null;
                                            $sc_rF = $child->data_realizada_fim    ? \Carbon\Carbon::parse($child->data_realizada_fim)->format('Y-m-d')    : null;
                                            $sc_tR = $sc_rI && $sc_rF;
                                        @endphp
                                        <div class="cr-subitem-cal-row" wire:key="scr-c-{{ $child->id }}">
                                            @foreach($diasTimeline as $idx => $d)
                                                @php
                                                    $dd=$d['data']; $inP=$sc_pI&&$sc_pF&&$dd>=$sc_pI&&$dd<=$sc_pF; $inR=$sc_rI&&$sc_rF&&$dd>=$sc_rI&&$dd<=$sc_rF;
                                                    $cls='cr-cal-cell';
                                                    if($d['isWeekend'])$cls.=' cr-weekend'; if($d['isHoje'])$cls.=' cr-hoje-col';
                                                    if($sc_tR){if($inP&&$inR)$cls.=' cr-cell-real';elseif($inR)$cls.=' cr-cell-atraso';elseif($inP)$cls.=' cr-cell-prev';}
                                                    elseif($inP){$cls.=$child->recebido?' cr-cell-real':' cr-cell-prev';}
                                                @endphp
                                                <div class="{{ $cls }}" :class="highlightedCol==={{ $idx }}?'cr-col-highlighted':''" @click.stop="toggleCol({{ $idx }})" title="{{ $child->titulo }} — {{ \Carbon\Carbon::parse($d['data'])->format('d/m/Y') }}"></div>
                                            @endforeach
                                        </div>
                                        @if($expandindoFilhosDeItemId === $child->id)
                                            <div class="cr-subitem-gantt-row-spacer"></div>
                                        @endif
                                        @foreach($child->children as $grandchild)
                                            @php
                                                $sg_pI = $grandchild->data_prevista_inicio  ? \Carbon\Carbon::parse($grandchild->data_prevista_inicio)->format('Y-m-d')  : null;
                                                $sg_pF = $grandchild->data_prevista_fim     ? \Carbon\Carbon::parse($grandchild->data_prevista_fim)->format('Y-m-d')     : null;
                                                $sg_rI = $grandchild->data_realizada_inicio ? \Carbon\Carbon::parse($grandchild->data_realizada_inicio)->format('Y-m-d') : null;
                                                $sg_rF = $grandchild->data_realizada_fim    ? \Carbon\Carbon::parse($grandchild->data_realizada_fim)->format('Y-m-d')    : null;
                                                $sg_tR = $sg_rI && $sg_rF;
                                            @endphp
                                            <div class="cr-subitem-cal-row" wire:key="scr-g-{{ $grandchild->id }}">
                                                @foreach($diasTimeline as $idx => $d)
                                                    @php
                                                        $dd=$d['data']; $inP=$sg_pI&&$sg_pF&&$dd>=$sg_pI&&$dd<=$sg_pF; $inR=$sg_rI&&$sg_rF&&$dd>=$sg_rI&&$dd<=$sg_rF;
                                                        $cls='cr-cal-cell';
                                                        if($d['isWeekend'])$cls.=' cr-weekend'; if($d['isHoje'])$cls.=' cr-hoje-col';
                                                        if($sg_tR){if($inP&&$inR)$cls.=' cr-cell-real';elseif($inR)$cls.=' cr-cell-atraso';elseif($inP)$cls.=' cr-cell-prev';}
                                                        elseif($inP){$cls.=$grandchild->recebido?' cr-cell-real':' cr-cell-prev';}
                                                    @endphp
                                                    <div class="{{ $cls }}" :class="highlightedCol==={{ $idx }}?'cr-col-highlighted':''" @click.stop="toggleCol({{ $idx }})" title="{{ $grandchild->titulo }} — {{ \Carbon\Carbon::parse($d['data'])->format('d/m/Y') }}"></div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    @endforeach
                                @endforeach
                                <div class="cr-subitem-gantt-row-spacer"></div>{{-- linha "Adicionar subitem" --}}
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Legenda --}}
            <div class="cr-legend">
                <div class="cr-legend-item">
                    <div class="cr-legend-color" style="background: var(--cr-nao-iniciado)"></div>
                    Não Iniciado
                </div>
                <div class="cr-legend-item">
                    <div class="cr-legend-color" style="background: var(--cr-em-andamento)"></div>
                    Em Andamento
                </div>
                <div class="cr-legend-item">
                    <div class="cr-legend-color" style="background: var(--cr-concluido)"></div>
                    Concluído
                </div>
                <div class="cr-legend-item">
                    <div class="cr-legend-color" style="background: var(--cr-atrasado)"></div>
                    Atrasado
                </div>
                <div class="cr-legend-item">
                    <div class="cr-legend-color" style="background:transparent;border:1px dashed var(--vo-text-muted);"></div>
                    Previsto
                </div>
                <div class="cr-legend-item">
                    <div class="cr-legend-color" style="background:rgba(251,186,0,.18);border:1px solid rgba(251,186,0,.5);"></div>
                    Âncora
                </div>
                <div class="cr-legend-item">
                    <div class="cr-legend-color" style="width:10px;height:10px;transform:rotate(45deg);border-radius:2px;background:var(--vo-text-faint)"></div>
                    Marco
                </div>
            </div>
            @else
            {{-- VISAO INDIVIDUAL: Tabela detalhada --}}
            <div
                 x-data="{
                     faseW:         parseInt(localStorage.getItem('cr:col:fase')          || '220'),
                     statusW:       parseInt(localStorage.getItem('cr:col:status')        || '155'),
                     planejadoW:    parseInt(localStorage.getItem('cr:col:planejado')     || '230'),
                     durplanW:      parseInt(localStorage.getItem('cr:col:durplan')       || '90'),
                     realizadoW:    parseInt(localStorage.getItem('cr:col:realizado')     || '230'),
                     pctW:          parseInt(localStorage.getItem('cr:col:pct')           || '130'),
                     valorW:        parseInt(localStorage.getItem('cr:col:valor')         || '160'),
                     responsaveisW: parseInt(localStorage.getItem('cr:col:responsaveis')  || '180'),
                     revisorW:      parseInt(localStorage.getItem('cr:col:revisor')        || '160'),
                     depsW:         parseInt(localStorage.getItem('cr:col:deps')          || '260'),
                     comentariosW:  parseInt(localStorage.getItem('cr:col:comentarios')   || '200'),
                     cols: (function() { var d={planejado:true,durplan:true,realizado:true,pct:true,valor:true,responsaveis:true,revisor:true,deps:false,comentarios:true}; try { return JSON.parse(localStorage.getItem('cr:cols:vis')) || d; } catch(e) { return d; } })(),
                     mostrarColPanel: false,
                     resizing: false,
                     // ─── Multi-seleção ─────────────────────────────────────
                     selItemIds: [],
                     toggleSelItem(id) {
                         const idx = this.selItemIds.indexOf(id);
                         if (idx === -1) this.selItemIds.push(id); else this.selItemIds.splice(idx, 1);
                     },
                     isSelItem(id) { return this.selItemIds.includes(id); },
                     limparSelecao() { this.selItemIds = []; },
                     // Batch modal de datas
                     batchModalDatas: false,
                     batchInicio: '', batchFim: '',
                     abrirBatchDatas() { this.batchModalDatas = true; this.batchInicio = ''; this.batchFim = ''; },
                     confirmarBatchDatas() {
                         if (!this.batchInicio && !this.batchFim) { this.batchModalDatas = false; return; }
                         $wire.alterarDatasSubitemsEmLote([...this.selItemIds], this.batchInicio || null, this.batchFim || null);
                         this.batchModalDatas = false; this.limparSelecao();
                     },
                     // Batch modal de responsável
                     batchModalResp: false,
                     batchUserId: '',
                     abrirBatchResp() { this.batchModalResp = true; this.batchUserId = ''; },
                     confirmarBatchResp() {
                         if (!this.batchUserId) { this.batchModalResp = false; return; }
                         $wire.atribuirResponsavelSubitemsEmLote([...this.selItemIds], parseInt(this.batchUserId));
                         this.batchModalResp = false; this.limparSelecao();
                     },
                     // Batch modal criar grupo
                     batchModalGrupo: false,
                     batchGrupoNome: '',
                     abrirBatchGrupo() { this.batchModalGrupo = true; this.batchGrupoNome = ''; },
                     confirmarBatchGrupo() {
                         if (!this.batchGrupoNome.trim()) { this.batchModalGrupo = false; return; }
                         $wire.criarGrupoAtividades(this.batchGrupoNome.trim(), [...this.selItemIds]);
                         this.batchModalGrupo = false; this.limparSelecao();
                     },
                     // Batch modal unificado de edição
                     batchModalEditar: false,
                     batchInicio: '', batchFim: '', batchDuracao: '',
                     batchUserId: '', batchRevisorId: '',
                     batchDepAlvo: '', batchDepGatilho: 'fim_anterior', batchDepGap: 1,
                     abrirBatchEditar() {
                         this.batchModalEditar = true;
                         this.batchInicio = ''; this.batchFim = ''; this.batchDuracao = '';
                         this.batchUserId = ''; this.batchRevisorId = '';
                         this.batchDepAlvo = ''; this.batchDepGatilho = 'fim_anterior'; this.batchDepGap = 1;
                     },
                     confirmarBatchEditar() {
                         const algoCampo = this.batchInicio || this.batchFim || this.batchDuracao || this.batchUserId || this.batchRevisorId || this.batchDepAlvo;
                         if (!algoCampo) { this.batchModalEditar = false; return; }
                         $wire.editarSubitemsEmLote(
                             [...this.selItemIds],
                             this.batchInicio || null,
                             this.batchFim || null,
                             this.batchDuracao ? parseInt(this.batchDuracao) : null,
                             this.batchUserId ? parseInt(this.batchUserId) : null,
                             this.batchRevisorId ? parseInt(this.batchRevisorId) : null,
                             this.batchDepAlvo || null,
                             this.batchDepGatilho,
                             parseInt(this.batchDepGap) || 0
                         );
                         this.batchModalEditar = false; this.limparSelecao();
                     },
                     // ─── Drag-drop de subitens ─────────────────────────────
                     dragItemSrc: null, dragFaseItemSrc: null,
                     dragItemTarget: null, dragFaseTarget: null,
                     onDropSubitem(targetItemId, targetFaseId, targetOrdem) {
                         if (this.dragItemSrc !== null && this.dragItemSrc !== targetItemId) {
                             $wire.moverSubitem(this.dragItemSrc, this.dragFaseItemSrc, targetFaseId, targetOrdem);
                         }
                         this.dragItemSrc = null; this.dragFaseItemSrc = null;
                         this.dragItemTarget = null; this.dragFaseTarget = null;
                     },
                     onDropFase(targetFaseId) {
                         if (this.dragItemSrc !== null) {
                             $wire.moverSubitem(this.dragItemSrc, this.dragFaseItemSrc, targetFaseId, 9999);
                         }
                         this.dragItemSrc = null; this.dragFaseItemSrc = null;
                         this.dragItemTarget = null; this.dragFaseTarget = null;
                     },
                     // ─── Drag-drop de fases (reordenação) ──────────────────
                     dragFaseSrc: null,
                     dragFaseReorderTarget: null,
                     onDropReordenarFase(targetFaseId, targetOrdem) {
                         if (this.dragFaseSrc !== null && this.dragFaseSrc !== targetFaseId) {
                             $wire.moverFase(this.dragFaseSrc, targetOrdem);
                         }
                         this.dragFaseSrc = null;
                         this.dragFaseReorderTarget = null;
                     },
                     init() {
                         this.aplicarLarguras();
                     },
                     aplicarLarguras() {
                         const el = this.$el;
                         el.style.setProperty('--cr-fase-col-width',     this.faseW         + 'px');
                         el.style.setProperty('--cr-col-status-w',       this.statusW       + 'px');
                         el.style.setProperty('--cr-col-planejado-w',    this.planejadoW    + 'px');
                         el.style.setProperty('--cr-col-durplan-w',      this.durplanW      + 'px');
                         el.style.setProperty('--cr-col-realizado-w',    this.realizadoW    + 'px');
                         el.style.setProperty('--cr-col-pct-w',          this.pctW          + 'px');
                         el.style.setProperty('--cr-col-valor-w',        this.valorW        + 'px');
                         el.style.setProperty('--cr-col-responsaveis-w', this.responsaveisW + 'px');
                         el.style.setProperty('--cr-col-revisor-w',      this.revisorW      + 'px');
                         el.style.setProperty('--cr-col-deps-w',         this.depsW         + 'px');
                         el.style.setProperty('--cr-col-comentarios-w',  this.comentariosW  + 'px');
                     },
                     startResize(e, col, min, max) {
                         this.resizing = true;
                         const propMap = {
                             fase:         '--cr-fase-col-width',
                             status:       '--cr-col-status-w',
                             planejado:    '--cr-col-planejado-w',
                             durplan:      '--cr-col-durplan-w',
                             realizado:    '--cr-col-realizado-w',
                             pct:          '--cr-col-pct-w',
                             valor:        '--cr-col-valor-w',
                             responsaveis: '--cr-col-responsaveis-w',
                             revisor:      '--cr-col-revisor-w',
                             deps:         '--cr-col-deps-w',
                             comentarios:  '--cr-col-comentarios-w',
                         };
                         const prop   = propMap[col];
                         const key    = col + 'W';
                         const startX = e.clientX;
                         const startW = this[key];
                         const el     = this.$el;
                         const onMove = (ev) => {
                             const w = Math.max(min, Math.min(max, startW + ev.clientX - startX));
                             this[key] = w;
                             el.style.setProperty(prop, w + 'px');
                         };
                         const onUp = () => {
                             this.resizing = false;
                             localStorage.setItem('cr:col:' + col, this[key]);
                             document.removeEventListener('mousemove', onMove);
                             document.removeEventListener('mouseup', onUp);
                         };
                         document.addEventListener('mousemove', onMove);
                         document.addEventListener('mouseup', onUp);
                     },
                     salvarCols() { localStorage.setItem('cr:cols:vis', JSON.stringify(this.cols)); },
                 }"
                 >
                {{-- Toolbar: busca, filtro, colunas --}}
                <div class="cr-tbl-toolbar">
                    <div class="cr-tbl-search-wrap">
                        <svg class="cr-tbl-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" class="cr-tbl-search" wire:model.live.debounce.300ms="buscaFase" placeholder="Buscar fase…">
                    </div>
                    <select class="cr-tbl-filter" wire:model.live="filtroStatusFase">
                        <option value="">Todos os status</option>
                        @foreach(\App\Enums\StatusCronograma::cases() as $opt)
                        <option value="{{ $opt->value }}">{{ $opt->label() }}</option>
                        @endforeach
                    </select>
                    <div style="position:relative;margin-left:auto;">
                        <button type="button" class="cr-tbl-cols-btn" @click.stop="mostrarColPanel = !mostrarColPanel">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
                            Colunas
                        </button>
                        <div class="cr-tbl-cols-panel" x-show="mostrarColPanel" x-cloak x-transition.opacity.duration.100ms @click.outside="mostrarColPanel = false">
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.planejado"    @change="salvarCols()"> Planejado</label>
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.durplan"      @change="salvarCols()"> Dur. Plan.</label>
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.realizado"    @change="salvarCols()"> Realizado</label>
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.pct"          @change="salvarCols()"> Percentual</label>
                            @can('ver_valores_planejamento')
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.valor"        @change="salvarCols()"> Valor</label>
                            @endcan
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.responsaveis" @change="salvarCols()"> Responsáveis</label>
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.revisor"      @change="salvarCols()"> Revisor</label>
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.deps"         @change="salvarCols()"> Dependência</label>
                            <label class="cr-tbl-cols-item"><input type="checkbox" x-model="cols.comentarios"  @change="salvarCols()"> Comentários</label>
                        </div>
                    </div>
                </div>
                <div class="cr-table-wrap" :class="{ 'cr-fase-resizing': resizing }">
                <table class="cr-table cr-table-detalhada">
                    <colgroup>
                        <col class="cr-col-fase">
                        <col class="cr-col-status">
                        <col x-show="cols.planejado"    :style="`width:${planejadoW}px`">
                        <col x-show="cols.durplan"      :style="`width:${durplanW}px`">
                        <col x-show="cols.realizado"    :style="`width:${realizadoW}px`">
                        <col x-show="cols.pct"          :style="`width:${pctW}px`">
                        @can('ver_valores_planejamento')
                        <col x-show="cols.valor"        :style="`width:${valorW}px`">
                        @endcan
                        <col x-show="cols.responsaveis" :style="`width:${responsaveisW}px`">
                        <col x-show="cols.revisor"      :style="`width:${revisorW}px`">
                        <col x-show="cols.deps"         :style="`width:${depsW}px`">
                        <col x-show="cols.comentarios"  :style="`width:${comentariosW}px`">
                        <col style="width:48px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="cr-th-sticky cr-col-fase cr-th-resizable">
                                Fase
                                <span class="cr-fase-resize-handle"
                                      @mousedown.prevent="startResize($event, 'fase', 180, 600)"
                                      title="Arrastar para redimensionar" aria-label="Redimensionar coluna Fase"></span>
                            </th>
                            <th class="cr-th-sticky cr-col-status cr-th-resizable">
                                Status
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'status', 120, 300)"></span>
                            </th>
                            <th x-show="cols.planejado" class="cr-th-resizable">
                                Planejado
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'planejado', 150, 380)"></span>
                            </th>
                            <th x-show="cols.durplan" class="cr-th-resizable">
                                Dur. Plan.
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'durplan', 60, 200)"></span>
                            </th>
                            <th x-show="cols.realizado" class="cr-th-resizable">
                                Realizado
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'realizado', 150, 380)"></span>
                            </th>
                            <th x-show="cols.pct" class="cr-th-resizable">
                                %
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'pct', 60, 220)"></span>
                            </th>
                            @can('ver_valores_planejamento')
                            <th x-show="cols.valor" class="cr-th-resizable" style="text-align:right;white-space:nowrap;">
                                Valor
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'valor', 100, 280)"></span>
                            </th>
                            @endcan
                            <th x-show="cols.responsaveis" class="cr-th-resizable" style="white-space:nowrap;">
                                Responsáveis
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'responsaveis', 100, 320)"></span>
                            </th>
                            <th x-show="cols.revisor" class="cr-th-resizable" style="white-space:nowrap;">
                                Revisor
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'revisor', 100, 320)"></span>
                            </th>
                            <th x-show="cols.deps" class="cr-th-resizable">
                                Dependência
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'deps', 150, 420)"></span>
                            </th>
                            <th x-show="cols.comentarios" class="cr-th-resizable">
                                Comentários
                                <span class="cr-th-rhandle" @mousedown.prevent="startResize($event, 'comentarios', 120, 380)"></span>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fases as $fase)
                            @php $faseNum = $loop->iteration; @endphp
                            @php
                                $duracaoPlan = ($fase->data_prevista_inicio && $fase->data_prevista_fim)
                                    ? $fase->data_prevista_inicio->diffInDays($fase->data_prevista_fim) + 1
                                    : null;
                            @endphp
                            @php
                                $hojeStrTbl = now()->toDateString();
                                $prevFStr = $fase->data_prevista_fim?->toDateString();
                                $realFStr = $fase->data_realizada_fim?->toDateString();
                                $concluidoTbl = \App\Services\CronogramaTemplateService::bloqueadoRecalculo($fase->status);
                                $atrasoTbl = $fase->status === \App\Enums\StatusCronograma::ATRASADO
                                    || ($prevFStr && $prevFStr < $hojeStrTbl && ! $concluidoTbl)
                                    || ($concluidoTbl && $realFStr && $prevFStr && $realFStr > $prevFStr);
                            @endphp
                            @php $farolTblRow = $fase->farol; $qtdItensTblRow = $fase->itens->count(); @endphp
                            <tr class="cr-table-row {{ $farolTblRow !== 'neutro' ? 'cr-fase-linha-'.$farolTblRow : '' }}"
                                @dragover.prevent="
                                    if (dragItemSrc !== null) dragFaseTarget = {{ $fase->id }};
                                    else if (dragFaseSrc !== null && dragFaseSrc !== {{ $fase->id }}) dragFaseReorderTarget = {{ $fase->id }};
                                "
                                @dragleave="
                                    if (dragFaseTarget === {{ $fase->id }}) dragFaseTarget = null;
                                    if (dragFaseReorderTarget === {{ $fase->id }}) dragFaseReorderTarget = null;
                                "
                                @drop.prevent="
                                    if (dragItemSrc !== null) onDropFase({{ $fase->id }});
                                    else if (dragFaseSrc !== null) onDropReordenarFase({{ $fase->id }}, {{ $fase->ordem }});
                                "
                                :class="{
                                    'cr-fase-dragover-target': dragItemSrc !== null && dragFaseTarget === {{ $fase->id }},
                                    'cr-fase-dragging': dragFaseSrc === {{ $fase->id }},
                                    'cr-fase-reorder-target': dragFaseSrc !== null && dragFaseSrc !== {{ $fase->id }} && dragFaseReorderTarget === {{ $fase->id }}
                                }">
                                <td class="cr-td-sticky cr-col-fase" style="font-weight:500;{{ $qtdItensTblRow > 0 ? 'cursor:pointer;' : '' }}"
                                    @if($qtdItensTblRow > 0) wire:click="alternarExpansaoFase({{ $fase->id }})" @endif>
                                    <span style="display:flex;align-items:center;gap:6px;">
                                        <span class="cr-drag-handle-fase"
                                              draggable="true"
                                              title="Arrastar para reordenar"
                                              @dragstart.stop="dragFaseSrc = {{ $fase->id }}"
                                              @dragend.stop="dragFaseSrc = null; dragFaseReorderTarget = null"
                                              @click.stop>⠿</span>
                                        @php
                                            $farolTbl = $fase->farol;
                                            $farolCorTbl = match ($farolTbl) {
                                                'vermelho' => 'var(--cr-atrasado, #ff4d6a)',
                                                'amarelo' => '#f5ba00',
                                                'verde' => 'var(--cr-concluido, #2dd67c)',
                                                default => 'transparent',
                                            };
                                            $farolTituloTbl = match ($farolTbl) {
                                                'vermelho' => 'Atraso crítico',
                                                'amarelo' => 'Leve atraso',
                                                'verde' => 'Em dia',
                                                default => 'Sem indicador',
                                            };
                                            $qtdItensTbl = $fase->itens->count();
                                            $badgeBg = match ($farolTbl) {
                                                'vermelho' => '#ff4d6a',
                                                'amarelo' => '#f5ba00',
                                                'verde' => '#2dd67c',
                                                default => '#6b7280',
                                            };
                                            $badgeColor = $farolTbl === 'amarelo' ? '#111' : '#fff';
                                        @endphp
                                        <span class="cr-fase-num-badge" style="background:{{ $badgeBg }};color:{{ $badgeColor }}">{{ $faseNum }}</span>
                                        @if($farolTbl !== 'neutro')
                                            <span class="cr-farol" title="{{ $farolTituloTbl }}" style="background:{{ $farolCorTbl }}"></span>
                                        @endif
                                        {{ $fase->label_exibicao }}
                                        @if($fase->bloqueada_pos_contrato)
                                            <span class="cr-fase-cadeado" title="Fase bloqueada após assinatura do contrato"
                                                  style="display:inline-flex;align-items:center;color:#92400e;flex-shrink:0;margin-left:2px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            </span>
                                        @endif
                                        @php $faseExpandidaTbl = in_array($fase->id, $fasesExpandidas, true); @endphp
                                        <button type="button" class="cr-checklist-btn {{ $faseExpandidaTbl ? 'cr-checklist-btn-open' : '' }}"
                                                wire:click.stop="alternarExpansaoFase({{ $fase->id }})"
                                                title="{{ $faseExpandidaTbl ? 'Recolher subitens' : 'Expandir subitens' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>
                                            {{ $qtdItensTbl > 0 ? $qtdItensTbl : '+' }}
                                        </button>
                                        <button type="button"
                                                wire:click.stop="expandirFaseEFocarInput({{ $fase->id }})"
                                                title="Nova atividade"
                                                style="padding:2px 7px;border:1px dashed var(--vo-accent);border-radius:.25rem;background:transparent;cursor:pointer;color:var(--vo-accent);font-size:0.7rem;line-height:1.4;white-space:nowrap;flex-shrink:0;">
                                            + Atividade
                                        </button>
                                    </span>
                                </td>
                                <td class="cr-td-sticky cr-col-status">
                                    <div class="cr-status-dropdown" x-data="{ open: false, pos: {top:0,left:0}, reposition() { const r = this.$refs.trigger.getBoundingClientRect(); this.pos = {top: r.bottom + 4, left: r.left}; } }" @click.outside="open = false">
                                        <button type="button" class="cr-status-trigger" style="background:{{ $fase->status->color() }}" x-ref="trigger"
                                                @click="reposition(); open = !open" :aria-expanded="open">
                                            <span class="cr-status-dot"></span>
                                            {{ $fase->status->label() }}
                                            <svg class="cr-status-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/></svg>
                                        </button>
                                        <template x-teleport="body">
                                        <div class="cr-status-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms :style="`top:${pos.top}px;left:${pos.left}px`" @click.outside="open = false">
                                            @foreach($fase->fase->statusDisponiveis() as $s)
                                                <button type="button"
                                                        class="cr-status-option {{ $fase->status === $s ? 'cr-status-active' : '' }}"
                                                        wire:click="alterarStatusFase({{ $fase->id }}, '{{ $s->value }}')"
                                                        @click="open = false">
                                                    <span class="cr-opt-dot" style="background:{{ $s->color() }}"></span>
                                                    {{ $s->label() }}
                                                </button>
                                            @endforeach
                                        </div>
                                        </template>
                                    </div>
                                </td>
                                <td x-show="cols.planejado" style="font-variant-numeric:tabular-nums;color:var(--vo-text-secondary);">
                                    <div style="display:flex;gap:4px;align-items:center;">
                                        <span>{{ $fase->data_prevista_inicio?->format('d/m/Y') ?? '—' }}</span>
                                        <span style="color:var(--vo-text-faint);">—</span>
                                        <span>{{ $fase->data_prevista_fim?->format('d/m/Y') ?? '—' }}</span>
                                    </div>
                                </td>
                                <td x-show="cols.durplan" class="cr-td-center" style="font-variant-numeric:tabular-nums;">
                                    @if($duracaoPlan !== null)
                                        {{ $duracaoPlan }} dias
                                    @else
                                        <span style="color:var(--vo-text-faint)">-</span>
                                    @endif
                                </td>
                                <td x-show="cols.realizado" style="font-variant-numeric:tabular-nums;color:var(--vo-text-secondary);">
                                    <div style="display:flex;gap:4px;align-items:center;"
                                         x-data="{ ri: '{{ $fase->data_realizada_inicio?->toDateString() }}', rf: '{{ $fase->data_realizada_fim?->toDateString() }}' }">
                                        <input type="date" x-model="ri"
                                               @blur="if (ri) $wire.salvarDataInline({{ $fase->id }}, 'data_realizada_inicio', ri)"
                                               title="Data realizada de início"
                                               class="cr-inline-date">
                                        <button type="button"
                                                class="cr-date-copy-btn"
                                                :disabled="!ri"
                                                @click="if (ri) { rf = ri; $wire.salvarDataInline({{ $fase->id }}, 'data_realizada_fim', ri) }"
                                                title="Copiar data de início para fim">→</button>
                                        <input type="date" x-model="rf"
                                               @blur="if (rf) $wire.salvarDataInline({{ $fase->id }}, 'data_realizada_fim', rf)"
                                               title="Data realizada de fim"
                                               class="cr-inline-date">
                                    </div>
                                </td>
                                <td x-show="cols.pct" class="cr-td-center">
                                    @php
                                        $pct = $fase->percentual_conclusao;
                                        $temSubitens = $fase->itens->isNotEmpty();
                                        [$farolPctCor, $farolPctTitle] = match(true) {
                                            $pct === 100          => ['#16a34a', 'Concluído (100%)'],
                                            $pct >= 75            => ['#84cc16', 'Bom progresso (75–99%)'],
                                            $pct >= 50            => ['#f59e0b', 'Em andamento (50–74%)'],
                                            $pct > 0              => ['#ef4444', 'Progresso baixo (1–49%)'],
                                            default               => ['#9ca3af', 'Não iniciado (0%)'],
                                        };
                                    @endphp
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        @if($temSubitens)
                                            <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:{{ $farolPctCor }};box-shadow:0 0 0 2px color-mix(in srgb,{{ $farolPctCor }} 25%,transparent);"
                                                  title="{{ $farolPctTitle }}"></span>
                                        @endif
                                        <div class="cr-progress-track" style="min-width:40px;max-width:60px;">
                                            <div class="cr-progress-fill" style="width:{{ $pct }}%;background:{{ $farolPctCor }}"></div>
                                        </div>
                                        <span style="font-weight:600;font-size:0.7rem;color:{{ $farolPctCor }};">{{ $pct }}%</span>
                                    </div>
                                </td>
                                @can('ver_valores_planejamento')
                                <td x-show="cols.valor" style="text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;font-size:0.78rem;padding:4px 8px;" title="Soma dos valores das atividades">
                                    @if($fase->valor)
                                        <span style="color:var(--vo-text-secondary);font-size:0.7rem;">R$</span>
                                        <span style="font-weight:600;color:var(--vo-text);">{{ number_format($fase->valor, 2, ',', '.') }}</span>
                                    @else
                                        <span style="color:var(--vo-text-faint);">—</span>
                                    @endif
                                </td>
                                @endcan
                                <td x-show="cols.responsaveis" style="color:var(--vo-text-faint);font-size:0.72rem;">—</td>
                                <td x-show="cols.revisor" style="color:var(--vo-text-faint);font-size:0.72rem;">—</td>
                                <td x-show="cols.deps">
                                    @php
                                        $regra = $fase->regraEfetiva();
                                        $depsTxt = [];
                                        foreach ($regra->dependencias as $dep) {
                                            $depEnum = $dep->depende_de_fase instanceof \App\Enums\FaseCronograma
                                                ? $dep->depende_de_fase
                                                : \App\Enums\FaseCronograma::tryFrom((string) $dep->depende_de_fase);
                                            if (! $depEnum) continue;
                                            $gatilhoEnum = $dep->gatilho instanceof \App\Enums\GatilhoTemplateFase
                                                ? $dep->gatilho
                                                : \App\Enums\GatilhoTemplateFase::tryFrom((string) $dep->gatilho);
                                            $gatilhoTxt = $gatilhoEnum?->labelCurto() ?? 'fim';
                                            $gap = (int) $dep->gap_dias;
                                            $gapTxt = $gap === 0 ? '' : ($gap > 0 ? '+'.$gap.'d' : $gap.'d');
                                            $depsTxt[] = $depEnum->label().' ('.$gatilhoTxt.') '.$gapTxt;
                                        }
                                        $isAncora = (bool) ($fase->templateFase?->is_ancora);
                                    @endphp
                                    @if(! $fase->cronograma_template_id)
                                        <span style="color:var(--vo-text-faint);font-size:0.7rem;">—</span>
                                    @else
                                        <span style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                            @if($isAncora)
                                                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;font-size:0.68rem;border-radius:1rem;background:var(--vo-bg-subtle);border:1px dashed var(--vo-border);color:var(--vo-text-secondary);">
                                                    ⚓ Âncora do template
                                                </span>
                                            @endif
                                            @if(!empty($depsTxt))
                                                @foreach($depsTxt as $txt)
                                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;font-size:0.68rem;border-radius:1rem;background:var(--vo-bg-subtle);border:1px dashed var(--vo-border);color:var(--vo-text-secondary);">
                                                        ← {{ $txt }}
                                                    </span>
                                                @endforeach
                                            @elseif(!$isAncora)
                                                <span style="color:var(--vo-text-faint);font-size:0.7rem;">—</span>
                                            @endif
                                            @if($fase->regra_customizada)
                                                <span title="Regra customizada neste projeto" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f97316;"></span>
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td x-show="cols.comentarios" style="max-width:220px;cursor:pointer;" wire:click="abrirComentarios({{ $fase->id }})" title="Clique para ver/adicionar comentários">
                                    @php
                                        $comentariosFase = $fase->relationLoaded('comentarios') ? $fase->comentarios : $fase->comentarios()->with('usuario')->latest()->get();
                                        $ultimoComentario = $comentariosFase->sortByDesc('created_at')->first();
                                        $qtdComentarios = $comentariosFase->count();
                                    @endphp
                                    @if($ultimoComentario)
                                        <div style="display:flex;align-items:flex-start;gap:6px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:2px;color:var(--vo-text-faint);"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                            <div style="overflow:hidden;min-width:0;">
                                                <div style="font-size:0.68rem;color:var(--vo-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $ultimoComentario->conteudo }}
                                                </div>
                                                <div style="font-size:0.6rem;color:var(--vo-text-faint);margin-top:1px;">
                                                    {{ $ultimoComentario->usuario?->name ?? 'Sistema' }} &middot; {{ $ultimoComentario->created_at->format('d/m H:i') }}
                                                    @if($qtdComentarios > 1)
                                                        &middot; +{{ $qtdComentarios - 1 }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span style="color:var(--vo-text-faint);font-size:0.68rem;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                            —
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" wire:click="abrirHistorico({{ $fase->id }})" title="Histórico desta fase"
                                            style="padding:3px 5px;border:1px solid var(--vo-border);background:transparent;border-radius:.25rem;cursor:pointer;color:var(--vo-text-muted);line-height:1;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </button>
                                </td>
                            </tr>
                            @if(in_array($fase->id, $fasesExpandidas, true))
                                @foreach($fase->itens->whereNull('parent_id')->sortBy('ordem') as $item)
                                    @include('filament.pages.cronograma-subitem-table', ['item' => $item, 'depth' => 0, 'fasesDependencia' => $fases, 'numPrefix' => $faseNum . '.' . $loop->iteration, 'usuarios' => $usuarios ?? []])
                                @endforeach
                                <tr class="cr-subitem-add-tr">
                                    <td class="cr-td-sticky cr-col-fase" colspan="2">
                                        <div style="display:flex;gap:6px;padding-left:18px;align-items:center;flex-wrap:wrap;">
                                            <span class="cr-subitem-tree">+</span>
                                            <input type="text"
                                                   wire:model="novoSubitemTitulos.{{ $fase->id }}"
                                                   wire:keydown.enter.prevent="adicionarSubitem({{ $fase->id }})"
                                                   placeholder="Adicionar novo subitem…"
                                                   class="cr-subitem-titulo-inline">
                                            <button type="button" wire:click="adicionarSubitem({{ $fase->id }})" class="cr-subitem-add-btn">
                                                Adicionar
                                            </button>
                                            <button type="button"
                                                    wire:click="abrirSelecionarGrupo({{ $fase->id }})"
                                                    title="Inserir grupo de atividades"
                                                    style="padding:3px 8px;font-size:0.68rem;border:1px dashed var(--vo-accent);border-radius:.25rem;background:transparent;cursor:pointer;color:var(--vo-accent);white-space:nowrap;">
                                                ⊞ Inserir grupo
                                            </button>
                                        </div>
                                    </td>
                                    <td colspan="20"></td>
                                </tr>
                            @endif
                        @endforeach
                        {{-- Linha para adicionar nova fase diretamente na tabela --}}
                        <tr>
                            <td colspan="99" style="padding:8px 14px;border-top:1px dashed var(--vo-border);background:var(--vo-bg);">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <input type="text"
                                           wire:model="novaFasePersonalizadaTitulo"
                                           wire:keydown.enter.prevent="adicionarFasePersonalizada"
                                           placeholder="+ Nova fase personalizada… (Enter para adicionar)"
                                           style="flex:1;min-width:180px;padding:5px 10px;border:1px dashed var(--vo-border);border-radius:.375rem;background:transparent;color:var(--vo-text);font-size:0.78rem;outline:none;">
                                    <button type="button"
                                            wire:click="adicionarFasePersonalizada"
                                            style="padding:4px 14px;font-size:0.75rem;font-weight:500;border:1px solid var(--vo-accent);background:transparent;color:var(--vo-accent);border-radius:.375rem;cursor:pointer;white-space:nowrap;">
                                        Adicionar fase
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>

                {{-- Barra de ações em lote (aparece quando há seleção) --}}
                <div x-show="selItemIds.length > 0" x-cloak class="cr-batch-toolbar">
                    <span class="cr-batch-label" x-text="selItemIds.length + ' selecionada(s)'"></span>
                    <div class="cr-batch-sep"></div>
                    <button type="button" class="cr-batch-btn cr-batch-btn--primary" @click="abrirBatchEditar()">✏️ Editar</button>
                    <button type="button" class="cr-batch-btn" @click="$wire.marcarConcluidsEmLote([...selItemIds]); limparSelecao()">✔ Concluir</button>
                    <button type="button" class="cr-batch-btn cr-batch-btn--primary" @click="abrirBatchGrupo()">⊞ Salvar como grupo</button>
                    <div class="cr-batch-sep"></div>
                    <button type="button" class="cr-batch-btn cr-batch-btn--danger"
                            @click="if (confirm('Excluir ' + selItemIds.length + ' itens selecionados?')) { $wire.excluirSubitemsEmLote([...selItemIds]); limparSelecao(); }">
                        🗑 Excluir
                    </button>
                    <button type="button" class="cr-batch-btn cr-batch-btn--cancel" @click="limparSelecao()">✕ Cancelar</button>
                </div>

                {{-- Modal unificado: editar atividades selecionadas --}}
                <div x-show="batchModalEditar" x-cloak class="cr-modal-overlay" @click.self="batchModalEditar = false">
                    <div class="cr-modal-box" style="max-width:460px;">
                        <div class="cr-modal-title">Editar atividades selecionadas</div>
                        <p style="font-size:0.72rem;color:var(--vo-text-faint);margin-bottom:14px;">Preencha apenas os campos que deseja alterar. Os demais serão ignorados.</p>
                        <div style="display:flex;flex-direction:column;gap:14px;">

                            {{-- Datas --}}
                            <div>
                                <div style="font-size:0.72rem;font-weight:600;color:var(--vo-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">📅 Datas previstas</div>
                                <div style="display:flex;gap:8px;">
                                    <label style="font-size:0.75rem;color:var(--vo-text-secondary);flex:1;">
                                        Início
                                        <input type="date" x-model="batchInicio" style="display:block;width:100%;margin-top:3px;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:5px 8px;font-size:0.78rem;">
                                    </label>
                                    <label style="font-size:0.75rem;color:var(--vo-text-secondary);flex:1;">
                                        Fim
                                        <input type="date" x-model="batchFim" style="display:block;width:100%;margin-top:3px;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:5px 8px;font-size:0.78rem;" :disabled="batchDuracao > 0">
                                    </label>
                                    <label style="font-size:0.75rem;color:var(--vo-text-secondary);width:80px;">
                                        Duração (d)
                                        <input type="number" x-model="batchDuracao" min="1" placeholder="—"
                                               style="display:block;width:100%;margin-top:3px;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:5px 8px;font-size:0.78rem;"
                                               @input="if (batchDuracao > 0) batchFim = ''">
                                    </label>
                                </div>
                                <p x-show="batchDuracao > 0 && batchInicio" style="font-size:0.68rem;color:var(--vo-text-faint);margin-top:4px;">
                                    Data fim será calculada automaticamente: início + duração − 1 dia
                                </p>
                                <p x-show="batchDuracao > 0 && !batchInicio" style="font-size:0.68rem;color:var(--vo-text-faint);margin-top:4px;">
                                    Duração será salva; data fim recalculada pela data de início de cada item
                                </p>
                            </div>

                            {{-- Responsável --}}
                            <div>
                                <div style="font-size:0.72rem;font-weight:600;color:var(--vo-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">👤 Responsável</div>
                                <select x-model="batchUserId" style="width:100%;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:6px 8px;font-size:0.78rem;">
                                    <option value="">— não alterar —</option>
                                    @foreach($usuarios ?? [] as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Revisor --}}
                            <div>
                                <div style="font-size:0.72rem;font-weight:600;color:var(--vo-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">📋 Revisor</div>
                                <select x-model="batchRevisorId" style="width:100%;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:6px 8px;font-size:0.78rem;">
                                    <option value="">— não alterar —</option>
                                    @foreach($usuarios ?? [] as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Dependência --}}
                            <div>
                                <div style="font-size:0.72rem;font-weight:600;color:var(--vo-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">🔗 Dependência (adicionar)</div>
                                <label style="font-size:0.75rem;color:var(--vo-text-secondary);">
                                    Depende de
                                    <select x-model="batchDepAlvo" style="display:block;width:100%;margin-top:3px;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:6px 8px;font-size:0.78rem;">
                                        <option value="">— não adicionar —</option>
                                        <optgroup label="Fases">
                                            @foreach($fases->sortBy('ordem') as $faseOpcao)
                                                <option value="fase:{{ $faseOpcao->id }}">{{ $faseOpcao->label_exibicao }}</option>
                                            @endforeach
                                        </optgroup>
                                        <optgroup label="Subitens">
                                            @foreach($fases->sortBy('ordem') as $faseOpcao)
                                                @foreach($faseOpcao->itens->sortBy('ordem') as $opcao)
                                                    <option value="item:{{ $opcao->id }}">{{ $faseOpcao->label_exibicao }} / {{ $opcao->titulo }}</option>
                                                @endforeach
                                            @endforeach
                                        </optgroup>
                                    </select>
                                </label>
                                <div style="display:flex;gap:8px;margin-top:6px;" x-show="batchDepAlvo">
                                    <label style="font-size:0.75rem;color:var(--vo-text-secondary);flex:1;">
                                        Gatilho
                                        <select x-model="batchDepGatilho" style="display:block;width:100%;margin-top:3px;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:5px 8px;font-size:0.78rem;">
                                            @foreach(\App\Enums\GatilhoTemplateFase::cases() as $gatilho)
                                                <option value="{{ $gatilho->value }}">{{ $gatilho->labelCurto() }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label style="font-size:0.75rem;color:var(--vo-text-secondary);width:80px;">
                                        Gap (dias)
                                        <input type="number" x-model="batchDepGap" min="0" style="display:block;width:100%;margin-top:3px;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:5px 8px;font-size:0.78rem;">
                                    </label>
                                </div>
                            </div>

                        </div>
                        <div class="cr-modal-actions">
                            <button type="button" class="cr-batch-btn cr-batch-btn--cancel" @click="batchModalEditar = false">Cancelar</button>
                            <button type="button" class="cr-batch-btn cr-batch-btn--primary" @click="confirmarBatchEditar()">Aplicar</button>
                        </div>
                    </div>
                </div>

                {{-- Modal: criar grupo de atividades --}}
                <div x-show="batchModalGrupo" x-cloak class="cr-modal-overlay" @click.self="batchModalGrupo = false">
                    <div class="cr-modal-box">
                        <div class="cr-modal-title">Salvar como grupo de atividades</div>
                        <p style="font-size:0.75rem;color:var(--vo-text-secondary);margin-bottom:12px;">
                            Grupos podem ser inseridos em qualquer fase de qualquer projeto.
                        </p>
                        <label style="font-size:0.78rem;color:var(--vo-text-secondary);">
                            Nome do grupo
                            <input type="text" x-model="batchGrupoNome" placeholder="Ex.: Vistoria padrão, Kit mudança…"
                                   @keydown.enter.prevent="confirmarBatchGrupo()"
                                   style="display:block;width:100%;margin-top:4px;border:1px solid var(--vo-border);border-radius:.3rem;background:var(--vo-bg);color:var(--vo-text);padding:5px 8px;font-size:0.8rem;">
                        </label>
                        <div class="cr-modal-actions">
                            <button type="button" class="cr-batch-btn cr-batch-btn--cancel" @click="batchModalGrupo = false">Cancelar</button>
                            <button type="button" class="cr-batch-btn cr-batch-btn--primary" @click="confirmarBatchGrupo()">Criar grupo</button>
                        </div>
                    </div>
                </div>

            </div>
            @endif

            {{-- Modal: selecionar grupo para inserir na fase --}}
            @if($modalSelecionarGrupo)
                <div class="cr-modal-overlay" style="z-index:200;" wire:click.self="fecharSelecionarGrupo">
                    <div class="cr-modal-box" style="max-width:520px;max-height:80vh;overflow-y:auto;">
                        <div class="cr-modal-title">Inserir grupo de atividades</div>
                        @if(empty($gruposDisponiveis))
                            <p style="font-size:0.8rem;color:var(--vo-text-faint);">Nenhum grupo criado ainda. Selecione atividades na tabela e use "Salvar como grupo".</p>
                        @else
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                @foreach($gruposDisponiveis as $g)
                                    <div style="border:1px solid var(--vo-border);border-radius:.4rem;padding:10px 12px;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                                            <span style="font-weight:600;font-size:0.82rem;color:var(--vo-text);">{{ $g['nome'] }}</span>
                                            <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
                                                <span style="font-size:0.65rem;color:var(--vo-text-faint);">{{ $g['total_itens'] }} atividade(s)</span>
                                                <button type="button" wire:click="inserirGrupoNaFase({{ $g['id'] }})"
                                                        class="cr-batch-btn cr-batch-btn--primary" style="padding:3px 10px;font-size:0.68rem;">
                                                    Inserir
                                                </button>
                                                <button type="button" wire:click="excluirGrupoAtividades({{ $g['id'] }})"
                                                        class="cr-batch-btn cr-batch-btn--danger" style="padding:3px 8px;font-size:0.68rem;"
                                                        onclick="return confirm('Excluir o grupo \'{{ $g['nome'] }}\'?')">
                                                    ×
                                                </button>
                                            </div>
                                        </div>
                                        @foreach($g['itens'] as $gi)
                                            <div style="font-size:0.72rem;color:var(--vo-text-secondary);padding-left:8px;">
                                                • {{ $gi['titulo'] }}
                                                @foreach($gi['filhos'] as $gf)
                                                    <div style="padding-left:16px;color:var(--vo-text-faint);">└ {{ $gf }}</div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="cr-modal-actions">
                            <button type="button" wire:click="fecharSelecionarGrupo" class="cr-batch-btn cr-batch-btn--cancel">Fechar</button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- Modal: criar novo planejamento --}}
    @if($mostrarModalNovoPlanejamento)
        <div class="cr-modal-overlay" style="z-index:300;" wire:click.self="$set('mostrarModalNovoPlanejamento', false)">
            <div class="cr-modal-box" style="max-width:420px;">
                <div class="cr-modal-title">Novo planejamento</div>
                <p style="font-size:0.78rem;color:var(--vo-text-muted);margin-bottom:14px;">
                    Cria um planejamento independente. Você poderá adicionar fases e atividades após a criação.
                </p>
                <label style="display:block;font-size:0.78rem;color:var(--vo-text-secondary);margin-bottom:12px;">
                    Nome do planejamento
                    <input type="text"
                           wire:model="novoPlanejamentoNome"
                           wire:keydown.enter.prevent="criarNovoPlanejamento"
                           placeholder="Ex.: Implantação BIM — DPC Consultoria"
                           autofocus
                           style="display:block;width:100%;margin-top:4px;border:1px solid var(--vo-border);border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);padding:7px 10px;font-size:0.82rem;">
                </label>
                @error('novoPlanejamentoNome')
                    <p style="font-size:0.72rem;color:#dc2626;margin-bottom:8px;">{{ $message }}</p>
                @enderror
                <div class="cr-modal-actions">
                    <button type="button" class="cr-batch-btn cr-batch-btn--cancel"
                            wire:click="$set('mostrarModalNovoPlanejamento', false)">Cancelar</button>
                    <button type="button" class="cr-batch-btn cr-batch-btn--primary"
                            wire:click="criarNovoPlanejamento">Criar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: inserir fase no planejamento --}}
    @if($mostrarModalNovaFase)
        <div class="cr-modal-overlay" style="z-index:300;" wire:click.self="$set('mostrarModalNovaFase', false)">
            <div class="cr-modal-box" style="max-width:440px;">
                <div class="cr-modal-title">Inserir fase</div>
                <p style="font-size:0.78rem;color:var(--vo-text-muted);margin-bottom:14px;">
                    Adiciona uma nova fase ao planejamento. Dentro dela você poderá criar tarefas e subtarefas.
                </p>
                <label style="display:block;font-size:0.78rem;color:var(--vo-text-secondary);margin-bottom:12px;">
                    Nome da fase
                    <input type="text"
                           wire:model="novaFasePersonalizadaTitulo"
                           wire:keydown.enter.prevent="adicionarFasePersonalizadaEFecharModal"
                           placeholder="Ex.: Projeto Modelo 1 – Morar Mais"
                           autofocus
                           style="display:block;width:100%;margin-top:4px;border:1px solid var(--vo-border);border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);padding:7px 10px;font-size:0.82rem;">
                </label>
                @error('novaFasePersonalizadaTitulo')
                    <p style="font-size:0.72rem;color:#dc2626;margin-bottom:8px;">{{ $message }}</p>
                @enderror
                <div class="cr-modal-actions">
                    <button type="button" class="cr-batch-btn cr-batch-btn--cancel"
                            wire:click="$set('mostrarModalNovaFase', false)">Cancelar</button>
                    <button type="button" class="cr-batch-btn cr-batch-btn--primary"
                            wire:click="adicionarFasePersonalizadaEFecharModal">Inserir</button>
                </div>
            </div>
        </div>
    @endif

    @if($mostrarModalExcluirPlanejamento)
        <div class="cr-modal-overlay" style="z-index:300;" wire:click.self="$set('mostrarModalExcluirPlanejamento', false)">
            <div class="cr-modal" style="max-width:420px;">
                <div class="cr-modal-header">
                    <span class="cr-modal-title" style="color:#dc2626;">Excluir planejamento</span>
                    <button type="button" class="cr-modal-close" wire:click="$set('mostrarModalExcluirPlanejamento', false)">×</button>
                </div>
                <div class="cr-modal-body" style="padding:16px 20px;">
                    <p style="margin:0 0 8px;font-size:0.875rem;">Tem certeza que deseja excluir este planejamento?</p>
                    <p style="margin:0;font-size:0.8rem;color:#dc2626;"><strong>Esta ação é irreversível.</strong> Todas as fases, atividades e dados associados serão permanentemente removidos.</p>
                </div>
                <div class="cr-modal-footer" style="display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;">
                    <button type="button" class="cr-batch-btn cr-batch-btn--cancel"
                            wire:click="$set('mostrarModalExcluirPlanejamento', false)">Cancelar</button>
                    <button type="button" class="cr-batch-btn cr-batch-btn--primary"
                            style="background:#dc2626;border-color:#dc2626;"
                            wire:click="excluirPlanejamento">Confirmar exclusão</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Painel de Versoes --}}
    @if($mostrarVersoes && $modoIndividual && $projetoSelecionado)
        @php
            $versoesQuery = \App\Models\CronogramaFaseHistorico::with('usuario')
                ->where(function ($q) use ($projetoSelecionado) {
                    $q->whereHas('cronogramaFase', fn($q2) => $q2->where('projeto_id', $projetoSelecionado))
                      ->orWhere('projeto_id', $projetoSelecionado);
                })
                ->whereIn('campo_alterado', ['data_prevista_inicio', 'data_prevista_fim', 'template'])
                ->orderBy('created_at', 'desc')
                ->get();

            $versoesAgrupadas = $versoesQuery->groupBy(function ($h) {
                return $h->created_at->format('Y-m-d H:i:s');
            });
        @endphp
        <div class="cr-versoes-panel">
            <div class="cr-versoes-header">
                <span>Versões</span>
                <button type="button" wire:click="toggleVersoes"
                        style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-muted);font-size:1rem;line-height:1;padding:2px;">
                    &times;
                </button>
            </div>

            <div class="cr-versoes-list">
                {{-- Versao atual --}}
                <div class="cr-versao-item {{ !$versaoSelecionada ? 'cr-versao-atual' : '' }}"
                     wire:click="voltarVersaoAtual">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:8px;height:8px;border-radius:50%;background:#2dd67c;flex-shrink:0;"></div>
                        <div>
                            <div style="font-size:0.75rem;font-weight:600;color:var(--vo-text);">Versão atual</div>
                            <div style="font-size:0.65rem;color:var(--vo-text-muted);">{{ now()->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Versoes historicas --}}
                @foreach($versoesAgrupadas as $timestamp => $entries)
                    @php
                        $primeiro = $entries->first();
                        $isTemplate = $entries->where('campo_alterado', 'template')->isNotEmpty();
                        $totalAlteracoes = $entries->count();
                        $fasesAfetadas = $entries->pluck('cronograma_fase_id')->filter()->unique()->count();
                        $isAtiva = $versaoSelecionada === $timestamp;
                    @endphp
                    <div class="cr-versao-item {{ $isAtiva ? 'cr-versao-ativa' : '' }}"
                         wire:click="selecionarVersao('{{ $timestamp }}')">
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <div style="width:8px;height:8px;border-radius:50%;margin-top:4px;flex-shrink:0;background:{{ $isTemplate ? 'var(--vo-accent)' : ($primeiro->automatico ? 'var(--vo-text-faint)' : '#4a9eff') }};"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.72rem;font-weight:500;color:var(--vo-text);margin-bottom:2px;">
                                    @if($isTemplate)
                                        {{ $entries->where('campo_alterado', 'template')->first()->motivo ?? 'Template alterado' }}
                                    @elseif($primeiro->motivo)
                                        {{ \Illuminate\Support\Str::limit($primeiro->motivo, 40) }}
                                    @else
                                        {{ $totalAlteracoes }} alteração(ões)
                                    @endif
                                </div>
                                <div style="font-size:0.62rem;color:var(--vo-text-muted);display:flex;align-items:center;gap:6px;">
                                    <span>{{ \Carbon\Carbon::parse($timestamp)->format('d/m/Y H:i') }}</span>
                                    @if($fasesAfetadas > 0)
                                        <span>&middot; {{ $fasesAfetadas }} fase(s)</span>
                                    @endif
                                </div>
                                <div style="font-size:0.62rem;color:var(--vo-text-faint);margin-top:1px;">
                                    {{ $primeiro->usuario?->name ?? 'Sistema' }}
                                </div>
                            </div>
                            @if(! $isTemplate)
                                <button type="button"
                                        wire:click.stop="restaurarVersao('{{ $timestamp }}')"
                                        wire:confirm="Restaurar todas as datas previstas para o estado de {{ \Carbon\Carbon::parse($timestamp)->format('d/m/Y H:i') }}? As alterações posteriores serão registradas no histórico como reversão."
                                        title="Restaurar todas as datas para este momento"
                                        style="flex-shrink:0;padding:3px 8px;font-size:0.62rem;font-weight:600;background:transparent;border:1px solid var(--vo-border);border-radius:.25rem;cursor:pointer;color:var(--vo-text-secondary);">
                                    ↺ Restaurar
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if($versoesAgrupadas->isEmpty())
                    <div style="padding:20px;text-align:center;color:var(--vo-text-muted);font-size:0.78rem;">
                        Nenhuma versão anterior.
                    </div>
                @endif
            </div>
        </div>
    @endif
    {{-- Editor de Fases (painel lateral) --}}
    @if($modoIndividual && $projetoSelecionado)
        <div class="cr-editor-fases-panel {{ $mostrarEditorFases ? 'open' : '' }}">

            {{-- ── Header ── --}}
            <div class="cr-editor-panel-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--vo-text-muted);flex-shrink:0;"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="10" y2="18"/><circle cx="19" cy="15" r="3"/></svg>
                    <span style="font-weight:700;font-size:0.9rem;color:var(--vo-text);">Editar fases</span>
                    @if($templateAplicadoNoProjeto ?? null)
                        <span style="font-size:0.7rem;color:var(--vo-text-muted);">— {{ $templateAplicadoNoProjeto->nome }}</span>
                    @endif
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button type="button"
                            wire:click="salvarOverridesObraFases"
                            style="padding:5px 16px;font-size:0.78rem;font-weight:600;background:var(--vo-accent);color:#111;border:1px solid var(--vo-accent);border-radius:.375rem;cursor:pointer;white-space:nowrap;">
                        Salvar
                    </button>
                    <button type="button" wire:click="fecharEditorFases"
                            style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-muted);font-size:1.2rem;line-height:1;padding:3px 6px;border-radius:.25rem;"
                            title="Fechar">
                        ×
                    </button>
                </div>
            </div>

            {{-- ── Corpo scrollável ── --}}
            <div class="cr-editor-panel-body">

                @php $fasesEditor = $fasesParaEditor ?? $fases->sortBy('ordem'); @endphp

                <div x-data="{
                    openCards: {},
                    isOpen(id)     { return !!this.openCards[id]; },
                    toggleCard(id) { this.openCards[id] = !this.openCards[id]; },
                    dragSrc: null,
                    dragTarget: null,
                    onDrop(targetId) {
                        if (this.dragSrc !== null && this.dragSrc !== targetId)
                            $wire.moverFaseParaPosicao(this.dragSrc, targetId);
                        this.dragSrc = null; this.dragTarget = null;
                    }
                }" style="display:flex;flex-direction:column;gap:8px;">

                @foreach($fasesEditor as $fase)
                    @php
                        $ovr        = $overridesObraFases[$fase->id] ?? [];
                        $ovrDeps    = $ovr['deps'] ?? [];
                        $diasAtr    = $fase->diasAtraso ?? 0;
                        $farolCor   = $diasAtr > 5 ? '#ef4444' : ($diasAtr > 0 ? '#f59e0b' : '#22c55e');
                        $nomeFase   = $fase->titulo_personalizado ?? $fase->fase?->label() ?? '—';
                        $naoAplica  = ! $fase->isVisivel();
                    @endphp
                    <div class="{{ $naoAplica ? 'cr-ef-card cr-ef-card--nao-aplica' : 'cr-ef-card' }}"
                         draggable="true"
                         :class="{ 'cr-ef-card--over': dragTarget === {{ $fase->id }} && dragSrc !== {{ $fase->id }}, 'cr-ef-card--dragging': dragSrc === {{ $fase->id }} }"
                         @dragstart="dragSrc = {{ $fase->id }}"
                         @dragover.prevent="dragTarget = {{ $fase->id }}"
                         @drop.prevent="onDrop({{ $fase->id }})"
                         @dragend="dragSrc = null; dragTarget = null">

                        {{-- cabeçalho --}}
                        <div class="cr-ef-card-head" @click="toggleCard({{ $fase->id }})">

                            {{-- handle de arrasto --}}
                            <span class="cr-ef-drag-handle" title="Arrastar para reposicionar" @click.stop>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="9" cy="5"  r="1.5"/><circle cx="15" cy="5"  r="1.5"/>
                                    <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                    <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
                                </svg>
                            </span>

                            {{-- farol --}}
                            <span style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:{{ $farolCor }};box-shadow:0 0 0 2px color-mix(in srgb,{{ $farolCor }} 20%,transparent);"></span>

                            {{-- marco --}}
                            @if($fase->marco ?? false)
                                <span style="display:inline-block;width:7px;height:7px;transform:rotate(45deg);background:var(--vo-accent);flex-shrink:0;"></span>
                            @endif

                            {{-- nome --}}
                            <span style="flex:1;font-size:0.83rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--vo-text);">
                                {{ $nomeFase }}
                            </span>

                            {{-- badges e ações --}}
                            <div style="display:flex;gap:4px;align-items:center;flex-shrink:0;" @click.stop>
                                @if($naoAplica)
                                    <span style="font-size:0.6rem;padding:1px 6px;background:#fef9c3;color:#854d0e;border-radius:99px;font-weight:600;border:1px solid #fde68a;">oculta</span>
                                    {{-- Olho: restaurar visibilidade --}}
                                    <button type="button"
                                            wire:click="desmarcarNaoSeAplica({{ $fase->id }})"
                                            class="cr-ef-btn-icon"
                                            title="Restaurar fase no cronograma">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                @else
                                    @if($fase->regra_customizada)
                                        <span style="font-size:0.6rem;padding:1px 6px;background:var(--vo-accent-light,#ede9fe);color:var(--vo-accent);border-radius:99px;font-weight:600;">custom</span>
                                    @endif
                                    @php $qtdItens = $fase->itens->count(); @endphp
                                    @if($qtdItens > 0)
                                        <span style="font-size:0.6rem;padding:1px 6px;background:var(--vo-bg-subtle);color:var(--vo-text-muted);border-radius:99px;border:1px solid var(--vo-border);">
                                            {{ $qtdItens }} {{ $qtdItens === 1 ? 'item' : 'itens' }}
                                        </span>
                                    @endif
                                    <button type="button"
                                            wire:click="resetarFaseLote({{ $fase->id }})"
                                            wire:confirm="Resetar esta fase para o padrão do template?"
                                            class="cr-ef-btn-icon"
                                            title="Restaurar padrão do template">↺</button>
                                    {{-- Olho: ocultar fase --}}
                                    <button type="button"
                                            wire:click="marcarFaseNaoSeAplica({{ $fase->id }})"
                                            class="cr-ef-btn-icon"
                                            title="Ocultar fase do cronograma">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                            <line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </button>
                                @endif
                                {{-- X: excluir fase do projeto --}}
                                <button type="button"
                                        wire:click="excluirFaseProjeto({{ $fase->id }})"
                                        class="cr-ef-btn-icon"
                                        style="color:#b91c1c;"
                                        title="Excluir fase do projeto">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                    </svg>
                                </button>
                            </div>

                            <span style="font-size:0.7rem;color:var(--vo-text-faint);flex-shrink:0;margin-left:2px;" x-text="open ? '▴' : '▾'"></span>
                        </div>

                        {{-- corpo expansível --}}
                        <div class="cr-ef-card-body" x-show="isOpen({{ $fase->id }})" x-cloak>

                            {{-- ─ Duração ─ --}}
                            <div>
                                <div class="cr-ef-section-label">Duração</div>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <input type="number" min="0"
                                           wire:model="overridesObraFases.{{ $fase->id }}.duracao"
                                           class="cr-ef-input"
                                           style="width:72px;">
                                    <span style="font-size:0.78rem;color:var(--vo-text-muted);">dias</span>
                                    <select wire:model="overridesObraFases.{{ $fase->id }}.tipo_dias"
                                            class="cr-ef-input" style="flex:1;">
                                        @foreach($tipoDiasOptionsEnum as $td)
                                            <option value="{{ $td->value }}">{{ $td->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- ─ Dependências de fase ─ --}}
                            <div>
                                <div class="cr-ef-section-label">Dependências da fase</div>

                                @if(empty($ovrDeps))
                                    <p style="font-size:0.73rem;color:var(--vo-text-faint);margin:0 0 6px;">Nenhuma dependência</p>
                                @endif

                                @foreach($ovrDeps as $idx => $dep)
                                    <div wire:key="ovr-dep-{{ $fase->id }}-{{ $idx }}" class="cr-ef-dep-row" style="margin-bottom:4px;">
                                        <select wire:model="overridesObraFases.{{ $fase->id }}.deps.{{ $idx }}.alvo"
                                                class="cr-ef-input">
                                            <option value="">— selecione —</option>
                                            <optgroup label="Fases">
                                                @foreach($fasesEditor->reject(fn($f) => $f->id === $fase->id) as $faseOpt)
                                                    <option value="fase:{{ $faseOpt->fase->value }}">{{ $faseOpt->titulo_personalizado ?? $faseOpt->fase?->label() ?? '—' }}</option>
                                                @endforeach
                                            </optgroup>
                                            @php $fasesComItensOvr = $fasesEditor->reject(fn($f) => $f->id === $fase->id)->filter(fn($f) => $f->itens->isNotEmpty()); @endphp
                                            @if($fasesComItensOvr->isNotEmpty())
                                                <optgroup label="Subitens">
                                                    @foreach($fasesComItensOvr as $fOpt)
                                                        @foreach($fOpt->itens->where('parent_id', null) as $item)
                                                            <option value="item:{{ $item->id }}">{{ $fOpt->fase?->label() }} › {{ $item->titulo }}</option>
                                                            @foreach($item->children as $filho)
                                                                <option value="item:{{ $filho->id }}">{{ $fOpt->fase?->label() }} › {{ $item->titulo }} › {{ $filho->titulo }}</option>
                                                            @endforeach
                                                        @endforeach
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                        <select wire:model="overridesObraFases.{{ $fase->id }}.deps.{{ $idx }}.gatilho"
                                                class="cr-ef-input">
                                            @foreach($gatilhoOptionsEnum as $g)
                                                <option value="{{ $g->value }}">{{ $g->labelCurto() }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number"
                                               wire:model="overridesObraFases.{{ $fase->id }}.deps.{{ $idx }}.gap_dias"
                                               class="cr-ef-input"
                                               placeholder="dias">
                                        <button type="button"
                                                wire:click="removerDepFaseObra({{ $fase->id }}, {{ $idx }})"
                                                class="cr-ef-btn-remove">×</button>
                                    </div>
                                @endforeach

                                <button type="button"
                                        wire:click="adicionarDepFaseObra({{ $fase->id }})"
                                        class="cr-ef-btn-ghost">
                                    + dependência
                                </button>
                            </div>

                            {{-- ─ Subitens ─ --}}
                            <div style="border-top:1px solid var(--vo-border);padding-top:10px;">
                                <div class="cr-ef-section-label">Subitens</div>

                                @foreach($fase->itens->whereNull('parent_id')->sortBy('ordem') as $subitem)
                                    @include('filament.pages.cronograma-editor-subitem', [
                                        'item'             => $subitem,
                                        'depth'            => 0,
                                        'fasesDependencia' => $fases,
                                    ])
                                @endforeach

                                @if($fase->itens->whereNull('parent_id')->isEmpty())
                                    <p style="font-size:0.73rem;color:var(--vo-text-faint);margin:0 0 6px;">Nenhum subitem</p>
                                @endif

                                <div style="display:flex;gap:6px;margin-top:6px;">
                                    <input type="text"
                                           wire:model="novoSubitemTitulos.{{ $fase->id }}"
                                           wire:keydown.enter.prevent="adicionarSubitem({{ $fase->id }})"
                                           placeholder="Adicionar subitem…"
                                           class="cr-ef-input">
                                    <button type="button"
                                            wire:click="adicionarSubitem({{ $fase->id }})"
                                            class="cr-ef-btn-ghost"
                                            style="white-space:nowrap;">+ item</button>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
                </div>{{-- fim wrapper drag x-data --}}

                {{-- ── Nova fase ── --}}
                <div style="padding:12px 14px;border:1px solid var(--vo-border);border-radius:.5rem;background:var(--vo-bg-subtle);">
                    <div class="cr-ef-section-label" style="margin-bottom:8px;">Nova fase</div>
                    <div style="display:flex;gap:6px;">
                        <input type="text"
                               wire:model="novaFasePersonalizadaTitulo"
                               wire:keydown.enter.prevent="adicionarFasePersonalizada"
                               placeholder="Nome da fase personalizada…"
                               class="cr-ef-input">
                        <button type="button"
                                wire:click="adicionarFasePersonalizada"
                                class="cr-ef-btn-ghost"
                                style="white-space:nowrap;">+ Adicionar</button>
                    </div>
                </div>

                {{-- ── Modo de âncora ── --}}
                @php
                    $modo = $modoAncoraAtual ?? \App\Enums\ModoAncoraCronograma::POSSE;
                    $modoCor = $modo === \App\Enums\ModoAncoraCronograma::POSSE ? '#f59e0b' : '#10b981';
                    $modoCorBg = $modo === \App\Enums\ModoAncoraCronograma::POSSE ? 'rgba(245,158,11,.10)' : 'rgba(16,185,129,.10)';
                    $modoNome = $modo === \App\Enums\ModoAncoraCronograma::POSSE ? 'Posse' : 'Obras';
                @endphp
                <div style="margin-top:4px;padding:12px 14px;border:1px solid {{ $modoCor }};border-left-width:4px;border-radius:.5rem;background:{{ $modoCorBg }};">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                        <span style="font-size:0.72rem;font-weight:700;color:var(--vo-text-secondary);text-transform:uppercase;letter-spacing:.04em;">
                            Âncora do cronograma
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:2px 8px;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;border-radius:99px;background:{{ $modoCor }};color:#fff;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            Ativo: {{ $modoNome }}
                        </span>
                    </div>
                    <p style="font-size:0.72rem;color:var(--vo-text-muted);margin:0 0 10px;">
                        {{ $modo->descricao() }}
                    </p>
                    <div style="display:flex;gap:6px;">
                        @php $ativoPosse = $modo === \App\Enums\ModoAncoraCronograma::POSSE; @endphp
                        <button type="button"
                                wire:click="definirModoAncora('posse')"
                                @disabled($ativoPosse)
                                title="{{ $ativoPosse ? 'Já está ancorado em Posse' : 'Mudar para ancorar em Posse' }}"
                                style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:7px 10px;font-size:0.75rem;font-weight:600;border-radius:.375rem;cursor:{{ $ativoPosse ? 'default' : 'pointer' }};border:1px solid {{ $ativoPosse ? '#f59e0b' : 'var(--vo-border)' }};{{ $ativoPosse ? 'background:#f59e0b;color:#fff;box-shadow:0 1px 2px rgba(245,158,11,.3);' : 'background:var(--vo-bg);color:var(--vo-text-muted);' }}">
                            @if($ativoPosse)
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            @endif
                            Ancorar em Posse
                        </button>
                        @php $ativoObras = $modo === \App\Enums\ModoAncoraCronograma::OBRAS; @endphp
                        <button type="button"
                                wire:click="definirModoAncora('obras')"
                                @disabled($ativoObras)
                                title="{{ $ativoObras ? 'Já está ancorado em Obras' : 'Mudar para ancorar em Obras' }}"
                                style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:7px 10px;font-size:0.75rem;font-weight:600;border-radius:.375rem;cursor:{{ $ativoObras ? 'default' : 'pointer' }};border:1px solid {{ $ativoObras ? '#10b981' : 'var(--vo-border)' }};{{ $ativoObras ? 'background:#10b981;color:#fff;box-shadow:0 1px 2px rgba(16,185,129,.3);' : 'background:var(--vo-bg);color:var(--vo-text-muted);' }}">
                            @if($ativoObras)
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            @endif
                            Ancorar em Obras
                        </button>
                    </div>
                </div>

                {{-- ── Zona de perigo ── --}}
                <div style="margin-top:4px;padding:14px;border:1px solid #fca5a5;border-radius:.5rem;background:color-mix(in srgb,#fee2e2 50%,var(--vo-bg));">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span style="font-size:0.75rem;font-weight:700;color:#b91c1c;text-transform:uppercase;letter-spacing:.04em;">Zona de perigo</span>
                    </div>

                    <label style="display:block;font-size:0.75rem;color:var(--vo-text-muted);margin-bottom:4px;">Template</label>
                    <select wire:model.live="templateSelecionadoParaAplicar"
                            class="cr-ef-input" style="margin-bottom:10px;">
                        <option value="">— selecione —</option>
                        @foreach(($templatesDisponiveis ?? []) as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->nome }}</option>
                        @endforeach
                    </select>

                    <label style="display:block;font-size:0.75rem;color:var(--vo-text-muted);margin-bottom:4px;">{{ $templateAncoraLabel ?? 'Data-âncora' }}</label>
                    <input type="date" wire:model.live="templateDataAncora"
                           class="cr-ef-input" style="margin-bottom:12px;">

                    <div style="display:flex;gap:8px;">
                        <button type="button"
                                wire:click="zerarDatasObra"
                                wire:confirm="Zerar todas as datas do cronograma? Esta ação não pode ser desfeita."
                                style="flex:1;padding:7px 6px;border:1px solid #fca5a5;background:transparent;border-radius:.375rem;color:#b91c1c;cursor:pointer;font-size:0.75rem;font-weight:500;">
                            Zerar datas
                        </button>
                        <button type="button"
                                wire:click="aplicarTemplate"
                                wire:confirm="Reaplicar o template? Isso recalculará todas as datas previstas."
                                @if(!$templateSelecionadoParaAplicar || !$templateDataAncora) disabled @endif
                                style="flex:1;padding:7px 6px;border:1px solid #fca5a5;background:transparent;border-radius:.375rem;color:#b91c1c;cursor:pointer;font-size:0.75rem;font-weight:500;@if(!$templateSelecionadoParaAplicar || !$templateDataAncora)opacity:0.35;cursor:not-allowed;@endif">
                            Reaplicar template
                        </button>
                    </div>
                </div>

            </div>{{-- fim .cr-editor-panel-body --}}
        </div>{{-- fim .cr-editor-fases-panel --}}

        {{-- Alterar Datas (painel lateral) --}}
        <div class="cr-alterar-datas-panel {{ $mostrarModalDatas ? 'open' : '' }}">
            <div class="cr-editor-panel-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--vo-text-muted);flex-shrink:0;"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M12 14l2 2 4-4"/></svg>
                    <span style="font-weight:700;font-size:0.9rem;color:var(--vo-text);">Alterar datas</span>
                    <span style="font-size:0.7rem;color:var(--vo-text-muted);">— prévia em tempo real</span>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button type="button"
                            wire:click="abrirConfirmacaoSalvarDatas"
                            style="padding:5px 16px;font-size:0.78rem;font-weight:600;background:var(--vo-accent);color:#111;border:1px solid var(--vo-accent);border-radius:.375rem;cursor:pointer;white-space:nowrap;">
                        Salvar
                    </button>
                    <button type="button" wire:click="fecharModalDatas"
                            style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-muted);font-size:1.2rem;line-height:1;padding:3px 6px;border-radius:.25rem;"
                            title="Fechar">
                        ×
                    </button>
                </div>
            </div>

            <div class="cr-alterar-datas-body">
                @if($mostrarModalDatas ?? false)
                    <p style="font-size:0.74rem;color:var(--vo-text-muted);margin:0;">
                        As alterações abaixo aparecem em prévia no Gantt e no resumo. Clique em <strong>Salvar</strong> para persistir no banco. Deixe campos em branco para limpar.
                    </p>

                    <table class="cr-alterar-datas-table">
                        <thead>
                            <tr>
                                <th>Fase</th>
                                <th>Previsto Início</th>
                                <th>Previsto Fim</th>
                                <th style="text-align:center;">Duração</th>
                                <th style="text-align:center;">Padrão</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fases as $fase)
                                @php
                                    $temTpl = (bool) $fase->templateFase;
                                    $durTpl = $temTpl ? (int) $fase->templateFase->duracao_dias : null;
                                    $durAtual = (int) ($edicaoLoteDatas[$fase->id]['duracao'] ?? 0);
                                    $durCustom = $temTpl && $durAtual !== $durTpl;
                                    $travado = !empty($edicaoLoteDatas[$fase->id]['travado']);
                                    $elastica = $fase->regra_elastica ?? $fase->templateFase?->regra_elastica ?? false;

                                    $origI = $datasOriginaisLote[$fase->id]['prev_i'] ?? null;
                                    $origF = $datasOriginaisLote[$fase->id]['prev_f'] ?? null;
                                    $novaI = $edicaoLoteDatas[$fase->id]['prev_i'] ?? null;
                                    $novaF = $edicaoLoteDatas[$fase->id]['prev_f'] ?? null;
                                    $mudouI = $origI !== $novaI;
                                    $mudouF = $origF !== $novaF;
                                    $deltaI = ($mudouI && $origI && $novaI) ? \Carbon\Carbon::parse($origI)->diffInDays(\Carbon\Carbon::parse($novaI), false) : null;
                                    $deltaF = ($mudouF && $origF && $novaF) ? \Carbon\Carbon::parse($origF)->diffInDays(\Carbon\Carbon::parse($novaF), false) : null;
                                @endphp
                                <tr style="{{ $travado ? 'opacity:.55;' : '' }}">
                                    <td style="max-width:220px;">
                                        <span style="display:flex;align-items:flex-start;gap:6px;flex-wrap:wrap;">
                                            <button type="button"
                                                    wire:click="$set('edicaoLoteDatas.{{ $fase->id }}.travado', {{ $travado ? 'false' : 'true' }})"
                                                    title="{{ $travado ? 'Destravar recálculo' : 'Travar recálculo' }}"
                                                    style="cursor:pointer;background:none;border:none;padding:2px;line-height:1;color:{{ $travado ? 'var(--vo-accent)' : 'var(--vo-text-faint)' }};flex-shrink:0;margin-top:2px;">
                                                @if($travado)
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 019.9-1"/></svg>
                                                @endif
                                            </button>
                                            @if($fase->marco)
                                                <span style="display:inline-block;width:8px;height:8px;transform:rotate(45deg);background:{{ $fase->status->color() }};flex-shrink:0;margin-top:5px;"></span>
                                            @endif
                                            <span style="font-size:0.74rem;line-height:1.3;word-break:break-word;flex:1;min-width:0;">
                                                {{ $fase->label_exibicao }}
                                                @if($elastica)
                                                    <span title="Duração elástica: ajusta-se às dependências"
                                                          style="display:inline-flex;align-items:center;font-size:0.6rem;font-weight:600;padding:1px 6px;background:#ede9fe;color:#6d28d9;border-radius:99px;border:1px solid #ddd6fe;margin-left:4px;white-space:nowrap;">
                                                        ↔ Elástica
                                                    </span>
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                    <td>
                                        <div x-data style="display:flex;gap:4px;align-items:center;">
                                            <input type="date" wire:model.blur="edicaoLoteDatas.{{ $fase->id }}.prev_i" {{ $travado ? 'disabled' : '' }} style="padding:5px 8px;border:1px solid {{ $mudouI ? '#3b82f6' : 'var(--vo-border)' }};border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);font-size:.72rem;flex:1;min-width:0;">
                                            <button type="button"
                                                    class="cr-date-copy-btn"
                                                    @if($travado) disabled @endif
                                                    @click="const ini = $el.parentElement.querySelector('input[type=date]')?.value; if (ini) $wire.set('edicaoLoteDatas.{{ $fase->id }}.prev_f', ini)"
                                                    title="Copiar data de início para fim">→</button>
                                        </div>
                                        @if($mudouI && $origI)
                                            <div style="font-size:.58rem;color:var(--vo-text-faint);margin-top:2px;display:flex;align-items:center;gap:4px;">
                                                <span style="text-decoration:line-through;">{{ \Carbon\Carbon::parse($origI)->format('d/m') }}</span>
                                                <span style="color:{{ $deltaI > 0 ? '#ef4444' : '#22c55e' }};font-weight:600;">{{ $deltaI > 0 ? '+'.$deltaI.'d' : $deltaI.'d' }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="date" wire:model.blur="edicaoLoteDatas.{{ $fase->id }}.prev_f" {{ $travado ? 'disabled' : '' }} style="padding:5px 8px;border:1px solid {{ $mudouF ? '#3b82f6' : 'var(--vo-border)' }};border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);font-size:.72rem;width:100%;">
                                        @if($mudouF && $origF)
                                            <div style="font-size:.58rem;color:var(--vo-text-faint);margin-top:2px;display:flex;align-items:center;gap:4px;">
                                                <span style="text-decoration:line-through;">{{ \Carbon\Carbon::parse($origF)->format('d/m') }}</span>
                                                <span style="color:{{ $deltaF > 0 ? '#ef4444' : '#22c55e' }};font-weight:600;">{{ $deltaF > 0 ? '+'.$deltaF.'d' : $deltaF.'d' }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($elastica)
                                            <span title="Duração calculada automaticamente"
                                                  style="font-size:0.62rem;color:var(--vo-text-faint);font-style:italic;">auto</span>
                                        @else
                                            <div style="display:inline-flex;align-items:center;gap:4px;">
                                                <input type="number" min="0" wire:model.blur="edicaoLoteDatas.{{ $fase->id }}.duracao"
                                                       {{ $travado ? 'disabled' : '' }}
                                                       style="width:60px;padding:5px 8px;border:1px solid {{ $durCustom ? 'var(--vo-accent)' : 'var(--vo-border)' }};border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);font-size:.72rem;text-align:right;font-variant-numeric:tabular-nums;">
                                                <span style="font-size:.62rem;color:var(--vo-text-faint);">d</span>
                                            </div>
                                            @if($temTpl && $durCustom)
                                                <div style="font-size:.58rem;color:var(--vo-text-faint);margin-top:2px;">padrão: {{ $durTpl }}d</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        @if($temTpl)
                                            <button type="button"
                                                    wire:click="resetarFaseLote({{ $fase->id }})"
                                                    wire:confirm="Resetar esta fase para o padrão do template? Duração, tipo de dias e dependências customizadas serão descartadas."
                                                    title="Resetar ao padrão do template"
                                                    style="padding:4px 8px;border:1px solid var(--vo-border);background:var(--vo-bg);border-radius:.375rem;cursor:pointer;font-size:.65rem;color:var(--vo-text-secondary);">
                                                ↺
                                            </button>
                                        @else
                                            <span style="color:var(--vo-text-faint);font-size:.62rem;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>{{-- fim .cr-alterar-datas-panel --}}

        {{-- Modal: Confirmar salvar datas em lote (com motivo obrigatório) --}}
        @if($mostrarConfirmacaoSalvarDatas ?? false)
            @php $posseAlterada = $this->posseAlteradaNoLote(); @endphp
            <div class="cr-modal-overlay" wire:click.self="fecharConfirmacaoSalvarDatas" style="z-index:9000;">
                <div class="cr-modal" style="max-width:520px;width:92vw;">
                    <h3 style="margin-top:0;">Confirmar alteração de datas</h3>
                    <p style="font-size:0.78rem;color:var(--vo-text-muted);margin-bottom:14px;">
                        Informe o motivo da alteração. Esse texto será gravado no histórico de cada fase alterada.
                    </p>

                    <label style="display:flex;flex-direction:column;gap:4px;font-size:0.74rem;color:var(--vo-text-secondary);font-weight:600;">
                        Motivo da alteração <span style="color:#ef4444;">*</span>
                        <textarea rows="3" wire:model.live="motivoLoteDatas" placeholder="Ex.: replanejamento por atraso de fornecedor..."
                                  style="width:100%;font-size:0.78rem;padding:8px 10px;border:1px solid var(--vo-border);border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);resize:vertical;"></textarea>
                    </label>

                    @if($posseAlterada)
                        <div style="margin-top:14px;padding:12px;border:1px solid #fbbf24;background:#fffbeb;border-radius:.375rem;">
                            <div style="font-size:0.72rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">
                                ⚠️ Data de Posse alterada — selecione a classificação
                            </div>

                            <label style="display:flex;flex-direction:column;gap:4px;font-size:0.74rem;color:var(--vo-text-secondary);font-weight:600;">
                                Motivo padronizado <span style="color:#ef4444;">*</span>
                                <select wire:model.live="motivoPosseCodigo"
                                        style="width:100%;font-size:0.78rem;padding:8px 10px;border:1px solid var(--vo-border);border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);">
                                    <option value="">— selecione —</option>
                                    @foreach($motivosPosseOptions ?? [] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    @endif

                    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;">
                        <button type="button" class="vo-btn-outline" wire:click="fecharConfirmacaoSalvarDatas" style="padding:7px 14px;">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="confirmarSalvarDatasEmLote"
                                @disabled(blank(trim($motivoLoteDatas ?? '')) || ($posseAlterada && blank($motivoPosseCodigo)))
                                style="padding:7px 14px;background:var(--vo-accent);color:#111;border:none;border-radius:.375rem;font-weight:600;cursor:pointer;@if(blank(trim($motivoLoteDatas ?? '')) || ($posseAlterada && blank($motivoPosseCodigo))) opacity:0.4;cursor:not-allowed; @endif">
                            Salvar alterações
                        </button>
                    </div>
                </div>
            </div>
        @endif

    {{-- Modal: reaplicar variante após troca de modo de âncora --}}
    @if($mostrarConfirmacaoReaplicar ?? false)
        <div class="cr-modal-overlay" wire:click.self="cancelarReaplicarVariante">
            <div class="cr-modal" style="width:520px;max-width:95vw;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:rgba(59,130,246,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    </div>
                    <h3 style="margin:0;">Reaplicar variante do template?</h3>
                </div>

                <p style="font-size:0.85rem;color:var(--vo-text-secondary);line-height:1.5;margin:0 0 12px;">
                    Você mudou o modo de âncora. O template aplicado tem uma variante específica para esse modo:
                    <strong>{{ $varianteSugeridaNome }}</strong>.
                </p>
                <p style="font-size:0.75rem;color:var(--vo-text-muted);line-height:1.5;margin:0 0 6px;">
                    Reaplicar vai recalcular as <strong>datas previstas</strong> conforme a nova variante. Status e datas <em>realizadas</em> são preservados; fases concluídas/canceladas não serão recalculadas.
                </p>
                <p style="font-size:0.72rem;color:var(--vo-text-faint);margin:0;">
                    Se preferir só mudar o comportamento de recálculo sem mexer no cronograma, escolha "Apenas mudar o modo".
                </p>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;">
                    <button type="button" class="vo-btn-outline" wire:click="cancelarReaplicarVariante" style="padding:7px 14px;">
                        Apenas mudar o modo
                    </button>
                    <button type="button" wire:click="confirmarReaplicarVariante"
                            style="padding:7px 14px;background:#2563eb;color:#fff;border:none;border-radius:.375rem;font-weight:600;cursor:pointer;">
                        Reaplicar variante
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de justificativa de alteração de Data de Posse --}}
    @if($mostrarModalMotivoPosse ?? false)
        <div class="cr-modal-overlay" wire:click.self="cancelarMotivoPosse" style="z-index:9100;">
            <div class="cr-modal" style="max-width:520px;width:92vw;">
                <h3 style="margin-top:0;">Justifique a alteração da Data de Posse</h3>
                <p style="font-size:0.78rem;color:var(--vo-text-muted);margin-bottom:14px;">
                    A alteração da Data de Posse será registrada no histórico do projeto.
                </p>

                <label style="display:flex;flex-direction:column;gap:4px;font-size:0.74rem;color:var(--vo-text-secondary);font-weight:600;margin-bottom:12px;">
                    Motivo padronizado <span style="color:#ef4444;">*</span>
                    <select wire:model.live="motivoPosseCodigo"
                            style="width:100%;font-size:0.78rem;padding:8px 10px;border:1px solid var(--vo-border);border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);">
                        <option value="">— selecione —</option>
                        @foreach($motivosPosseOptions ?? [] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label style="display:flex;flex-direction:column;gap:4px;font-size:0.74rem;color:var(--vo-text-secondary);font-weight:600;">
                    Detalhe a justificativa (motivo histórico)
                    <textarea rows="3" wire:model.live="motivoPosseHistorico"
                              placeholder="Ex.: proprietário antecipou a entrega do Shell em 30 dias..."
                              style="width:100%;font-size:0.78rem;padding:8px 10px;border:1px solid var(--vo-border);border-radius:.375rem;background:var(--vo-bg);color:var(--vo-text);resize:vertical;"></textarea>
                </label>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;">
                    <button type="button" class="vo-btn-outline" wire:click="cancelarMotivoPosse" style="padding:7px 14px;">
                        Cancelar
                    </button>
                    <button type="button"
                            wire:click="confirmarMotivoPosse"
                            @disabled(blank($motivoPosseCodigo))
                            style="padding:7px 14px;background:var(--vo-accent);color:#111;border:none;border-radius:.375rem;font-weight:600;cursor:pointer;@if(blank($motivoPosseCodigo)) opacity:0.4;cursor:not-allowed; @endif">
                        Salvar com justificativa
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de conflito de dependência ao marcar "não se aplica" --}}
    @if($mostrarModalConflitoDep)
        <div style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);"
             wire:click.self="cancelarNaoSeAplica">
            <div style="background:var(--vo-bg);border-radius:.75rem;padding:24px 28px;max-width:500px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,0.28);border:1px solid var(--vo-border);">

                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span style="font-size:1.2rem;">⚠️</span>
                    <span style="font-size:1rem;font-weight:700;color:var(--vo-text);">
                        {{ $acaoPendenteFase === 'excluir' ? 'Excluir fase — reconfigurar dependências' : 'Ocultar fase — reconfigurar dependências' }}
                    </span>
                </div>

                <p style="font-size:0.84rem;color:var(--vo-text-secondary);margin:0 0 16px;">
                    As fases abaixo dependem desta. Escolha para cada uma a fase que vai substituí-la ou deixe em branco para remover a dependência.
                </p>

                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                    @foreach($fasesConflitantes as $idx => $conf)
                        <div wire:key="conf-{{ $idx }}" style="background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.5rem;padding:10px 14px;">

                            <div style="font-size:0.8rem;font-weight:600;color:var(--vo-text);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                <span style="color:#f59e0b;">→</span>
                                {{ $conf['fase_nome'] }}
                            </div>

                            {{-- Fase substituta --}}
                            <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 10px;align-items:center;">
                                <label style="font-size:0.72rem;color:var(--vo-text-muted);white-space:nowrap;">Depender de</label>
                                <select wire:model="fasesConflitantes.{{ $idx }}.substituir_por"
                                        style="padding:4px 8px;border:1px solid var(--vo-border);border-radius:.3rem;font-size:0.78rem;background:var(--vo-bg);color:var(--vo-text);">
                                    <option value="">— Remover dependência —</option>
                                    @foreach($fasesParaEditor->reject(fn($f) => $f->fase?->value === $faseParaNaoAplicarEnum) as $faseOpt)
                                        <option value="{{ $faseOpt->fase?->value }}">{{ $faseOpt->titulo_personalizado ?? $faseOpt->fase?->label() ?? '—' }}</option>
                                    @endforeach
                                </select>

                                {{-- Gatilho --}}
                                <label style="font-size:0.72rem;color:var(--vo-text-muted);white-space:nowrap;">Gatilho</label>
                                <select wire:model="fasesConflitantes.{{ $idx }}.gatilho"
                                        style="padding:4px 8px;border:1px solid var(--vo-border);border-radius:.3rem;font-size:0.78rem;background:var(--vo-bg);color:var(--vo-text);">
                                    @foreach($gatilhoOptionsEnum as $g)
                                        <option value="{{ $g->value }}"
                                            @selected(($conf['gatilho'] ?? '') === $g->value)>
                                            {{ $g->label() }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Deslocamento --}}
                                <label style="font-size:0.72rem;color:var(--vo-text-muted);white-space:nowrap;">Deslocamento (dias)</label>
                                <input type="number"
                                       wire:model="fasesConflitantes.{{ $idx }}.gap_dias"
                                       style="padding:4px 8px;border:1px solid var(--vo-border);border-radius:.3rem;font-size:0.78rem;background:var(--vo-bg);color:var(--vo-text);width:100%;">
                            </div>

                        </div>
                    @endforeach
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" wire:click="cancelarNaoSeAplica"
                            style="padding:8px 18px;border:1px solid var(--vo-border);border-radius:.4rem;background:var(--vo-bg);color:var(--vo-text-secondary);cursor:pointer;font-size:0.84rem;">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarNaoSeAplicaRemoveDeps"
                            style="padding:8px 18px;border:1px solid #dc2626;border-radius:.4rem;background:#dc2626;color:#fff;cursor:pointer;font-size:0.84rem;font-weight:600;">
                        Confirmar
                    </button>
                </div>

            </div>
        </div>
    @endif
    @endif

    </div>{{-- fecha flex container --}}

    {{-- Modal de Edicao --}}
    @if($editingFaseId)
        @php $faseEditando = \App\Models\CronogramaFase::find($editingFaseId); @endphp
        @if($faseEditando)
            <div class="cr-modal-overlay" wire:click.self="fecharEdicao">
                <div class="cr-modal">
                    <h3>{{ $faseEditando->fase->label() }}</h3>

                    <div class="cr-modal-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <label>Data Prevista Início</label>
                            <input type="date" wire:model="editDataPrevistaInicio">
                        </div>
                        <div>
                            <label>Data Prevista Fim</label>
                            <input type="date" wire:model="editDataPrevistaFim">
                        </div>
                    </div>

                    @if($faseEditando->data_realizada_inicio || $faseEditando->data_realizada_fim)
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px;">
                            <div>
                                <label style="color:var(--vo-text-muted);font-size:0.7rem;">Real Início</label>
                                <div style="padding:7px 10px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.375rem;font-size:0.78rem;color:var(--vo-text-secondary);">
                                    {{ $faseEditando->data_realizada_inicio?->format('d/m/Y') ?? '—' }}
                                </div>
                            </div>
                            <div>
                                <label style="color:var(--vo-text-muted);font-size:0.7rem;">Real Fim</label>
                                <div style="padding:7px 10px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.375rem;font-size:0.78rem;color:var(--vo-text-secondary);">
                                    {{ $faseEditando->data_realizada_fim?->format('d/m/Y') ?? '—' }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <label>Status</label>
                    <div class="cr-status-dropdown" x-data="{ open: false, pos: {top:0,left:0}, reposition() { const r = this.$refs.trigger.getBoundingClientRect(); this.pos = {top: r.bottom + 4, left: r.left}; } }" @click.outside="open = false" style="margin-bottom:12px;">
                        @php $statusAtualModal = \App\Enums\StatusCronograma::tryFrom($editStatus); @endphp
                        <button type="button" class="cr-status-trigger" style="background:{{ $statusAtualModal?->color() ?? '#6b7280' }};font-size:0.75rem;padding:5px 12px 5px 8px;" x-ref="trigger"
                                @click="reposition(); open = !open" :aria-expanded="open">
                            <span class="cr-status-dot"></span>
                            {{ $statusAtualModal?->label() ?? 'Selecione' }}
                            <svg class="cr-status-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"/></svg>
                        </button>
                        <template x-teleport="body">
                        <div class="cr-status-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms :style="`top:${pos.top}px;left:${pos.left}px`" @click.outside="open = false">
                            @foreach($faseEditando->fase->statusDisponiveis() as $s)
                                <button type="button"
                                        class="cr-status-option {{ $editStatus === $s->value ? 'cr-status-active' : '' }}"
                                        wire:click="$set('editStatus', '{{ $s->value }}')"
                                        @click="open = false">
                                    <span class="cr-opt-dot" style="background:{{ $s->color() }}"></span>
                                    {{ $s->label() }}
                                </button>
                            @endforeach
                        </div>
                        </template>
                    </div>

                    <label>Percentual de Conclusão</label>
                    <div class="cr-percentual"
                         x-data="{ pct: @entangle('editPercentual').live, updateTrack() { this.$refs.rangeInput.style.setProperty('--pct', this.pct + '%'); } }"
                         x-init="updateTrack()"
                         x-effect="updateTrack()">
                        <input x-ref="rangeInput" type="range" min="0" max="100" x-model="pct" wire:model.live="editPercentual" style="--pct: {{ $editPercentual }}%">
                        <span x-text="pct + '%'"></span>
                    </div>


                    @if($faseEditando->dias_atraso > 0)
                        <div style="font-size:0.73rem;color:var(--vo-danger-text);margin-bottom:12px">
                            {{ $faseEditando->dias_atraso }} dia(s) de atraso
                        </div>
                    @endif

                    @if($faseEditando->cronograma_template_id)
                        <div x-data="{ open: {{ $editRegraCustomizada ? 'true' : 'false' }} }" style="margin-top:16px;border-top:1px solid var(--vo-border);padding-top:14px;">
                            <button type="button" @click="open = !open"
                                    style="display:flex;align-items:center;gap:8px;background:transparent;border:none;padding:0;cursor:pointer;color:var(--vo-text);font-weight:600;font-size:0.82rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     :style="open ? 'transform:rotate(90deg)' : ''"><path d="M9 18l6-6-6-6"/></svg>
                                Regra do cronograma
                                @if($editRegraCustomizada)
                                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f97316;" title="Regra customizada"></span>
                                @endif
                            </button>

                            <div x-show="open" x-cloak style="margin-top:12px;display:flex;flex-direction:column;gap:14px;">
                                <label class="ct-checkbox-row" style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" wire:model.live="editRegraElastica">
                                    <span>Fase elástica (duração só pelas dependências)</span>
                                </label>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                    <div>
                                        <label>Duração (dias)</label>
                                        <input type="number" min="0" wire:model="editRegraDuracaoDias" @disabled($editRegraElastica)>
                                    </div>
                                    <div>
                                        <label>Tipo de dias</label>
                                        <select wire:model="editRegraTipoDias">
                                            <option value="corridos">Dias corridos</option>
                                            <option value="uteis">Dias úteis</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                        <label style="margin:0;">Dependências</label>
                                        <button type="button" class="vo-btn-outline" wire:click="adicionarDependenciaObra" style="padding:4px 10px;font-size:0.7rem;">
                                            + Adicionar dependência
                                        </button>
                                    </div>

                                    @if(empty($editDependencias))
                                        <div style="padding:10px;font-size:0.7rem;color:var(--vo-text-muted);background:var(--vo-bg-subtle);border:1px dashed var(--vo-border);border-radius:.375rem;text-align:center;">
                                            Sem dependências. A fase começa na data da âncora do template.
                                        </div>
                                    @else
                                        <div style="display:flex;flex-direction:column;gap:8px;">
                                            @foreach($editDependencias as $idx => $dep)
                                                <div wire:key="dep-obra-{{ $editingFaseId }}-{{ $idx }}" style="display:grid;grid-template-columns:1fr 160px 100px 32px;gap:6px;align-items:end;padding:8px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.375rem;">
                                                    <div>
                                                        <label style="font-size:0.62rem;margin-bottom:2px;">Depende de</label>
                                                        <select wire:model="editDependencias.{{ $idx }}.alvo">
                                                            <option value="">— selecione —</option>
                                                            <optgroup label="Fases">
                                                                @foreach($fasesParaEditor as $fOpt)
                                                                    @if($fOpt->fase?->value !== $editFaseValue)
                                                                        <option value="fase:{{ $fOpt->fase?->value }}">{{ $fOpt->titulo_personalizado ?? $fOpt->fase?->label() ?? '—' }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </optgroup>
                                                            @php $fasesComItens = $fasesParaEditor->filter(fn($f) => $f->itens->isNotEmpty()); @endphp
                                                            @if($fasesComItens->isNotEmpty())
                                                                <optgroup label="Subitens">
                                                                    @foreach($fasesComItens as $fOpt)
                                                                        @foreach($fOpt->itens->where('parent_id', null) as $item)
                                                                            <option value="item:{{ $item->id }}">{{ $fOpt->fase?->label() }} › {{ $item->titulo }}</option>
                                                                            @foreach($item->children as $filho)
                                                                                <option value="item:{{ $filho->id }}">{{ $fOpt->fase?->label() }} › {{ $item->titulo }} › {{ $filho->titulo }}</option>
                                                                            @endforeach
                                                                        @endforeach
                                                                    @endforeach
                                                                </optgroup>
                                                            @endif
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:0.62rem;margin-bottom:2px;">Gatilho</label>
                                                        <select wire:model="editDependencias.{{ $idx }}.gatilho">
                                                            @foreach(\App\Enums\GatilhoTemplateFase::cases() as $g)
                                                                <option value="{{ $g->value }}">{{ $g->label() }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:0.62rem;margin-bottom:2px;">Deslocamento</label>
                                                        <input type="number" wire:model="editDependencias.{{ $idx }}.gap_dias">
                                                    </div>
                                                    <button type="button" wire:click="removerDependenciaObra({{ $idx }})"
                                                            title="Remover dependência"
                                                            style="width:32px;height:32px;border:1px solid #fca5a5;background:transparent;color:#b91c1c;border-radius:.375rem;cursor:pointer;font-size:1rem;line-height:1;">
                                                        ×
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if($editRegraCustomizada)
                                    <div>
                                        <button type="button" class="vo-btn-outline" wire:click="resetarRegraFase"
                                                style="color:#b45309;border-color:#fcd34d;">
                                            Restaurar regra do template
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <div x-show="open" x-cloak style="margin-top:8px;font-size:0.7rem;color:var(--vo-text-muted);">
                                Alterar a regra aqui só afeta este projeto. Mudar as dependências ou a duração dispara o recálculo híbrido completo do cronograma.
                            </div>
                        </div>
                    @endif

                    @if($mostrarMotivoDatas)
                        <div style="margin-top:16px;padding:14px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.4);border-radius:.5rem;">
                            <div style="font-size:0.82rem;font-weight:600;color:var(--vo-text);margin-bottom:6px;">
                                Motivo da alteração de datas
                            </div>
                            <textarea rows="2" wire:model="editMotivoDatas" placeholder="Informe o motivo da alteração..."
                                      style="width:100%;font-size:0.78rem;margin-bottom:8px;"></textarea>
                            <button type="button" class="vo-btn-accent" wire:click="salvarFase">
                                Confirmar alteração
                            </button>
                        </div>
                    @endif

                    @if($editConfirmacaoShift)
                        <div style="margin-top:16px;padding:14px;background:rgba(251,186,0,.12);border:1px solid rgba(251,186,0,.5);border-radius:.5rem;">
                            <div style="font-size:0.82rem;font-weight:600;color:var(--vo-text);margin-bottom:6px;">
                                Você alterou apenas uma das datas.
                            </div>
                            <p style="margin:0 0 12px;font-size:0.74rem;color:var(--vo-text-muted);line-height:1.5;">
                                O que você quer fazer? Mover a fase inteira preservando a duração original
                                ({{ $editFaseDuracaoOriginal }} dias), ou manter as datas exatas e alterar a duração?
                            </p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="button" class="vo-btn-accent" wire:click="confirmarSalvarShift">
                                    Mover fase inteira ({{ $editFaseDuracaoOriginal }} dias)
                                </button>
                                <button type="button" class="vo-btn-outline" wire:click="confirmarSalvarExato"
                                        style="color:#b45309;border-color:#fcd34d;">
                                    Manter datas exatas (alterar duração)
                                </button>
                                <button type="button" class="vo-btn-outline" wire:click="cancelarConfirmacaoShift">
                                    Voltar
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="cr-modal-actions" style="display:flex;gap:8px;justify-content:space-between;align-items:center;">
                        <button class="vo-btn-outline" type="button"
                                wire:click="toggleVisibilidadeFase({{ $editingFaseId }})"
                                @if($editConfirmacaoShift) disabled @endif
                                style="color:{{ $editFaseVisivel ? '#b45309' : '#15803d' }};border-color:{{ $editFaseVisivel ? '#fcd34d' : '#86efac' }};@if($editConfirmacaoShift)opacity:.4;cursor:not-allowed;@endif">
                            {{ $editFaseVisivel ? 'Ocultar fase no projeto' : 'Exibir fase no projeto' }}
                        </button>
                        <div style="display:flex;gap:8px;">
                            <button class="vo-btn-outline" wire:click="fecharEdicao">Cancelar</button>
                            <button class="vo-btn-accent" wire:click="salvarFase"
                                    @if($editConfirmacaoShift) disabled style="opacity:.4;cursor:not-allowed;" @endif>
                                Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Modal de Comentarios --}}
    @if($mostrarComentarios && $projetoSelecionado)
        @php
            $modoGlobalComentarios = ! $comentarioFaseId;

            if ($modoGlobalComentarios) {
                $comentariosList = \App\Models\Comentario::with(['usuario', 'comentavel'])
                    ->whereHasMorph('comentavel', [\App\Models\CronogramaFase::class], fn($q) => $q->where('projeto_id', $projetoSelecionado))
                    ->latest()
                    ->take(100)
                    ->get();
                $tituloComentarios = 'Comentários do Projeto';
                $fasesParaSelect = \App\Models\CronogramaFase::where('projeto_id', $projetoSelecionado)->orderBy('ordem')->get();
            } else {
                $faseComentario = \App\Models\CronogramaFase::find($comentarioFaseId);
                $comentariosList = $faseComentario?->comentarios()->with('usuario')->latest()->get() ?? collect();
                $tituloComentarios = ($faseComentario?->fase->label() ?? 'Fase') . ' — Comentários';
                $fasesParaSelect = collect();
            }
        @endphp
        <div class="cr-modal-overlay" wire:click.self="fecharComentarios">
            <div class="cr-modal" style="width:700px;max-width:95vw;max-height:85vh;display:flex;flex-direction:column;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="margin:0;">{{ $tituloComentarios }}</h3>
                    <div style="display:flex;align-items:center;gap:8px;">
                        @if($comentarioFaseId)
                            <button type="button" class="vo-btn-outline" wire:click="abrirComentariosGlobal"
                                    style="font-size:0.72rem;padding:4px 10px;">
                                Ver todos
                            </button>
                        @endif
                        <button type="button" wire:click="fecharComentarios"
                                style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-muted);font-size:1.2rem;line-height:1;padding:4px;">
                            &times;
                        </button>
                    </div>
                </div>

                {{-- Novo comentario --}}
                <div style="margin-bottom:16px;padding:14px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.5rem;">
                    @if($modoGlobalComentarios)
                        <div style="margin-bottom:10px;">
                            <label style="display:block;font-size:0.65rem;font-weight:600;color:var(--vo-text-faint);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Comentar na fase</label>
                            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                @foreach($fasesParaSelect as $fp)
                                    @php $selecionada = (int) $novoComentarioFaseId === $fp->id; @endphp
                                    <button type="button"
                                            wire:click="$set('novoComentarioFaseId', {{ $fp->id }})"
                                            style="padding:4px 10px;font-size:0.68rem;border-radius:1rem;cursor:pointer;transition:all .15s;border:1px solid {{ $selecionada ? 'var(--vo-accent)' : 'var(--vo-border)' }};background:{{ $selecionada ? 'var(--vo-accent)' : 'var(--vo-bg)' }};color:{{ $selecionada ? '#111' : 'var(--vo-text-secondary)' }};font-weight:{{ $selecionada ? '600' : '400' }};">
                                        @if($fp->marco)
                                            <span style="display:inline-block;width:6px;height:6px;transform:rotate(45deg);background:{{ $fp->status->color() }};margin-right:4px;vertical-align:middle;"></span>
                                        @endif
                                        {{ $fp->fase->label() }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        {{-- select hidden para manter wire:model sync --}}
                        <select wire:model="novoComentarioFaseId" style="display:none;">
                            <option value="">Selecione a fase...</option>
                            @foreach($fasesParaSelect as $fp)
                                <option value="{{ $fp->id }}">{{ $fp->fase->label() }}</option>
                            @endforeach
                        </select>
                    @endif
                    <textarea rows="2" wire:model="novoComentario" placeholder="Escreva um comentário..."
                              style="width:100%;box-sizing:border-box;background:var(--vo-bg);color:var(--vo-text);border:1px solid var(--vo-border);border-radius:.375rem;padding:10px 12px;font-size:0.8rem;font-family:inherit;resize:vertical;"></textarea>
                    <div style="display:flex;justify-content:flex-end;margin-top:8px;">
                        <button type="button" class="vo-btn-accent" wire:click="salvarComentario"
                                style="padding:6px 16px;font-size:0.78rem;">
                            Comentar
                        </button>
                    </div>
                </div>

                {{-- Lista de comentarios --}}
                @if($comentariosList->isEmpty())
                    <div style="padding:24px;text-align:center;color:var(--vo-text-muted);font-size:0.82rem;">
                        Nenhum comentário ainda.
                    </div>
                @else
                    <div style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:6px;">
                        @foreach($comentariosList as $comentario)
                            <div style="padding:10px 14px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.5rem;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span style="font-weight:600;font-size:0.78rem;color:var(--vo-text);">
                                            {{ $comentario->usuario?->name ?? 'Sistema' }}
                                        </span>
                                        @if($modoGlobalComentarios)
                                            <span style="font-size:0.65rem;padding:1px 8px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:1rem;color:var(--vo-text-muted);">
                                                {{ $comentario->comentavel?->fase->label() ?? '—' }}
                                            </span>
                                        @endif
                                    </div>
                                    <span style="font-size:0.68rem;color:var(--vo-text-muted);">
                                        {{ $comentario->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <div style="font-size:0.8rem;color:var(--vo-text-secondary);line-height:1.5;white-space:pre-wrap;">{{ $comentario->conteudo }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Modal de Historico --}}
    @if($mostrarHistorico)
        @php
            // Determina o escopo: global (id=0), projeto explícito (id>0), ou projeto selecionado atual.
            $escopoProjetoId = $historicoProjetoId === 0
                ? null
                : ($historicoProjetoId ?? $projetoSelecionado);

            $queryHistorico = \App\Models\CronogramaFaseHistorico::with(['cronogramaFase.projeto', 'usuario']);

            if ($escopoProjetoId) {
                $queryHistorico->where(function ($q) use ($escopoProjetoId) {
                    $q->whereHas('cronogramaFase', fn($q2) => $q2->where('projeto_id', $escopoProjetoId))
                      ->orWhere('projeto_id', $escopoProjetoId);
                });
            }

            if ($historicoFaseId) {
                $queryHistorico->where('cronograma_fase_id', $historicoFaseId);
                $faseHistorico = \App\Models\CronogramaFase::find($historicoFaseId);
                $tituloHistorico = 'Histórico — ' . ($faseHistorico?->fase->label() ?? 'Fase');
            } elseif ($escopoProjetoId && $historicoProjetoId) {
                $projetoHistorico = \App\Models\Projeto::find($escopoProjetoId);
                $tituloHistorico = 'Histórico — ' . ($projetoHistorico?->nome ?? 'Projeto #'.$escopoProjetoId);
            } elseif (! $escopoProjetoId) {
                $tituloHistorico = 'Histórico de Alterações (Todos os Projetos)';
            } else {
                $tituloHistorico = 'Histórico de Alterações';
            }

            $registrosHistorico = $queryHistorico->latest()->take(200)->get();

            // Agrupa por lote: mesmo created_at (segundo) + mesmo motivo + mesmo usuario
            $lotes = $registrosHistorico->groupBy(function ($h) {
                return $h->created_at->format('Y-m-d H:i:s') . '|' . ($h->motivo ?? '') . '|' . ($h->usuario_id ?? 0);
            });
        @endphp
        <div class="cr-modal-overlay" wire:click.self="fecharHistorico">
            <div class="cr-modal" style="width:900px;max-width:95vw;max-height:85vh;display:flex;flex-direction:column;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="margin:0;">{{ $tituloHistorico }}</h3>
                    <div style="display:flex;align-items:center;gap:8px;">
                        @if($historicoFaseId)
                            <button type="button" class="vo-btn-outline" wire:click="abrirHistorico"
                                    style="font-size:0.72rem;padding:4px 10px;">
                                Ver todos
                            </button>
                        @endif
                        <button type="button" wire:click="fecharHistorico"
                                style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-muted);font-size:1.2rem;line-height:1;padding:4px;">
                            &times;
                        </button>
                    </div>
                </div>

                @if($registrosHistorico->isEmpty())
                    <div style="padding:32px;text-align:center;color:var(--vo-text-muted);font-size:0.82rem;">
                        Nenhuma alteração registrada.
                    </div>
                @else
                    <div style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:10px;min-height:0;">
                        @foreach($lotes as $loteEntries)
                            @php
                                $primeiro = $loteEntries->first();
                                $templateEntries = $loteEntries->where('campo_alterado', 'template');
                                $nonTemplateEntries = $loteEntries->where('campo_alterado', '!=', 'template');
                                $manuais = $nonTemplateEntries->where('automatico', false);
                                $cascatas = $nonTemplateEntries->where('automatico', true);
                                $temCascata = $cascatas->isNotEmpty();
                                $totalFasesAfetadas = $cascatas->pluck('cronograma_fase_id')->unique()->count();
                            @endphp

                            {{-- Entrada de mudança de template --}}
                            @foreach($templateEntries as $tplEntry)
                                <div style="border:1px solid var(--vo-accent);border-radius:.5rem;overflow:hidden;background:rgba(251,186,0,.04);flex-shrink:0;">
                                    <div style="padding:12px 16px;display:flex;align-items:center;gap:12px;">
                                        <div style="width:32px;height:32px;border-radius:50%;background:var(--vo-accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:0.8rem;font-weight:600;color:var(--vo-text);">
                                                {{ $tplEntry->motivo }}
                                            </div>
                                            <div style="font-size:0.7rem;color:var(--vo-text-muted);margin-top:2px;display:flex;align-items:center;gap:8px;">
                                                @if($tplEntry->valor_anterior)
                                                    <span style="padding:1px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;text-decoration:line-through;">{{ $tplEntry->valor_anterior }}</span>
                                                    <span>&rarr;</span>
                                                @endif
                                                <span style="padding:1px 6px;background:var(--vo-bg);border:1px solid var(--vo-accent);border-radius:.25rem;font-weight:600;">{{ $tplEntry->valor_novo }}</span>
                                            </div>
                                        </div>
                                        <div style="text-align:right;flex-shrink:0;">
                                            <div style="font-size:0.68rem;color:var(--vo-text-muted);">{{ $tplEntry->created_at->format('d/m/Y H:i') }}</div>
                                            <div style="font-size:0.68rem;color:var(--vo-text-muted);">{{ $tplEntry->usuario?->name ?? 'Sistema' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if($nonTemplateEntries->isEmpty()) @continue @endif

                            <div style="border:1px solid var(--vo-border);border-radius:.5rem;overflow:hidden;flex-shrink:0;">
                                {{-- Cabeçalho do lote --}}
                                <div style="padding:10px 14px;background:var(--vo-bg-subtle);border-bottom:1px solid var(--vo-border);display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                    <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;">
                                        @if($primeiro->automatico)
                                            <span style="padding:1px 8px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:1rem;font-size:0.62rem;font-weight:600;color:var(--vo-text-muted);white-space:nowrap;">Auto</span>
                                        @else
                                            <span style="font-size:0.75rem;font-weight:600;color:var(--vo-text);">{{ $primeiro->usuario?->name ?? 'Sistema' }}</span>
                                        @endif
                                        @if($primeiro->motivo)
                                            <span style="font-size:0.72rem;color:var(--vo-text-secondary);font-style:italic;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $primeiro->motivo }}</span>
                                        @endif
                                    </div>
                                    <span style="font-size:0.68rem;color:var(--vo-text-muted);white-space:nowrap;">{{ $primeiro->created_at->format('d/m/Y H:i') }}</span>
                                </div>

                                {{-- Alterações manuais --}}
                                @foreach($manuais as $h)
                                    @php
                                        $delta = ($h->valor_anterior && $h->valor_novo)
                                            ? (int) \Carbon\Carbon::parse($h->valor_anterior)->diffInDays(\Carbon\Carbon::parse($h->valor_novo), false)
                                            : null;
                                    @endphp
                                    <div style="padding:8px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--vo-border-light);font-size:0.73rem;">
                                        @if(! $historicoFaseId)
                                            <span style="font-weight:600;color:var(--vo-text);min-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                {{ $h->cronogramaFase?->fase->label() ?? '—' }}
                                            </span>
                                        @endif
                                        <span style="color:var(--vo-text-secondary);min-width:90px;">
                                            {{ str_replace(['data_prevista_inicio', 'data_prevista_fim'], ['Início prev.', 'Fim prev.'], $h->campo_alterado) }}
                                        </span>
                                        <span style="padding:2px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.7rem;">
                                            {{ $h->valor_anterior ? \Carbon\Carbon::parse($h->valor_anterior)->format('d/m/Y') : '—' }}
                                        </span>
                                        <span style="color:var(--vo-text-faint);">&rarr;</span>
                                        <span style="padding:2px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.7rem;font-weight:600;">
                                            {{ $h->valor_novo ? \Carbon\Carbon::parse($h->valor_novo)->format('d/m/Y') : '—' }}
                                        </span>
                                        @if($delta !== null && $delta !== 0)
                                            <span style="padding:2px 6px;border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.68rem;font-weight:600;background:{{ $delta > 0 ? 'rgba(239,68,68,.12)' : 'rgba(34,197,94,.12)' }};color:{{ $delta > 0 ? '#ef4444' : '#22c55e' }};">
                                                {{ $delta > 0 ? '+'.$delta.'d' : $delta.'d' }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Cascatas agrupadas --}}
                                @if($temCascata)
                                    <div x-data="{ expanded: false }">
                                        <button type="button" @click="expanded = !expanded"
                                                style="width:100%;padding:6px 14px;background:rgba(251,186,0,.06);border:none;border-bottom:1px solid var(--vo-border-light);cursor:pointer;display:flex;align-items:center;gap:6px;font-size:0.7rem;color:var(--vo-text-muted);text-align:left;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                                 :style="expanded ? 'transform:rotate(90deg)' : ''" style="transition:transform .15s;flex-shrink:0;"><path d="M9 18l6-6-6-6"/></svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M7 16V4m0 0L3 8m4-4l4 4"/><path d="M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                                            Recálculo em cascata — {{ $totalFasesAfetadas }} fase(s) afetada(s)
                                        </button>
                                        <div x-show="expanded" x-cloak>
                                            @foreach($cascatas as $h)
                                                @php
                                                    $deltaC = ($h->valor_anterior && $h->valor_novo)
                                                        ? (int) \Carbon\Carbon::parse($h->valor_anterior)->diffInDays(\Carbon\Carbon::parse($h->valor_novo), false)
                                                        : null;
                                                @endphp
                                                <div style="padding:6px 14px 6px 36px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--vo-border-light);font-size:0.7rem;color:var(--vo-text-secondary);">
                                                    @if(! $historicoFaseId)
                                                        <span style="font-weight:500;min-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                            {{ $h->cronogramaFase?->fase->label() ?? '—' }}
                                                        </span>
                                                    @endif
                                                    <span style="min-width:90px;">
                                                        {{ str_replace(['data_prevista_inicio', 'data_prevista_fim'], ['Início prev.', 'Fim prev.'], $h->campo_alterado) }}
                                                    </span>
                                                    <span style="padding:1px 5px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.68rem;">
                                                        {{ $h->valor_anterior ? \Carbon\Carbon::parse($h->valor_anterior)->format('d/m/Y') : '—' }}
                                                    </span>
                                                    <span style="color:var(--vo-text-faint);">&rarr;</span>
                                                    <span style="padding:1px 5px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.68rem;font-weight:600;">
                                                        {{ $h->valor_novo ? \Carbon\Carbon::parse($h->valor_novo)->format('d/m/Y') : '—' }}
                                                    </span>
                                                    @if($deltaC !== null && $deltaC !== 0)
                                                        <span style="padding:1px 5px;border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.66rem;font-weight:600;background:{{ $deltaC > 0 ? 'rgba(239,68,68,.12)' : 'rgba(34,197,94,.12)' }};color:{{ $deltaC > 0 ? '#ef4444' : '#22c55e' }};">
                                                            {{ $deltaC > 0 ? '+'.$deltaC.'d' : $deltaC.'d' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Cascatas sem alteração manual (ex: aplicação de template) --}}
                                @if($manuais->isEmpty())
                                    @foreach($cascatas as $h)
                                        @php
                                            $deltaCs = ($h->valor_anterior && $h->valor_novo)
                                                ? (int) \Carbon\Carbon::parse($h->valor_anterior)->diffInDays(\Carbon\Carbon::parse($h->valor_novo), false)
                                                : null;
                                        @endphp
                                        <div style="padding:8px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--vo-border-light);font-size:0.73rem;">
                                            @if(! $historicoFaseId)
                                                <span style="font-weight:600;color:var(--vo-text);min-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $h->cronogramaFase?->fase->label() ?? '—' }}
                                                </span>
                                            @endif
                                            <span style="color:var(--vo-text-secondary);min-width:90px;">
                                                {{ str_replace(['data_prevista_inicio', 'data_prevista_fim'], ['Início prev.', 'Fim prev.'], $h->campo_alterado) }}
                                            </span>
                                            <span style="padding:2px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.7rem;">
                                                {{ $h->valor_anterior ? \Carbon\Carbon::parse($h->valor_anterior)->format('d/m/Y') : '—' }}
                                            </span>
                                            <span style="color:var(--vo-text-faint);">&rarr;</span>
                                            <span style="padding:2px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.7rem;font-weight:600;">
                                                {{ $h->valor_novo ? \Carbon\Carbon::parse($h->valor_novo)->format('d/m/Y') : '—' }}
                                            </span>
                                            @if($deltaCs !== null && $deltaCs !== 0)
                                                <span style="padding:2px 6px;border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.68rem;font-weight:600;background:{{ $deltaCs > 0 ? 'rgba(239,68,68,.12)' : 'rgba(34,197,94,.12)' }};color:{{ $deltaCs > 0 ? '#ef4444' : '#22c55e' }};">
                                                    {{ $deltaCs > 0 ? '+'.$deltaCs.'d' : $deltaCs.'d' }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <script>
        function ganttChart() {
            return {
                ppd: 22,
                ppdDefault: 22,
                ppdSteps: [6, 10, 14, 18, 22, 28, 36, 48],
                highlightedRow: null,
                highlightedCol: null,
                isFullscreen: false,
                colunasOcultas: JSON.parse(localStorage.getItem('cr:macro:cols') || '[]'),
                painelColunas: false,
                mostrarDeps: JSON.parse(localStorage.getItem('cr:gantt:mostrarDeps') ?? 'true'),
                ganttColPanel: false,
                ganttCols: (function() {
                    var d = { status: true, pct: true, planejado: true, realizado: false };
                    try { return JSON.parse(localStorage.getItem('cr:gantt:cols')) || d; }
                    catch(e) { return d; }
                })(),

                get ganttLeftW() {
                    let w = 360; // cr-col-fase base
                    if (this.ganttCols.status)    w += 120;
                    if (this.ganttCols.pct)        w += 70;
                    if (this.ganttCols.planejado)  w += 190;
                    if (this.ganttCols.realizado)  w += 190;
                    if (this.mostrarDeps)          w += 180;
                    return w + 'px';
                },

                get zoomLevel() {
                    return this.ppd / this.ppdDefault;
                },

                init() {
                    this.$watch('colunasOcultas', (val) => {
                        localStorage.setItem('cr:macro:cols', JSON.stringify(val));
                    });
                    this.$watch('mostrarDeps', (val) => {
                        localStorage.setItem('cr:gantt:mostrarDeps', JSON.stringify(val));
                    });
                    this.$watch('ganttCols', (val) => {
                        localStorage.setItem('cr:gantt:cols', JSON.stringify(val));
                    });
                    this.$nextTick(() => {
                        this.scrollParaInicio();
                        setTimeout(() => this.scrollParaInicio(), 150);
                        setTimeout(() => this.scrollParaInicio(), 500);
                        document.addEventListener('fullscreenchange', () => {
                            this.isFullscreen = !!document.fullscreenElement;
                        });
                        // Foca o input de nova atividade quando Livewire despacha o evento
                        window.addEventListener('focarInputNovaAtividade', (e) => {
                            const faseId = e.detail?.faseId;
                            if (!faseId) return;
                            setTimeout(() => {
                                const input = document.querySelector(`input[wire\\:model="novoSubitemTitulos.${faseId}"]`);
                                if (input) {
                                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    input.focus();
                                }
                            }, 300);
                        });

                        // Dá ao cr-table-wrap um max-height = espaço restante no viewport abaixo dele.
                        // Transforma o wrap no scroll container → thead sticky relativo ao wrap.
                        // NOTA: Filament usa scroll container interno (não window), então window.scrollY
                        // é sempre 0. Usamos rect.top diretamente, mas só quando > 60 (tabela visível),
                        // garantindo que o valor capturado é o da posição real na primeira renderização.
                        const tableWrap = this.$el.querySelector('.cr-table-wrap');
                        if (tableWrap) {
                            const aplicarAlturaTabela = () => {
                                const rect = tableWrap.getBoundingClientRect();
                                // Só aplica quando a tabela está visivelmente abaixo do topo do viewport.
                                // Após max-height ser definido, a tabela vira scroll container e rect.top
                                // não muda mais, tornando o valor estável para interações futuras.
                                if (rect.top > 60) {
                                    const h = Math.max(window.innerHeight - rect.top - 8, 200);
                                    this.$el.style.setProperty('--cr-table-max-h', h + 'px');
                                }
                            };
                            aplicarAlturaTabela();
                            setTimeout(aplicarAlturaTabela, 150);
                            setTimeout(aplicarAlturaTabela, 600);
                            setTimeout(aplicarAlturaTabela, 1500);
                            window.addEventListener('resize', () => setTimeout(aplicarAlturaTabela, 150), { passive: true });
                        }
                    });
                },

                toggleColuna(key) {
                    if (this.colunasOcultas.includes(key)) {
                        this.colunasOcultas = this.colunasOcultas.filter(k => k !== key);
                    } else {
                        this.colunasOcultas = [...this.colunasOcultas, key];
                    }
                },
                mostrarColuna(key) {
                    return !this.colunasOcultas.includes(key);
                },
                resetarColunas() {
                    this.colunasOcultas = [];
                },

                scrollParaInicio() {
                    const container = this.$refs.ganttContainer;
                    if (!container) return;
                    const cells = container.querySelectorAll('.cr-cell-prev, .cr-cell-real, .cr-cell-atraso');
                    if (!cells.length) return;
                    const containerRect = container.getBoundingClientRect();
                    let min = Infinity;
                    cells.forEach(c => {
                        const delta = c.getBoundingClientRect().left - containerRect.left + container.scrollLeft;
                        if (delta < min) min = delta;
                    });
                    if (min !== Infinity) container.scrollLeft = Math.max(0, min);
                },

                highlightRow(id) {
                    this.highlightedRow = this.highlightedRow === id ? null : id;
                },

                toggleCol(idx) {
                    this.highlightedCol = this.highlightedCol === idx ? null : idx;
                },

                toggleFullscreen() {
                    const ref = this.$refs.ganttContainer;
                    if (!ref) return;
                    const el = ref.closest('.vo-theme-cronograma') || ref;
                    if (!document.fullscreenElement) {
                        if (el.requestFullscreen) {
                            el.requestFullscreen();
                        } else {
                            this.isFullscreen = true;
                            el.classList.add('cr-fullscreen-fallback');
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else {
                            this.isFullscreen = false;
                            el.classList.remove('cr-fullscreen-fallback');
                        }
                    }
                },

                zoomIn() {
                    const idx = this.ppdSteps.findIndex(s => s >= this.ppd);
                    if (idx < this.ppdSteps.length - 1) {
                        this.ppd = this.ppdSteps[idx + 1];
                        this.$nextTick(() => this.scrollParaInicio());
                    }
                },

                zoomOut() {
                    const idx = this.ppdSteps.findIndex(s => s >= this.ppd);
                    if (idx > 0) {
                        this.ppd = this.ppdSteps[idx - 1];
                        this.$nextTick(() => this.scrollParaInicio());
                    }
                },

                zoomReset() {
                    this.ppd = this.ppdDefault;
                    this.$nextTick(() => this.scrollParaInicio());
                },

                labelVisivel(diasMes, idx) {
                    const larguraPx = diasMes * this.ppd;
                    if (larguraPx >= 50) return true;
                    if (larguraPx >= 30) return idx % 2 === 0;
                    if (larguraPx >= 15) return idx % 3 === 0;
                    return idx % 6 === 0;
                },
            }
        }
    </script>

    {{-- ===================== MODAL: confirmação de mudança de status (data real) ===================== --}}
    @if($confirmacaoStatusFaseId && $confirmacaoStatusValue)
        <div class="cr-modal-overlay" wire:click.self="cancelarFinalizacaoStatus" style="z-index:9999;">
            <div class="cr-modal" style="max-width:480px;width:92vw;">
                <h3 style="margin-top:0;">{{ $confirmacaoApenasInicio ? 'Confirmar mudança de status' : 'Confirmar finalização' }}</h3>
                <p style="font-size:0.78rem;color:var(--vo-text-muted);margin-bottom:14px;">
                    Você está marcando <strong style="color:var(--vo-text);">{{ $confirmacaoFaseLabel }}</strong>
                    como <strong style="color:var(--vo-text);">{{ $confirmacaoStatusLabel }}</strong>.
                    @if($confirmacaoApenasInicio)
                        Informe a data em que esse status passou a valer.
                    @else
                        Informe a data em que a fase foi efetivamente concluída.
                    @endif
                </p>

                <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:18px;">
                    @if($confirmacaoApenasInicio)
                        <label style="display:flex;flex-direction:column;gap:4px;font-size:0.72rem;color:var(--vo-text-secondary);">
                            <span>Data de início *</span>
                            <input type="date" wire:model="confirmacaoDataRealInicio" required
                                   style="padding:7px 10px;border:1px solid var(--vo-border);border-radius:.375rem;font-size:0.8rem;background:var(--vo-bg);color:var(--vo-text);">
                        </label>
                    @elseif(! $confirmacaoFaseMarco)
                        <label style="display:flex;flex-direction:column;gap:4px;font-size:0.72rem;color:var(--vo-text-secondary);">
                            <span>Data de início real</span>
                            <input type="date" wire:model="confirmacaoDataRealInicio"
                                   style="padding:7px 10px;border:1px solid var(--vo-border);border-radius:.375rem;font-size:0.8rem;background:var(--vo-bg);color:var(--vo-text);">
                        </label>
                        <label style="display:flex;flex-direction:column;gap:4px;font-size:0.72rem;color:var(--vo-text-secondary);">
                            <span>Data de conclusão *</span>
                            <input type="date" wire:model="confirmacaoDataRealFim" required
                                   style="padding:7px 10px;border:1px solid var(--vo-border);border-radius:.375rem;font-size:0.8rem;background:var(--vo-bg);color:var(--vo-text);">
                        </label>
                    @else
                        <label style="display:flex;flex-direction:column;gap:4px;font-size:0.72rem;color:var(--vo-text-secondary);">
                            <span>Data de execução *</span>
                            <input type="date" wire:model="confirmacaoDataRealFim" required
                                   style="padding:7px 10px;border:1px solid var(--vo-border);border-radius:.375rem;font-size:0.8rem;background:var(--vo-bg);color:var(--vo-text);">
                        </label>
                    @endif
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="vo-btn-outline" wire:click="cancelarFinalizacaoStatus" style="padding:7px 14px;">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarFinalizacaoStatus"
                            style="padding:7px 14px;background:var(--vo-accent);color:#111;border:none;border-radius:.375rem;font-weight:600;cursor:pointer;">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
