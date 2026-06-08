<x-filament-panels::page>
    @php
        $obra = $this->record;
        $projeto = $obra->projeto;
        $tiposUnidade = collect($obra->tipos_unidade ?? [])
            ->map(fn ($tipo) => trim((string) $tipo))
            ->filter(fn (string $tipo) => $tipo !== '')
            ->values();
        $isRetrofit = $tiposUnidade->contains('RETROFIT');
        // Carrega status de contratação cadastrados (gerenciados na tela de
        // Controle de Pedidos Retrofit) para renderizar com cor/label dinâmicos.
        $retrofitStatusEntries = \App\Models\Status::ativosPorContexto('retrofit')
            ->mapWithKeys(function (\App\Models\Status $status): array {
                $nome = (string) $status->nome;
                $key = (string) $status->slug;
                $label = \Illuminate\Support\Str::of($nome)->lower()->ucfirst()->toString();
                $cor = $status->cor ?: '#6b7280';

                return [$key => ['label' => $label, 'cor' => $cor]];
            })
            ->all();

        $retrofitContrastingText = function (string $hex): string {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
                return '#fff';
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            // Luminância relativa simples (0..255). Acima de ~150 → texto escuro.
            $lum = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

            return $lum > 150 ? '#1f2937' : '#ffffff';
        };

        $retrofitStatusFor = function (?string $statusKey) use ($retrofitStatusEntries, $retrofitContrastingText): array {
            $key = \Illuminate\Support\Str::of((string) $statusKey)
                ->lower()
                ->replace(' ', '_')
                ->ascii()
                ->toString();

            if ($key === '') {
                return ['label' => '—', 'style' => 'background:var(--vo-bg-subtle);color:var(--vo-text);'];
            }

            $entry = $retrofitStatusEntries[$key] ?? null;
            $label = $entry['label'] ?? \Illuminate\Support\Str::of((string) $statusKey)
                ->replace('_', ' ')
                ->lower()
                ->ucfirst()
                ->toString();
            $cor = $entry['cor'] ?? '#6b7280';
            $textColor = $retrofitContrastingText($cor);

            return [
                'label' => $label,
                'style' => "background:{$cor};color:{$textColor};",
            ];
        };
        $controlesNotaFiscalAmpliacao = $obra->controlesNotaFiscal()
            ->where(function ($query): void {
                $query->whereNull('tipo_unidade')
                    ->orWhere('tipo_unidade', \App\Enums\TipoUnidade::EXPANSAO->value);
            })
            ->with(['itens.asEscopo', 'auxiliares'])
            ->get();
        $itensContratuais = $controlesNotaFiscalAmpliacao
            ->flatMap(fn ($c) => $c->itens)
            ->values();

        $itensExtraContratuais = $controlesNotaFiscalAmpliacao
            ->flatMap(fn ($c) => $c->auxiliares)
            ->values();
    @endphp

    <style>
        /* ── Cover ── */
        .vo-cover {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #1a3a28, #2d5a3a, #3d7a56);
            border-radius: 0.75rem 0.75rem 0 0;
            position: relative;
            overflow: hidden;
        }
        .vo-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .vo-cover-label {
            position: absolute;
            top: 10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: rgba(255,255,255,.85);
            letter-spacing: .08em;
            text-transform: uppercase;
            text-shadow: 0 1px 4px rgba(0,0,0,.5);
        }

        /* ── Profile Bar ── */
        .vo-profile-bar {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-top: none;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            min-height: 80px;
            position: relative;
            z-index: 2;
        }
        .vo-profile-photo {
            width: 200px;
            height: 100px;
            border-radius: 12px;
            border: 4px solid var(--vo-bg);
            background: var(--vo-accent);
            margin-top: -52px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: #111;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
            cursor: pointer;
            overflow: hidden;
            position: relative;
            z-index: 3;
        }
        .vo-profile-photo img { width: 100%; height: 100%; object-fit: cover; }
        .vo-profile-info { flex: 1; padding: 12px 0; }
        .vo-profile-name { font-size: 1.15rem; font-weight: 700; color: var(--vo-text); }
        .vo-profile-sub { font-size: 0.75rem; color: var(--vo-text-muted); margin-top: 2px; }
        .vo-tags { display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
        .vo-tag { font-size: 0.7rem; padding: 3px 12px; border-radius: 1rem; font-weight: 600; }
        .vo-tag-amber { background: var(--vo-accent); color: #111; }
        .vo-tag-gray { background: var(--vo-border); color: var(--vo-text-secondary); }
        .vo-tag-status-success { background: #166534; color: #fff; }
        .vo-tag-status-danger { background: #dc2626; color: #fff; }
        .vo-tag-status-warning { background: #d97706; color: #fff; }
        .vo-tag-status-info { background: #2563eb; color: #fff; }
        .vo-tag-status-gray { background: #6b7280; color: #fff; }
        .vo-profile-actions { display: flex; gap: 8px; flex-shrink: 0; }
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
        }
        .vo-btn-outline:hover { border-color: var(--vo-text-muted); }

        /* ── Info + Datas (2 colunas) ── */
        .vo-header-info {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-top: none;
            padding: 14px 24px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 40px;
            font-size: 0.78rem;
            color: var(--vo-text-secondary);
        }
        @media (max-width: 768px) {
            .vo-header-info { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .vo-cover { height: 140px; border-radius: 0.5rem 0.5rem 0 0; }
            .vo-profile-bar {
                padding: 0 12px 12px;
                min-height: auto;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .vo-profile-photo {
                width: 150px;
                height: 76px;
                margin-top: -30px;
                border-width: 3px;
                font-size: 1.6rem;
            }
            .vo-profile-info {
                width: 100%;
                padding: 0;
            }
            .vo-profile-name { font-size: 0.95rem; line-height: 1.25; }
            .vo-profile-sub { font-size: 0.72rem; }
            .vo-info-row { gap: 8px; flex-wrap: wrap; }
            .vo-info-label { min-width: 112px; }
            .vo-header-info { padding: 10px 12px; gap: 6px 14px; }
            .vo-dias-box { padding: 5px 10px; }
            .vo-dias-box-value { font-size: 0.95rem; }
        }
        .vo-header-left { display: flex; flex-direction: column; gap: 4px; }
        .vo-header-right { display: flex; flex-direction: column; gap: 4px; }
        .vo-info-row { display: flex; gap: 16px; align-items: baseline; }
        .vo-info-label { color: var(--vo-text-faint); font-weight: 600; text-transform: uppercase; font-size: 0.65rem; min-width: 140px; }
        .vo-info-value { font-weight: 600; color: var(--vo-accent); }
        .vo-dias-box {
            display: inline-block;
            border: 2px solid var(--vo-accent);
            border-radius: 0.5rem;
            padding: 6px 14px;
            text-align: center;
            margin-top: 4px;
        }
        .vo-dias-box-label { font-size: 0.6rem; color: var(--vo-text-faint); text-transform: uppercase; font-weight: 600; }
        .vo-dias-box-value { font-size: 1.1rem; font-weight: 800; color: var(--vo-accent); }

        /* ── Tabs ── */
        .vo-tabs {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-top: none;
            border-radius: 0 0 0.75rem 0.75rem;
            padding: 0 24px;
            display: flex;
            gap: 0;
            overflow-x: auto;
        }
        .vo-tab {
            padding: 10px 16px;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--vo-text-muted);
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            cursor: default;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .vo-tab-active { color: var(--vo-text); border-bottom-color: var(--vo-accent); font-weight: 700; }

        /* ── Section Titles ── */
        .vo-section-title {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--vo-text-faint);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 8px;
            padding: 0 2px;
        }

        /* ── Columns ── */
        .vo-columns {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            padding: 16px 0;
        }
        @media (min-width: 1024px) {
            .vo-columns { grid-template-columns: 260px 1fr 260px; }
        }

        /* ── Card ── */
        .vo-card {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: var(--vo-shadow);
            color: var(--vo-text-secondary);
        }
        .vo-card-head {
            padding: 10px 16px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--vo-text);
            border-bottom: 1px solid var(--vo-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .vo-card-head-accent {
            padding: 10px 16px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #111;
            background: var(--vo-accent);
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .vo-card-head > span,
        .vo-card-head-accent > span {
            min-width: 0;
            flex: 1;
        }
        .dark .vo-card-head-accent {
            background: rgba(251,186,0,.85);
        }
        .vo-card-body { padding: 10px 16px; }
        .vo-columns > div { min-width: 0; }
        .vo-check-item > span:last-child,
        .vo-contract-item > span:last-child {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .vo-stat-row > span:first-child,
        .vo-consumo-row > span:first-child {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .vo-btn-link {
            background: none; border: none;
            font-size: 0.7rem; color: var(--vo-text-muted);
            cursor: pointer; font-weight: 500; padding: 0;
        }
        .vo-btn-link:hover { color: var(--vo-accent); }

        /* ── Checklist ── */
        .vo-check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            border-bottom: 1px solid var(--vo-border-light);
            font-size: 0.75rem;
        }
        .vo-check-item:last-child { border-bottom: none; }
        .vo-check-icon {
            width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .vo-check-icon-ok { background: #22c55e; }
        .vo-check-icon-warn { background: #d4a94b; }
        .dark .vo-check-icon-warn { background: #a8893e; }
        .vo-check-icon-empty {
            background: transparent;
            border: 2px solid var(--vo-border);
        }

        /* ── Contratações ── */
        .vo-contract-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
            border-bottom: 1px solid var(--vo-border-light);
            font-size: 0.73rem;
        }
        .vo-contract-item:last-child { border-bottom: none; }
        .vo-contract-item > span:nth-child(2) { flex: 1; min-width: 0; overflow-wrap: anywhere; }
        .vo-contract-eye-btn {
            border: 0; background: transparent; padding: 4px; border-radius: 4px;
            cursor: pointer; color: var(--vo-text-faint); display: inline-flex;
            align-items: center; justify-content: center; flex-shrink: 0;
            transition: all .15s;
        }
        .vo-contract-eye-btn:hover { background: rgba(0,0,0,.06); color: var(--vo-accent); }

        /* ── Detalhe do item (modal read-only) ── */
        .vo-detalhe-section { margin-bottom: 18px; }
        .vo-detalhe-section:last-child { margin-bottom: 0; }
        .vo-detalhe-section-title {
            font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
            color: var(--vo-text-faint); margin: 0 0 8px; padding-bottom: 4px;
            border-bottom: 1px solid var(--vo-border-light);
        }
        .vo-detalhe-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 14px; }
        @media (max-width: 640px) { .vo-detalhe-grid { grid-template-columns: 1fr; } }
        .vo-detalhe-field { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .vo-detalhe-field.full { grid-column: 1 / -1; }
        .vo-detalhe-label {
            font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
            color: var(--vo-text-faint);
        }
        .vo-detalhe-value {
            font-size: .8rem; color: var(--vo-text); font-weight: 500; word-break: break-word;
        }
        .vo-detalhe-value.muted { color: var(--vo-text-faint); font-style: italic; font-weight: 400; }
        .vo-detalhe-value.money { font-weight: 700; font-variant-numeric: tabular-nums; }
        .vo-detalhe-notas-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        @media (max-width: 640px) { .vo-detalhe-notas-grid { grid-template-columns: repeat(2, 1fr); } }
        .vo-detalhe-nota-stat {
            background: var(--vo-bg-subtle); border: 1px solid var(--vo-border-light);
            border-radius: 8px; padding: 10px 12px; text-align: center;
        }
        .vo-detalhe-nota-stat-valor { font-size: 1rem; font-weight: 700; color: var(--vo-text); }
        .vo-detalhe-nota-stat-label { font-size: .62rem; color: var(--vo-text-faint); text-transform: uppercase; letter-spacing: .04em; margin-top: 2px; }

        /* ── Gallery ── */
        .vo-gallery-carousel {
            position: relative;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid var(--vo-border);
            background: var(--vo-bg-subtle);
        }
        .vo-gallery-viewport {
            position: relative;
            overflow: hidden;
            aspect-ratio: 16/9;
        }
        .vo-gallery-track {
            display: flex;
            width: 100%;
            height: 100%;
            transition: transform .7s ease;
        }
        .vo-gallery-slide {
            width: 100%;
            flex: 0 0 100%;
            height: 100%;
            cursor: zoom-in;
            background: #0b0f18;
        }
        .vo-gallery-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .vo-gallery-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: none;
            background: rgba(17, 24, 39, .6);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            z-index: 2;
        }
        .vo-gallery-arrow:hover {
            background: rgba(17, 24, 39, .82);
        }
        .vo-gallery-arrow-prev { left: 8px; }
        .vo-gallery-arrow-next { right: 8px; }
        .vo-gallery-dots {
            position: absolute;
            bottom: 58px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 2;
        }
        .vo-gallery-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            border: none;
            background: rgba(255,255,255,.45);
            cursor: pointer;
            padding: 0;
        }
        .vo-gallery-dot.is-active {
            background: var(--vo-accent);
            box-shadow: 0 0 0 2px rgba(0,0,0,.2);
        }
        .vo-gallery-actions {
            display: flex;
            gap: 8px;
            padding: 10px;
            border-top: 1px solid var(--vo-border-light);
            background: var(--vo-bg);
            flex-wrap: wrap;
        }
        .vo-gallery-action-btn {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            font-size: 0.72rem;
            padding: 6px 12px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            color: var(--vo-text-secondary);
            min-height: 34px;
            flex: 1 1 0;
        }
        .vo-gallery-action-btn:hover {
            border-color: var(--vo-text-muted);
            color: var(--vo-text);
        }
        .vo-gallery-action-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }
        .vo-gallery-loading {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.7rem;
            color: var(--vo-text-muted);
            margin-left: 4px;
        }
        .vo-gallery-loading-spinner {
            width: 12px;
            height: 12px;
            border: 2px solid var(--vo-border);
            border-top-color: var(--vo-accent);
            border-radius: 50%;
            animation: vo-spin .75s linear infinite;
        }
        @keyframes vo-spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 640px) {
            .vo-gallery-viewport {
                aspect-ratio: 4 / 3;
            }
            .vo-gallery-slide img {
                height: 100% !important;
                object-fit: contain;
                background: #0b0f18;
            }
            .vo-gallery-arrow {
                width: 30px;
                height: 30px;
            }
            .vo-gallery-dots {
                bottom: 52px;
                gap: 5px;
            }
            .vo-gallery-dot {
                width: 7px;
                height: 7px;
            }
            .vo-gallery-actions {
                padding: 8px;
                gap: 6px;
            }
            .vo-gallery-action-btn {
                font-size: 0.68rem;
                padding: 6px 8px;
            }
            .vo-gallery-loading {
                width: 100%;
                margin-left: 0;
                justify-content: center;
            }
        }

        /* ── Gallery Grid ── */
        .vo-gallery-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 4px;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .vo-gallery-grid-item {
            aspect-ratio: 1;
            cursor: zoom-in;
            position: relative;
            overflow: hidden;
            background: var(--vo-bg-subtle);
        }
        .vo-gallery-grid-item img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .2s;
        }
        .vo-gallery-grid-item:hover img { transform: scale(1.05); }
        .vo-gallery-grid-more {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.55);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.1rem; font-weight: 800;
            cursor: pointer;
        }
        @media (max-width: 640px) {
            .vo-gallery-grid { grid-template-columns: repeat(3, 1fr); }
        }

        /* ── Galeria Tab ── */
        .vo-galeria-filter-bar {
            display: flex; gap: 6px; flex-wrap: wrap; padding: 12px 0;
        }
        .vo-galeria-filter-btn {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            font-size: 0.72rem; padding: 5px 14px;
            border-radius: 999px; cursor: pointer;
            font-weight: 600; color: var(--vo-text-muted);
            transition: all .15s;
        }
        .vo-galeria-filter-btn:hover { border-color: var(--vo-text-muted); color: var(--vo-text); }
        .vo-galeria-filter-btn.is-active {
            background: var(--vo-accent); color: #111;
            border-color: var(--vo-accent);
        }
        .vo-galeria-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
        }
        .vo-galeria-grid-item {
            aspect-ratio: 1; cursor: zoom-in;
            position: relative; overflow: hidden;
            border-radius: 0.5rem;
            background: var(--vo-bg-subtle);
        }
        .vo-galeria-grid-item img {
            width: 100%; height: 100%;
            object-fit: cover; transition: transform .2s;
        }
        .vo-galeria-grid-item:hover img { transform: scale(1.05); }
        .vo-galeria-count {
            font-size: 0.72rem; color: var(--vo-text-muted);
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .vo-galeria-grid { grid-template-columns: repeat(3, 1fr); }
        }
        .vo-galeria-cat-delete {
            position: absolute; top: -6px; right: -6px;
            width: 16px; height: 16px; border-radius: 50%;
            background: #e74c3c; color: #fff; border: none;
            font-size: 0.65rem; line-height: 1; cursor: pointer;
            display: none; align-items: center; justify-content: center;
            z-index: 2;
        }
        span:hover > .vo-galeria-cat-delete { display: flex; }
        .vo-cat-delete-popover {
            position: absolute; top: 100%; left: 0; z-index: 50;
            background: var(--vo-bg); border: 1px solid var(--vo-border);
            border-radius: 0.5rem; padding: 12px; min-width: 200px;
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
            margin-top: 4px;
        }
        .vo-cat-delete-select {
            width: 100%; padding: 5px 8px; font-size: 0.75rem;
            border: 1px solid var(--vo-border); border-radius: 0.375rem;
            background: var(--vo-bg); color: var(--vo-text);
            text-transform: capitalize;
        }
        .vo-cat-input {
            font-size: 0.72rem; padding: 5px 10px;
            border: 1px solid var(--vo-border); border-radius: 999px;
            background: var(--vo-bg); color: var(--vo-text);
            width: 140px; outline: none;
        }
        .vo-cat-input:focus { border-color: var(--vo-accent); }
        .vo-galeria-add-cat { font-size: 0.85rem; font-weight: 700; padding: 5px 12px; }

        /* ── RDO Tab ── */
        .vo-rdo-list { display: flex; flex-direction: column; gap: 10px; }
        .vo-rdo-card {
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 0.75rem;
            padding: 14px 16px;
            box-shadow: var(--vo-shadow);
        }
        .vo-rdo-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 10px;
        }
        .vo-rdo-date { font-size: 0.8rem; font-weight: 700; color: var(--vo-text); }
        .vo-rdo-pct { font-size: 0.85rem; font-weight: 800; color: var(--vo-accent); }
        .vo-rdo-progress {
            width: 100%; height: 6px;
            background: var(--vo-border);
            border-radius: 3px; overflow: hidden;
            margin-bottom: 10px;
        }
        .vo-rdo-progress-fill {
            height: 100%; background: var(--vo-accent);
            border-radius: 3px; transition: width .3s;
        }
        .vo-rdo-activity {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.72rem; color: var(--vo-text-secondary);
            padding: 3px 0;
            border-bottom: 1px solid var(--vo-border-light);
        }
        .vo-rdo-activity:last-child { border-bottom: none; }
        .vo-rdo-activity-pct {
            font-weight: 700; color: var(--vo-accent);
            min-width: 40px; text-align: right;
        }

        /* ── Lightbox Actions ── */
        .vo-lightbox-actions {
            position: absolute; bottom: 40px; left: 50%;
            transform: translateX(-50%);
            display: flex; gap: 8px; align-items: center;
            z-index: 1001;
        }
        .vo-lightbox-actions button {
            background: rgba(0,0,0,.6); border: 1px solid rgba(255,255,255,.25);
            color: #fff; font-size: 0.72rem; font-weight: 600;
            padding: 7px 16px; border-radius: 999px; cursor: pointer;
            backdrop-filter: blur(8px); transition: all .2s;
            white-space: nowrap;
        }
        .vo-lightbox-actions button:hover {
            background: var(--vo-accent); color: #111; border-color: var(--vo-accent);
        }
        .vo-lightbox-actions button:disabled { opacity: .5; cursor: not-allowed; }

        /* ── Lightbox Nav ── */
        .vo-lightbox-nav {
            position: absolute; top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,.5); border: none;
            color: #fff; font-size: 2rem; cursor: pointer;
            width: 48px; height: 48px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
            z-index: 1000;
        }
        .vo-lightbox-nav:hover { background: rgba(0,0,0,.75); }
        .vo-lightbox-nav-prev { left: 16px; }
        .vo-lightbox-nav-next { right: 16px; }
        .vo-lightbox-counter {
            position: absolute; bottom: 16px; left: 50%;
            transform: translateX(-50%);
            color: rgba(255,255,255,.7); font-size: 0.75rem;
            font-weight: 600; z-index: 1000;
        }

        /* ── Feed ── */
        .vo-feed-input {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 14px 16px; border-bottom: 1px solid var(--vo-border-light);
        }
        .vo-feed-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
            margin-top: 2px;
        }
        .vo-feed-input-field {
            flex: 1; width: 100%; box-sizing: border-box;
            border: 1px solid var(--vo-border); border-radius: 1.5rem;
            padding: 10px 20px; font-size: 0.85rem; background: var(--vo-bg-subtle);
            color: var(--vo-text); font-family: inherit; resize: vertical;
            min-height: 44px; line-height: 1.5; transition: all .2s;
        }
        .vo-feed-input-field::placeholder { color: var(--vo-text-faint); }
        .vo-feed-input-field:focus {
            outline: none; border-color: var(--vo-accent);
            box-shadow: 0 0 0 3px rgba(251,186,0,.2);
            background: var(--vo-bg);
        }
        .vo-feed-item { padding: 12px 16px; border-bottom: 1px solid var(--vo-border-light); }
        .vo-feed-item:last-child { border-bottom: none; }
        .vo-feed-row { display: flex; gap: 10px; align-items: flex-start; }
        .vo-feed-content { flex: 1; min-width: 0; }
        .vo-feed-author { font-size: 0.8rem; font-weight: 700; color: var(--vo-text); }
        .vo-feed-date { font-size: 0.7rem; color: var(--vo-text-faint); margin-left: 6px; }
        .vo-feed-text { font-size: 0.8rem; color: var(--vo-text-secondary); margin-top: 3px; line-height: 1.5; }
        .vo-feed-tag {
            display: inline-block; font-size: 0.65rem; padding: 2px 10px;
            border-radius: 1rem; margin-top: 6px; font-weight: 600;
        }
        .vo-feed-actions { display: flex; gap: 12px; margin-top: 6px; font-size: 0.7rem; }
        .vo-feed-action-btn {
            background: none; border: none; cursor: pointer;
            color: var(--vo-text-muted); font-size: 0.7rem; padding: 0; font-family: inherit;
        }
        .vo-feed-action-btn:hover { color: var(--vo-accent); }
        .vo-feed-respostas { margin-left: 44px; margin-top: 8px; border-left: 2px solid var(--vo-border); padding-left: 12px; }
        .vo-feed-resposta-input { margin-left: 44px; margin-top: 8px; display: flex; gap: 6px; align-items: flex-start; }
        .vo-feed-resposta-input textarea {
            flex: 1; border: 1px solid var(--vo-border); border-radius: 0.375rem;
            padding: 6px 10px; font-size: 0.75rem; resize: none; min-height: 36px;
            font-family: inherit; background: var(--vo-bg-subtle); color: var(--vo-text);
        }
        .vo-feed-resposta-input textarea:focus { outline: none; border-color: var(--vo-accent); box-shadow: 0 0 0 2px rgba(251,186,0,.15); }
        .vo-btn-sm {
            font-size: 0.7rem; padding: 5px 12px; border-radius: 0.375rem; border: none;
            background: var(--vo-accent); color: #111; cursor: pointer; font-weight: 600; white-space: nowrap;
        }
        .vo-mencao { color: var(--vo-info-text); font-weight: 600; }
        .vo-fixado { border-left: 3px solid var(--vo-accent); background: rgba(251,186,0,.04); }
        .vo-badge-auto {
            font-size: 0.575rem; background: var(--vo-border-light); color: var(--vo-text-muted);
            padding: 1px 6px; border-radius: 1rem; margin-left: 4px;
        }
        .vo-mencao-dropdown {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--vo-bg); border: 1px solid var(--vo-border);
            border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,.15); z-index: 50;
            max-height: 160px; overflow-y: auto; margin-top: 2px;
        }
        .vo-mencao-item { padding: 6px 12px; font-size: 0.8rem; cursor: pointer; color: var(--vo-text); }
        .vo-mencao-item:hover { background: rgba(251,186,0,.1); }

        /* ── Stats ── */
        .vo-stat-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 0; border-bottom: 1px solid var(--vo-border-light); font-size: 0.75rem;
        }
        .vo-stat-row:last-child { border-bottom: none; }
        .vo-stat-badge {
            font-size: 0.65rem; padding: 2px 10px; border-radius: 1rem;
            font-weight: 700; min-width: 24px; text-align: center;
        }
        .vo-consumo-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 0; border-bottom: 1px solid var(--vo-border-light); font-size: 0.75rem;
        }
        .vo-consumo-row:last-child { border-bottom: none; }
        .vo-consumo-note-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 2px 0 10px; font-size: 0.68rem; color: var(--vo-text-faint);
        }
        .vo-consumo-note-row span { line-height: 1.3; }
        .vo-consumo-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .vo-consumo-badge { font-size: 0.65rem; padding: 2px 10px; border-radius: 1rem; font-weight: 600; }

        /* ── Edit Button ── */
        .vo-edit-btn {
            background: rgba(0,0,0,.12); border: none; border-radius: 50%;
            width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #111; flex-shrink: 0;
            transition: background .15s;
        }
        .vo-edit-btn:hover { background: rgba(0,0,0,.22); }
        .dark .vo-edit-btn { color: #e5e7eb; background: rgba(255,255,255,.1); }
        .dark .vo-edit-btn:hover { background: rgba(255,255,255,.2); }

        @media (max-width: 768px) {
            .vo-columns {
                gap: 10px;
                padding: 12px 0;
            }
            .vo-card-head,
            .vo-card-head-accent {
                padding: 9px 10px;
                font-size: 0.75rem;
                gap: 6px;
            }
            .vo-card-body {
                padding: 8px 10px;
            }
            .vo-card-body[style*="max-height: 500px"] {
                max-height: 280px !important;
            }
            .vo-card-head .vo-btn-link {
                max-width: 40%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .vo-check-item,
            .vo-contract-item,
            .vo-stat-row,
            .vo-consumo-row {
                gap: 6px;
                flex-wrap: wrap;
                align-items: flex-start;
            }
            .vo-stat-badge,
            .vo-consumo-badge {
                max-width: 68%;
                white-space: normal;
                overflow-wrap: anywhere;
                text-align: right;
                line-height: 1.2;
                flex-shrink: 0;
            }
            .vo-feed-input {
                padding: 10px 10px;
                gap: 8px;
            }
            .vo-feed-avatar {
                width: 32px;
                height: 32px;
                font-size: 0.65rem;
            }
            .vo-feed-input-main {
                min-width: 0;
            }
            .vo-feed-input-field {
                padding: 9px 12px;
                font-size: 0.78rem;
            }
            .vo-feed-submit {
                width: 100%;
                justify-content: center;
            }
            .vo-feed-respostas {
                margin-left: 0;
                padding-left: 8px;
            }
            .vo-feed-resposta-input {
                margin-left: 0;
                flex-wrap: wrap;
            }
        }

        /* ── Modais ── */
        .vo-modal-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,.55);
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
        }
        .vo-modal {
            background: var(--vo-bg); border: 1px solid var(--vo-border);
            border-radius: 0.75rem; box-shadow: 0 20px 60px rgba(0,0,0,.3);
            display: flex; flex-direction: column; max-height: 90vh; width: 100%;
        }
        .vo-modal-sm { max-width: 460px; }
        .vo-modal-lg { max-width: 820px; }
        .vo-modal-head {
            padding: 14px 20px; border-bottom: 1px solid var(--vo-border);
            display: flex; align-items: center; justify-content: space-between;
            font-weight: 700; font-size: 0.875rem; flex-shrink: 0;
        }
        .vo-modal-body { padding: 16px 20px; overflow-y: auto; flex: 1; }
        .vo-modal-foot {
            padding: 10px 20px; border-top: 1px solid var(--vo-border);
            display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0;
        }
        .vo-btn-primary {
            background: var(--vo-accent); color: #111; border: none;
            padding: 8px 20px; border-radius: 6px; font-weight: 600;
            font-size: 0.8rem; cursor: pointer;
        }
        .vo-btn-cancel {
            background: var(--vo-bg-subtle); color: var(--vo-text-muted);
            border: 1px solid var(--vo-border); padding: 8px 20px;
            border-radius: 6px; font-size: 0.8rem; cursor: pointer;
        }
        .vo-form-group { margin-bottom: 14px; }
        .vo-form-label {
            display: block; font-size: 0.65rem; font-weight: 700;
            color: var(--vo-text-muted); margin-bottom: 5px;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .vo-form-select {
            width: 100%; padding: 8px 10px; border: 1px solid var(--vo-border);
            border-radius: 6px; background: var(--vo-bg); color: var(--vo-text);
            font-size: 0.8rem;
        }
        .vo-form-textarea {
            width: 100%; min-height: 116px; padding: 10px 12px;
            border: 1px solid var(--vo-border); border-radius: 10px;
            background: var(--vo-bg); color: var(--vo-text);
            font-size: 0.9rem; line-height: 1.4; resize: vertical;
        }
        .vo-consumo-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
        .vo-consumo-card {
            background: var(--vo-bg-subtle); border: 1px solid var(--vo-border);
            border-radius: 16px; padding: 18px;
        }
        .vo-file-list {
            margin-top: 10px;
            display: grid;
            gap: 8px;
            word-break: break-word;
        }
        .vo-file-item {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 10px; padding: 10px 12px;
            background: rgba(146, 162, 192, 0.08);
            border: 1px solid var(--vo-border-light);
            border-radius: 10px;
            font-size: 0.8rem;
            color: var(--vo-text);
            flex-wrap: wrap;
            min-width: 0;
        }
        .vo-file-item span {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            display: inline-block;
            max-width: calc(100% - 100px);
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .vo-file-item button {
            background: none; border: none; color: var(--vo-danger-text);
            font-size: 0.78rem; font-weight: 700; cursor: pointer;
            flex-shrink: 0;
        }
        .vo-file-item-action {
            background: none; border: none; cursor: pointer;
            padding: 4px 6px; border-radius: 4px;
            color: var(--vo-text-secondary);
            display: inline-flex; align-items: center; justify-content: center;
            transition: background-color 0.15s ease, color 0.15s ease;
            flex-shrink: 0;
        }
        .vo-file-item-action:hover {
            background-color: rgba(63, 81, 181, 0.08);
            color: var(--vo-text);
        }
        .vo-file-item-action[style*="danger-text"]:hover {
            background-color: rgba(220, 38, 38, 0.08);
            color: var(--vo-danger-text);
        }
        .vo-form-error {
            margin-top: 8px;
            color: var(--vo-danger-text);
            font-size: 0.75rem;
            line-height: 1.4;
        }
        .vo-doc-upload-wrap {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 220px;
            max-width: 100%;
            border: 1px solid var(--vo-border);
            background: var(--vo-bg-subtle);
            border-radius: 10px;
            padding: 10px 12px;
            line-height: 1.25;
            overflow: hidden;
        }
        .vo-doc-upload-name {
            min-width: 0;
            font-size: 0.78rem;
            color: var(--vo-text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow-wrap: anywhere;
        }
        .vo-doc-upload-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .vo-doc-help {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 220px;
            max-width: 100%;
            border: 1px solid var(--vo-border);
            background: var(--vo-bg-subtle);
            border-radius: 10px;
            padding: 10px 12px;
            line-height: 1.25;
            overflow: hidden;
        }
        .vo-doc-upload-btn {
            border: none;
            border-radius: 8px;
            background: var(--vo-accent);
            color: #111;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 8px 12px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .vo-doc-upload-name {
            min-width: 0;
            font-size: 0.78rem;
            color: var(--vo-text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .vo-doc-upload-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .vo-doc-help {
            margin-top: 6px;
            font-size: 0.72rem;
            color: var(--vo-text-faint);
        }
        .vo-doc-upload-wrap:hover { border-color: #d4a94b; }
        .vo-doc-upload-wrap:focus-within { outline: none; border-color: var(--vo-accent); box-shadow: 0 0 0 2px rgba(251,186,0,.2); }
        .vo-doc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: start;
        }
        .vo-doc-item {
            padding: 10px 14px;
            border-bottom: 1px solid var(--vo-border-light);
            align-self: start;
        }
        .vo-doc-item-locked {
            background: var(--vo-bg-subtle);
            border-left: 3px solid var(--vo-info-text, #0369a1);
        }
        .vo-doc-section-title {
            grid-column: span 2;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 18px 8px;
            background: var(--vo-bg-subtle);
            border-top: 1px solid var(--vo-border);
            border-bottom: 1px solid var(--vo-border-light);
        }
        .vo-doc-section-title:first-child {
            border-top: none;
        }
        .vo-doc-section-title-text {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--vo-text-muted);
        }
        .vo-doc-section-count {
            font-size: 0.6rem;
            font-weight: 700;
            color: var(--vo-text-muted);
            background: var(--vo-border-light);
            border-radius: 1rem;
            padding: 2px 8px;
        }
        .vo-doc-attachments {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 4px;
        }
        .vo-doc-attachment {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--vo-bg-subtle);
            border: 1px solid var(--vo-border-light);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.7rem;
            color: var(--vo-text);
            min-width: 0;
        }
        .vo-doc-attachment-icon {
            color: var(--vo-text-muted);
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }
        .vo-doc-attachment-name {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .vo-doc-attachment-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .vo-doc-attachment-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0;
            line-height: 1;
        }
        .vo-doc-attachment-btn.view {
            color: var(--vo-info-text);
        }
        .vo-doc-attachment-btn.remove {
            color: var(--vo-danger-text);
        }
        .vo-doc-head {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .vo-doc-name {
            flex: 1;
            min-width: 0;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--vo-text);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }
        .vo-doc-row {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .vo-doc-upload-wrap {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 220px;
            max-width: 100%;
            border: 1px solid var(--vo-border);
            background: var(--vo-bg-subtle);
            border-radius: 8px;
            padding: 6px 8px;
            line-height: 1.25;
            overflow: hidden;
        }
        .vo-doc-upload-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .vo-doc-upload-btn {
            border: none;
            border-radius: 6px;
            background: var(--vo-accent);
            color: #111;
            font-weight: 700;
            font-size: 0.68rem;
            padding: 6px 10px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .vo-doc-upload-name {
            min-width: 0;
            font-size: 0.72rem;
            color: var(--vo-text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .vo-doc-upload-wrap:hover {
            border-color: #d4a94b;
        }
        .vo-doc-upload-wrap:focus-within {
            outline: none;
            border-color: var(--vo-accent);
            box-shadow: 0 0 0 2px rgba(251,186,0,.2);
        }
        .vo-doc-link {
            font-size: 0.68rem;
            color: var(--vo-info-text);
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
        }
        .vo-doc-remove {
            background: none;
            border: none;
            color: var(--vo-danger-text);
            font-size: 0.68rem;
            cursor: pointer;
            padding: 0;
            white-space: nowrap;
        }
        .vo-doc-help {
            margin-top: 6px;
            font-size: 0.62rem;
            color: var(--vo-text-faint);
        }
        .vo-doc-empty {
            font-size: 0.66rem;
            color: var(--vo-text-faint);
        }
        @media (max-width: 768px) {
            .vo-doc-grid { grid-template-columns: 1fr; }
            .vo-doc-upload-wrap {
                min-width: 100%;
            }
            .vo-consumo-grid {
                grid-template-columns: 1fr;
            }
        }
        .vo-pedidos-grid {
            display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2px;
        }
        .vo-pedido-item {
            display: flex; align-items: center; gap: 6px;
            padding: 5px 6px; border-radius: 4px; cursor: pointer;
            font-size: 0.68rem; line-height: 1.3;
            transition: background .12s;
        }
        .vo-pedido-item:hover { background: var(--vo-bg-subtle); }
        .vo-pedido-item input[type="checkbox"] { cursor: pointer; accent-color: var(--vo-accent); }
        .vo-pedido-contratado { color: var(--vo-success-text); font-weight: 600; }

        /* ── Progress Ring ── */
        .vo-ring-wrap {
            display: flex; align-items: center; gap: 4px;
        }
        .vo-ring-label {
            font-size: 0.6rem; font-weight: 700; color: #111;
        }

        /* ── Lightbox ── */
        .vo-cover { cursor: pointer; }
        .vo-lightbox {
            position: fixed; inset: 0; z-index: 999;
            background: rgba(0,0,0,.85);
            display: flex; align-items: center; justify-content: center;
            cursor: zoom-out;
        }
        .vo-lightbox img {
            max-width: 92vw; max-height: 90vh;
            object-fit: contain; border-radius: 8px;
            box-shadow: 0 8px 40px rgba(0,0,0,.5);
        }
        .vo-lightbox-close {
            position: absolute; top: 16px; right: 20px;
            background: none; border: none; color: #fff;
            font-size: 2rem; cursor: pointer; line-height: 1;
        }

        /* ── Botão Voltar ── */
        .vo-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            margin-bottom: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--vo-text-secondary);
            background: var(--vo-bg);
            border: 1px solid var(--vo-border);
            border-radius: 8px;
            cursor: pointer;
            transition: all .15s ease;
            text-decoration: none;
        }
        .vo-back-btn:hover {
            background: var(--vo-bg-subtle);
            color: var(--vo-text);
            border-color: var(--vo-text-muted);
        }
        .vo-back-btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* ── Entrega Contratual Inline Inputs ── */
        .vo-ec-input {
            transition: all 0.2s ease;
        }
        .vo-ec-input:hover {
            border-color: var(--vo-border-light) !important;
            background-color: var(--vo-bg-subtle) !important;
        }
        .vo-ec-input:focus {
            border-color: var(--vo-accent) !important;
            background-color: var(--vo-bg) !important;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1) !important;
        }
    </style>

    {{-- Wrapper para eliminar gap do Filament --}}
    <div style="display: flex; flex-direction: column;">

    {{-- ══════ Botão Voltar ══════ --}}
    <a href="{{ \App\Filament\Resources\Obras\ObrasResource::getUrl('index') }}" class="vo-back-btn">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Voltar para Obras
    </a>

    {{-- ══════ Cover ══════ --}}
    @php
        $imagemCapa = $obra->foto_capa;
        $capaUrl = null;
        $mediaDisk = Storage::disk((string) config('filesystems.media_disk', 'r2'));
        $resolveStoredUrl = function (?string $path) use ($mediaDisk): ?string {
            if (! filled($path)) {
                return null;
            }

            if (\Illuminate\Support\Str::startsWith((string) $path, ['http://', 'https://'])) {
                return $path;
            }

            if ($mediaDisk->exists($path)) {
                return $mediaDisk->url($path);
            }

            return null;
        };

        if ($imagemCapa) {
            $capaUrl = $resolveStoredUrl($imagemCapa);
        } elseif ($projeto && $projeto->imagem_ponto) {
            $imgs = is_array($projeto->imagem_ponto) ? $projeto->imagem_ponto : [$projeto->imagem_ponto];
            $capaUrl = isset($imgs[0]) ? $resolveStoredUrl($imgs[0]) : null;
        }

        $perfilUrl = $obra->foto_perfil ? $resolveStoredUrl($obra->foto_perfil) : null;
        $perfilLabel = trim((string) ($obra->sigla ?? $obra->codigo ?? 'OBRA'));
    @endphp
    <div class="vo-cover vo-theme-view-obra"
         @if($capaUrl)
             x-data x-on:click="$dispatch('open-lightbox', { src: '{{ $capaUrl }}' })" style="cursor: pointer;"
         @endif>
        @if($capaUrl)
            <img src="{{ $capaUrl }}" alt="Elevação">
        @endif
        <div class="vo-cover-label">
            Elevação do Projeto — {{ $obra->unidade ?? $projeto?->nome ?? '' }}
        </div>
    </div>

    {{-- ══════ Profile Bar ══════ --}}
    <div class="vo-profile-bar">
        <div class="vo-profile-photo"
             @if($perfilUrl)
                 x-data x-on:click="$dispatch('open-lightbox', { src: '{{ $perfilUrl }}' })"
             @endif>
            @if($perfilUrl)
                <img src="{{ $perfilUrl }}" alt="Perfil">
            @else
                {{ $perfilLabel }}
            @endif
        </div>
        <div class="vo-profile-info">
            <div class="vo-profile-name">
                {{ $obra->sigla ?? $obra->codigo }} — {{ $obra->unidade ?? $projeto?->nome ?? 'Obra' }}
            </div>
            <div class="vo-profile-sub">
                {{ $obra->codigo }}
                @if($obra->marca) &middot; {{ strtoupper($obra->marca) }} @endif
            </div>
            <div class="vo-tags">
                @foreach($tiposUnidade as $tipoUnidade)
                    <span class="vo-tag vo-tag-amber">{{ $tipoUnidade }}</span>
                @endforeach
                @if($obra->status)
                    @php
                        $statusLower = mb_strtolower($obra->status);
                        $statusTagClass = match (true) {
                            str_contains($statusLower, 'inaugurad') => 'vo-tag-status-success',
                            str_contains($statusLower, 'obra') => 'vo-tag-status-danger',
                            str_contains($statusLower, 'processo') => 'vo-tag-status-warning',
                            str_contains($statusLower, 'cancelad') => 'vo-tag-status-gray',
                            str_contains($statusLower, 'paralisad') => 'vo-tag-status-danger',
                            str_contains($statusLower, 'pré') => 'vo-tag-status-info',
                            str_contains($statusLower, 'pós') => 'vo-tag-status-info',
                            default => 'vo-tag-status-gray',
                        };
                    @endphp
                    <span class="vo-tag {{ $statusTagClass }}">{{ $obra->status }}</span>
                @endif
                @if($obra->tipo_imovel)
                    <span class="vo-tag vo-tag-amber">{{ $obra->tipo_imovel }}</span>
                @endif
                @if($projeto?->n_vagas_livres)
                    <span class="vo-tag vo-tag-gray">{{ $projeto->n_vagas_livres }} vagas</span>
                @endif
            </div>
        </div>
        <div class="vo-profile-actions">
            <button wire:click="abrirModalFotos" class="vo-btn-outline">+ Adicionar Fotos</button>
            @if($obra->link)
                <button wire:click="abrirVisi" class="vo-btn-outline" title="Abrir VISI em nova aba" style="display: flex; align-items: center; gap: 6px;">
                    <img src="{{ asset('images/logo-visi.png') }}" alt="VISI" style="height: 20px; width: auto;">
                    VISI
                </button>
            @endif
            <a href="{{ \App\Filament\Resources\Obras\ObrasResource::getUrl('edit', ['record' => $obra]) }}" class="vo-btn-outline" style="text-decoration: none;">Editar</a>
        </div>
    </div>

    {{-- ══════ Info + Datas (2 colunas) ══════ --}}
    <div class="vo-header-info">
        {{-- Coluna esquerda: dados do imóvel --}}
        <div class="vo-header-left">
            @if($obra->endereco)
                <div>Endereço: {{ $obra->endereco }}{{ $obra->cidade ? ', ' . $obra->cidade : '' }} | {{ $obra->uf ?? '' }}</div>
            @endif
            @if($projeto?->nome_contato || $projeto?->contato)
                <div>Proprietário: {{ $projeto->nome_contato }}{{ $projeto->contato ? ' (' . $projeto->contato . ')' : '' }}</div>
            @endif
            <div>
                {{ collect([$obra->tipo_imovel, $obra->empreendimento])->filter()->join('; ') }}{{ ($projeto?->area_locada || $projeto?->area_academia) ? '; ' . number_format($projeto->area_locada ?? $projeto->area_academia, 0, ',', '.') . 'M² de academia' : '' }}{{ $obra->locacao ? ' ' . $obra->locacao : '' }}.
            </div>
            @if($projeto?->n_vagas_livres)
                <div>{{ $projeto->n_vagas_livres }} vagas de estacionamento livres e exclusivas</div>
            @endif
        </div>

        {{-- Coluna direita: datas + dias --}}
        <div class="vo-header-right">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px;">
                <div class="vo-info-row">
                    <span class="vo-info-label">Posse</span>
                    <span class="vo-info-value">{{ $obra->entrada_ponto?->format('d/m/Y') ?? '—' }}</span>
                </div>
                @php
                    $fimImp      = $obra->fim_imp     ? \Carbon\Carbon::parse($obra->fim_imp)     : null;
                    $inauguracao = $obra->inauguracao ?? ($fimImp ? $fimImp->copy()->addDay()      : null);
                    $inaugCarbon = $inauguracao instanceof \Carbon\Carbon ? $inauguracao : ($inauguracao ? \Carbon\Carbon::parse($inauguracao) : null);

                    // Dias de Obra: início real da obra → inauguração (duração total)
                    $inicioObra  = $obra->inicio_real ?? ($obra->inicio ? \Carbon\Carbon::parse($obra->inicio) : null);
                    $diasDeObra  = ($inicioObra && $inaugCarbon)
                        ? \Carbon\Carbon::parse($inicioObra)->diffInDays($inaugCarbon) . ' dias'
                        : null;

                    // Dias para Entrega: hoje → inauguração (negativo = em atraso).
                    // Obras inauguradas não ficam "em atraso", mesmo que a data já tenha passado.
                    $diasRestantes   = $inaugCarbon ? (int) now()->diffInDays($inaugCarbon, false) : null;
                    $diasParaEntrega = $diasRestantes !== null ? abs($diasRestantes) . ' dias' : null;
                    $obraInaugurada  = mb_strtolower((string) $obra->status) === 'inaugurada';
                    $entregaAtrasada = ! $obraInaugurada && $diasRestantes !== null && $diasRestantes < 0;
                @endphp
                <div class="vo-info-row">
                    <span class="vo-info-label">Dias de Obra</span>
                    <span class="vo-info-value">{{ $diasDeObra ?? '—' }}</span>
                </div>
                <div class="vo-info-row">
                    <span class="vo-info-label">Início de Obra</span>
                    <span class="vo-info-value">{{ $obra->inicio_real?->format('d/m/Y') ?? ($obra->inicio ? \Carbon\Carbon::parse($obra->inicio)->format('d/m/Y') : '—') }}</span>
                </div>
                @if(! $obraInaugurada)
                    <div class="vo-info-row" style="grid-row: span 3;">
                        <div class="vo-dias-box" @if($entregaAtrasada) style="border-color:var(--vo-danger-text);" @endif>
                            <div class="vo-dias-box-label" @if($entregaAtrasada) style="color:var(--vo-danger-text);" @endif>
                                {{ $entregaAtrasada ? 'Em atraso' : 'Dias para Entrega da Obra' }}
                            </div>
                            <div class="vo-dias-box-value" @if($entregaAtrasada) style="color:var(--vo-danger-text);" @endif>
                                {{ $diasParaEntrega ?? '—' }}
                            </div>
                        </div>
                    </div>
                @endif
                <div class="vo-info-row">
                    <span class="vo-info-label">Entrega de Obra</span>
                    <span class="vo-info-value">{{ $projeto?->entrega_obra?->format('d/m/Y') ?? $projeto?->data_entrega_shell?->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div class="vo-info-row">
                    <span class="vo-info-label">Implantação</span>
                    <span class="vo-info-value">{{ $obra->inicio_imp ? \Carbon\Carbon::parse($obra->inicio_imp)->format('d/m/Y') : '—' }}</span>
                </div>
                <div class="vo-info-row">
                    <span class="vo-info-label">Inauguração</span>
                    <span class="vo-info-value">{{ $obra->inauguracao?->format('d/m/Y') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════ Tabs ══════ --}}
    <div x-data="{ activeTab: 'inicio' }" x-on:switch-tab.window="activeTab = $event.detail || $event">
        <div class="vo-tabs">
            <div class="vo-tab" :class="{ 'vo-tab-active': activeTab === 'inicio' }" @click="activeTab = 'inicio'" style="cursor: pointer;">Início</div>
            <div class="vo-tab" :class="{ 'vo-tab-active': activeTab === 'galeria' }" @click="activeTab = 'galeria'" style="cursor: pointer;">Galeria</div>
            <div class="vo-tab" :class="{ 'vo-tab-active': activeTab === 'rdo' }" @click="activeTab = 'rdo'; $wire.loadRdos();" style="cursor: pointer;">RDO</div>
            @if($isRetrofit)
                <div class="vo-tab" :class="{ 'vo-tab-active': activeTab === 'pedidos-retrofit' }" @click="activeTab = 'pedidos-retrofit'; $wire.loadPedidosRetrofit();" style="cursor: pointer;">Pedidos Retrofit</div>
            @endif
            <div class="vo-tab" :class="{ 'vo-tab-active': activeTab === 'entrega-contratual' }" @click="activeTab = 'entrega-contratual'; $wire.loadEntregaContratual();" style="cursor: pointer;">Entrega Contratual</div>
        </div>

    {{-- ══════ Tab: Início — Layout 3 Colunas ══════ --}}
    <div class="vo-columns" x-show="activeTab === 'inicio'">

        {{-- ══ Coluna Esquerda: Recebimentos + Contratações ══ --}}
        <div style="display: flex; flex-direction: column; gap: 12px;">

            {{-- Pontos de Atenção --}}
            @php
                $podeEditarPontosAtencao = $this->podeEditarPontosAtencao;
                $colunasPontosAtencao = $obra->colunasPersonalizadas()->orderBy('nome')->get();
            @endphp
            <div class="vo-card">
                <div class="vo-card-head-accent" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Pontos de Atenção</span>
                    @if($podeEditarPontosAtencao)
                        <button wire:click="abrirModalPontosAtencao" class="vo-edit-btn" title="Editar pontos de atenção">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                    @endif
                </div>
                <div class="vo-card-body" style="font-size:0.75rem;line-height:1.5;">
                    @forelse($colunasPontosAtencao as $coluna)
                        @php
                            $valor = $coluna->valor;
                            if ($coluna->tipo === 'data' && filled($valor)) {
                                try {
                                    $valor = \Carbon\Carbon::parse($valor)->format('d/m/Y');
                                } catch (\Throwable $e) {
                                }
                            }
                        @endphp
                        <div style="padding: 4px 0; border-bottom: 1px solid var(--vo-border-light);">
                            <div style="font-size: 0.64rem; color: var(--vo-text-faint); text-transform: uppercase; letter-spacing: .06em;">
                                {{ $coluna->nome }}
                            </div>
                            <div style="font-weight: 600; color: {{ filled($valor) ? 'var(--vo-danger-text)' : 'var(--vo-text-faint)' }};">
                                {{ filled($valor) ? $valor : 'Sem informação' }}
                            </div>
                        </div>
                    @empty
                        <div style="color:var(--vo-text-faint);">Nenhum ponto de atenção registrado.</div>
                    @endforelse
                </div>
            </div>

            {{-- Fachada --}}
            @php
                $fachadaStatus = $obra->fachada_status;
                $fachadaStyles = [
                    'finalizada' => 'background:var(--vo-success-bg);color:var(--vo-success-text)',
                    'agendada' => 'background:var(--vo-info-bg);color:var(--vo-info-text)',
                    'aguardando_contratacao' => 'background:var(--vo-warn-bg);color:var(--vo-warn-text)',
                    'em_atraso' => 'background:rgba(153,27,27,.1);color:var(--vo-danger-text)',
                    'com_pendencia' => 'background:rgba(153,27,27,.1);color:var(--vo-danger-text)',
                ];
                $fachadaLabels = [
                    'finalizada' => 'Finalizada',
                    'agendada' => 'Agendada',
                    'aguardando_contratacao' => 'Aguardando contratação',
                    'em_atraso' => 'Em atraso',
                    'com_pendencia' => 'Com pendência',
                ];
            @endphp
            <div class="vo-card">
                <div class="vo-card-head-accent" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Fachada</span>
                    <button wire:click="abrirModalFachada" class="vo-edit-btn" title="Editar fachada">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                </div>
                <div class="vo-card-body" style="display:flex;flex-direction:column;gap:8px;font-size:0.74rem;">
                    <div class="vo-stat-row">
                        <span>Data de instalação</span>
                        <span style="font-weight:600;color:var(--vo-text-secondary);">{{ $obra->fachada_data_instalacao?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="vo-stat-row">
                        <span>Status</span>
                        @if($fachadaStatus)
                            <span class="vo-consumo-badge" style="{{ $fachadaStyles[$fachadaStatus] ?? 'background:var(--vo-bg-subtle);color:var(--vo-text-muted)' }}">{{ $fachadaLabels[$fachadaStatus] ?? $fachadaStatus }}</span>
                        @else
                            <span class="vo-consumo-badge" style="background:var(--vo-bg-subtle);color:var(--vo-text-muted)">Não informado</span>
                        @endif
                    </div>
                    <div style="font-size:0.7rem;color:var(--vo-text-faint);text-transform:uppercase;letter-spacing:.05em;">Observação</div>
                    @if(filled($obra->fachada_observacao))
                        <div style="white-space:pre-line;color:var(--vo-text-secondary);">{{ $obra->fachada_observacao }}</div>
                    @else
                        <div style="color:var(--vo-text-faint);">Sem observações.</div>
                    @endif
                </div>
            </div>

            {{-- Controle de Recebimentos --}}
            @php
                $recebimentos = $obra->recebimentos;
                $recebTotal   = $recebimentos->count();
                $recebOk      = $recebimentos->whereIn('status', ['recebido', 'nao_aplicavel'])->count();
                $recebPct     = $recebTotal > 0 ? round(($recebOk / $recebTotal) * 100) : 0;
            @endphp
            <div class="vo-card">
                <div class="vo-card-head-accent" style="display:flex;align-items:center;justify-content:space-between;" x-data="{ showLegenda: false }">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        @include('filament.pages.obras.partials.progress-ring', ['pct' => $recebPct, 'size' => 32])
                        Controle de Recebimentos
                    </span>
                    <div style="display:flex;align-items:center;gap:6px;">
                        @if($this->podeVisualizarRecebimentos)
                            <button wire:click="abrirModalRecebimentos" class="vo-edit-btn" title="Gerenciar recebimentos">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        @endif
                        <div style="position: relative;">
                            <button @click="showLegenda = !showLegenda" style="background:rgba(0,0,0,.15);border:none;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:0.7rem;font-weight:700;color:#111;">?</button>
                            <div x-show="showLegenda" x-cloak @click.outside="showLegenda = false" x-transition.opacity
                                 style="position:absolute;top:28px;right:0;z-index:10;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:0.5rem;box-shadow:0 4px 12px rgba(0,0,0,.15);padding:10px 14px;display:flex;flex-direction:column;gap:6px;font-size:0.65rem;color:var(--vo-text-secondary);white-space:nowrap;">
                            <span style="display:flex;align-items:center;gap:6px;"><span class="vo-check-icon vo-check-icon-ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span> Recebido</span>
                            <span style="display:flex;align-items:center;gap:6px;"><span class="vo-check-icon vo-check-icon-warn"><svg width="10" height="10" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L1 21h22L12 2zm0 4l7.5 13h-15L12 6z" fill="none"/><text x="12" y="18" text-anchor="middle" font-size="14" font-weight="bold" fill="#fff">!</text></svg></span> Pendente</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="vo-card-body" style="padding: 6px 16px; max-height: 500px; overflow-y: auto;">
                    @forelse($recebimentos as $rec)
                        <div class="vo-check-item">
                            @if($rec->status === 'recebido')
                                <span class="vo-check-icon vo-check-icon-ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                            @elseif($rec->status === 'nao_aplicavel')
                                <span class="vo-check-icon vo-check-icon-ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                            @else
                                <span class="vo-check-icon vo-check-icon-warn"><svg width="10" height="10" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L1 21h22L12 2zm0 4l7.5 13h-15L12 6z" fill="none"/><text x="12" y="18" text-anchor="middle" font-size="14" font-weight="bold" fill="#fff">!</text></svg></span>
                            @endif
                            <span style="display:flex;flex-direction:column;gap:4px;min-width:0;">
                                <span>{{ $rec->nome }}</span>
                                <span style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:0.65rem;color:var(--vo-text-faint);">
                                    @if(filled($rec->construtora?->nome))
                                        <span>{{ $rec->construtora->nome }}</span>
                                    @endif

                            @if($rec->hasFotoEntrega())
                                        <button
                                            type="button"
                                            wire:click="abrirArquivoRecebimento({{ $rec->id }}, 'foto')"
                                            style="background:var(--vo-info-bg);color:var(--vo-info-text);border:none;padding:2px 8px;border-radius:999px;font-size:0.62rem;font-weight:700;cursor:pointer;"
                                        >
                                            Foto
                                        </button>
                                    @endif

                            @if($rec->hasNotaFiscal())
                                        <button
                                            type="button"
                                            wire:click="abrirArquivoRecebimento({{ $rec->id }}, 'nota')"
                                            style="background:var(--vo-success-bg);color:var(--vo-success-text);border:none;padding:2px 8px;border-radius:999px;font-size:0.62rem;font-weight:700;cursor:pointer;"
                                        >
                                            NF
                                        </button>
                                    @endif
                                </span>
                            </span>
                        </div>
                    @empty
                        <div style="padding:12px 0;text-align:center;font-size:0.75rem;color:var(--vo-text-faint);">Nenhum item cadastrado</div>
                    @endforelse
                </div>
            </div>

            {{-- Controle de Contratações (derivado dos Controles de Medição da obra) --}}
            <div class="vo-card">
                @php
                    $totalContratuais = $itensContratuais->count();
                    $totalExtras = $itensExtraContratuais->count();
                    $totalGeral = $totalContratuais + $totalExtras;

                    $contratadosContratuais = $itensContratuais
                        ->filter(fn ($i) => filled($i->empresa))
                        ->count();
                    $contratadosExtras = $itensExtraContratuais
                        ->filter(fn ($i) => filled($i->empresa ?? null))
                        ->count();
                    $contratadosGeral = $contratadosContratuais + $contratadosExtras;

                    $contratPct = $totalGeral > 0 ? round(($contratadosGeral / $totalGeral) * 100) : 0;
                @endphp
                <div class="vo-card-head-accent" style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        @include('filament.pages.obras.partials.progress-ring', ['pct' => $contratPct, 'size' => 32])
                        Controle de Contratações
                    </span>
                </div>
                <div class="vo-card-body" style="padding: 6px 16px; max-height: 500px; overflow-y: auto;">
                    @if($totalGeral > 0)
                        @if($totalContratuais > 0)
                            <div style="font-size:.65rem;font-weight:700;color:var(--vo-text-faint);text-transform:uppercase;letter-spacing:.04em;padding:8px 0 4px;">
                                Itens contratuais ({{ $contratadosContratuais }}/{{ $totalContratuais }})
                            </div>
                            @foreach($itensContratuais as $item)
                                @php
                                    $contratado = filled($item->empresa);
                                    $titulo = trim(
                                        ($item->numero_as ? $item->numero_as : '')
                                        .($item->numero_complemento ? ' '.$item->numero_complemento : '')
                                    );
                                    $descricao = $item->escopo ?: '—';
                                @endphp
                                <div class="vo-contract-item">
                                    @if($contratado)
                                        <span class="vo-check-icon vo-check-icon-ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                    @else
                                        <span class="vo-check-icon vo-check-icon-warn"><svg width="10" height="10" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L1 21h22L12 2zm0 4l7.5 13h-15L12 6z" fill="none"/><text x="12" y="18" text-anchor="middle" font-size="14" font-weight="bold" fill="#fff">!</text></svg></span>
                                    @endif
                                    <span>{{ $titulo }} - {{ $descricao }}</span>
                                    <button type="button" class="vo-contract-eye-btn" wire:click="abrirModalDetalheItem({{ $item->id }}, 'item')" title="Ver detalhes">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        @endif

                        @if($totalExtras > 0)
                            <div style="font-size:.65rem;font-weight:700;color:var(--vo-text-faint);text-transform:uppercase;letter-spacing:.04em;padding:8px 0 4px;">
                                Itens extra contratuais ({{ $contratadosExtras }}/{{ $totalExtras }})
                            </div>
                            @foreach($itensExtraContratuais as $item)
                                @php
                                    $contratado = filled($item->empresa ?? null);
                                    $titulo = trim(($item->numero_as ?? '') ?: ($item->grupo ?? ''));
                                    $descricao = $item->escopo ?: ($item->grupo ?: '—');
                                @endphp
                                <div class="vo-contract-item">
                                    @if($contratado)
                                        <span class="vo-check-icon vo-check-icon-ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                    @else
                                        <span class="vo-check-icon vo-check-icon-warn"><svg width="10" height="10" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L1 21h22L12 2zm0 4l7.5 13h-15L12 6z" fill="none"/><text x="12" y="18" text-anchor="middle" font-size="14" font-weight="bold" fill="#fff">!</text></svg></span>
                                    @endif
                                    <span>{{ $titulo ? $titulo.' - ' : '' }}{{ $descricao }}</span>
                                    <button type="button" class="vo-contract-eye-btn" wire:click="abrirModalDetalheItem({{ $item->id }}, 'auxiliar')" title="Ver detalhes">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    @else
                        <div style="padding: 12px 0; text-align: center; font-size: 0.75rem; color: var(--vo-text-faint);">
                            Nenhum item de controle de medição cadastrado para esta obra.
                        </div>
                    @endif
                </div>
            </div>

            {{-- ASA --}}
            <div class="vo-card">
                <div class="vo-card-head-accent">ASA</div>
                <div class="vo-card-body" style="padding: 6px 16px;">
                    @php
                        $asaStats = [
                            ['label' => 'Recebidos', 'valor' => $obra->homologados_em_atraso ?? 0, 'cor' => 'var(--vo-accent)', 'bgCor' => 'rgba(251,186,0,.15)'],
                            ['label' => 'Em análise', 'valor' => $obra->itens_criticos ?? 0, 'cor' => '#3b82f6', 'bgCor' => 'rgba(59,130,246,.15)'],
                            ['label' => 'Aprovados', 'valor' => 0, 'cor' => '#22c55e', 'bgCor' => 'rgba(34,197,94,.15)'],
                        ];
                    @endphp
                    @foreach($asaStats as $stat)
                        <div class="vo-stat-row">
                            <span>{{ $stat['label'] }}</span>
                            <span class="vo-stat-badge" style="background: {{ $stat['bgCor'] }}; color: {{ $stat['cor'] }};">{{ $stat['valor'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══ Coluna Central: Fotos + Feed ══ --}}
        <div style="display: flex; flex-direction: column; gap: 12px;">

            {{-- Fotos --}}
            <div class="vo-card">
                <div class="vo-card-head-accent">Fotos</div>
                <div class="vo-card-body">
                    @php
                        $galeriaFotos = [];
                        $mediaDisk = Storage::disk((string) config('filesystems.media_disk', 'r2'));
                        $resolveStoredUrl = function (?string $path) use ($mediaDisk): ?string {
                            if (! filled($path)) {
                                return null;
                            }

                            if (\Illuminate\Support\Str::startsWith((string) $path, ['http://', 'https://'])) {
                                return $path;
                            }

                            if ($mediaDisk->exists($path)) {
                                return $mediaDisk->url($path);
                            }

                            return null;
                        };

                        // Fotos manuais (salvas no campo fotos da obra)
                        $fotosManual = $obra->fotos ?? [];
                        foreach ($fotosManual as $path) {
                            $originalUrl = $resolveStoredUrl($path);
                            if (blank($originalUrl)) {
                                continue;
                            }
                            $galeriaFotos[] = [
                                'url' => \App\Support\ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $path, 1600, 1200, 'contain', 78) ?? $originalUrl,
                                'thumb_url' => \App\Support\ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $path, 420, 420, 'cover', 70) ?? $originalUrl,
                                'original_url' => $originalUrl,
                                'path' => $path,
                            ];
                        }

                        // Fotos da Constructin
                        if (! empty($constructinFotos)) {
                            foreach ($constructinFotos as $foto) {
                                $galeriaFotos[] = [
                                    'url' => $foto['url'],
                                    'thumb_url' => $foto['thumb_url'] ?? $foto['url'],
                                    'original_url' => $foto['original_url'] ?? $foto['url'],
                                    'path' => null,
                                ];
                            }
                        }

                        // Fallback: imagem do ponto do projeto
                        if (empty($galeriaFotos) && $projeto?->imagem_ponto) {
                            $imgs = is_array($projeto->imagem_ponto) ? $projeto->imagem_ponto : [$projeto->imagem_ponto];
                            foreach ($imgs as $img) {
                                $originalUrl = $resolveStoredUrl($img);
                                if (blank($originalUrl)) {
                                    continue;
                                }
                                $galeriaFotos[] = [
                                    'url' => \App\Support\ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $img, 1600, 1200, 'contain', 78) ?? $originalUrl,
                                    'thumb_url' => \App\Support\ImageVariantUrl::forStorage((string) config('filesystems.media_disk', 'r2'), (string) $img, 420, 420, 'cover', 70) ?? $originalUrl,
                                    'original_url' => $originalUrl,
                                    'path' => $img,
                                ];
                            }
                        }

                        $totalFotos = count($galeriaFotos);
                    @endphp

                    @if($totalFotos === 0)
                        <div style="padding:20px;text-align:center;font-size:0.75rem;color:var(--vo-text-faint);">
                            Nenhuma foto disponível
                        </div>
                    @else
                        <div x-data="{
                            fotos: @js(array_values($galeriaFotos)),
                            selected: 0,
                            selectAndOpen(idx) {
                                this.selected = idx;
                                $dispatch('open-lightbox', { src: this.fotos[idx].url, fotos: this.fotos.map(f => f.url), paths: this.fotos.map(f => f.path), originals: this.fotos.map(f => f.original_url || f.url), idx: idx });
                            }
                        }">
                            <div class="vo-gallery-grid">
                                <template x-for="(foto, idx) in fotos.slice(0, 10)" :key="idx">
                                    <div class="vo-gallery-grid-item" @click="selectAndOpen(idx)">
                                        <img :src="foto.thumb_url || foto.url" :alt="`Foto ${idx + 1}`" :loading="idx < 5 ? 'eager' : 'lazy'" decoding="async"
                                             x-on:error="if (foto.thumb_url && $event.target.src !== foto.url) { $event.target.src = foto.url; } else if (foto.original_url && $event.target.src !== foto.original_url) { $event.target.src = foto.original_url; }">
                                        <template x-if="idx === 9 && fotos.length > 10">
                                            <div class="vo-gallery-grid-more" @click.stop="$dispatch('switch-tab', 'galeria')">
                                                <span x-text="`+${fotos.length - 9}`"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                        </div>
                    @endif
                </div>
            </div>

            {{-- Feed --}}
            <div class="vo-section-title">Feed</div>
            <div class="vo-card">
                <div class="vo-card-head">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        Comentários e atividade
                    </span>
                </div>
                <livewire:obras.historico-obra :obra-id="$obra->id" :key="'historico-obra-'.$obra->id" />
            </div>
        </div>

        {{-- ══ Coluna Direita: Cronograma + Docs + Consumo + ASA ══ --}}
        <div style="display: flex; flex-direction: column; gap: 12px;">

            {{-- Cronograma / Curva S (Construct-IN) --}}
            @php
                $sLabels   = $constructinVisi['labels']   ?? [];
                $sDates    = $constructinVisi['dates']    ?? [];
                $sPlanned  = $constructinVisi['planned']  ?? [];
                $sActual   = $constructinVisi['actual']   ?? [];
                $sOriginal = $constructinVisi['original'] ?? [];
                $visiDisponivel = ! empty($sLabels);

                $progressHoje = $constructinProgress ?? [];
                $previsto = filled($progressHoje['percentual_obra'] ?? null)
                    ? (float) $progressHoje['percentual_obra']
                    : (float) ($obra->percentual_obra ?? 0);
                $realizado = filled($progressHoje['percentual_obra_executado'] ?? null)
                    ? (float) $progressHoje['percentual_obra_executado']
                    : (float) ($obra->percentual_obra_executado ?? 0);
                $referenciaHoje = $progressHoje['referencia'] ?? null;

                // Constrói série pulando pontos null no início e fim — Apex/SVG falha quando
                // todos os primeiros y são null (gera "M 0 NaN" no path). Manter null no meio é ok.
                $sBuildSeries = static function (array $values) use ($sDates): array {
                    $first = null;
                    $last = null;
                    foreach ($values as $i => $v) {
                        if ($v !== null) {
                            if ($first === null) $first = $i;
                            $last = $i;
                        }
                    }
                    if ($first === null) return [];
                    $out = [];
                    for ($i = $first; $i <= $last; $i++) {
                        $out[] = ['x' => $sDates[$i] ?? $i, 'y' => $values[$i]];
                    }
                    return $out;
                };

                $sChartSeries = $visiDisponivel ? [
                    ['name' => 'Original',  'data' => $sBuildSeries($sOriginal)],
                    ['name' => 'Previsto',  'data' => $sBuildSeries($sPlanned)],
                    ['name' => 'Realizado', 'data' => $sBuildSeries($sActual)],
                ] : [];
            @endphp
            <div class="vo-card" x-data="{ expanded: false }" @if(!$visiDisponivel) style="opacity: 0.5; pointer-events: none;" @endif>
                <div class="vo-card-head-accent">Cronograma</div>
                @if($visiDisponivel)
                    @php
                        $sChartUid = 'sCurve_'.$obra->id;
                        $sChartPayload = [
                            'dates' => $sDates,
                            'planned' => $sPlanned,
                            'actual' => $sActual,
                            'original' => $sOriginal,
                        ];
                    @endphp

                    @once
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1/dist/chartjs-adapter-luxon.umd.min.js"></script>
                    @endonce

                    <script>
                        window.{{ $sChartUid }} = @json($sChartPayload);

                        if (!window.__waitForChartJs) {
                            window.__waitForChartJs = function (cb, attempt) {
                                attempt = attempt || 0;
                                if (typeof Chart !== 'undefined' && typeof luxon !== 'undefined') { cb(); return; }
                                if (attempt > 100) { console.warn('[sCurve] Chart.js failed to load after 10s'); return; }
                                setTimeout(function () { window.__waitForChartJs(cb, attempt + 1); }, 100);
                            };
                        }

                        if (!window.__buildSCurveDatasets) {
                            window.__buildSCurveDatasets = function (payload, opts) {
                                opts = opts || {};
                                var dates = payload.dates || [];
                                var toPoints = function (values) {
                                    var pts = [];
                                    for (var i = 0; i < dates.length; i++) {
                                        if (values[i] == null) continue;
                                        pts.push({ x: dates[i], y: values[i] });
                                    }
                                    return pts;
                                };
                                return [
                                    {
                                        label: 'Original',
                                        data: toPoints(payload.original || []),
                                        borderColor: '#94a3b8',
                                        backgroundColor: 'transparent',
                                        borderWidth: opts.borderWidth || 2,
                                        borderDash: [6, 4],
                                        tension: 0.35,
                                        pointRadius: 0,
                                        pointHoverRadius: opts.hoverRadius || 5,
                                        spanGaps: true
                                    },
                                    {
                                        label: 'Previsto',
                                        data: toPoints(payload.planned || []),
                                        borderColor: '#9ca3af',
                                        backgroundColor: 'transparent',
                                        borderWidth: opts.borderWidth || 2,
                                        tension: 0.35,
                                        pointRadius: opts.pointRadius || 0,
                                        pointHoverRadius: opts.hoverRadius || 5,
                                        spanGaps: true
                                    },
                                    {
                                        label: 'Realizado',
                                        data: toPoints(payload.actual || []),
                                        borderColor: '#3b82f6',
                                        backgroundColor: 'transparent',
                                        borderWidth: opts.borderWidth || 3,
                                        tension: 0.35,
                                        pointRadius: opts.pointRadius || 0,
                                        pointHoverRadius: opts.hoverRadius || 6,
                                        spanGaps: true
                                    }
                                ];
                            };
                        }

                        if (!window.__renderSCurveCard) {
                            window.__renderSCurveCard = function (containerEl, payload) {
                                if (!containerEl) return;
                                window.__waitForChartJs(function () {
                                    try { if (containerEl._chart) containerEl._chart.destroy(); } catch (e) {}
                                    containerEl.innerHTML = '<canvas style="width:100%;height:100%"></canvas>';
                                    var canvas = containerEl.querySelector('canvas');
                                    containerEl._chart = new Chart(canvas, {
                                        type: 'line',
                                        data: { datasets: window.__buildSCurveDatasets(payload, { borderWidth: 1.6, hoverRadius: 4 }) },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            interaction: { mode: 'index', intersect: false },
                                            plugins: { legend: { display: false }, tooltip: { enabled: false } },
                                            scales: {
                                                x: { type: 'time', display: false, time: { unit: 'month' } },
                                                y: { display: false, min: 0, max: 100 }
                                            },
                                            elements: { line: { borderJoinStyle: 'round' } }
                                        }
                                    });
                                });
                            };
                        }

                        if (!window.__renderSCurveModal) {
                            window.__renderSCurveModal = function (containerEl, payload) {
                                if (!containerEl) return;
                                window.__waitForChartJs(function () {
                                    try { if (containerEl._chart) containerEl._chart.destroy(); } catch (e) {}
                                    if (containerEl._observer) { try { containerEl._observer.disconnect(); } catch (e) {} }
                                    containerEl.innerHTML = '<canvas style="width:100%;height:100%"></canvas>';
                                    var canvas = containerEl.querySelector('canvas');

                                    var buildOptions = function () {
                                        var isDark = document.documentElement.classList.contains('dark');
                                        var axisColor = isDark ? '#cbd5e1' : '#475569';
                                        var gridColor = isDark ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.25)';
                                        return {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            interaction: { mode: 'index', intersect: false },
                                            plugins: {
                                                legend: {
                                                    position: 'bottom',
                                                    labels: { color: axisColor, font: { size: 12 }, boxWidth: 18, boxHeight: 4, padding: 16, usePointStyle: false }
                                                },
                                                tooltip: {
                                                    enabled: true,
                                                    backgroundColor: isDark ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.98)',
                                                    titleColor: isDark ? '#f1f5f9' : '#0f172a',
                                                    bodyColor: isDark ? '#e2e8f0' : '#334155',
                                                    borderColor: gridColor,
                                                    borderWidth: 1,
                                                    padding: 10,
                                                    callbacks: {
                                                        title: function (items) {
                                                            if (!items.length) return '';
                                                            var d = luxon.DateTime.fromMillis(items[0].parsed.x).setLocale('pt-BR');
                                                            return d.toFormat('dd LLL yyyy');
                                                        },
                                                        label: function (ctx) {
                                                            var v = ctx.parsed.y;
                                                            return ctx.dataset.label + ': ' + (v == null ? '—' : v.toFixed(1).replace('.', ',') + '%');
                                                        }
                                                    }
                                                }
                                            },
                                            scales: {
                                                x: {
                                                    type: 'time',
                                                    time: {
                                                        unit: 'month',
                                                        tooltipFormat: 'dd LLL yyyy',
                                                        displayFormats: { month: 'dd LLL yyyy', week: 'dd LLL', day: 'dd LLL' }
                                                    },
                                                    adapters: { date: { locale: 'pt-BR' } },
                                                    ticks: { color: axisColor, font: { size: 12 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                                                    grid: { display: false },
                                                    border: { color: gridColor }
                                                },
                                                y: {
                                                    position: 'right',
                                                    min: 0,
                                                    max: 100,
                                                    ticks: { color: axisColor, font: { size: 12 }, callback: function (v) { return v + '%'; }, stepSize: 20 },
                                                    grid: { color: gridColor, borderDash: [4, 4] },
                                                    border: { display: false }
                                                }
                                            },
                                            elements: { line: { borderJoinStyle: 'round', borderCapStyle: 'round' } }
                                        };
                                    };

                                    containerEl._chart = new Chart(canvas, {
                                        type: 'line',
                                        data: { datasets: window.__buildSCurveDatasets(payload, { borderWidth: 2.5, hoverRadius: 6 }) },
                                        options: buildOptions()
                                    });

                                    containerEl._observer = new MutationObserver(function () {
                                        if (!containerEl._chart) return;
                                        var newOpts = buildOptions();
                                        Object.assign(containerEl._chart.options, newOpts);
                                        containerEl._chart.update();
                                    });
                                    containerEl._observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                                });
                            };
                        }
                    </script>

                    <div class="vo-card-body" style="text-align: center; padding: 8px 12px 4px; cursor: pointer;" x-on:click="expanded = true">
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--vo-accent); margin-bottom: 4px;">CURVA S</div>
                        @if(filled($referenciaHoje))
                            <div style="font-size: 0.64rem; color: var(--vo-text-faint); margin-bottom: 2px;">
                                Atualizado em {{ \Carbon\Carbon::parse($referenciaHoje)->format('d/m/Y') }}
                            </div>
                        @endif
                        <div
                            x-data
                            x-init="$nextTick(() => window.__renderSCurveCard($el, window.{{ $sChartUid }}))"
                            style="width: 100%; height: 130px;">
                        </div>
                        <div style="display: flex; justify-content: space-around; font-size: 0.75rem; margin-top: 2px;">
                            <div>
                                <div style="font-weight: 700; color: #ef4444;">{{ number_format($previsto, 1, ',', '.') }}%</div>
                                <div style="font-size: 0.6rem; color: var(--vo-text-faint);">Previsto</div>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #22c55e;">{{ number_format($realizado, 1, ',', '.') }}%</div>
                                <div style="font-size: 0.6rem; color: var(--vo-text-faint);">Realizado</div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal maximizado --}}
                    <template x-if="expanded">
                        <div style="position: fixed; inset: 0; z-index: 999; background: rgba(0,0,0,.88); display: flex; align-items: center; justify-content: center; cursor: zoom-out; padding: 1.5rem;"
                             x-on:click="expanded = false" x-on:keydown.escape.window="expanded = false" x-transition.opacity>
                            <div style="background: var(--vo-bg); border-radius: 1rem; padding: 20px 28px; max-width: 95vw; width: 1100px; cursor: default;" x-on:click.stop>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <div style="font-size: 1rem; font-weight: 700; color: var(--vo-accent);">CURVA S — {{ $obra->unidade ?? $obra->sigla }}</div>
                                    <button x-on:click="expanded = false" style="background: none; border: none; font-size: 1.5rem; color: var(--vo-text-muted); cursor: pointer;">&times;</button>
                                </div>
                                <div
                                    x-data
                                    x-init="$nextTick(() => window.__renderSCurveModal($el, window.{{ $sChartUid }}))"
                                    style="width: 100%; height: 460px;">
                                </div>
                            </div>
                        </div>
                    </template>
                @else
                    <div class="vo-card-body" style="text-align: center; padding: 24px 16px;">
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--vo-text-faint); margin-bottom: 6px;">CURVA S</div>
                        <div style="font-size: 0.7rem; color: var(--vo-text-faint);">
                            Integração Constructin indisponível
                        </div>
                    </div>
                @endif
            </div>

            {{-- Envio de Documentos --}}
            @php
                $documentos  = $obra->documentos;
                $docsTotal   = $documentos->count();
                $docsOk      = $documentos->whereIn('status', ['enviado', 'nao_aplicavel'])->count();
                $docsPct     = $docsTotal > 0 ? round(($docsOk / $docsTotal) * 100) : 0;
        $statusDocLabel = ['pendente' => 'Pendente', 'enviado' => 'Enviado', 'nao_aplicavel' => 'Enviado'];
                $statusDocStyle = [
                    'pendente'       => 'background:var(--vo-warn-bg);color:var(--vo-warn-text)',
                    'enviado'        => 'background:var(--vo-success-bg);color:var(--vo-success-text)',
                    'nao_aplicavel'  => 'background:var(--vo-success-bg);color:var(--vo-success-text)',
                ];
            @endphp
            <div class="vo-card">
                <div class="vo-card-head-accent" style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        @include('filament.pages.obras.partials.progress-ring', ['pct' => $docsPct, 'size' => 32])
                        Envio de Documentos
                    </span>
                    @if($this->podeVisualizarDocumentos)
                        <button wire:click="abrirModalDocumentos" class="vo-edit-btn" title="Gerenciar documentos">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                    @endif
                </div>
                <div class="vo-card-body" style="padding: 4px 16px; max-height: 500px; overflow-y: auto;">
                    @forelse($documentos as $doc)
                        @php
                            $isCnpj = $doc->nome === 'CNPJ (definitivo)';
                            $cnpjDefinitivo = $obra->projeto?->status_cnpj === 'definitivo';
                            $docLabelNome = $isCnpj ? 'CNPJ' : $doc->nome;
                            $docStatusLabel = $isCnpj
                                ? ($cnpjDefinitivo ? 'Definitivo' : 'Provisório')
                                : ($statusDocLabel[$doc->status] ?? $doc->status);
                            $docStatusStyle = $isCnpj
                                ? ($cnpjDefinitivo ? $statusDocStyle['enviado'] : $statusDocStyle['pendente'])
                                : ($statusDocStyle[$doc->status] ?? '');
                        @endphp
                        <div class="vo-check-item" style="justify-content: space-between; gap: 6px; align-items:flex-start;">
                            <div style="display:flex;align-items:flex-start;gap:6px;min-width:0;flex:1;">
                                @if($doc->status === 'enviado')
                                    <span class="vo-check-icon vo-check-icon-ok" style="flex-shrink:0;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                @elseif($doc->status === 'nao_aplicavel')
                                    <span class="vo-check-icon vo-check-icon-ok" style="flex-shrink:0;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                @else
                                    <span class="vo-check-icon vo-check-icon-warn" style="flex-shrink:0;"><svg width="10" height="10" viewBox="0 0 24 24" fill="#fff"><path d="M12 2L1 21h22L12 2zm0 4l7.5 13h-15L12 6z" fill="none"/><text x="12" y="18" text-anchor="middle" font-size="14" font-weight="bold" fill="#fff">!</text></svg></span>
                                @endif
                                <span style="font-size:0.72rem;overflow:hidden;text-overflow:ellipsis;white-space:normal;overflow-wrap:anywhere;">{{ $docLabelNome }}</span>
                                @php $totalAnexosDoc = $isCnpj ? 0 : count($doc->arquivos_nomes_resolved); @endphp
                                @if($totalAnexosDoc > 0)
                                    <button type="button"
                                            wire:click="abrirModalAnexosDocumento({{ $doc->id }})"
                                            title="Ver anexos ({{ $totalAnexosDoc }})"
                                            style="background:var(--vo-info-bg);color:var(--vo-info-text);border:none;padding:2px 6px;border-radius:999px;font-size:0.62rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:3px;flex-shrink:0;">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        {{ $totalAnexosDoc }}
                                    </button>
                                @endif
                            </div>
                            <span style="font-size:0.6rem;padding:1px 7px;border-radius:1rem;font-weight:600;flex-shrink:0;max-width:48%;white-space:normal;overflow-wrap:anywhere;{{ $docStatusStyle }}">
                                {{ $docStatusLabel }}
                            </span>
                        </div>
                    @empty
                        <div style="padding: 12px 0; text-align: center; font-size: 0.75rem; color: var(--vo-text-faint);">
                            Nenhum documento cadastrado
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Contas de Consumo --}}
            @php
                $documentosConsumo = $obra->documentos
                    ->whereIn('nome', ['Conta de Energia', 'Conta de Água', 'Conta de Gás'])
                    ->groupBy('nome');

                $consumos = [
                    ['label' => 'Energia', 'valor' => $obra->energia, 'cor' => '#3b82f6', 'observacoes' => $obra->energia_observacoes, 'docsCount' => ($documentosConsumo['Conta de Energia'] ?? collect())->count()],
                    ['label' => 'Água', 'valor' => $obra->agua, 'cor' => '#22c55e', 'observacoes' => $obra->agua_observacoes, 'docsCount' => ($documentosConsumo['Conta de Água'] ?? collect())->count()],
                    ['label' => 'Gás', 'valor' => $obra->gas, 'cor' => '#f59e0b', 'observacoes' => $obra->gas_observacoes, 'docsCount' => ($documentosConsumo['Conta de Gás'] ?? collect())->count()],
                ];
                $contaCor = function($v) {
                    if (in_array($v, ['Ligada em nome da Smart', 'Ligada / Rateio'])) {
                        return ['bg' => 'var(--vo-success-bg)', 'text' => 'var(--vo-success-text)'];
                    }
                    if ($v === 'Ligada, necessário trocar titularidade') {
                        return ['bg' => 'var(--vo-info-bg)', 'text' => 'var(--vo-info-text)'];
                    }
                    if (in_array($v, ['Pendente, responsavel Smart', 'Boiler Instalado provisório', 'GERADOR'])) {
                        return ['bg' => 'var(--vo-warn-bg)', 'text' => 'var(--vo-warn-text)'];
                    }
                    if ($v === 'Pendente, responsavel PP') {
                        return ['bg' => 'rgba(153,27,27,.1)', 'text' => 'var(--vo-danger-text)'];
                    }
                    return ['bg' => 'var(--vo-warn-bg)', 'text' => 'var(--vo-warn-text)'];
                };
            @endphp
            <div class="vo-card">
                <div class="vo-card-head-accent" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Contas de Consumo</span>
                    <button wire:click="abrirModalConsumo" class="vo-edit-btn" title="Editar contas de consumo">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                </div>
                <div class="vo-card-body" style="padding: 6px 16px;">
                    @foreach($consumos as $item)
                        <div class="vo-consumo-row">
                            <span><span class="vo-consumo-dot" style="background: {{ $item['cor'] }};"></span>{{ $item['label'] }}</span>
                            @if($item['valor'])
                                @php $cor = $contaCor($item['valor']); @endphp
                                <span class="vo-consumo-badge" style="background: {{ $cor['bg'] }}; color: {{ $cor['text'] }};">
                                    {{ $item['valor'] }}
                                </span>
                            @else
                                <span class="vo-consumo-badge" style="background: var(--vo-warn-bg); color: var(--vo-warn-text);">Pend.</span>
                            @endif
                        </div>
                        @php
                            $docs = $documentosConsumo["Conta de {$item['label']}"] ?? collect();
                        @endphp
                        @if($item['observacoes'] || $docs->isNotEmpty())
                            <div class="vo-consumo-note-row" style="display:grid;gap:6px;padding:4px 0 10px;">
                                @if($docs->isNotEmpty())
                                    <div style="font-size:0.72rem;color:var(--vo-text-faint);">
                                        <strong>Anexos:</strong> {{ $docs->count() }}
                                        <div style="margin-top:4px; display:grid; gap:2px;">
                                            @foreach($docs as $doc)
                                                <span title="{{ $doc->arquivo_nome }}" style="display:inline-block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:normal; word-break:break-word; overflow-wrap:anywhere;">
                                                    • {{ \Illuminate\Support\Str::limit($doc->arquivo_nome, 50) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if($item['observacoes'])
                                    <span style="font-size:0.72rem;color:var(--vo-text-faint);">Observações: {{ \Illuminate\Support\Str::limit($item['observacoes'], 120) }}</span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Observações --}}
            @php
                $observacaoLimpa = $obra->observacao ? trim(strip_tags($obra->observacao)) : '';
            @endphp
            <div class="vo-card">
                <div class="vo-card-head">Observações</div>
                <div class="vo-card-body" style="font-size: 0.75rem;">
                    @if($observacaoLimpa)
                        <div style="padding: 4px 0;">{{ $observacaoLimpa }}</div>
                    @else
                        <div style="padding: 4px 0; color: var(--vo-text-faint);">Sem observações cadastradas.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>{{-- /vo-columns (Início) --}}

    {{-- ══════ Tab: Galeria ══════ --}}
    <div x-show="activeTab === 'galeria'" x-cloak style="padding: 16px 0;">
        <div wire:ignore.self
             x-data="{
            fotos: @js($this->galeriaCompleta),
            filter: 'all',
            brokenUids: [],
            addingCat: false,
            newCatName: '',
            deletingCat: null,
            deleteDestino: 'obra',
            page: 1,
            pageSize: 24,
            get customCats() {
                return $wire.fotoCategorias;
            },
            get filtered() {
                let result = this.filter === 'all' ? this.fotos : this.fotos.filter(f => f.source === this.filter);
                if (this.brokenUids.length) {
                    result = result.filter(f => !this.brokenUids.includes(f.uid));
                }
                return result;
            },
            get totalPages() {
                return Math.max(1, Math.ceil(this.filtered.length / this.pageSize));
            },
            get paginated() {
                if (this.page > this.totalPages) this.page = 1;
                const start = (this.page - 1) * this.pageSize;
                return this.filtered.slice(start, start + this.pageSize);
            },
            get availableDestinos() {
                let dests = ['obra'];
                this.customCats.forEach(c => { if (c !== this.deletingCat) dests.push(c); });
                return dests;
            },
            markBroken(uid) {
                if (!this.brokenUids.includes(uid)) {
                    this.brokenUids = [...this.brokenUids, uid];
                }
            }
        }"
        x-on:fotos-updated.window="
            fotos = $event.detail.fotos;
            brokenUids = [];
            page = 1;
            if (filter !== 'all' && !fotos.some(f => f.source === filter)) { filter = 'all'; }
        "
        x-on:galeria-filter-change.window="filter = $event.detail.filter; page = 1;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                <div class="vo-galeria-filter-bar">
                    {{-- Filtros fixos --}}
                    <button class="vo-galeria-filter-btn" :class="{ 'is-active': filter === 'all' }" @click="filter = 'all'; page = 1;">
                        Todas <span x-text="`(${fotos.length})`" style="opacity:.7;"></span>
                    </button>
                    <button class="vo-galeria-filter-btn" :class="{ 'is-active': filter === 'obra' }" @click="filter = 'obra'; page = 1;">
                        Obra <span x-text="`(${fotos.filter(f => f.source === 'obra').length})`" style="opacity:.7;"></span>
                    </button>
                    <button class="vo-galeria-filter-btn" :class="{ 'is-active': filter === 'constructin' }" @click="filter = 'constructin'; page = 1;">
                        Constructin <span x-text="`(${fotos.filter(f => f.source === 'constructin').length})`" style="opacity:.7;"></span>
                    </button>
                    <button class="vo-galeria-filter-btn" :class="{ 'is-active': filter === 'relatorio_fotografico' }" @click="filter = 'relatorio_fotografico'; page = 1;">
                        Rel. Fotográfico <span x-text="`(${fotos.filter(f => f.source === 'relatorio_fotografico').length})`" style="opacity:.7;"></span>
                    </button>
                    <button class="vo-galeria-filter-btn" :class="{ 'is-active': filter === 'visita_tecnica' }" @click="filter = 'visita_tecnica'; page = 1;">
                        Visita Técnica <span x-text="`(${fotos.filter(f => f.source === 'visita_tecnica').length})`" style="opacity:.7;"></span>
                    </button>

                    {{-- Categorias personalizadas --}}
                    <template x-for="cat in customCats" :key="cat">
                        <span style="position: relative; display: inline-flex;">
                            <button class="vo-galeria-filter-btn" :class="{ 'is-active': filter === cat }" @click="filter = cat; page = 1;">
                                <span x-text="cat" style="text-transform: capitalize;"></span>
                                <span x-text="`(${fotos.filter(f => f.source === cat).length})`" style="opacity:.7; margin-left: 2px;"></span>
                            </button>
                            <button class="vo-galeria-cat-delete"
                                    @click.stop="deletingCat = cat; deleteDestino = 'obra';"
                                    title="Remover categoria">
                                &times;
                            </button>

                            {{-- Popover de confirmação de deleção --}}
                            <div x-show="deletingCat === cat" x-cloak
                                 @click.outside="deletingCat = null"
                                 class="vo-cat-delete-popover">
                                <p style="font-size: 0.75rem; font-weight: 600; margin: 0 0 8px;">Mover fotos para:</p>
                                <select x-model="deleteDestino" class="vo-cat-delete-select">
                                    <template x-for="dest in availableDestinos" :key="dest">
                                        <option :value="dest" x-text="dest" style="text-transform: capitalize;"></option>
                                    </template>
                                </select>
                                <div style="display: flex; gap: 6px; margin-top: 8px;">
                                    <button class="vo-btn-cancel" style="font-size: 0.7rem; padding: 4px 10px;" @click="deletingCat = null">Cancelar</button>
                                    <button class="vo-btn-primary" style="font-size: 0.7rem; padding: 4px 10px;"
                                            @click="$wire.removerFotoCategoria(cat, deleteDestino); deletingCat = null;">
                                        Confirmar
                                    </button>
                                </div>
                            </div>
                        </span>
                    </template>

                    {{-- Botão + para adicionar categoria --}}
                    <span style="display: inline-flex; align-items: center; gap: 4px;">
                        <template x-if="!addingCat">
                            <button class="vo-galeria-filter-btn vo-galeria-add-cat" @click="addingCat = true; $nextTick(() => $refs.newCatInput?.focus());" title="Nova categoria">
                                +
                            </button>
                        </template>
                        <template x-if="addingCat">
                            <span style="display: inline-flex; align-items: center; gap: 4px;">
                                <input x-ref="newCatInput"
                                       x-model="newCatName"
                                       @keydown.enter="if(newCatName.trim()) { $wire.criarFotoCategoria(newCatName.trim()); newCatName = ''; addingCat = false; }"
                                       @keydown.escape="addingCat = false; newCatName = '';"
                                       class="vo-cat-input"
                                       placeholder="Nome da categoria"
                                       maxlength="30">
                                <button class="vo-galeria-filter-btn" style="padding: 5px 8px;"
                                        @click="if(newCatName.trim()) { $wire.criarFotoCategoria(newCatName.trim()); newCatName = ''; addingCat = false; }">
                                    &#10003;
                                </button>
                                <button class="vo-galeria-filter-btn" style="padding: 5px 8px;" @click="addingCat = false; newCatName = '';">
                                    &#10005;
                                </button>
                            </span>
                        </template>
                    </span>
                </div>
                <span class="vo-galeria-count" x-text="`${filtered.length} foto(s)`"></span>
            </div>

            <template x-if="filtered.length === 0">
                <div style="padding: 40px; text-align: center; font-size: 0.8rem; color: var(--vo-text-faint);">
                    Nenhuma foto encontrada para este filtro.
                </div>
            </template>

            <div class="vo-galeria-grid">
                <template x-for="(foto, idx) in paginated" :key="foto.uid">
                    <div class="vo-galeria-grid-item" @click="$dispatch('open-lightbox', { src: foto.url, fotos: filtered.map(f => f.url), paths: filtered.map(f => f.path), originals: filtered.map(f => f.original_url || f.url), idx: (page - 1) * pageSize + idx })">
                        <img :src="foto.thumb_url || foto.url" :alt="`Foto ${(page - 1) * pageSize + idx + 1}`" loading="lazy" decoding="async"
                             x-on:error="if (foto.thumb_url && $event.target.src !== foto.url) { $event.target.src = foto.url; } else if (foto.original_url && $event.target.src !== foto.original_url) { $event.target.src = foto.original_url; } else { markBroken(foto.uid); }">
                    </div>
                </template>
            </div>

            <div x-show="totalPages > 1" class="vo-galeria-pagination"
                 style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 16px;">
                <button class="vo-galeria-filter-btn" :disabled="page === 1" @click="page = Math.max(1, page - 1)">
                    Anterior
                </button>
                <span style="font-size: 0.8rem; color: var(--vo-text-muted);"
                      x-text="`Página ${page} de ${totalPages}`"></span>
                <button class="vo-galeria-filter-btn" :disabled="page === totalPages" @click="page = Math.min(totalPages, page + 1)">
                    Próxima
                </button>
            </div>
        </div>
    </div>

    {{-- ══════ Tab: RDO ══════ --}}
    <div x-data="{ async preloadRdoDetails(ids) { for (const id of ids || []) { if (id) { await $wire.loadRdoDetail(id); await new Promise(r => setTimeout(r, 120)); } } } }"
         x-on:rdos-preload.window="preloadRdoDetails($event.detail.ids || [])"
         x-show="activeTab === 'rdo'" x-cloak style="padding: 16px 0;">
        @if(! $this->record->constructin_project_id)
            <div class="vo-card">
                <div class="vo-card-body" style="padding: 30px; text-align: center; font-size: 0.8rem; color: var(--vo-text-faint);">
                    Projeto não vinculado ao Constructin. Defina o ID do projeto na página de edição.
                </div>
            </div>
        @else
            <div wire:loading.flex wire:target="loadRdos" style="padding: 30px; justify-content: center; align-items: center; gap: 8px; color: var(--vo-text-muted); font-size: 0.8rem;">
                <span class="vo-gallery-loading-spinner"></span>
                Carregando RDOs...
            </div>
            <div wire:loading.remove wire:target="loadRdos">
                @if($rdosLoaded && empty($rdosData))
                    <div class="vo-card">
                        <div class="vo-card-body" style="padding: 30px; text-align: center; font-size: 0.8rem; color: var(--vo-text-faint);">
                            Nenhum RDO disponível para este projeto.
                        </div>
                    </div>
                @elseif($rdosLoaded)
                    @php
                        $rdosTotal = count($rdosData ?? []);
                        $rdosTotalPages = max(1, (int) ceil($rdosTotal / max($rdosPerPage ?? 10, 1)));
                        $rdosPageAtual = max(1, min($rdosPage ?? 1, $rdosTotalPages));
                        $rdosPagina = collect($rdosData ?? [])->forPage($rdosPageAtual, $rdosPerPage ?? 10)->values();
                    @endphp
                    <div class="vo-rdo-list">
                        @foreach($rdosPagina as $rdo)
                            @php
                                $rdoId = (int) $rdo['id'];
                                $rdoDetalhe = $rdosDetalhes[(string) $rdoId] ?? null;
                                $rdoStatus = is_numeric($rdo['averagePercentage'] ?? null)
                                    ? ((float) $rdo['averagePercentage'] >= 100 ? 'Concluído' : 'Em andamento')
                                    : 'Sem status';
                            @endphp
                            <div class="vo-rdo-card" wire:key="rdo-{{ $rdoId }}">
                                <div class="vo-rdo-header">
                                    <div style="display:flex;flex-direction:column;gap:2px;min-width:0;">
                                        <span class="vo-rdo-date">
                                            {{ \Carbon\Carbon::parse($rdo['date'])->format('d/m/Y') }}
                                        </span>
                                        @if(! empty($rdo['title']))
                                            <span style="font-size:0.72rem;color:var(--vo-text-faint);overflow-wrap:anywhere;">
                                                {{ $rdo['title'] }}
                                            </span>
                                        @endif
                                        <span style="font-size:0.68rem;color:var(--vo-text-muted);text-transform:uppercase;letter-spacing:.04em;margin-top:2px;">
                                            Status: {{ $rdoStatus }}
                                        </span>
                                    </div>
                                    <span class="vo-rdo-pct">
                                        {{ is_numeric($rdo['averagePercentage'] ?? null) ? $rdo['averagePercentage'].'%' : '—' }}
                                    </span>
                                </div>
                                <div class="vo-rdo-progress">
                                    <div class="vo-rdo-progress-fill" style="width: {{ is_numeric($rdo['averagePercentage'] ?? null) ? min((float) $rdo['averagePercentage'], 100) : 0 }}%"></div>
                                </div>
                                <div style="display:flex;justify-content:flex-end;margin-bottom:8px;">
                                    <button type="button"
                                            wire:click="abrirModalRdo({{ $rdoId }})"
                                            style="width:28px;height:28px;border:1px solid var(--vo-border);background:var(--vo-bg);color:var(--vo-text-secondary);border-radius:999px;font-size:1rem;font-weight:800;cursor:pointer;line-height:1;">
                                        +
                                    </button>
                                </div>

                                <div style="display:none;" x-cloak>
                                    @if(! empty($rdoDetalhe['summary'] ?? []))
                                        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--vo-border-light);display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
                                            @foreach($rdoDetalhe['summary'] as $label => $value)
                                                <div style="padding:8px 10px;border:1px solid var(--vo-border-light);border-radius:10px;background:var(--vo-bg-subtle);">
                                                    <div style="font-size:0.62rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--vo-text-faint);margin-bottom:3px;font-weight:700;">
                                                        {{ \Illuminate\Support\Str::headline($label) }}
                                                    </div>
                                                    <div style="font-size:0.78rem;color:var(--vo-text-secondary);word-break:break-word;">
                                                        {{ $value }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(! empty($rdoDetalhe['activities'] ?? []))
                                        <div style="margin-top:10px;">
                                            @foreach($rdoDetalhe['activities'] as $activity)
                                                <div class="vo-rdo-activity">
                                                    <span>{{ $activity['name'] }}</span>
                                                    <span class="vo-rdo-activity-pct">
                                                        {{ is_numeric($activity['percentage'] ?? null) ? $activity['percentage'].'%' : '—' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif(in_array($rdoId, $rdosDetalhesCarregando ?? [], true))
                                        <div class="vo-rdo-activity" style="color:var(--vo-text-faint);">
                                            <span>Carregando detalhes...</span>
                                        </div>
                                    @else
                                        <div class="vo-rdo-activity" style="color:var(--vo-text-faint);">
                                            <span>Sem atividades detalhadas retornadas pela API.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-top:16px;flex-wrap:wrap;">
                        <button type="button"
                                wire:click="irParaRdoPage({{ max($rdosPageAtual - 1, 1) }})"
                                @disabled($rdosPageAtual <= 1)
                                style="border:1px solid var(--vo-border);background:var(--vo-bg);color:var(--vo-text-secondary);border-radius:999px;padding:7px 14px;font-size:0.75rem;font-weight:700;cursor:pointer;">
                            Anterior
                        </button>
                        <span style="font-size:0.78rem;color:var(--vo-text-muted);">
                            Página {{ $rdosPageAtual }} de {{ $rdosTotalPages }}
                        </span>
                        <button type="button"
                                wire:click="irParaRdoPage({{ min($rdosPageAtual + 1, $rdosTotalPages) }})"
                                @disabled($rdosPageAtual >= $rdosTotalPages)
                                style="border:1px solid var(--vo-border);background:var(--vo-bg);color:var(--vo-text-secondary);border-radius:999px;padding:7px 14px;font-size:0.75rem;font-weight:700;cursor:pointer;">
                            Próxima
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($isRetrofit)
        {{-- ══════ Tab: Pedidos Retrofit ══════ --}}
        <div x-show="activeTab === 'pedidos-retrofit'" x-cloak style="padding: 16px 0;">
            <div wire:loading.flex wire:target="loadPedidosRetrofit" style="padding: 30px; justify-content: center; align-items: center; gap: 8px; color: var(--vo-text-muted); font-size: 0.8rem;">
                <span class="vo-gallery-loading-spinner"></span>
                Carregando pedidos retrofit...
            </div>
            <div wire:loading.remove wire:target="loadPedidosRetrofit">
                @if($pedidosRetrofitLoaded ?? false)
                    @php
                        $pedidoData = $pedidosRetrofitData ?? [];
                        $pedidos = $pedidoData['pedidos'] ?? [];
                        $totalPedidos = count($pedidos);
                        $totalValorPedidos = collect($pedidos)->sum(fn ($pedido) => (float) ($pedido['valor'] ?? 0));
                    @endphp

                    @if(empty($pedidoData) || $totalPedidos === 0)
                        <div class="vo-card">
                            <div class="vo-card-body" style="padding: 30px; text-align: center; font-size: 0.8rem; color: var(--vo-text-faint);">
                                Nenhum pedido contratado encontrado para o controle retrofit desta unidade.
                            </div>
                        </div>
                    @else
                        <div class="vo-card">
                            <div class="vo-card-head-accent" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                <span>Pedidos contratados</span>
                                <span style="font-size:0.7rem;font-weight:700;white-space:nowrap;">
                                    Controle #{{ $pedidoData['numero'] ?? $pedidoData['controle_id'] }}
                                </span>
                            </div>
                            <div class="vo-card-body" style="padding: 0;">
                                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;padding:12px 16px;border-bottom:1px solid var(--vo-border-light);font-size:0.75rem;color:var(--vo-text-secondary);">
                                    <div>
                                        <div style="font-size:0.62rem;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);font-weight:700;">Status</div>
                                        <div style="font-weight:600;color:var(--vo-text);">{{ $pedidoData['status'] ?? '—' }}</div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.62rem;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);font-weight:700;">Contratação</div>
                                        <div style="font-weight:600;color:var(--vo-text);">{{ $pedidoData['contratacao'] ?? '—' }}</div>
                                    </div>
                                    <div>
                                        <div style="font-size:0.62rem;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);font-weight:700;">Quantidade</div>
                                        <div style="font-weight:600;color:var(--vo-text);">{{ $totalPedidos }} pedido(s)</div>
                                    </div>
                                </div>

                                <table style="width:100%;border-collapse:collapse;font-size:0.78rem;">
                                    <thead>
                                        <tr style="background: var(--vo-bg-subtle); text-align: left;">
                                            <th style="padding: 10px 12px; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--vo-text-faint); font-weight: 700; border-bottom: 1px solid var(--vo-border-light); width: 140px;">Grupo / A.S.</th>
                                            <th style="padding: 10px 12px; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--vo-text-faint); font-weight: 700; border-bottom: 1px solid var(--vo-border-light);">Escopo</th>
                                            <th style="padding: 10px 12px; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--vo-text-faint); font-weight: 700; border-bottom: 1px solid var(--vo-border-light); width: 180px;">Fornecedor</th>
                                            <th style="padding: 10px 12px; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--vo-text-faint); font-weight: 700; border-bottom: 1px solid var(--vo-border-light); width: 170px;">Status</th>
                                            <th style="padding: 10px 12px; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--vo-text-faint); font-weight: 700; border-bottom: 1px solid var(--vo-border-light); text-align:right; width: 160px;">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pedidos as $pedido)
                                            @php
                                                $codigoExibido = filled($pedido['codigo'] ?? null)
                                                    ? $pedido['codigo'].(filled($pedido['numero_complemento'] ?? null) ? '/'.$pedido['numero_complemento'] : '')
                                                    : '—';
                                            @endphp
                                            <tr style="border-bottom:1px solid var(--vo-border-light);">
                                                <td style="padding:10px 12px;color:var(--vo-text-secondary);font-weight:700;white-space:nowrap;">
                                                    {{ filled($pedido['grupo'] ?? null) ? $pedido['grupo'] : '—' }}
                                                    <div style="font-size:0.64rem;font-weight:600;color:var(--vo-text-faint);margin-top:2px;">
                                                        {{ $codigoExibido }}
                                                    </div>
                                                </td>
                                                <td style="padding:10px 12px;color:var(--vo-text);font-weight:600;word-break:break-word;">{{ $pedido['escopo'] ?? '—' }}</td>
                                                <td style="padding:10px 12px;color:var(--vo-text-secondary);word-break:break-word;">{{ $pedido['empresa'] ?? '—' }}</td>
                                                <td style="padding:10px 12px;">
                                                    @php
                                                        $statusInfo = $retrofitStatusFor($pedido['status'] ?? null);
                                                    @endphp
                                                    <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:9999px;font-size:0.65rem;font-weight:700;letter-spacing:0.02em;{{ $statusInfo['style'] }}white-space:nowrap;">
                                                        {{ $statusInfo['label'] }}
                                                    </span>
                                                </td>
                                                <td style="padding:10px 12px;text-align:right;font-variant-numeric:tabular-nums;color:var(--vo-text);">
                                                    R$ {{ number_format((float) ($pedido['valor'] ?? 0), 2, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background: var(--vo-bg-subtle);">
                                            <td colspan="4" style="padding: 10px 12px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--vo-text-faint); font-weight: 700; border-top: 1px solid var(--vo-border-light);">
                                                Total dos pedidos
                                            </td>
                                            <td style="padding: 10px 12px; text-align: right; font-variant-numeric: tabular-nums; color: var(--vo-text); font-weight: 700; border-top: 1px solid var(--vo-border-light);">
                                                R$ {{ number_format($totalValorPedidos, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>

                                @if(filled($pedidoData['observacoes'] ?? null))
                                    <div style="padding:12px 16px;border-top:1px solid var(--vo-border-light);font-size:0.75rem;color:var(--vo-text-secondary);">
                                        <div style="font-size:0.62rem;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);font-weight:700;margin-bottom:4px;">Observações</div>
                                        <div style="white-space:pre-line;">{{ $pedidoData['observacoes'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    {{-- ══════ Tab: Entrega Contratual ══════ --}}
    <div x-show="activeTab === 'entrega-contratual'" x-cloak style="padding: 16px 0;">
        <div wire:loading.flex wire:target="loadEntregaContratual" style="padding: 30px; justify-content: center; align-items: center; gap: 8px; color: var(--vo-text-muted); font-size: 0.8rem;">
            <span class="vo-gallery-loading-spinner"></span>
            Carregando entregas contratuais...
        </div>
        <div wire:loading.remove wire:target="loadEntregaContratual">
            @if($entregaContratualLoaded ?? false)
                @php
                    $entregas = $entregaContratualData ?? [];
                    $totalEntregas = count($entregas);
                    $custoContrato = 0;
                    $custoSemContrato = 0;
                    foreach ($entregas as $e) {
                        $custoContrato    += (float) ($e['custo_contrato']    ?? 0);
                        $custoSemContrato += (float) ($e['custo_sem_contrato'] ?? 0);
                    }
                    $custoTotal = $custoContrato + $custoSemContrato;
                    $statusOptions = $this->getEntregaContratualStatusOptions();
                    $statusCorMap = $this->getEntregaContratualStatusCorMap();
                    $previstoOptions = $this->getEntregaContratualPrevistoOptions();
                    $previstoCorMap = $this->getEntregaContratualPrevistoCorMap();
                    $previstoTipoCustoMap = $this->getEntregaContratualPrevistoTipoCustoMap();
                    $previstoProtectedMap = $this->getEntregaContratualPrevistoProtectedMap();
                    $labelStyle = 'font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--vo-text-faint); margin-bottom: 4px;';
                    $moedaInputStyle = 'width: 100%; padding: 4px 4px 4px 28px; border: none; background: transparent; color: var(--vo-text); text-align: right; border-radius: 4px; border: 1px solid transparent; transition: border 0.2s; outline: none; font-variant-numeric: tabular-nums; font-size: 0.78rem;';
                @endphp
                @if($totalEntregas === 0)
                    <div class="vo-card">
                        <div class="vo-card-body" style="padding: 30px; text-align: center; font-size: 0.8rem; color: var(--vo-text-faint);">
                            Nenhuma entrega contratual cadastrada para esta obra.
                        </div>
                    </div>
                @endif

                {{-- Lista de cards de entrega --}}
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($entregas as $e)
                        @php $eId = (int) $e['id']; @endphp
                        @php
                            $statusKey   = $e['status'] ?? 'nao_entregue';
                            $statusColor = $statusCorMap[$statusKey] ?? ($statusCorMap['nao_entregue'] ?? '#ef4444');
                            $statusLabel = $statusOptions[$statusKey] ?? $statusKey;
                            $dataValor   = $e['data_entrega'] ? \Carbon\Carbon::parse($e['data_entrega'])->format('Y-m-d') : '';

                            $previstoSlug   = $e['previsto_status']
                                ?? (! empty($e['previsto_em_contrato']) ? 'previsto_sim' : 'previsto_nao');
                            $previstoColor  = $previstoCorMap[$previstoSlug] ?? '#6b7280';
                            $previstoLabel  = $previstoOptions[$previstoSlug] ?? $previstoSlug;
                            $tipoCustoAtual = $previstoTipoCustoMap[$previstoSlug] ?? null;
                            $habilitaCustoContrato    = $tipoCustoAtual === 'contrato';
                            $habilitaCustoSemContrato = $tipoCustoAtual === 'sem_contrato';

                            $eId = (int) $e['id'];
                        @endphp
                        <div class="vo-card" style="border-radius: 10px; overflow: visible;" wire:key="entrega-card-{{ $eId }}">
                            <div class="vo-card-body" style="padding: 12px 14px;">

                                {{-- Linha 1: Tipo + Nome + pills (Previsto / Status) + botão remover --}}
                                <div style="display: flex; align-items: flex-start; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                                    <div style="flex: 0 0 160px;">
                                        <div style="{{ $labelStyle }}">Tipo</div>
                                        <input type="text"
                                            value="{{ $e['tipo'] ?? '' }}"
                                            wire:change="atualizarEntregaContratual({{ $eId }}, 'tipo', $event.target.value)"
                                            placeholder="Tipo"
                                            style="width: 100%; border: none; background: transparent; color: var(--vo-text-secondary); font-size: 0.78rem; padding: 3px 5px; border-radius: 4px; border: 1px solid transparent; transition: border 0.2s; outline: none;"
                                            class="vo-ec-input" />
                                    </div>
                                    <div style="flex: 1; min-width: 120px;">
                                        <div style="{{ $labelStyle }}">Entrega contratual</div>
                                        <input type="text"
                                            value="{{ $e['entrega'] ?? '' }}"
                                            wire:change="atualizarEntregaContratual({{ $eId }}, 'entrega', $event.target.value)"
                                            placeholder="Nome da entrega"
                                            style="width: 100%; border: none; background: transparent; color: var(--vo-text); font-weight: 600; font-size: 0.85rem; padding: 3px 5px; border-radius: 4px; border: 1px solid transparent; transition: border 0.2s; outline: none;"
                                            class="vo-ec-input" />
                                    </div>

                                    {{-- Previsto em contrato --}}
                                    <div style="flex-shrink: 0;">
                                        <div style="{{ $labelStyle }}">Previsto?</div>
                                        <div
                                            wire:key="vo-previsto-menu-{{ $eId }}-{{ $previstoSlug }}"
                                            x-data="{ open: false, toggle() { this.open = !this.open; } }"
                                            x-on:keydown.escape="open = false"
                                            x-on:click.away="open = false"
                                            style="position: relative; display: inline-block;"
                                        >
                                            <button type="button" x-on:click.stop="toggle()" aria-haspopup="listbox" :aria-expanded="open.toString()"
                                                style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; letter-spacing: 0.02em; color: #fff; border: 0; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,.08); transition: filter .12s ease; background: {{ $previstoColor }};">
                                                <span>{{ $previstoLabel }}</span>
                                                <svg style="width: 11px; height: 11px; opacity: .9;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5.25 7.5L10 12.25L14.75 7.5"/></svg>
                                            </button>
                                            <div x-show="open" x-transition.opacity.duration.120ms x-cloak
                                                style="position: absolute; left: 0; top: 100%; margin-top: 4px; z-index: 9999; min-width: 150px; padding: 6px; border-radius: 10px; background: var(--vo-bg); border: 1px solid var(--vo-border-light); box-shadow: 0 12px 32px rgba(0,0,0,.18); display: flex; flex-direction: column; gap: 2px;">
                                                @foreach($previstoOptions as $pKey => $pLabel)
                                                    @php
                                                        $pCor = $previstoCorMap[$pKey] ?? '#6b7280';
                                                        $pTipo = $previstoTipoCustoMap[$pKey] ?? null;
                                                        $custoAtualOposto = match ($pTipo) {
                                                            'contrato' => (float) ($e['custo_sem_contrato'] ?? 0),
                                                            'sem_contrato' => (float) ($e['custo_contrato'] ?? 0),
                                                            'nenhum' => (float) ($e['custo_contrato'] ?? 0) + (float) ($e['custo_sem_contrato'] ?? 0),
                                                            default => 0.0,
                                                        };
                                                    @endphp
                                                    <button type="button" wire:key="vo-previsto-option-{{ $eId }}-{{ $pKey }}"
                                                        x-on:click.stop="open = false; voMostrarConfirmacaoPrevisto({{ $eId }}, @js((string) $pKey), {{ (float) $custoAtualOposto }}, $wire)"
                                                        style="display: flex; align-items: center; gap: 8px; width: 100%; text-align: left; padding: 7px 10px; background: {{ $previstoSlug === $pKey ? 'rgba(99,102,241,.08)' : 'transparent' }}; border: 0; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; color: var(--vo-text); outline: none;">
                                                        <span style="width: 9px; height: 9px; border-radius: 9999px; background: {{ $pCor }}; flex-shrink: 0;"></span>
                                                        <span>{{ $pLabel }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Status --}}
                                    <div style="flex-shrink: 0;">
                                        <div style="{{ $labelStyle }}">Status</div>
                                        <div x-data="{ open: false }" @click.away="open = false" style="position: relative; display: inline-block;">
                                            <button type="button" @click="open = !open"
                                                style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; letter-spacing: 0.02em; background: {{ $statusColor }}; color: #fff; border: none; cursor: pointer; white-space: nowrap; transition: filter 0.12s ease; outline: none;">
                                                {{ $statusLabel }}
                                                <svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="opacity:.9;"><path d="M5.25 7.5L10 12.25L14.75 7.5"/></svg>
                                            </button>
                                            <div x-show="open" @click.stop
                                                style="position: absolute; left: 0; top: 100%; z-index: 9999; min-width: 180px; margin-top: 4px; padding: 6px; border-radius: 10px; background: var(--vo-bg); border: 1px solid var(--vo-border-light); box-shadow: 0 12px 32px rgba(0,0,0,.18); display: flex; flex-direction: column; gap: 2px;">
                                                @foreach($statusOptions as $sKey => $sLabel)
                                                    <button type="button"
                                                        @click="open = false; $wire.atualizarEntregaContratual({{ $eId }}, 'status', '{{ $sKey }}')"
                                                        style="display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; padding: 7px 10px; background: {{ $statusKey === $sKey ? 'rgba(99,102,241,.08)' : 'transparent' }}; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; color: var(--vo-text); outline: none;">
                                                        <span style="width: 9px; height: 9px; border-radius: 9999px; background: {{ $statusCorMap[$sKey] ?? '#ef4444' }}; flex-shrink: 0;"></span>
                                                        <span>{{ $sLabel }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Data de entrega --}}
                                    <div style="flex-shrink: 0;">
                                        <div style="{{ $labelStyle }}">Data de entrega</div>
                                        @if($statusKey === 'nao_entregue')
                                            <span style="font-style: italic; color: var(--vo-text-faint); font-size: 0.72rem; display: block; padding: 4px 2px;">Não possui data</span>
                                        @else
                                            <input type="date" value="{{ $dataValor }}"
                                                wire:change="atualizarEntregaContratual({{ $eId }}, 'data_entrega', $event.target.value)"
                                                style="border: 1px solid var(--vo-border-light); background: var(--vo-bg); color: var(--vo-text-secondary); padding: 4px 6px; border-radius: 6px; font-size: 0.78rem; outline: none;"
                                                class="vo-ec-input" />
                                        @endif
                                    </div>

                                    {{-- Botão remover --}}
                                    <div style="flex-shrink: 0; margin-left: auto; padding-top: 16px;">
                                        <button wire:click="removerEntregaContratual({{ $eId }})" wire:confirm="Remover esta entrega?"
                                            style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 6px; background: transparent; border: none; color: var(--vo-text-faint); cursor: pointer; outline: none;"
                                            @mouseenter="$el.style.color='#ef4444'; $el.style.backgroundColor='rgba(239,68,68,.1)'"
                                            @mouseleave="$el.style.color='var(--vo-text-faint)'; $el.style.backgroundColor='transparent'">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Linha 2: Descrições --}}
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <div style="{{ $labelStyle }}">Descrição da entrega</div>
                                        <textarea
                                            placeholder="Descrição da entrega"
                                            wire:change="atualizarEntregaContratual({{ $eId }}, 'descricao_entrega', $event.target.value)"
                                            style="width: 100%; min-height: 64px; border: 1px solid var(--vo-border-light); background: var(--vo-bg); color: var(--vo-text-secondary); padding: 6px 8px; border-radius: 6px; font-family: inherit; font-size: 0.78rem; line-height: 1.4; resize: none; outline: none; transition: border 0.2s;"
                                            class="vo-ec-input">{{ $e['descricao_entrega'] ?? '' }}</textarea>
                                    </div>
                                    <div>
                                        <div style="{{ $labelStyle }}">Descrição do existente</div>
                                        <textarea
                                            placeholder="Descrição do existente"
                                            wire:change="atualizarEntregaContratual({{ $eId }}, 'descricao_existente', $event.target.value)"
                                            style="width: 100%; min-height: 64px; border: 1px solid var(--vo-border-light); background: var(--vo-bg); color: var(--vo-text-secondary); padding: 6px 8px; border-radius: 6px; font-family: inherit; font-size: 0.78rem; line-height: 1.4; resize: none; outline: none; transition: border 0.2s;"
                                            class="vo-ec-input">{{ $e['descricao_existente'] ?? '' }}</textarea>
                                    </div>
                                </div>

                                {{-- Linha 3: Custos --}}
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border-top: 1px solid var(--vo-border-light); padding-top: 10px;">
                                    <div style="{{ ! $habilitaCustoContrato ? 'opacity: 0.4; pointer-events: none; user-select: none;' : '' }}">
                                        <div style="{{ $labelStyle }}">Custo c/ contrato</div>
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-size: 0.72rem; color: var(--vo-text-faint); pointer-events: none;">R$</span>
                                            <input type="text" inputmode="numeric" wire:key="custo-contrato-{{ $eId }}-{{ $previstoSlug }}-{{ $this->entregaContratualRefresh[$eId] ?? 0 }}"
                                                x-data="voMoedaInput({{ (float) ($e['custo_contrato'] ?? 0) }})"
                                                x-model="display"
                                                @input="aoDigitar($event)"
                                                @blur="$wire.atualizarEntregaContratual({{ $eId }}, 'custo_contrato', raw())"
                                                {{ ! $habilitaCustoContrato ? 'tabindex="-1" disabled' : '' }}
                                                style="width: 100%; padding: 5px 6px 5px 30px; border: 1px solid var(--vo-border-light); background: var(--vo-bg); color: var(--vo-text); text-align: right; border-radius: 6px; font-variant-numeric: tabular-nums; font-size: 0.78rem; outline: none; transition: border 0.2s;"
                                                class="vo-ec-input" />
                                        </div>
                                    </div>
                                    <div style="{{ ! $habilitaCustoSemContrato ? 'opacity: 0.4; pointer-events: none; user-select: none;' : '' }}">
                                        <div style="{{ $labelStyle }}">Custo s/ contrato</div>
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-size: 0.72rem; color: var(--vo-text-faint); pointer-events: none;">R$</span>
                                            <input type="text" inputmode="numeric" wire:key="custo-sem-contrato-{{ $eId }}-{{ $previstoSlug }}-{{ $this->entregaContratualRefresh[$eId] ?? 0 }}"
                                                x-data="voMoedaInput({{ (float) ($e['custo_sem_contrato'] ?? 0) }})"
                                                x-model="display"
                                                @input="aoDigitar($event)"
                                                @blur="$wire.atualizarEntregaContratual({{ $eId }}, 'custo_sem_contrato', raw())"
                                                {{ ! $habilitaCustoSemContrato ? 'tabindex="-1" disabled' : '' }}
                                                style="width: 100%; padding: 5px 6px 5px 30px; border: 1px solid var(--vo-border-light); background: var(--vo-bg); color: var(--vo-text); text-align: right; border-radius: 6px; font-variant-numeric: tabular-nums; font-size: 0.78rem; outline: none; transition: border 0.2s;"
                                                class="vo-ec-input" />
                                        </div>
                                    </div>
                                </div>

                                {{-- Linha 4: Observações --}}
                                <div style="margin-top: 10px; border-top: 1px solid var(--vo-border-light); padding-top: 10px;">
                                    <div style="{{ $labelStyle }}">Observações</div>
                                    <textarea
                                        placeholder="Observações"
                                        wire:change="atualizarEntregaContratual({{ $eId }}, 'observacoes', $event.target.value)"
                                        style="width: 100%; min-height: 64px; border: 1px solid var(--vo-border-light); background: var(--vo-bg); color: var(--vo-text-secondary); padding: 6px 8px; border-radius: 6px; font-family: inherit; font-size: 0.78rem; line-height: 1.4; resize: none; outline: none; transition: border 0.2s;"
                                        class="vo-ec-input">{{ $e['observacoes'] ?? '' }}</textarea>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Rodapé: totais + botão adicionar --}}
                @if($totalEntregas > 0)
                    <div style="margin-top: 10px; padding: 12px 14px; background: var(--vo-bg-subtle); border-radius: 10px; border: 1px solid var(--vo-border-light); display: flex; flex-wrap: wrap; align-items: center; gap: 12px;">
                        <div style="display: flex; flex-wrap: wrap; gap: 16px; flex: 1;">
                            <div>
                                <div style="{{ $labelStyle }}">Custo c/ contrato</div>
                                <span style="font-size: 0.82rem; font-weight: 700; color: var(--vo-text); font-variant-numeric: tabular-nums;">R$ {{ number_format($custoContrato, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <div style="{{ $labelStyle }}">Custo s/ contrato</div>
                                <span style="font-size: 0.82rem; font-weight: 700; color: var(--vo-text); font-variant-numeric: tabular-nums;">R$ {{ number_format($custoSemContrato, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <div style="{{ $labelStyle }}">Total geral</div>
                                <span style="font-size: 0.88rem; font-weight: 800; color: var(--vo-accent); font-variant-numeric: tabular-nums;">R$ {{ number_format($custoTotal, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <div style="margin-top: 8px;">
                    <button wire:click="adicionarEntregaContratual()"
                        style="width: 100%; padding: 10px 14px; background: transparent; border: 1px dashed var(--vo-border-light); border-radius: 10px; color: var(--vo-text-faint); font-size: 0.78rem; font-weight: 500; cursor: pointer; outline: none; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.15s, color 0.15s;"
                        @mouseenter="$el.style.backgroundColor='rgba(99,102,241,.05)'; $el.style.color='var(--vo-text-secondary)'"
                        @mouseleave="$el.style.backgroundColor='transparent'; $el.style.color='var(--vo-text-faint)'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>Adicionar entrega</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if($modalRdoOpen && $modalRdoData)
    <div class="vo-modal-overlay" wire:click.self="fecharModalRdo"
         x-data x-on:keydown.escape.window="$wire.fecharModalRdo()">
        <div class="vo-modal vo-modal-lg" style="max-width:900px;width:min(900px, calc(100vw - 32px));">
            <div class="vo-modal-head">
                <span>
                    RDO de {{ $modalRdoId && collect($rdosData)->firstWhere('id', $modalRdoId) ? \Carbon\Carbon::parse(collect($rdosData)->firstWhere('id', $modalRdoId)['date'])->format('d/m/Y') : '—' }}
                </span>
                <button wire:click="fecharModalRdo" class="vo-edit-btn" style="border-radius:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="vo-modal-body">
                @if(! empty($modalRdoData['weather'] ?? []))
                    <div style="margin-bottom:14px;">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);margin-bottom:8px;">
                            Condições Climáticas
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;">
                            @foreach($modalRdoData['weather'] as $label => $value)
                                <div style="padding:10px 12px;border:1px solid var(--vo-border-light);border-radius:10px;background:var(--vo-bg-subtle);">
                                    <div style="font-size:0.62rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--vo-text-faint);margin-bottom:4px;font-weight:700;">
                                        {{ $label }}
                                    </div>
                                    <div style="font-size:0.8rem;color:var(--vo-text-secondary);word-break:break-word;">
                                        {{ $value }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(! empty($modalRdoData['audit'] ?? []))
                    <div style="margin-bottom:14px;">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);margin-bottom:8px;">
                            Status e responsáveis
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                            @foreach([
                                'data_relatorio' => 'Data do relatório',
                                'data_criacao' => 'Data de criação',
                                'ultima_atualizacao' => 'Última atualização',
                                'criado_por' => 'Criado por',
                                'aprovado_por' => 'Aprovado por',
                                'aprovado_em' => 'Aprovado em',
                            ] as $key => $label)
                                @if(filled(data_get($modalRdoData, "audit.$key")))
                                    <div style="padding:10px 12px;border:1px solid var(--vo-border-light);border-radius:10px;background:var(--vo-bg-subtle);">
                                        <div style="font-size:0.62rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--vo-text-faint);margin-bottom:4px;font-weight:700;">
                                            {{ $label }}
                                        </div>
                                        <div style="font-size:0.8rem;color:var(--vo-text-secondary);word-break:break-word;">
                                            {{ data_get($modalRdoData, "audit.$key") }}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(filled(data_get($modalRdoData, 'summary.comments')))
                    <div style="margin-bottom:14px;">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);margin-bottom:8px;">
                            Observações
                        </div>
                        <div style="padding:10px 12px;border:1px solid var(--vo-border-light);border-radius:10px;background:var(--vo-bg-subtle);font-size:0.8rem;color:var(--vo-text-secondary);word-break:break-word;">
                            {{ data_get($modalRdoData, 'summary.comments') }}
                        </div>
                    </div>
                @endif

                @if(filled($modalRdoData['manpower'] ?? null))
                    <div style="margin-bottom:14px;">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);margin-bottom:8px;">
                            Mão de Obra
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                            @foreach($this->normalizeRdoWorkforce($modalRdoData['manpower']) as $worker)
                                <div style="padding:10px 12px;border:1px solid var(--vo-border-light);border-radius:10px;background:var(--vo-bg-subtle);">
                                    <div style="font-size:0.78rem;font-weight:700;color:var(--vo-text);margin-bottom:8px;overflow-wrap:anywhere;">
                                        {{ $worker['title'] }}
                                    </div>
                                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px 10px;">
                                        <div>
                                            <div style="font-size:0.6rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--vo-text-faint);font-weight:700;">Tipo</div>
                                            <div style="font-size:0.78rem;color:var(--vo-text-secondary);word-break:break-word;">
                                                {{ $worker['type'] ?: 'Direto' }}
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size:0.6rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--vo-text-faint);font-weight:700;">Quantidade</div>
                                            <div style="font-size:0.78rem;color:var(--vo-text-secondary);word-break:break-word;">
                                                {{ filled($worker['quantity'] ?? null) ? $worker['quantity'] : '—' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(filled($modalRdoData['equipment'] ?? null))
                    <div style="margin-bottom:14px;">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);margin-bottom:8px;">
                            Equipamentos
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
                            @foreach($this->normalizeRdoCards($modalRdoData['equipment']) as $card)
                                <div style="padding:10px 12px;border:1px solid var(--vo-border-light);border-radius:10px;background:var(--vo-bg-subtle);">
                                    <div style="font-size:0.78rem;font-weight:700;color:var(--vo-text);margin-bottom:8px;overflow-wrap:anywhere;">
                                        {{ $card['title'] }}
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr;gap:6px 10px;">
                                        <div>
                                            <div style="font-size:0.6rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--vo-text-faint);font-weight:700;">Quantidade</div>
                                            <div style="font-size:0.78rem;color:var(--vo-text-secondary);word-break:break-word;">
                                                {{ filled($card['quantity'] ?? null) ? $card['quantity'] : '—' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-faint);margin:4px 0 2px;">
                        Atividades
                    </div>
                    @if(! empty($modalRdoData['activities'] ?? []))
                        @foreach($modalRdoData['activities'] as $activity)
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--vo-border-light);font-size:0.78rem;">
                                <span style="color:var(--vo-text-secondary);">{{ $activity['name'] }}</span>
                                <span style="font-weight:700;color:var(--vo-accent);">
                                    {{ is_numeric($activity['percentage'] ?? null) ? $activity['percentage'].'%' : '—' }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <div style="font-size:0.8rem;color:var(--vo-text-faint);">
                            Nenhuma atividade detalhada retornada pela API para este RDO.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    </div>{{-- /alpine-tabs-wrapper --}}

    {{-- ══════ Modal — Pontos de Atenção ══════ --}}
    @if($modalPontosAtencaoOpen)
    <div class="vo-modal-overlay" wire:click.self="$set('modalPontosAtencaoOpen', false)"
         x-data x-on:keydown.escape.window="$wire.set('modalPontosAtencaoOpen', false)">
        <div class="vo-modal vo-modal-sm">
            <div class="vo-modal-head">
                <span>Pontos de Atenção</span>
                <button wire:click="$set('modalPontosAtencaoOpen', false)" class="vo-edit-btn" style="border-radius:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="vo-modal-body">
                <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:12px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:180px;">
                        <label class="vo-form-label">Nome da coluna</label>
                        <input type="text"
                               wire:model.defer="novaColunaPersonalizadaNome"
                               placeholder="Ex.: Terreno desocupado"
                               class="vo-form-select">
                    </div>
                    <div style="min-width:150px;">
                        <label class="vo-form-label">Tipo</label>
                        <select wire:model.defer="novaColunaPersonalizadaTipo" class="vo-form-select">
                            <option value="texto">Texto</option>
                            <option value="numero">Número</option>
                            <option value="data">Data</option>
                            <option value="select">Selecione</option>
                        </select>
                    </div>
                    @if($novaColunaPersonalizadaTipo === 'select')
                        <div style="flex:1;min-width:220px;">
                            <label class="vo-form-label">Opções do Selecione</label>
                            <input type="text"
                                   wire:model.defer="novaColunaPersonalizadaOpcoes"
                                   placeholder="Ex.: Pendente, Em análise, Concluído"
                                   class="vo-form-select">
                        </div>
                    @endif
                    <button wire:click="adicionarColunaPersonalizadaPontoAtencao" class="vo-btn-primary">
                        + Adicionar
                    </button>
                </div>

                @php
                    $colunasPontosAtencaoModal = $obra->colunasPersonalizadas()->orderBy('nome')->get();
                @endphp

                <div style="display:flex;flex-direction:column;gap:8px;max-height:320px;overflow:auto;">
                    @forelse($colunasPontosAtencaoModal as $coluna)
                        <div style="border:1px solid var(--vo-border-light);border-radius:8px;padding:8px 10px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                <div style="font-size:0.66rem;color:var(--vo-text-faint);text-transform:uppercase;letter-spacing:.05em;">
                                    {{ $coluna->nome }} ({{ ucfirst($coluna->tipo) }})
                                </div>
                                <button wire:click="removerColunaPersonalizadaPontoAtencao({{ $coluna->id }})"
                                        wire:confirm="Excluir o campo '{{ $coluna->nome }}' em todas as obras?"
                                        style="background:none;border:none;color:var(--vo-danger-text);font-size:.68rem;font-weight:600;cursor:pointer;padding:0;">
                                    Excluir
                                </button>
                            </div>

                            @if($coluna->tipo === 'data')
                                <input type="date"
                                       wire:model.defer="colunasPersonalizadasValores.{{ $coluna->id }}"
                                       class="vo-form-select"
                                       style="margin-top:6px;">
                            @elseif($coluna->tipo === 'numero')
                                <input type="number"
                                       step="0.01"
                                       wire:model.defer="colunasPersonalizadasValores.{{ $coluna->id }}"
                                       class="vo-form-select"
                                       style="margin-top:6px;">
                            @elseif($coluna->tipo === 'select')
                                <select wire:model.defer="colunasPersonalizadasValores.{{ $coluna->id }}"
                                        class="vo-form-select"
                                        style="margin-top:6px;">
                                    <option value="">— Selecione —</option>
                                    @foreach(($coluna->opcoes ?? []) as $opcao)
                                        <option value="{{ $opcao }}">{{ $opcao }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text"
                                       wire:model.defer="colunasPersonalizadasValores.{{ $coluna->id }}"
                                       class="vo-form-select"
                                       style="margin-top:6px;">
                            @endif
                        </div>
                    @empty
                        <div style="font-size:0.75rem;color:var(--vo-text-faint);">
                            Nenhuma coluna personalizada criada.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="vo-modal-foot">
                <button wire:click="$set('modalPontosAtencaoOpen', false)" class="vo-btn-cancel">Cancelar</button>
                <button wire:click="salvarPontosAtencao" class="vo-btn-primary">Salvar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ Modal — Fachada ══════ --}}
    @if($modalFachadaOpen)
    <div class="vo-modal-overlay" wire:click.self="$set('modalFachadaOpen', false)"
         x-data x-on:keydown.escape.window="$wire.set('modalFachadaOpen', false)">
        <div class="vo-modal vo-modal-sm">
            <div class="vo-modal-head">
                <span>Fachada</span>
                <button wire:click="$set('modalFachadaOpen', false)" class="vo-edit-btn" style="border-radius:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="vo-modal-body">
                <div class="vo-form-group">
                    <label class="vo-form-label">Data de instalação</label>
                    <input type="date" wire:model="fachadaDataInstalacao" class="vo-form-select">
                </div>
                <div class="vo-form-group">
                    <label class="vo-form-label">Status</label>
                    <select wire:model="fachadaStatus" class="vo-form-select">
                        <option value="">— Selecione —</option>
                        <option value="finalizada">Finalizada</option>
                        <option value="agendada">Agendada</option>
                        <option value="aguardando_contratacao">Aguardando contratação</option>
                        <option value="em_atraso">Em atraso</option>
                        <option value="com_pendencia">Com pendência</option>
                    </select>
                </div>
                <div class="vo-form-group" style="margin-bottom:0;">
                    <label class="vo-form-label">Observação</label>
                    <textarea wire:model="fachadaObservacao"
                              rows="4"
                              placeholder="Observações sobre a fachada"
                              class="vo-form-select"
                              style="resize:vertical;"></textarea>
                </div>
            </div>
            <div class="vo-modal-foot">
                <button wire:click="$set('modalFachadaOpen', false)" class="vo-btn-cancel">Cancelar</button>
                <button wire:click="salvarFachada" class="vo-btn-primary">Salvar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ Modal — Documentos ══════ --}}
    @if($modalDocumentosOpen)
    @php
        $statusDocOpts = ['pendente' => 'Pendente', 'enviado' => 'Enviado', 'nao_aplicavel' => 'Enviado'];
        $badgeDocStyle = [
            'pendente'      => 'background:var(--vo-warn-bg);color:var(--vo-warn-text)',
            'enviado'       => 'background:var(--vo-success-bg);color:var(--vo-success-text)',
            'nao_aplicavel' => 'background:var(--vo-success-bg);color:var(--vo-success-text)',
        ];
        $construtorasDaObraDoc = $this->construtorasDaObra;
        $documentosExibidos = $this->documentosExibidos;
        $usuarioAtual = auth()->user();
        $isConstrutoraUser = $usuarioAtual && $usuarioAtual->hasRole('Fornecedor');
    @endphp
    <div class="vo-modal-overlay" wire:click.self="$set('modalDocumentosOpen', false)"
         x-data x-on:keydown.escape.window="$wire.set('modalDocumentosOpen', false)">
        <div class="vo-modal vo-modal-lg">
            <div class="vo-modal-head">
                <span>Envio de Documentos</span>
                <button wire:click="$set('modalDocumentosOpen', false)" class="vo-edit-btn" style="border-radius:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="vo-modal-body" style="padding: 0;">

                {{-- Lista de documentos — separados em Contratados x Pendentes --}}
                @php
                    // Regra simples: qualquer documento persistido (já criado no banco) vai para
                    // "Contratados". Documentos virtuais (apenas nomes da lista canônica que ainda
                    // não foram atribuídos pelo gestor) vão para "Pendentes de atribuição".
                    // ARTs vinculadas ao Controle de Medição também aparecem em "Contratados"
                    // mesmo quando virtuais (são criadas automaticamente pelo controle).
                    $contratados = [];
                    $pendentes = [];
                    foreach ($documentosExibidos as $itemExibAux) {
                        $isPersAux = $itemExibAux['persistido'];
                        $nomeAux = $itemExibAux['nome'];
                        $isContratadoAux = $isPersAux || $this->isDocumentoArtTrancado($nomeAux);
                        if ($isContratadoAux) {
                            $contratados[] = $itemExibAux;
                        } else {
                            $pendentes[] = $itemExibAux;
                        }
                    }
                @endphp

                <div class="vo-doc-grid">
                    @if(count($contratados))
                        <div class="vo-doc-section-title">
                            <span class="vo-doc-section-title-text">Contratados</span>
                            <span class="vo-doc-section-count">{{ count($contratados) }}</span>
                        </div>
                        @foreach($contratados as $idx => $itemExib)
                            @php $borderRight = $idx % 2 === 0 ? 'border-right:1px solid var(--vo-border-light);' : ''; @endphp
                            @include('filament.pages.obras.partials._documento-card', [
                                'itemExib' => $itemExib,
                                'borderRight' => $borderRight,
                                'statusDocOpts' => $statusDocOpts,
                                'badgeDocStyle' => $badgeDocStyle,
                                'construtorasDaObraDoc' => $construtorasDaObraDoc,
                                'documentosAtribuirAbertos' => $documentosAtribuirAbertos,
                                'documentosVirtuaisAtribuirAbertos' => $documentosVirtuaisAtribuirAbertos,
                                'documentosUploadBufferPorDoc' => $documentosUploadBufferPorDoc,
                                'documentosUploadInputVersion' => $documentosUploadInputVersion,
                            ])
                        @endforeach
                    @endif

                    @if(count($pendentes))
                        <div class="vo-doc-section-title">
                            <span class="vo-doc-section-title-text">Pendentes de atribuição</span>
                            <span class="vo-doc-section-count">{{ count($pendentes) }}</span>
                        </div>
                        @foreach($pendentes as $idx => $itemExib)
                            @php $borderRight = $idx % 2 === 0 ? 'border-right:1px solid var(--vo-border-light);' : ''; @endphp
                            @include('filament.pages.obras.partials._documento-card', [
                                'itemExib' => $itemExib,
                                'borderRight' => $borderRight,
                                'statusDocOpts' => $statusDocOpts,
                                'badgeDocStyle' => $badgeDocStyle,
                                'construtorasDaObraDoc' => $construtorasDaObraDoc,
                                'documentosAtribuirAbertos' => $documentosAtribuirAbertos,
                                'documentosVirtuaisAtribuirAbertos' => $documentosVirtuaisAtribuirAbertos,
                                'documentosUploadBufferPorDoc' => $documentosUploadBufferPorDoc,
                                'documentosUploadInputVersion' => $documentosUploadInputVersion,
                            ])
                        @endforeach
                    @endif

                    @if(count($contratados) === 0 && count($pendentes) === 0)
                        <div style="grid-column:span 2;padding:20px;text-align:center;font-size:0.75rem;color:var(--vo-text-faint);">
                            Nenhum documento cadastrado. Adicione abaixo.
                        </div>
                    @endif
                </div>

                {{-- Bloco antigo desativado (mantido fora da árvore para diff visual). --}}
                @if(false)
                <div class="vo-doc-grid-legacy" style="display:none">
                    @forelse([] as $idx => $itemExib)
                        @php
                            $borderRight = $idx % 2 === 0 ? 'border-right:1px solid var(--vo-border-light);' : '';
                            $isPersistido = $itemExib['persistido'];
                            $doc = $itemExib['doc'];
                            $nomeDoc = $itemExib['nome'];
                            $docCategoria = $this->categoriaDoDocumento($nomeDoc);
                            $isCnpjDoc = $nomeDoc === 'CNPJ (definitivo)';
                            $cnpjEhDefinitivo = $obra->projeto?->status_cnpj === 'definitivo';
                            $docNomeLabel = $isCnpjDoc ? 'CNPJ' : $nomeDoc;

                            if ($isPersistido) {
                                $docBadgeLabel = $isCnpjDoc
                                    ? ($cnpjEhDefinitivo ? 'Definitivo' : 'Provisório')
                                    : ($statusDocOpts[$doc->status] ?? $doc->status);
                                $docBadgeStyle = $isCnpjDoc
                                    ? ($cnpjEhDefinitivo ? $badgeDocStyle['enviado'] : $badgeDocStyle['pendente'])
                                    : ($badgeDocStyle[$doc->status] ?? '');
                                $permiteSelectFornecedor = $docCategoria === 'construtora'
                                    || ($docCategoria === 'manual' && (filled($doc->construtora_id) || in_array($doc->id, $documentosAtribuirAbertos, true)));
                                $permiteRemover = $this->podeGerenciarDocumentos && $docCategoria !== 'automatico';
                            } else {
                                $docBadgeLabel = 'Pendente';
                                $docBadgeStyle = $badgeDocStyle['pendente'];
                                $permiteSelectFornecedor = $docCategoria === 'construtora'
                                    || ($docCategoria === 'manual' && in_array($nomeDoc, $documentosVirtuaisAtribuirAbertos, true));
                                $permiteRemover = false;
                            }
                        @endphp
                        <div class="vo-doc-item" style="{{ $borderRight }}">
                            <div class="vo-doc-head">
                                <span class="vo-doc-name">{{ $docNomeLabel }}</span>
                                <span
                                        style="font-size:0.6rem;padding:2px 8px;border-radius:1rem;font-weight:700;flex-shrink:0;{{ $docBadgeStyle }}">
                                    {{ $docBadgeLabel }}
                                </span>
                                @if($permiteRemover)
                                    <button wire:click="removerDocumento({{ $doc->id }})"
                                            wire:confirm="Tem certeza que deseja remover o documento '{{ $doc->nome }}'? Esta ação não pode ser desfeita."
                                            title="Remover"
                                            style="background:none;border:none;cursor:pointer;color:var(--vo-danger-text);padding:2px 4px;border-radius:4px;display:flex;align-items:center;flex-shrink:0;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                    </button>
                                @endif
                            </div>

                            {{-- Select de fornecedor: persistido ou virtual --}}
                            @if($this->podeGerenciarDocumentos && $permiteSelectFornecedor)
                                <div style="padding:4px 0;">
                                    @if($isPersistido)
                                        <select
                                            class="vo-form-select"
                                            style="margin:0;font-size:0.7rem;padding:3px 6px;width:100%;"
                                            wire:model="documentosConstrutoraEdit.{{ $doc->id }}"
                                            title="Fornecedor responsável"
                                        >
                                            <option value="">— Sem fornecedor —</option>
                                            @foreach($construtorasDaObraDoc as $cId => $cNome)
                                                <option value="{{ $cId }}">{{ $cNome }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <select
                                            class="vo-form-select"
                                            style="margin:0;font-size:0.7rem;padding:3px 6px;width:100%;"
                                            wire:model="documentosVirtuaisConstrutoraEdit.{{ $nomeDoc }}"
                                            title="Fornecedor responsável"
                                        >
                                            <option value="">— Sem fornecedor —</option>
                                            @foreach($construtorasDaObraDoc as $cId => $cNome)
                                                <option value="{{ $cId }}">{{ $cNome }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @elseif($this->podeGerenciarDocumentos && $docCategoria === 'manual')
                                <div style="padding:2px 0;">
                                    @if($isPersistido)
                                        <button type="button"
                                                wire:click="abrirAtribuicaoFornecedor({{ $doc->id }})"
                                                style="background:none;border:1px dashed var(--vo-border);color:var(--vo-text-muted);font-size:0.62rem;padding:2px 8px;border-radius:4px;cursor:pointer;">
                                            + Atribuir a um fornecedor?
                                        </button>
                                    @else
                                        <button type="button"
                                                wire:click="abrirAtribuicaoFornecedorVirtual('{{ $nomeDoc }}')"
                                                style="background:none;border:1px dashed var(--vo-border);color:var(--vo-text-muted);font-size:0.62rem;padding:2px 8px;border-radius:4px;cursor:pointer;">
                                            + Atribuir a um fornecedor?
                                        </button>
                                    @endif
                                </div>
                            @endif

                            <div class="vo-doc-row">
                                <div style="flex:1;min-width:0;">
                                    @if($isCnpjDoc)
                                        @php
                                            $cnpjValor = $cnpjEhDefinitivo
                                                ? $obra->projeto?->cnpj
                                                : $obra->projeto?->cnpj_provisorio;
                                        @endphp
                                        @if(filled($cnpjValor))
                                            <span style="font-size:0.72rem;font-weight:700;color:var(--vo-text);font-family:ui-monospace,Menlo,monospace;">{{ $cnpjValor }}</span>
                                        @else
                                            <span class="vo-doc-empty">Nenhum CNPJ cadastrado</span>
                                        @endif
                                    @elseif($isPersistido)
                                        @php
                                            $arquivosNomes = $doc->arquivos_nomes_resolved;
                                            $podeAnexarItem = $this->podeAnexarDocumentoBlade($doc);
                                        @endphp
                                        @if(count($arquivosNomes))
                                            <div class="vo-doc-attachments">
                                                @foreach($arquivosNomes as $index => $arquivoNome)
                                                    <div class="vo-doc-attachment" title="{{ $arquivoNome }}">
                                                        <span class="vo-doc-attachment-icon">
                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                        </span>
                                                        <span class="vo-doc-attachment-name">{{ $arquivoNome }}</span>
                                                        <span class="vo-doc-attachment-actions">
                                                            <button type="button"
                                                                    wire:click="abrirArquivoDocumento({{ $doc->id }}, {{ $index }})"
                                                                    class="vo-doc-attachment-btn view"
                                                                    title="Visualizar">
                                                                Ver
                                                            </button>
                                                            @if($podeAnexarItem)
                                                                <button type="button"
                                                                        wire:click="removerArquivoDocumento({{ $doc->id }}, {{ $index }})"
                                                                        wire:confirm="Remover este arquivo?"
                                                                        class="vo-doc-attachment-btn remove"
                                                                        title="Remover">
                                                                    Remover
                                                                </button>
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="vo-doc-empty">Sem anexo</span>
                                        @endif
                                    @else
                                        <span class="vo-doc-empty">Sem anexo</span>
                                    @endif
                                </div>

                                @if(! $isCnpjDoc && ! $isPersistido)
                                    <span class="vo-doc-empty">Atribua um fornecedor para criar este item</span>
                                @endif
                            </div>

                            {{-- Bloco de upload (somente para documentos persistidos, não-CNPJ, não-automaticos) --}}
                            @if($isPersistido && ! $isCnpjDoc && $docCategoria !== 'automatico')
                                @php
                                    $podeAnexar = $this->podeAnexarDocumentoBlade($doc);
                                    $bufferDoc = $documentosUploadBufferPorDoc[$doc->id] ?? [];
                                    $countBuffer = is_array($bufferDoc) ? count($bufferDoc) : 0;
                                @endphp
                                @if($podeAnexar)
                                    <div style="padding:8px 0 2px 0;border-top:1px dashed var(--vo-border-light);margin-top:8px;">
                                        <div style="font-size:0.6rem;font-weight:700;color:var(--vo-text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Anexar PDFs</div>

                                        <div class="vo-doc-upload-wrap">
                                            <button type="button" class="vo-doc-upload-btn">Selecionar PDF(s)</button>
                                            <span class="vo-doc-upload-name">
                                                @if($countBuffer)
                                                    {{ $countBuffer }} arquivo{{ $countBuffer > 1 ? 's' : '' }} pronto{{ $countBuffer > 1 ? 's' : '' }} para envio
                                                @else
                                                    Nenhum arquivo selecionado
                                                @endif
                                            </span>
                                            <input type="file"
                                                   accept="application/pdf,.pdf"
                                                   multiple
                                                   wire:key="upload-input-{{ $doc->id }}-{{ $documentosUploadInputVersion }}"
                                                   wire:model="documentosUploadPorDoc.{{ $doc->id }}"
                                                   class="vo-doc-upload-input">
                                        </div>

                                        @if($countBuffer)
                                            <div class="vo-file-list" style="margin-top:6px;">
                                                @foreach($bufferDoc as $bufIdx => $tmpFile)
                                                    <div class="vo-file-item">
                                                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $tmpFile->getClientOriginalName() }}</span>
                                                        <button type="button"
                                                                wire:click="removerArquivoBuffer({{ $doc->id }}, {{ $bufIdx }})"
                                                                class="vo-file-item-action"
                                                                style="color:var(--vo-danger-text);"
                                                                title="Remover da seleção">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="3 6 5 6 21 6"/>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div style="display:flex;gap:6px;margin-top:8px;align-items:center;">
                                            <button type="button"
                                                    wire:click="fazerUploadDocumento({{ $doc->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="fazerUploadDocumento({{ $doc->id }})"
                                                    @disabled($countBuffer === 0)
                                                    style="background:{{ $countBuffer ? 'var(--vo-accent)' : 'var(--vo-border-light)' }};color:#111;border:none;padding:6px 12px;border-radius:6px;font-weight:700;font-size:0.7rem;cursor:{{ $countBuffer ? 'pointer' : 'not-allowed' }};opacity:{{ $countBuffer ? '1' : '.55' }};">
                                                <span wire:loading.remove wire:target="fazerUploadDocumento({{ $doc->id }})">
                                                    Enviar{{ $countBuffer > 1 ? ' '.$countBuffer.' arquivos' : '' }}
                                                </span>
                                                <span wire:loading wire:target="fazerUploadDocumento({{ $doc->id }})">Enviando…</span>
                                            </button>
                                            @error('documentosUploadBufferPorDoc.'.$doc->id.'.*')
                                                <span style="font-size:0.62rem;color:var(--vo-danger-text);">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="vo-doc-help" style="margin-top:4px;">Apenas PDF, máx 50MB cada. Pode selecionar várias vezes — os arquivos se acumulam.</div>
                                    </div>
                                @endif
                            @endif

                        </div>
                    @empty
                        <div style="grid-column:span 2;padding:20px;text-align:center;font-size:0.75rem;color:var(--vo-text-faint);">
                            Nenhum documento cadastrado. Adicione abaixo.
                        </div>
                    @endforelse
                </div>
                @endif
                {{-- /Bloco antigo desativado --}}

                {{-- Adicionar novo --}}
                @if($this->podeGerenciarDocumentos)
                    <div style="padding:14px 20px;border-top:2px solid var(--vo-border);background:var(--vo-bg-subtle);">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-muted);margin-bottom:8px;">Adicionar documento</div>
                        <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
                            <div style="flex:1;min-width:180px;">
                                <input wire:model="novoDocNome"
                                       wire:keydown.enter="adicionarDocumento"
                                       type="text"
                                       placeholder="Nome do documento"
                                       class="vo-form-select"
                                       style="margin:0;">
                            </div>
                            @if($novoDocAtribuirFornecedor)
                                <div style="flex:0 0 200px;">
                                    <select wire:model="novoDocConstrutoraId" class="vo-form-select" style="margin:0;">
                                        <option value="">— Sem fornecedor —</option>
                                        @foreach($construtorasDaObraDoc as $cId => $cNome)
                                            <option value="{{ $cId }}">{{ $cNome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <button type="button"
                                        wire:click="$set('novoDocAtribuirFornecedor', true)"
                                        style="flex:0 0 auto;background:none;border:1px dashed var(--vo-border);color:var(--vo-text-muted);font-size:0.72rem;padding:8px 12px;border-radius:6px;cursor:pointer;white-space:nowrap;">
                                    + Atribuir a um fornecedor?
                                </button>
                            @endif
                            <button wire:click="adicionarDocumento"
                                    style="flex-shrink:0;background:var(--vo-accent);color:#111;border:none;padding:8px 14px;border-radius:6px;font-weight:700;font-size:0.8rem;cursor:pointer;white-space:nowrap;">
                                + Adicionar
                            </button>
                        </div>
                    </div>
                @endif

            </div>

            <div class="vo-modal-foot">
                <button wire:click="$set('modalDocumentosOpen', false)" class="vo-btn-cancel">Fechar</button>
                @if($this->podeGerenciarDocumentos)
                    <button wire:click="salvarDocumentos" class="vo-btn-primary">Salvar</button>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ Modal — Anexos do documento (popup do card resumo) ══════ --}}
    @if($modalAnexosDocOpen && $modalAnexosDocId)
        @php
            $docAnexos = \App\Models\ObraDocumento::query()
                ->where('id', $modalAnexosDocId)
                ->where('obra_id', $obra->id)
                ->first();
            $anexosNomes = $docAnexos ? $docAnexos->arquivos_nomes_resolved : [];
            $anexosPaths = $docAnexos ? $docAnexos->arquivos_paths_resolved : [];
            $podeAnexarAnexo = $docAnexos ? $this->podeAnexarDocumentoBlade($docAnexos) : false;
        @endphp
        <div class="vo-modal-overlay" wire:click.self="fecharModalAnexosDocumento"
             x-data x-on:keydown.escape.window="$wire.fecharModalAnexosDocumento()">
            <div class="vo-modal vo-modal-sm">
                <div class="vo-modal-head">
                    <span style="display:flex;align-items:center;gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Anexos do documento
                    </span>
                    <button wire:click="fecharModalAnexosDocumento" class="vo-edit-btn" style="border-radius:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="vo-modal-body">
                    @if(! $docAnexos)
                        <div class="vo-doc-empty">Documento não encontrado.</div>
                    @else
                        <div style="font-size:0.85rem;font-weight:700;color:var(--vo-text);margin-bottom:4px;">{{ $docAnexos->nome }}</div>
                        @if($docAnexos->construtora)
                            <div style="font-size:0.7rem;color:var(--vo-text-muted);margin-bottom:12px;">
                                Fornecedor: <strong>{{ $docAnexos->construtora->nome }}</strong>
                            </div>
                        @endif

                        @if(count($anexosNomes) === 0)
                            <div class="vo-doc-empty">Nenhum arquivo anexado ainda.</div>
                        @else
                            <div class="vo-doc-attachments">
                                @foreach($anexosNomes as $idxAnexo => $arquivoNome)
                                    <div class="vo-doc-attachment" title="{{ $arquivoNome }}">
                                        <span class="vo-doc-attachment-icon">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        </span>
                                        <span class="vo-doc-attachment-name">{{ $arquivoNome }}</span>
                                        <span class="vo-doc-attachment-actions">
                                            <button type="button"
                                                    wire:click="abrirArquivoDocumento({{ $docAnexos->id }}, {{ $idxAnexo }})"
                                                    class="vo-doc-attachment-btn view"
                                                    title="Visualizar">
                                                Ver
                                            </button>
                                            @if($podeAnexarAnexo)
                                                <button type="button"
                                                        wire:click="removerArquivoDocumento({{ $docAnexos->id }}, {{ $idxAnexo }})"
                                                        wire:confirm="Remover este arquivo?"
                                                        class="vo-doc-attachment-btn remove"
                                                        title="Remover">
                                                    Remover
                                                </button>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
                <div class="vo-modal-foot">
                    <button wire:click="fecharModalAnexosDocumento" class="vo-btn-cancel">Fechar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════ Modal — Recebimentos ══════ --}}
    @if($modalRecebimentosOpen)
    @php
        $statusRecOpts = [
            'pendente' => 'Pendente',
            'recebido' => 'Recebido',
            'nao_aplicavel' => 'Recebido',
        ];
        $badgeRecStyle = [
            'pendente'      => 'background:var(--vo-warn-bg);color:var(--vo-warn-text)',
            'recebido'      => 'background:var(--vo-success-bg);color:var(--vo-success-text)',
            'nao_aplicavel' => 'background:var(--vo-success-bg);color:var(--vo-success-text)',
        ];
        $construtorasDaObra = $this->construtorasDaObra;
        $itensPadraoPendentes = $this->itensPadraoRecebimentoPendentes;
    @endphp
    <div class="vo-modal-overlay" wire:click.self="$set('modalRecebimentosOpen', false)"
         x-data x-on:keydown.escape.window="$wire.set('modalRecebimentosOpen', false)">
        <div class="vo-modal vo-modal-lg">
            <div class="vo-modal-head">
                <span>Controle de Recebimentos</span>
                <button wire:click="$set('modalRecebimentosOpen', false)" class="vo-edit-btn" style="border-radius:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="vo-modal-body" style="padding: 0;">

                {{-- Itens padrão pendentes — atribua o fornecedor pra criar o recebimento --}}
                @if(count($itensPadraoPendentes) > 0)
                    <div style="padding:12px 20px;border-bottom:1px solid var(--vo-border);background:var(--vo-bg-subtle);">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-muted);margin-bottom:8px;">
                            Itens padrão ({{ count($itensPadraoPendentes) }} pendentes — selecione um fornecedor e clique em Salvar)
                        </div>

                        @if(empty($construtorasDaObra))
                            <div style="font-size:0.72rem;color:var(--vo-danger-text);background:var(--vo-danger-bg);padding:8px 12px;border-radius:6px;">
                                Nenhum fornecedor cadastrado. <a href="{{ \App\Filament\Resources\ConstrutoraResource::getUrl('create') }}" style="text-decoration:underline;color:inherit;font-weight:700;">Cadastrar fornecedor</a>.
                            </div>
                        @else
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;">
                                @foreach($itensPadraoPendentes as $nomePadrao)
                                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0;" wire:key="padrao-{{ md5($nomePadrao) }}">
                                        <span style="flex:1;font-size:0.76rem;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $nomePadrao }}</span>
                                        <select
                                            class="vo-form-select"
                                            style="margin:0;flex-shrink:0;min-width:140px;max-width:200px;font-size:0.72rem;padding:4px 6px;"
                                            wire:model="recebimentosPadraoSelecao.{{ $nomePadrao }}"
                                        >
                                            <option value="">— Sem fornecedor —</option>
                                            @foreach($construtorasDaObra as $cId => $cNome)
                                                <option value="{{ $cId }}">{{ $cNome }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Lista de recebimentos — 2 colunas --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;">
                    @forelse($obra->recebimentos as $rec)
                        @php
                            $borderRight = $loop->index % 2 === 0 ? 'border-right:1px solid var(--vo-border-light);' : '';
                        @endphp
                        <div style="display:flex;align-items:center;gap:8px;padding:7px 16px;border-bottom:1px solid var(--vo-border-light);{{ $borderRight }}">
                            {{-- Nome --}}
                            <span style="flex:1;display:flex;flex-direction:column;gap:4px;min-width:0;">
                                <span style="font-size:0.76rem;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $rec->nome }}</span>
                                <span style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:0.65rem;color:var(--vo-text-faint);">
                                    {{-- Select de construtora (edição em buffer; commit ao clicar em Salvar) --}}
                                    <select
                                        class="vo-form-select"
                                        style="margin:0;font-size:0.62rem;padding:2px 4px;max-width:160px;"
                                        wire:model="recebimentosConstrutoraEdit.{{ $rec->id }}"
                                        title="Fornecedor responsável"
                                    >
                                        <option value="">— Sem fornecedor —</option>
                                        @foreach($construtorasDaObra as $cId => $cNome)
                                            <option value="{{ $cId }}">{{ $cNome }}</option>
                                        @endforeach
                                    </select>

                                    @if($rec->hasFotoEntrega())
                                        <button
                                            type="button"
                                            wire:click="abrirArquivoRecebimento({{ $rec->id }}, 'foto')"
                                            style="background:var(--vo-info-bg);color:var(--vo-info-text);border:none;padding:2px 8px;border-radius:999px;font-size:0.62rem;font-weight:700;cursor:pointer;"
                                        >
                                            Foto da entrega
                                        </button>
                                    @endif

                                    @if($rec->hasNotaFiscal())
                                        <button
                                            type="button"
                                            wire:click="abrirArquivoRecebimento({{ $rec->id }}, 'nota')"
                                            style="background:var(--vo-success-bg);color:var(--vo-success-text);border:none;padding:2px 8px;border-radius:999px;font-size:0.62rem;font-weight:700;cursor:pointer;"
                                        >
                                            Nota fiscal
                                        </button>
                                    @endif
                                </span>
                            </span>

                            {{-- Badge de status (somente visual) --}}
                            <span
                                style="font-size:0.6rem;padding:2px 8px;border-radius:1rem;font-weight:700;flex-shrink:0;{{ $badgeRecStyle[$rec->status] ?? '' }}"
                            >
                                {{ \App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource::getStatusLabel($rec->status) }}
                            </span>

                            {{-- Excluir --}}
                            <button wire:click="removerRecebimento({{ $rec->id }})"
                                    wire:confirm="Remover '{{ $rec->nome }}'?"
                                    title="Remover"
                                    style="background:none;border:none;cursor:pointer;color:var(--vo-danger-text);padding:2px 4px;border-radius:4px;display:flex;align-items:center;flex-shrink:0;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                            </button>
                        </div>
                    @empty
                        <div style="grid-column:span 2;padding:20px;text-align:center;font-size:0.75rem;color:var(--vo-text-faint);">
                            Nenhum item cadastrado. Atribua um item padrão acima ou adicione abaixo.
                        </div>
                    @endforelse
                </div>

                {{-- Adicionar novo --}}
                <div style="padding:14px 20px;border-top:2px solid var(--vo-border);background:var(--vo-bg-subtle);">
                    <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--vo-text-muted);margin-bottom:8px;">Adicionar item</div>
                    <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
                        <div style="flex:1;min-width:180px;">
                            <input wire:model="novoRecNome"
                                   wire:keydown.enter="adicionarRecebimento"
                                   type="text"
                                   placeholder="Nome do item"
                                   class="vo-form-select"
                                   style="margin:0;">
                        </div>
                        <div style="flex:0 0 200px;">
                            <select wire:model="novoRecConstrutoraId" class="vo-form-select" style="margin:0;">
                                <option value="">— Sem fornecedor —</option>
                                @foreach($construtorasDaObra as $cId => $cNome)
                                    <option value="{{ $cId }}">{{ $cNome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button wire:click="adicionarRecebimento"
                                style="flex-shrink:0;background:var(--vo-accent);color:#111;border:none;padding:8px 14px;border-radius:6px;font-weight:700;font-size:0.8rem;cursor:pointer;white-space:nowrap;">
                            + Adicionar
                        </button>
                    </div>
                </div>

            </div>

            <div class="vo-modal-foot">
                <button wire:click="$set('modalRecebimentosOpen', false)" class="vo-btn-cancel">Fechar</button>
                <button wire:click="salvarRecebimentos" class="vo-btn-primary">Salvar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ Modal — Contas de Consumo ══════ --}}
    @if($modalConsumoOpen)
    <div class="vo-modal-overlay" wire:click.self="$set('modalConsumoOpen', false)"
         x-data x-on:keydown.escape.window="$wire.set('modalConsumoOpen', false)">
        <div class="vo-modal vo-modal-lg">
            <div class="vo-modal-head">
                <span>Contas de Consumo</span>
                <button wire:click="$set('modalConsumoOpen', false)" class="vo-edit-btn" style="border-radius:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="vo-modal-body">
                @php
                    $contas = [
                        [
                            'label' => 'Energia',
                            'field' => 'consumoEnergia',
                            'obs' => 'consumoEnergiaObservacoes',
                            'buffer' => 'consumoUploadEnergiaBuffer',
                            'files' => $consumoUploadEnergia,
                            'options' => [
                                'Ligada / Rateio',
                                'Ligada em nome da Smart',
                                'Ligada, necessário trocar titularidade',
                                'Pendente, responsavel Smart',
                                'Pendente, responsavel PP',
                                'GERADOR',
                            ],
                        ],
                        [
                            'label' => 'Água',
                            'field' => 'consumoAgua',
                            'obs' => 'consumoAguaObservacoes',
                            'buffer' => 'consumoUploadAguaBuffer',
                            'files' => $consumoUploadAgua,
                            'options' => [
                                'Ligada em nome da Smart',
                                'Ligada, necessário trocar titularidade',
                                'Pendente, responsavel Smart',
                                'Pendente, responsavel PP',
                                'Ligada / Rateio',
                            ],
                        ],
                        [
                            'label' => 'Gás',
                            'field' => 'consumoGas',
                            'obs' => 'consumoGasObservacoes',
                            'buffer' => 'consumoUploadGasBuffer',
                            'files' => $consumoUploadGas,
                            'options' => [
                                'Ligada em nome da Smart',
                                'Ligada, necessário trocar titularidade',
                                'Pendente, responsavel Smart',
                                'Pendente, responsavel PP',
                                'Boiler Instalado provisório',
                            ],
                        ],
                    ];
                @endphp

                <div class="vo-consumo-grid">
                    @foreach($contas as $conta)
                        <div class="vo-consumo-card">
                            <div class="vo-form-group">
                                <label class="vo-form-label">{{ $conta['label'] }}</label>
                                <select wire:model.defer="{{ $conta['field'] }}" class="vo-form-select">
                                    <option value="">— Selecione —</option>
                                    @foreach($conta['options'] as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="vo-form-group">
                                <label class="vo-form-label">{{ $conta['label'] }} - Anexos</label>
                                
                                @php
                                    $docsKey = match($conta['label']) {
                                        'Energia' => 'consumoDocumentosEnergia',
                                        'Água' => 'consumoDocumentosAgua',
                                        'Gás' => 'consumoDocumentosGas',
                                        default => 'consumoDocumentosEnergia',
                                    };
                                    $docsExistentes = $this->$docsKey ?? [];
                                @endphp
                                
                                @if(count($docsExistentes) > 0)
                                    <div style="margin-bottom: 12px;">
                                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--vo-text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Anexos Existentes</div>
                                        <div class="vo-file-list">
                                            @foreach($docsExistentes as $doc)
                                                <div class="vo-file-item">
                                                    <span title="{{ $doc['arquivo_nome'] }}">{{ $doc['arquivo_nome'] }}</span>
                                                    <div style="display: flex; gap: 6px; flex-shrink: 0;">
                                                        <button type="button"
                                                                wire:click="$call('visualizarDocumentoConsumo', {{ $doc['id'] }})"
                                                                class="vo-file-item-action"
                                                                title="Visualizar">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                        </button>
                                                        <button type="button"
                                                                wire:click="removerDocumentoConsumo('{{ $conta['label'] }}', {{ $doc['id'] }})"
                                                                class="vo-file-item-action"
                                                                style="color: var(--vo-danger-text);"
                                                                title="Remover">
                                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                <div style="font-size: 0.75rem; font-weight: 600; color: var(--vo-text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Novos Anexos</div>
                                <div class="vo-doc-upload-wrap">
                                    <button type="button" class="vo-doc-upload-btn">Selecionar PDF(s)</button>
                                    <span class="vo-doc-upload-name">
                                        @if(count($conta['files']))
                                            {{ count($conta['files']) }} arquivo(s) selecionado(s)
                                        @else
                                            Nenhum arquivo selecionado
                                        @endif
                                    </span>
                                    <input wire:key="{{ $conta['buffer'] }}-{{ $consumoUploadVersion }}"
                                           wire:model="{{ $conta['buffer'] }}"
                                           type="file"
                                           accept="application/pdf,.pdf"
                                           class="vo-doc-upload-input"
                                           multiple>
                                </div>
                                <div class="vo-doc-help">Apenas PDF. Tamanho máximo 50MB por arquivo. Selecione vários arquivos de uma vez ou adicione mais depois.</div>
                                @error($conta['buffer'])<div class="vo-form-error">{{ $message }}</div>@enderror
                                @error($conta['buffer'].'.*')<div class="vo-form-error">{{ $message }}</div>@enderror

                                @if(count($conta['files']))
                                    <div class="vo-file-list">
                                        @foreach($conta['files'] as $index => $arquivo)
                                            <div class="vo-file-item">
                                                <span>{{ $arquivo->getClientOriginalName() }}</span>
                                                <button type="button" wire:click="removerArquivoConsumo('{{ $conta['label'] }}', {{ $index }})" class="vo-file-item-action" title="Remover">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="vo-form-group" style="margin-bottom:0;">
                                <label class="vo-form-label">{{ $conta['label'] }} - Observações</label>
                                <textarea wire:model.defer="{{ $conta['obs'] }}"
                                          class="vo-form-textarea"
                                          placeholder="Observações sobre {{ strtolower($conta['label']) }}"></textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="vo-modal-foot">
                <button wire:click="$set('modalConsumoOpen', false)" class="vo-btn-cancel">Cancelar</button>
                <button wire:click="salvarConsumo" class="vo-btn-primary">Salvar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ Modal — Detalhe do Item (somente leitura) ══════ --}}
    @if($modalDetalheItemOpen)
    @php
        $d = $detalheItemDados;
        $fmtMoney = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
        $fmtPct = fn ($v) => number_format((float) $v, 2, ',', '.').'%';
        $tituloAs = trim(($d['numero_as'] ?? '').(!empty($d['numero_complemento']) ? ' '.$d['numero_complemento'] : ''));
    @endphp
    <div class="vo-modal-overlay" wire:click.self="$set('modalDetalheItemOpen', false)"
         x-data x-on:keydown.escape.window="$wire.set('modalDetalheItemOpen', false)">
        <div class="vo-modal vo-modal-lg">
            <div class="vo-modal-head">
                <span>Detalhes do Item {{ ($d['tipo'] ?? '') === 'auxiliar' ? '(Extra Contratual)' : '(Contratual)' }}</span>
                <button @click="$wire.set('modalDetalheItemOpen', false)" class="vo-edit-btn" style="border-radius:4px;" title="Fechar">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="vo-modal-body">

                {{-- Identificação --}}
                <div class="vo-detalhe-section">
                    <h4 class="vo-detalhe-section-title">Identificação</h4>
                    <div class="vo-detalhe-grid">
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">Grupo</span>
                            <span class="vo-detalhe-value {{ empty($d['grupo']) ? 'muted' : '' }}">{{ $d['grupo'] ?: '—' }}</span>
                        </div>
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">A.S. / Complemento</span>
                            <span class="vo-detalhe-value {{ empty($tituloAs) ? 'muted' : '' }}">{{ $tituloAs ?: '—' }}</span>
                        </div>
                        <div class="vo-detalhe-field full">
                            <span class="vo-detalhe-label">Escopo</span>
                            <span class="vo-detalhe-value {{ empty($d['escopo']) ? 'muted' : '' }}">{{ $d['escopo'] ?: '—' }}</span>
                        </div>
                        @if (! empty($d['escopo_complementar']))
                            <div class="vo-detalhe-field full">
                                <span class="vo-detalhe-label">Escopo Complementar</span>
                                <span class="vo-detalhe-value">{{ $d['escopo_complementar'] }}</span>
                            </div>
                        @endif
                        <div class="vo-detalhe-field full">
                            <span class="vo-detalhe-label">Empresa</span>
                            <span class="vo-detalhe-value {{ empty($d['empresa']) ? 'muted' : '' }}">{{ $d['empresa'] ?: 'Não contratado' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Financeiro --}}
                <div class="vo-detalhe-section">
                    <h4 class="vo-detalhe-section-title">Financeiro</h4>
                    <div class="vo-detalhe-grid">
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">Valor Global (A)</span>
                            <span class="vo-detalhe-value money">{{ $fmtMoney($d['valor_global_a'] ?? 0) }}</span>
                        </div>
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">Total Medido</span>
                            <span class="vo-detalhe-value money">{{ $fmtMoney($d['total_medicao_a_menos_b'] ?? 0) }}</span>
                        </div>
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">Acumulado Medido</span>
                            <span class="vo-detalhe-value money">{{ $fmtMoney($d['valor_acumulado_medido'] ?? 0) }}</span>
                        </div>
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">Saldo</span>
                            <span class="vo-detalhe-value money">{{ $fmtMoney($d['saldo'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Faturamento (Mão de Obra / Material) --}}
                <div class="vo-detalhe-section">
                    <h4 class="vo-detalhe-section-title">Faturamento</h4>
                    <div class="vo-detalhe-grid">
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">% Mão de Obra</span>
                            <span class="vo-detalhe-value money">{{ $fmtPct($d['percentual_faturamento_mao_obra'] ?? 0) }}</span>
                        </div>
                        <div class="vo-detalhe-field">
                            <span class="vo-detalhe-label">% Material</span>
                            <span class="vo-detalhe-value money">{{ $fmtPct($d['percentual_faturamento_material'] ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Notas Fiscais (mini-resumo) --}}
                @if (($d['total_notas'] ?? 0) > 0)
                    <div class="vo-detalhe-section">
                        <h4 class="vo-detalhe-section-title">Notas Fiscais Lançadas</h4>
                        <div class="vo-detalhe-notas-grid">
                            <div class="vo-detalhe-nota-stat">
                                <div class="vo-detalhe-nota-stat-valor">{{ $d['total_notas'] }}</div>
                                <div class="vo-detalhe-nota-stat-label">Total de Notas</div>
                            </div>
                            <div class="vo-detalhe-nota-stat">
                                <div class="vo-detalhe-nota-stat-valor">{{ $d['notas_mao_obra'] ?? 0 }}</div>
                                <div class="vo-detalhe-nota-stat-label">Mão de Obra</div>
                            </div>
                            <div class="vo-detalhe-nota-stat">
                                <div class="vo-detalhe-nota-stat-valor">{{ $d['notas_material'] ?? 0 }}</div>
                                <div class="vo-detalhe-nota-stat-label">Material</div>
                            </div>
                            <div class="vo-detalhe-nota-stat">
                                <div class="vo-detalhe-nota-stat-valor">{{ $fmtMoney($d['soma_notas'] ?? 0) }}</div>
                                <div class="vo-detalhe-nota-stat-label">Soma Notas</div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="vo-detalhe-section">
                        <h4 class="vo-detalhe-section-title">Notas Fiscais Lançadas</h4>
                        <div class="vo-detalhe-value muted">Nenhuma nota fiscal lançada para este item.</div>
                    </div>
                @endif

                {{-- Observações --}}
                @if (! empty($d['observacoes']))
                    <div class="vo-detalhe-section">
                        <h4 class="vo-detalhe-section-title">Observações</h4>
                        <div class="vo-detalhe-value" style="white-space: pre-wrap;">{{ $d['observacoes'] }}</div>
                    </div>
                @endif

            </div>
            <div class="vo-modal-foot">
                <button @click="$wire.set('modalDetalheItemOpen', false)" class="vo-btn-cancel">Fechar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ Modal — Fotos ══════ --}}
    @if($modalFotosOpen)
    <div class="vo-modal-overlay" wire:click.self="$set('modalFotosOpen', false)"
         x-data x-on:keydown.escape.window="$wire.set('modalFotosOpen', false)">
        <div class="vo-modal vo-modal-sm">
            <div class="vo-modal-head">
                <span>Adicionar Fotos</span>
                <button wire:click="$set('modalFotosOpen', false)" class="vo-edit-btn" style="border-radius:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="vo-modal-body" style="padding: 16px;">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 0.75rem; font-weight: 600; margin-bottom: 4px; color: var(--vo-text-muted);">Categoria</label>
                    <select wire:model="fotoCategoriaSelecionada" class="vo-cat-delete-select" style="text-transform: capitalize;">
                        <option value="obra">Obra</option>
                        @foreach($fotoCategorias as $cat)
                            <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                {{ $this->fotosForm }}
            </div>
            <div class="vo-modal-foot">
                <button wire:click="$set('modalFotosOpen', false)" class="vo-btn-cancel">Cancelar</button>
                <button wire:click="salvarFotos" class="vo-btn-primary">Salvar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════ Lightbox ══════ --}}
    <div x-data="{
            open: false,
            src: '',
            fotos: [],
            paths: [],
            originals: [],
            currentIdx: 0,
            loading: false,
            openLightbox(detail) {
                this.loading = true;
                this.src = typeof detail === 'string' ? detail : detail.src;
                this.fotos = detail.fotos || [];
                this.paths = detail.paths || [];
                this.originals = detail.originals || [];
                this.currentIdx = detail.idx ?? 0;
                this.open = true;
            },
            next() {
                if (this.fotos.length <= 1) return;
                this.currentIdx = (this.currentIdx + 1) % this.fotos.length;
                this.loading = true;
                this.src = this.fotos[this.currentIdx];
            },
            prev() {
                if (this.fotos.length <= 1) return;
                this.currentIdx = (this.currentIdx - 1 + this.fotos.length) % this.fotos.length;
                this.loading = true;
                this.src = this.fotos[this.currentIdx];
            },
            onLoaded() {
                this.loading = false;
            },
            onError() {
                const original = this.originals[this.currentIdx] ?? null;
                if (original && this.src !== original) {
                    this.src = original;
                    return;
                }
                this.loading = false;
            },
            currentPath() {
                return this.paths[this.currentIdx] ?? null;
            },
            currentUrl() {
                return this.fotos[this.currentIdx] ?? this.src;
            }
         }"
         x-on:open-lightbox.window="openLightbox($event.detail)"
         x-on:keydown.escape.window="open = false"
         x-on:keydown.right.window="if (open) next()"
         x-on:keydown.left.window="if (open) prev()">
        <template x-if="open">
            <div class="vo-lightbox" x-on:click="open = false" x-transition.opacity>
                <button class="vo-lightbox-close" x-on:click.stop="open = false">&times;</button>
                <template x-if="fotos.length > 1">
                    <button class="vo-lightbox-nav vo-lightbox-nav-prev" x-on:click.stop="prev()" aria-label="Anterior">&lsaquo;</button>
                </template>
                <template x-if="fotos.length > 1">
                    <button class="vo-lightbox-nav vo-lightbox-nav-next" x-on:click.stop="next()" aria-label="Próxima">&rsaquo;</button>
                </template>
                <div x-show="loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem;">
                    Carregando imagem...
                </div>
                <img
                    :src="src"
                    alt="Visualização"
                    x-on:load="onLoaded()"
                    x-on:error="onError()"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                    x-on:click.stop
                >
                <template x-if="fotos.length > 0">
                    <div class="vo-lightbox-actions" x-on:click.stop>
                        <button
                            type="button"
                            @click="$wire.definirFotoPerfil(currentPath(), currentUrl())"
                            wire:loading.attr="disabled"
                            wire:target="definirFotoPerfil,definirFotoCapa"
                        >Definir como perfil</button>
                        <button
                            type="button"
                            @click="$wire.definirFotoCapa(currentPath(), currentUrl())"
                            wire:loading.attr="disabled"
                            wire:target="definirFotoPerfil,definirFotoCapa"
                        >Definir como capa</button>
                        <span class="vo-gallery-loading" wire:loading.inline-flex wire:target="definirFotoPerfil" style="color:#fff;">
                            <span class="vo-gallery-loading-spinner"></span> Definindo perfil...
                        </span>
                        <span class="vo-gallery-loading" wire:loading.inline-flex wire:target="definirFotoCapa" style="color:#fff;">
                            <span class="vo-gallery-loading-spinner"></span> Definindo capa...
                        </span>
                    </div>
                </template>
                <template x-if="fotos.length > 1">
                    <div class="vo-lightbox-counter" x-text="`${currentIdx + 1} / ${fotos.length}`"></div>
                </template>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
        window.voFormatBRL = function (valor) {
            if (valor === null || valor === undefined || isNaN(valor)) return '';
            return valor.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        };


        window.voMostrarConfirmacaoPrevisto = function (entregaId, novoSlug, valorOposto, wire) {
            if (!valorOposto || valorOposto <= 0) {
                wire.dispatch('confirmarMudancaPrevistoEntregaContratual', { entregaId, novoSlug });
                return;
            }

            const valorFormatado = window.voFormatBRL(valorOposto);

            if (!document.getElementById('vo-modal-confirmacao')) {
                const modal = document.createElement('div');
                modal.id = 'vo-modal-confirmacao';
                modal.innerHTML = `
                    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;">
                        <div style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);" class="dark:bg-gray-900">
                            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--primary-600);" class="dark:text-primary-400">Confirmar alteração?</h3>
                            <p style="color: #666; margin-bottom: 16px; line-height: 1.5;" class="dark:text-gray-300">
                                Existe R$ <strong id="vo-valor-atual"></strong> em campos de custo que serão zerados ao mudar o status.
                            </p>
                            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                                <button id="vo-btn-cancelar" style="padding: 8px 16px; border: 1px solid #ddd; border-radius: 6px; background: white; cursor: pointer; font-weight: 500;">Cancelar</button>
                                <button id="vo-btn-confirmar" style="padding: 8px 16px; border: none; border-radius: 6px; background: var(--primary-600); color: white; cursor: pointer; font-weight: 500;">Confirmar</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            const modalEl = document.getElementById('vo-modal-confirmacao');
            document.getElementById('vo-valor-atual').textContent = valorFormatado;

            modalEl.style.display = 'flex';

            document.getElementById('vo-btn-cancelar').onclick = () => {
                modalEl.style.display = 'none';
            };

            document.getElementById('vo-btn-confirmar').onclick = () => {
                modalEl.style.display = 'none';
                wire.dispatch('confirmarMudancaPrevistoEntregaContratual', { entregaId, novoSlug });
            };
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('voMoedaInput', (valorInicial) => ({
                display: '',
                init() {
                    const v = parseFloat(valorInicial);
                    this.display = isNaN(v) || v === 0 ? '' : v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                },
                aoDigitar(event) {
                    const digits = String(event.target.value).replace(/\D/g, '');
                    if (digits === '') { this.display = ''; return; }
                    const cents = Number(digits);
                    const val = (cents / 100).toFixed(2);
                    const [int, dec] = val.split('.');
                    this.display = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + dec;
                },
                raw() {
                    if (this.display === '') return '0';
                    return this.display.replace(/\./g, '').replace(',', '.');
                },
            }));
        });
    </script>
    @endpush
</x-filament-panels::page>
