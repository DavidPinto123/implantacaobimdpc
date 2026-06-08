<div wire:poll.30s="loadData" x-data>

    {{-- Header --}}
    <div style="margin-bottom:1.5rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:.75rem;">
            <h1 class="po-kanban-title" style="margin:0;">Kanban de Pendências</h1>
            <div style="display:flex; align-items:center; gap:.75rem;">
                <a href="{{ $this->getListUrl() }}" class="po-kanban-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
                        <polyline points="3 6 4 7 6 5"/><polyline points="3 12 4 13 6 11"/><polyline points="3 18 4 19 6 17"/>
                    </svg>
                    Ver Lista
                </a>
                <a href="{{ \App\Filament\Resources\PosObra\PendenciaResource::getUrl('create') }}" class="po-kanban-btn-primary">
                    + Nova Pendência
                </a>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:.75rem;">
            <select wire:model.live="obraFiltro" class="po-kanban-select">
                <option value="">Todas as obras</option>
                @foreach ($obrasOpcoes as $id => $sigla)
                    <option value="{{ $id }}">{{ $sigla }}</option>
                @endforeach
            </select>
            <p class="po-kanban-sub" style="margin:0;">Atualiza a cada 30s</p>
        </div>
    </div>

    {{-- Board --}}
    <div class="po-kanban-scroll">
        <div class="po-kanban-board">
            @foreach ($colunas as $coluna)
                <div class="po-kanban-col" style="border-top:3px solid {{ $coluna['color'] }};">

                    {{-- Cabeçalho da coluna --}}
                    <div class="po-kanban-col-header">
                        <div style="display:flex; align-items:center; gap:.5rem;">
                            <span class="po-kanban-dot" style="background:{{ $coluna['color'] }};"></span>
                            <span class="po-kanban-col-label">{{ $coluna['label'] }}</span>
                        </div>
                        <span class="po-kanban-count">{{ $coluna['count'] }}</span>
                    </div>

                    {{-- Cards --}}
                    <div class="po-kanban-cards">
                        @forelse ($coluna['cards'] as $card)
                            <a href="{{ $card['url'] }}" class="po-kanban-card" wire:key="card-{{ $card['id'] }}">

                                {{-- Linha 1: código + urgência --}}
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:.4rem;">
                                    <span class="po-kanban-codigo">{{ $card['codigo'] }}</span>
                                    @php
                                        $urgColors = [
                                            'P1' => 'background:rgba(34,197,94,.18);color:#15803d',
                                            'P2' => 'background:rgba(251,186,0,.25);color:#92400e',
                                            'P3' => 'background:rgba(239,68,68,.18);color:#b91c1c',
                                        ];
                                        $urgStyle = $urgColors[$card['urgencia']] ?? 'background:#f3f4f6;color:#374151';
                                    @endphp
                                    <span class="po-kanban-badge" style="{{ $urgStyle }}">
                                        {{ $card['urgencia_label'] }}
                                    </span>
                                </div>

                                {{-- Descrição --}}
                                <p class="po-kanban-desc">{{ $card['descricao'] }}</p>

                                {{-- Rodapé: disciplina + obra + atrasada --}}
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.25rem; margin-top:.5rem;">
                                    <span class="po-kanban-meta">{{ $card['disciplina'] ?? '—' }}</span>
                                    <div style="display:flex; align-items:center; gap:.35rem;">
                                        @if ($card['obra'])
                                            <span class="po-kanban-meta">{{ $card['obra'] }}</span>
                                        @endif
                                        @if ($card['atrasada'])
                                            <span class="po-kanban-atrasada" title="Atrasada">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2Zm1 13h-2v-2h2v2Zm0-4h-2V7h2v4Z"/></svg>
                                                Atrasada
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="po-kanban-empty">Nenhuma pendência</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

