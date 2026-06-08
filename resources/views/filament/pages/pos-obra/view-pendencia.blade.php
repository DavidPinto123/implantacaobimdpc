<x-filament-panels::page>
@php
    $p = $this->record->load([
        'obra', 'disciplina', 'gestor', 'liderObra', 'construtora',
        'anexos', 'atualizacoesStatus' => fn ($q) => $q->orderBy('created_at', 'desc'),
    ]);

    /* ── helpers ── */
    $statusColor = fn (\App\Enums\PosObra\StatusPendencia|null $s) => match ($s) {
        \App\Enums\PosObra\StatusPendencia::REGISTRADA            => 'background:rgba(251,186,0,.2);color:#92400e',
        \App\Enums\PosObra\StatusPendencia::NOTIFICADA_PRESTADORA => 'background:rgba(99,102,241,.15);color:#4338ca',
        \App\Enums\PosObra\StatusPendencia::PENDENTE_COM_PRAZO    => 'background:rgba(59,130,246,.15);color:#1d4ed8',
        \App\Enums\PosObra\StatusPendencia::EM_EXECUCAO           => 'background:rgba(245,158,11,.2);color:#92400e',
        \App\Enums\PosObra\StatusPendencia::AGUARDANDO_APROVACAO  => 'background:rgba(139,92,246,.15);color:#6d28d9',
        \App\Enums\PosObra\StatusPendencia::CONCLUIDA             => 'background:rgba(34,197,94,.15);color:#15803d',
        \App\Enums\PosObra\StatusPendencia::AS_ORCAMENTOS         => 'background:rgba(6,182,212,.15);color:#0e7490',
        \App\Enums\PosObra\StatusPendencia::GARANTIA_SOLICITADA   => 'background:rgba(236,72,153,.15);color:#be185d',
        \App\Enums\PosObra\StatusPendencia::PROJ_COMPLEMENTAR     => 'background:rgba(124,58,237,.15);color:#6d28d9',
        \App\Enums\PosObra\StatusPendencia::CANCELADA             => 'background:rgba(107,114,128,.15);color:#374151',
        default                                                    => 'background:rgba(107,114,128,.15);color:#374151',
    };

    $urgColor = fn (\App\Enums\PosObra\UrgenciaPendencia|null $u) => match ($u) {
        \App\Enums\PosObra\UrgenciaPendencia::P1 => 'background:rgba(34,197,94,.15);color:#15803d',
        \App\Enums\PosObra\UrgenciaPendencia::P2 => 'background:rgba(251,186,0,.2);color:#92400e',
        \App\Enums\PosObra\UrgenciaPendencia::P3 => 'background:rgba(239,68,68,.15);color:#b91c1c',
        default                                   => 'background:rgba(107,114,128,.15);color:#374151',
    };

    /* ── SLA ── */
    $slaVencido = false;
    $slaTexto   = 'Sem prazo';
    if ($p->data_termino && ! $p->status->isTerminal()) {
        $diff = now()->diffInDays($p->data_termino, false);
        if ($diff < 0) {
            $slaVencido = true;
            $slaTexto   = 'Vencido há ' . abs((int) $diff) . ' dia(s)';
        } elseif ($diff === 0) {
            $slaTexto = 'Vence hoje';
        } else {
            $slaTexto = 'Vence em ' . (int) $diff . ' dia(s)';
        }
    }

    $transicoesDisponiveis = $this->getTransicoesDisponiveis();

    $isImage = fn (string $url) => preg_match('/\.(png|jpe?g|webp|gif)(\?|$)/i', $url);
@endphp

<div class="pv-root">

    {{-- ── Header interno ─────────────────────────────────────────────── --}}
    <div class="pv-header">
        <div class="pv-header-left">
            <div class="pv-title-row">
                <span class="pv-codigo">{{ $p->codigo }}</span>
                <span class="pv-pill" style="{{ $statusColor($p->status) }}">
                    {{ $p->status?->label() }}
                </span>
                <span class="pv-pill" style="{{ $urgColor($p->urgencia) }}">
                    {{ $p->urgencia?->label() }}
                </span>
                @if ($p->disciplina)
                    <span class="pv-pill pv-pill-gray">{{ $p->disciplina->label }}</span>
                @endif
                @if ($p->estaAtrasada())
                    <span class="pv-pill pv-pill-red">⚠ Atrasada</span>
                @endif
            </div>
            <p class="pv-created">
                Criada em {{ $p->created_at->format('d/m/Y H:i') }}
                @if ($p->gestor) · Gestor: {{ $p->gestor->name }} @endif
            </p>
        </div>
        <div class="pv-header-actions">
            @if (count($transicoesDisponiveis) > 0)
                <button wire:click="openStatusModal" class="pv-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                    Atualizar Status
                </button>
            @endif
            <a href="{{ \App\Filament\Resources\PosObra\PendenciaResource::getUrl('edit', ['record' => $p]) }}" class="pv-btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Editar
            </a>
        </div>
    </div>

    {{-- ── SLA bar ─────────────────────────────────────────────────────── --}}
    @if ($p->data_termino && ! $p->status->isTerminal())
        <div class="pv-sla {{ $slaVencido ? 'pv-sla-danger' : 'pv-sla-warn' }}">
            @if ($slaVencido)
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2Zm1 13h-2v-2h2v2Zm0-4h-2V7h2v4Z"/>
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            @endif
            <span class="pv-sla-text">
                Prazo: {{ $p->data_termino->format('d/m/Y') }} — {{ $slaTexto }}
            </span>
            @if ($p->impacto_operacao)
                <span class="pv-pill pv-pill-orange" style="font-size:.65rem;">⚡ Impacto na operação</span>
            @endif
        </div>
    @endif

    {{-- ── Layout principal ───────────────────────────────────────────── --}}
    <div class="pv-layout">

        {{-- ── Coluna principal 2/3 ──────────────────────────────────── --}}
        <div class="pv-main">

            {{-- Descrição --}}
            <div class="pv-card">
                <h2 class="pv-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="#FBBA00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                    </svg>
                    Descrição
                </h2>
                <p class="pv-desc">{{ $p->descricao }}</p>
                @if ($p->local_especifico)
                    <div class="pv-meta-row">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span><strong>Local:</strong> {{ $p->local_especifico }}</span>
                    </div>
                @endif
                @if ($p->observacoes)
                    <p class="pv-obs">{{ $p->observacoes }}</p>
                @endif
                @if ($p->ticket)
                    <div class="pv-meta-row" style="margin-top:.5rem;">
                        <span style="font-size:.75rem;color:#6b7280;">Ticket:</span>
                        <span style="font-family:monospace;font-size:.8rem;font-weight:600;">{{ $p->ticket }}</span>
                    </div>
                @endif
            </div>

            {{-- Timeline de Status --}}
            <div class="pv-card">
                <h2 class="pv-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="#FBBA00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Histórico de Status
                </h2>

                @if ($p->atualizacoesStatus->isNotEmpty())
                    <div class="pv-timeline">
                        <div class="pv-timeline-line"></div>
                        @foreach ($p->atualizacoesStatus as $atu)
                            <div class="pv-timeline-item">
                                <div class="pv-timeline-dot">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2.5"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                    </svg>
                                </div>
                                <div class="pv-timeline-content">
                                    <div class="pv-timeline-badges">
                                        @if ($atu->status_anterior instanceof \App\Enums\PosObra\StatusPendencia)
                                            <span class="pv-pill" style="{{ $statusColor($atu->status_anterior) }}">
                                                {{ $atu->status_anterior->label() }}
                                            </span>
                                            <span class="pv-arrow">→</span>
                                        @endif
                                        @if ($atu->status_novo instanceof \App\Enums\PosObra\StatusPendencia)
                                            <span class="pv-pill" style="{{ $statusColor($atu->status_novo) }}">
                                                {{ $atu->status_novo->label() }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($atu->comentario)
                                        <p class="pv-timeline-comment">{{ $atu->comentario }}</p>
                                    @endif
                                    <p class="pv-timeline-meta">
                                        {{ $atu->created_at->format('d/m/Y H:i') }}
                                        @if ($atu->atualizado_por) — por {{ $atu->atualizado_por }} @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="pv-empty">Nenhuma atualização registrada.</p>
                @endif
            </div>

            {{-- Fotos e Anexos --}}
            <div class="pv-card">
                <h2 class="pv-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="#FBBA00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    Fotos e Anexos
                </h2>

                @if ($p->anexos->isNotEmpty())
                    <div class="pv-gallery">
                        @foreach ($p->anexos as $anx)
                            <div class="pv-gallery-item">
                                <div class="pv-gallery-thumb">
                                    @if ($isImage($anx->url))
                                        <img src="{{ $anx->url }}" alt="{{ $anx->nome_arquivo }}"
                                             class="pv-gallery-img" loading="lazy">
                                    @else
                                        <div class="pv-gallery-file">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                 viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"
                                                 stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                                                <polyline points="13 2 13 9 20 9"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="pv-gallery-meta">
                                    @if ($anx->tipo instanceof \App\Enums\PosObra\TipoAnexo)
                                        <span class="pv-pill pv-pill-gray" style="font-size:.65rem;">
                                            {{ $anx->tipo->label() }}
                                        </span>
                                    @endif
                                    @if ($anx->nome_arquivo)
                                        <p class="pv-gallery-name">{{ \Illuminate\Support\Str::limit($anx->nome_arquivo, 25) }}</p>
                                    @endif
                                    <p class="pv-gallery-date">{{ $anx->created_at->format('d/m/Y H:i') }}</p>
                                    <a href="{{ $anx->url }}" target="_blank" rel="noreferrer" class="pv-link">
                                        Abrir arquivo
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="pv-empty">Nenhum anexo registrado.</p>
                @endif
            </div>

        </div>{{-- /main --}}

        {{-- ── Sidebar 1/3 ─────────────────────────────────────────────── --}}
        <div class="pv-sidebar">

            {{-- Obra --}}
            @if ($p->obra)
                <div class="pv-card">
                    <h3 class="pv-sidebar-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Obra
                    </h3>
                    <p class="pv-sidebar-name">{{ $p->obra->sigla ?? $p->obra->unidade }}</p>
                    @if ($p->obra->unidade && $p->obra->sigla)
                        <p class="pv-sidebar-sub">{{ $p->obra->unidade }}</p>
                    @endif
                    @if ($p->obra->endereco)
                        <p class="pv-sidebar-sub">{{ $p->obra->endereco }}</p>
                    @endif
                </div>
            @endif

            {{-- Fornecedor --}}
            @if ($p->construtora)
                <div class="pv-card">
                    <h3 class="pv-sidebar-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                        Fornecedor / Prestadora
                    </h3>
                    <p class="pv-sidebar-name">{{ $p->construtora->nome }}</p>
                </div>
            @endif

            {{-- Responsáveis --}}
            <div class="pv-card">
                <h3 class="pv-sidebar-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Responsáveis
                </h3>
                @if ($p->gestor)
                    <div class="pv-sidebar-field">
                        <span class="pv-sidebar-label">Gestor</span>
                        <span class="pv-sidebar-value">{{ $p->gestor->name }}</span>
                    </div>
                @endif
                @if ($p->liderObra)
                    <div class="pv-sidebar-field">
                        <span class="pv-sidebar-label">Líder de Unidade</span>
                        <span class="pv-sidebar-value">{{ $p->liderObra->name }}</span>
                    </div>
                @endif
            </div>

            {{-- Prazos --}}
            <div class="pv-card">
                <h3 class="pv-sidebar-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Prazos
                </h3>
                <div class="pv-sidebar-field">
                    <span class="pv-sidebar-label">Início</span>
                    <span class="pv-sidebar-value">{{ $p->data_inicio?->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div class="pv-sidebar-field">
                    <span class="pv-sidebar-label">Prazo previsto</span>
                    <span class="pv-sidebar-value {{ $p->estaAtrasada() ? 'pv-text-red' : '' }}">
                        {{ $p->data_termino?->format('d/m/Y') ?? '—' }}
                    </span>
                </div>
                @if ($p->data_conclusao)
                    <div class="pv-sidebar-field">
                        <span class="pv-sidebar-label">Concluída em</span>
                        <span class="pv-sidebar-value pv-text-green">
                            {{ $p->data_conclusao->format('d/m/Y H:i') }}
                        </span>
                    </div>
                @endif
                <div class="pv-sidebar-field">
                    <span class="pv-sidebar-label">Criado em</span>
                    <span class="pv-sidebar-value">{{ $p->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

        </div>{{-- /sidebar --}}
    </div>{{-- /layout --}}

</div>{{-- /pv-root --}}

{{-- ── Modal de Atualização de Status ─────────────────────────────────── --}}
@if ($this->showStatusModal)
    <div class="pv-modal-overlay" wire:click.self="closeStatusModal">
        <div class="pv-modal">
            <h3 class="pv-modal-title">Atualizar Status</h3>
            <p class="pv-modal-current">
                Status atual:
                <span class="pv-pill" style="{{ $statusColor($p->status) }}">
                    {{ $p->status?->label() }}
                </span>
            </p>

            <div class="pv-modal-field">
                <label class="pv-modal-label">Novo Status *</label>
                <select wire:model="novoStatus" class="pv-modal-select">
                    <option value="">Selecione...</option>
                    @foreach ($transicoesDisponiveis as $s)
                        <option value="{{ $s }}">
                            {{ \App\Enums\PosObra\StatusPendencia::from($s)->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pv-modal-field">
                <label class="pv-modal-label">Comentário (opcional)</label>
                <textarea wire:model="comentario" rows="3"
                    placeholder="Descreva o motivo da mudança de status..."
                    class="pv-modal-textarea"></textarea>
            </div>

            <div class="pv-modal-footer">
                <button wire:click="closeStatusModal" class="pv-btn-secondary">Cancelar</button>
                <button wire:click="confirmStatusUpdate"
                        @if (! $this->novoStatus) disabled @endif
                        class="pv-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirmar
                </button>
            </div>
        </div>
    </div>
@endif

<style>
/* ─── Root ─────────────────────────────────────────────────────────────── */
.pv-root { display:flex; flex-direction:column; gap:1.25rem; }

/* ─── Header ─────────────────────────────────────────────────────────── */
.pv-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    flex-wrap:wrap; gap:1rem;
}
.pv-title-row { display:flex; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:.25rem; }
.pv-codigo { font-size:1.5rem; font-weight:700; color:#111827; line-height:1.2; }
.dark .pv-codigo { color:#f9fafb; }
.pv-created { font-size:.78rem; color:#6b7280; margin:0; }
.dark .pv-created { color:#9ca3af; }
.pv-header-actions { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }

/* ─── Buttons ─────────────────────────────────────────────────────────── */
.pv-btn-primary {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.45rem 1rem; border-radius:.5rem; font-size:.82rem; font-weight:600;
    background:#FBBA00; color:#111827; border:none; cursor:pointer;
    text-decoration:none; white-space:nowrap; transition:opacity .15s;
}
.pv-btn-primary:hover { opacity:.85; }
.pv-btn-primary:disabled { opacity:.5; cursor:not-allowed; }
.pv-btn-secondary {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.45rem 1rem; border-radius:.5rem; font-size:.82rem; font-weight:500;
    background:transparent; border:1px solid #d1d5db; color:#374151;
    cursor:pointer; text-decoration:none; white-space:nowrap; transition:background .15s;
}
.pv-btn-secondary:hover { background:#f3f4f6; }
.dark .pv-btn-secondary { border-color:#4b5563; color:#d1d5db; }
.dark .pv-btn-secondary:hover { background:#374151; }

/* ─── Pill badges ─────────────────────────────────────────────────────── */
.pv-pill {
    display:inline-flex; align-items:center;
    padding:.2rem .65rem; border-radius:9999px;
    font-size:.73rem; font-weight:600; white-space:nowrap;
}
.pv-pill-gray { background:rgba(107,114,128,.12); color:#4b5563; }
.dark .pv-pill-gray { background:rgba(156,163,175,.15); color:#d1d5db; }
.pv-pill-red  { background:rgba(239,68,68,.15); color:#b91c1c; }
.pv-pill-orange { background:rgba(249,115,22,.15); color:#c2410c; }

/* ─── SLA bar ─────────────────────────────────────────────────────────── */
.pv-sla {
    display:flex; align-items:center; gap:.6rem; flex-wrap:wrap;
    padding:.65rem 1rem; border-radius:.6rem; font-size:.82rem; font-weight:500;
}
.pv-sla-warn  { background:rgba(251,191,36,.1); border:1px solid rgba(251,191,36,.4); color:#92400e; }
.pv-sla-danger{ background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.3);  color:#b91c1c; }
.dark .pv-sla-warn  { background:rgba(251,191,36,.08); border-color:rgba(251,191,36,.25); color:#fde68a; }
.dark .pv-sla-danger{ background:rgba(239,68,68,.08);  border-color:rgba(239,68,68,.25);  color:#fca5a5; }
.pv-sla-text { flex:1; }

/* ─── Layout 2/3 + 1/3 ────────────────────────────────────────────────── */
.pv-layout { display:grid; grid-template-columns:1fr; gap:1.25rem; }
@media (min-width:1024px) { .pv-layout { grid-template-columns:2fr 1fr; } }
.pv-main    { display:flex; flex-direction:column; gap:1.25rem; }
.pv-sidebar { display:flex; flex-direction:column; gap:1.25rem; }

/* ─── Card ─────────────────────────────────────────────────────────────── */
.pv-card {
    background:#ffffff; border:1px solid #e5e7eb; border-radius:.75rem; padding:1.25rem;
}
.dark .pv-card { background:#1f2937; border-color:#374151; }

.pv-card-title {
    display:flex; align-items:center; gap:.5rem;
    font-size:1rem; font-weight:600; color:#111827; margin:0 0 1rem;
}
.dark .pv-card-title { color:#f9fafb; }

/* ─── Description card ─────────────────────────────────────────────────── */
.pv-desc { font-size:.88rem; color:#374151; line-height:1.6; white-space:pre-wrap; margin:0; }
.dark .pv-desc { color:#d1d5db; }
.pv-obs  { font-size:.82rem; color:#6b7280; margin:.75rem 0 0; font-style:italic; }
.dark .pv-obs { color:#9ca3af; }
.pv-meta-row { display:flex; align-items:center; gap:.4rem; font-size:.82rem; color:#6b7280; margin-top:.75rem; }
.dark .pv-meta-row { color:#9ca3af; }

/* ─── Timeline ─────────────────────────────────────────────────────────── */
.pv-timeline { position:relative; display:flex; flex-direction:column; gap:1.25rem; }
.pv-timeline-line {
    position:absolute; left:15px; top:0; bottom:0; width:2px;
    background:#e5e7eb;
}
.dark .pv-timeline-line { background:#374151; }
.pv-timeline-item { display:flex; gap:1rem; position:relative; }
.pv-timeline-dot {
    position:relative; z-index:1; flex-shrink:0;
    width:32px; height:32px; border-radius:50%;
    background:rgba(251,186,0,.15); color:#FBBA00;
    display:flex; align-items:center; justify-content:center;
}
.dark .pv-timeline-dot { background:rgba(251,186,0,.1); }
.pv-timeline-content { flex:1; padding-bottom:.25rem; }
.pv-timeline-badges { display:flex; align-items:center; flex-wrap:wrap; gap:.35rem; margin-bottom:.35rem; }
.pv-arrow { color:#9ca3af; font-size:.8rem; }
.pv-timeline-comment { font-size:.82rem; color:#4b5563; margin:.3rem 0 0; }
.dark .pv-timeline-comment { color:#9ca3af; }
.pv-timeline-meta { font-size:.73rem; color:#9ca3af; margin:.2rem 0 0; }

/* ─── Gallery ─────────────────────────────────────────────────────────── */
.pv-gallery { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; }
@media (min-width:640px)  { .pv-gallery { grid-template-columns:repeat(3,1fr); } }
@media (min-width:1024px) { .pv-gallery { grid-template-columns:repeat(2,1fr); } }
@media (min-width:1280px) { .pv-gallery { grid-template-columns:repeat(3,1fr); } }
.pv-gallery-item { border:1px solid #e5e7eb; border-radius:.6rem; overflow:hidden; }
.dark .pv-gallery-item { border-color:#374151; }
.pv-gallery-thumb {
    aspect-ratio:1; background:#f3f4f6; overflow:hidden;
    display:flex; align-items:center; justify-content:center;
}
.dark .pv-gallery-thumb { background:#111827; }
.pv-gallery-img  { width:100%; height:100%; object-fit:cover; }
.pv-gallery-file { display:flex; align-items:center; justify-content:center; width:100%; height:100%; }
.pv-gallery-meta { padding:.5rem .6rem; display:flex; flex-direction:column; gap:.2rem; }
.pv-gallery-name { font-size:.72rem; color:#374151; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dark .pv-gallery-name { color:#d1d5db; }
.pv-gallery-date { font-size:.68rem; color:#9ca3af; margin:0; }

/* ─── Sidebar cards ─────────────────────────────────────────────────────── */
.pv-sidebar-title {
    display:flex; align-items:center; gap:.4rem;
    font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em;
    color:#6b7280; margin:0 0 .75rem;
}
.dark .pv-sidebar-title { color:#9ca3af; }
.pv-sidebar-name { font-size:.95rem; font-weight:600; color:#FBBA00; margin:0 0 .15rem; }
.pv-sidebar-sub  { font-size:.78rem; color:#6b7280; margin:0; }
.dark .pv-sidebar-sub { color:#9ca3af; }
.pv-sidebar-field { display:flex; flex-direction:column; gap:.1rem; margin-bottom:.6rem; }
.pv-sidebar-field:last-child { margin-bottom:0; }
.pv-sidebar-label { font-size:.7rem; color:#9ca3af; }
.pv-sidebar-value { font-size:.85rem; font-weight:500; color:#111827; }
.dark .pv-sidebar-value { color:#f3f4f6; }
.pv-text-red   { color:#ef4444 !important; }
.pv-text-green { color:#22c55e !important; }

/* ─── Empty state ─────────────────────────────────────────────────────── */
.pv-empty { font-size:.82rem; color:#9ca3af; }

/* ─── Link ────────────────────────────────────────────────────────────── */
.pv-link { font-size:.72rem; color:#FBBA00; text-decoration:none; }
.pv-link:hover { text-decoration:underline; }

/* ─── Modal ─────────────────────────────────────────────────────────── */
.pv-modal-overlay {
    position:fixed; inset:0; z-index:50;
    background:rgba(0,0,0,.5); backdrop-filter:blur(2px);
    display:flex; align-items:center; justify-content:center; padding:1rem;
}
.pv-modal {
    background:#ffffff; border-radius:.75rem; box-shadow:0 20px 60px rgba(0,0,0,.25);
    width:100%; max-width:460px; padding:1.5rem; display:flex; flex-direction:column; gap:1rem;
}
.dark .pv-modal { background:#1f2937; }
.pv-modal-title { font-size:1.1rem; font-weight:700; color:#111827; margin:0; }
.dark .pv-modal-title { color:#f9fafb; }
.pv-modal-current { font-size:.82rem; color:#6b7280; margin:0; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.dark .pv-modal-current { color:#9ca3af; }
.pv-modal-field { display:flex; flex-direction:column; gap:.35rem; }
.pv-modal-label { font-size:.8rem; font-weight:600; color:#374151; }
.dark .pv-modal-label { color:#d1d5db; }
.pv-modal-select, .pv-modal-textarea {
    border:1px solid #d1d5db; border-radius:.5rem; padding:.5rem .75rem;
    font-size:.85rem; background:#ffffff; color:#111827;
    width:100%; outline:none;
}
.pv-modal-select:focus, .pv-modal-textarea:focus { border-color:#FBBA00; box-shadow:0 0 0 2px rgba(251,186,0,.2); }
.dark .pv-modal-select, .dark .pv-modal-textarea {
    background:#374151; border-color:#4b5563; color:#f3f4f6;
}
.pv-modal-textarea { resize:vertical; min-height:80px; }
.pv-modal-footer { display:flex; justify-content:flex-end; gap:.6rem; }
</style>
</x-filament-panels::page>