<style>
/* ─── Layout ──────────────────────────────────────────────── */
.po-kanban-scroll  { overflow-x: auto; padding-bottom: 1rem; }
.po-kanban-board   { display: flex; gap: 1rem; min-width: 100%; padding-bottom: .5rem; }
.po-kanban-col     {
    min-width: 180px;
    flex: 1 1 0%;
    background: #ffffff;
    border-radius: .75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.dark .po-kanban-col { background: #1f2937; }

/* ─── Header ──────────────────────────────────────────────── */
.po-kanban-title { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0; }
.dark .po-kanban-title { color: #f9fafb; }
.po-kanban-sub   { font-size: .75rem; color: #6b7280; margin: .15rem 0 0; }
.dark .po-kanban-sub { color: #9ca3af; }

/* ─── Controls ────────────────────────────────────────────── */
.po-kanban-select {
    appearance: auto;
    -webkit-appearance: menulist;
    border: 1px solid #d1d5db;
    border-radius: .5rem;
    padding: .45rem 2rem .45rem .75rem;
    font-size: .8rem;
    font-weight: 500;
    background: #ffffff;
    color: #374151;
    min-width: 180px;
    cursor: pointer;
    outline: none;
    box-shadow: 0 1px 2px rgba(0,0,0,.05);
}
.dark .po-kanban-select {
    background: #374151;
    border-color: #4b5563;
    color: #f3f4f6;
}
.po-kanban-btn-primary {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .4rem .9rem; border-radius: .5rem; font-size: .8rem; font-weight: 600;
    background: #FBBA00; color: #111827; text-decoration: none; white-space: nowrap;
    transition: opacity .15s;
}
.po-kanban-btn-primary:hover { opacity: .85; }
.po-kanban-btn-secondary {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .4rem .9rem; border-radius: .5rem; font-size: .8rem; font-weight: 500;
    background: transparent; border: 1px solid #d1d5db; color: #374151; text-decoration: none; white-space: nowrap;
    transition: background .15s;
}
.po-kanban-btn-secondary:hover { background: #f3f4f6; }
.dark .po-kanban-btn-secondary { border-color: #4b5563; color: #d1d5db; }
.dark .po-kanban-btn-secondary:hover { background: #374151; }

/* ─── Column header ───────────────────────────────────────── */
.po-kanban-col-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .65rem .75rem;
    background: rgba(0,0,0,.025);
    border-bottom: 1px solid rgba(0,0,0,.06);
}
.dark .po-kanban-col-header { background: rgba(255,255,255,.04); border-bottom-color: rgba(255,255,255,.06); }
.po-kanban-dot   { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.po-kanban-col-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #374151; }
.dark .po-kanban-col-label { color: #d1d5db; }
.po-kanban-count {
    font-size: .7rem; font-weight: 700;
    background: #ffffff; color: #374151;
    padding: .1rem .4rem; border-radius: 9999px;
    border: 1px solid rgba(0,0,0,.08);
    min-width: 1.4rem; text-align: center;
}
.dark .po-kanban-count { background: #374151; color: #f3f4f6; border-color: rgba(255,255,255,.08); }

/* ─── Cards area ──────────────────────────────────────────── */
.po-kanban-cards { padding: .5rem; display: flex; flex-direction: column; gap: .4rem; max-height: 540px; overflow-y: auto; }
.po-kanban-empty { text-align: center; padding: 1.5rem .5rem; font-size: .75rem; color: #9ca3af; }

/* ─── Card ────────────────────────────────────────────────── */
.po-kanban-card {
    display: block;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    padding: .6rem .7rem;
    text-decoration: none;
    transition: box-shadow .15s, border-color .15s;
}
.po-kanban-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,.12); border-color: #cbd5e1; }
.dark .po-kanban-card { background: #111827; border-color: #374151; }
.dark .po-kanban-card:hover { border-color: #4b5563; box-shadow: 0 3px 10px rgba(0,0,0,.4); }

.po-kanban-codigo {
    font-family: monospace; font-size: .75rem; font-weight: 700; color: #FBBA00;
}
.po-kanban-badge {
    font-size: .65rem; font-weight: 700;
    padding: .15rem .45rem; border-radius: 9999px;
    white-space: nowrap;
}
.po-kanban-desc {
    font-size: .75rem; color: #4b5563; line-height: 1.4;
    margin: 0;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.dark .po-kanban-desc { color: #9ca3af; }
.po-kanban-meta { font-size: .65rem; color: #9ca3af; }
.po-kanban-atrasada {
    display: inline-flex; align-items: center; gap: .2rem;
    font-size: .65rem; font-weight: 600; color: #ef4444;
}
</style>
</div>
