<x-filament-panels::page>
<div class="ap-root" wire:poll.30s="loadData">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="ap-header">
        <div>
            <div class="ap-title-row">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="#ec4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <h1 class="ap-title">Aprovações de Conclusão</h1>
                @if ($this->total > 0)
                    <span class="ap-badge-count">{{ $this->total }}</span>
                @endif
            </div>
            <p class="ap-subtitle">Pendências aguardando aprovação de conclusão</p>
        </div>
        <button wire:click="loadData" class="ap-btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
            </svg>
            Atualizar
        </button>
    </div>

    {{-- ── Estado vazio ─────────────────────────────────────────────────── --}}
    @if (empty($this->pendencias))
        <div class="ap-empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                 stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <h3 class="ap-empty-title">Nenhuma aprovação pendente</h3>
            <p class="ap-empty-sub">Todas as solicitações de conclusão foram processadas.</p>
        </div>
    @endif

    {{-- ── Cards ───────────────────────────────────────────────────────── --}}
    <div class="ap-list">
        @foreach ($this->pendencias as $p)
            @php
                $urgStyle = match ($p['urgencia'] ?? '') {
                    'P1'    => 'background:rgba(34,197,94,.15);color:#15803d',
                    'P2'    => 'background:rgba(251,186,0,.2);color:#92400e',
                    'P3'    => 'background:rgba(239,68,68,.15);color:#b91c1c',
                    default => 'background:rgba(107,114,128,.15);color:#4b5563',
                };
            @endphp
            <div class="ap-card">

                {{-- Card header --}}
                <div class="ap-card-header">
                    <div class="ap-card-title-row">
                        <a href="{{ $p['url'] }}" class="ap-codigo">{{ $p['codigo'] }}</a>
                        <span class="ap-pill ap-pill-pink">Aguard. Aprovação</span>
                        @if ($p['urgencia'])
                            <span class="ap-pill" style="{{ $urgStyle }}">{{ $p['urgencia_label'] }}</span>
                        @endif
                        @if ($p['disciplina'])
                            <span class="ap-pill ap-pill-gray">{{ $p['disciplina'] }}</span>
                        @endif
                    </div>
                    <div class="ap-card-meta">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Solicitado em {{ $p['updated_at'] }}
                    </div>
                </div>

                {{-- Card body --}}
                <div class="ap-card-body">

                    {{-- Info row --}}
                    <div class="ap-info-grid">
                        @if ($p['obra'])
                            <div class="ap-info-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                                <div>
                                    <p class="ap-info-label">Obra</p>
                                    <p class="ap-info-value">{{ $p['obra'] }}</p>
                                    @if ($p['obra_sub'])
                                        <p class="ap-info-sub">{{ $p['obra_sub'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($p['construtora'])
                            <div class="ap-info-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                     stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                </svg>
                                <div>
                                    <p class="ap-info-label">Construtora</p>
                                    <p class="ap-info-value">{{ $p['construtora'] }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="ap-info-item ap-info-item--desc">
                            <div>
                                <p class="ap-info-label">Descrição</p>
                                <p class="ap-info-value ap-desc-clamp">{{ $p['descricao'] }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Local --}}
                    @if ($p['local_especifico'])
                        <p class="ap-local">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                            </svg>
                            <strong>Local:</strong> {{ $p['local_especifico'] }}
                        </p>
                    @endif

                    {{-- Evidências --}}
                    <div>
                        <h4 class="ap-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                 stroke="#ec4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                            Evidências de Finalização ({{ count($p['anexos']) }})
                        </h4>

                        @if (count($p['anexos']) > 0)
                            <div class="ap-gallery">
                                @foreach ($p['anexos'] as $anx)
                                    @php $isImg = preg_match('/\.(png|jpe?g|webp|gif)(\?|$)/i', $anx['url']); @endphp
                                    <div class="ap-gallery-item" wire:click="abrirPreview('{{ addslashes($anx['url']) }}')">
                                        <div class="ap-thumb">
                                            @if ($isImg)
                                                <img src="{{ $anx['url'] }}" alt="{{ $anx['nome_arquivo'] }}"
                                                     class="ap-thumb-img" loading="lazy">
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                                     fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                                                    <polyline points="13 2 13 9 20 9"/>
                                                </svg>
                                            @endif
                                            <div class="ap-thumb-overlay">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                                     fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </div>
                                        </div>
                                        @if ($anx['nome_arquivo'])
                                            <p class="ap-thumb-name">{{ \Illuminate\Support\Str::limit($anx['nome_arquivo'], 20) }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="ap-no-evidence">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                                Nenhuma evidência de finalização enviada
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card footer: ações --}}
                <div class="ap-card-footer">
                    @if ($this->rejeitandoId === $p['id'])
                        <div class="ap-reject-form">
                            <label class="ap-reject-label">Motivo da Rejeição *</label>
                            <textarea wire:model="motivoRejeicao" rows="3"
                                placeholder="Descreva o motivo da rejeição..."
                                class="ap-reject-textarea"></textarea>
                            <div class="ap-reject-actions">
                                <button wire:click="confirmarRejeicao"
                                        @if (! trim($this->motivoRejeicao)) disabled @endif
                                        class="ap-btn-danger">
                                    Confirmar Rejeição
                                </button>
                                <button wire:click="cancelarRejeicao" class="ap-btn-outline">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="ap-actions">
                            <button wire:click="aprovar({{ $p['id'] }})"
                                    wire:confirm="Confirma a aprovação da conclusão da pendência {{ $p['codigo'] }}?"
                                    class="ap-btn-success">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                Aprovar Conclusão
                            </button>
                            <button wire:click="iniciarRejeicao({{ $p['id'] }})" class="ap-btn-reject-soft">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Rejeitar
                            </button>
                            <a href="{{ $p['url'] }}" class="ap-btn-outline">Ver Detalhes</a>
                        </div>
                    @endif
                </div>

            </div>
        @endforeach
    </div>

</div>

{{-- ── Modal de preview ────────────────────────────────────────────────── --}}
@if ($this->previewUrl)
    <div class="ap-preview-overlay" wire:click="fecharPreview">
        <div class="ap-preview-inner" wire:click.stop>
            <button wire:click="fecharPreview" class="ap-preview-close">Fechar ✕</button>
            @php $isImg = preg_match('/\.(png|jpe?g|webp|gif)(\?|$)/i', $this->previewUrl); @endphp
            @if ($isImg)
                <img src="{{ $this->previewUrl }}" alt="Preview" class="ap-preview-img">
            @else
                <div class="ap-preview-file">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                         stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                        <polyline points="13 2 13 9 20 9"/>
                    </svg>
                    <a href="{{ $this->previewUrl }}" target="_blank" rel="noreferrer" class="ap-preview-file-link">
                        Abrir arquivo
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif

<style>
.ap-root { display:flex; flex-direction:column; gap:1.5rem; }

/* Header */
.ap-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.ap-title-row { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
.ap-title { font-size:1.35rem; font-weight:700; color:#111827; margin:0; }
.dark .ap-title { color:#f9fafb; }
.ap-subtitle { font-size:.8rem; color:#6b7280; margin:.2rem 0 0; }
.dark .ap-subtitle { color:#9ca3af; }
.ap-badge-count { background:rgba(236,72,153,.15); color:#be185d; padding:.15rem .6rem; border-radius:9999px; font-size:.75rem; font-weight:700; }
.dark .ap-badge-count { background:rgba(236,72,153,.2); color:#f9a8d4; }

/* Buttons */
.ap-btn-outline {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.4rem .9rem; border-radius:.5rem; font-size:.82rem; font-weight:500;
    background:transparent; border:1px solid #d1d5db; color:#374151;
    cursor:pointer; text-decoration:none; white-space:nowrap; transition:background .15s;
}
.ap-btn-outline:hover { background:#f3f4f6; }
.dark .ap-btn-outline { border-color:#4b5563; color:#d1d5db; }
.dark .ap-btn-outline:hover { background:#374151; }
.ap-btn-success {
    display:inline-flex; align-items:center; gap:.45rem;
    padding:.45rem 1.1rem; border-radius:.5rem; font-size:.83rem; font-weight:600;
    background:#16a34a; color:#fff; border:none; cursor:pointer; transition:background .15s;
}
.ap-btn-success:hover { background:#15803d; }
.ap-btn-reject-soft {
    display:inline-flex; align-items:center; gap:.45rem;
    padding:.45rem 1.1rem; border-radius:.5rem; font-size:.83rem; font-weight:600;
    background:rgba(239,68,68,.1); color:#b91c1c; border:none; cursor:pointer; transition:background .15s;
}
.ap-btn-reject-soft:hover { background:rgba(239,68,68,.18); }
.dark .ap-btn-reject-soft { background:rgba(239,68,68,.15); color:#fca5a5; }
.ap-btn-danger {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.4rem .9rem; border-radius:.5rem; font-size:.82rem; font-weight:600;
    background:#dc2626; color:#fff; border:none; cursor:pointer; transition:background .15s;
}
.ap-btn-danger:hover { background:#b91c1c; }
.ap-btn-danger:disabled { opacity:.5; cursor:not-allowed; }

/* Pills */
.ap-pill { display:inline-flex; align-items:center; padding:.18rem .6rem; border-radius:9999px; font-size:.7rem; font-weight:600; white-space:nowrap; }
.ap-pill-pink { background:rgba(236,72,153,.12); color:#be185d; }
.dark .ap-pill-pink { background:rgba(236,72,153,.2); color:#f9a8d4; }
.ap-pill-gray { background:rgba(107,114,128,.12); color:#4b5563; }
.dark .ap-pill-gray { background:rgba(156,163,175,.15); color:#d1d5db; }

/* Empty */
.ap-empty-state { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; padding:3.5rem; text-align:center; display:flex; flex-direction:column; align-items:center; gap:.75rem; }
.dark .ap-empty-state { background:#1f2937; border-color:#374151; }
.ap-empty-title { font-size:1rem; font-weight:600; color:#374151; margin:0; }
.dark .ap-empty-title { color:#d1d5db; }
.ap-empty-sub { font-size:.82rem; color:#9ca3af; margin:0; }

/* Card list */
.ap-list { display:flex; flex-direction:column; gap:1.25rem; }
.ap-card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; overflow:hidden; }
.dark .ap-card { background:#1f2937; border-color:#374151; }

/* Card header */
.ap-card-header { display:flex; flex-direction:column; gap:.5rem; padding:1rem 1.25rem; border-bottom:1px solid #f3f4f6; }
@media (min-width:640px) { .ap-card-header { flex-direction:row; align-items:center; justify-content:space-between; } }
.dark .ap-card-header { border-color:#374151; }
.ap-card-title-row { display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; }
.ap-codigo { font-size:1rem; font-weight:700; color:#FBBA00; text-decoration:none; }
.ap-codigo:hover { text-decoration:underline; }
.ap-card-meta { display:flex; align-items:center; gap:.35rem; font-size:.75rem; color:#9ca3af; white-space:nowrap; }

/* Card body */
.ap-card-body { padding:1rem 1.25rem; display:flex; flex-direction:column; gap:.9rem; }
.ap-info-grid { display:grid; grid-template-columns:1fr; gap:.75rem; }
@media (min-width:640px) { .ap-info-grid { grid-template-columns:1fr 1fr; } }
@media (min-width:1024px) { .ap-info-grid { grid-template-columns:1fr 1fr 2fr; } }
.ap-info-item { display:flex; align-items:flex-start; gap:.5rem; }
.ap-info-label { font-size:.68rem; color:#9ca3af; margin:0; }
.ap-info-value { font-size:.85rem; font-weight:500; color:#111827; margin:0; }
.dark .ap-info-value { color:#f3f4f6; }
.ap-info-sub { font-size:.72rem; color:#6b7280; margin:0; }
.dark .ap-info-sub { color:#9ca3af; }
.ap-desc-clamp { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.ap-local { display:flex; align-items:center; gap:.35rem; font-size:.82rem; color:#6b7280; margin:0; }
.dark .ap-local { color:#9ca3af; }

/* Section title */
.ap-section-title { display:flex; align-items:center; gap:.4rem; font-size:.78rem; font-weight:600; color:#4b5563; margin:0 0 .6rem; }
.dark .ap-section-title { color:#d1d5db; }

/* Gallery */
.ap-gallery { display:grid; grid-template-columns:repeat(4,1fr); gap:.6rem; }
@media (min-width:640px)  { .ap-gallery { grid-template-columns:repeat(6,1fr); } }
@media (min-width:1024px) { .ap-gallery { grid-template-columns:repeat(8,1fr); } }
.ap-gallery-item { cursor:pointer; border:1px solid #e5e7eb; border-radius:.5rem; overflow:hidden; }
.dark .ap-gallery-item { border-color:#374151; }
.ap-thumb { position:relative; aspect-ratio:1; background:#f3f4f6; display:flex; align-items:center; justify-content:center; overflow:hidden; }
.dark .ap-thumb { background:#111827; }
.ap-thumb-img { width:100%; height:100%; object-fit:cover; }
.ap-thumb-overlay { position:absolute; inset:0; background:rgba(0,0,0,0); display:flex; align-items:center; justify-content:center; transition:background .15s; }
.ap-gallery-item:hover .ap-thumb-overlay { background:rgba(0,0,0,.35); }
.ap-thumb-overlay svg { opacity:0; transition:opacity .15s; }
.ap-gallery-item:hover .ap-thumb-overlay svg { opacity:1; }
.ap-thumb-name { font-size:.62rem; color:#6b7280; padding:.25rem .35rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dark .ap-thumb-name { color:#9ca3af; }
.ap-no-evidence { display:flex; align-items:center; gap:.4rem; font-size:.8rem; color:#d97706; }
.dark .ap-no-evidence { color:#fbbf24; }

/* Card footer */
.ap-card-footer { padding:.9rem 1.25rem; background:#f9fafb; border-top:1px solid #f3f4f6; }
.dark .ap-card-footer { background:rgba(0,0,0,.15); border-color:#374151; }
.ap-actions { display:flex; flex-wrap:wrap; align-items:center; gap:.6rem; }

/* Reject form */
.ap-reject-form { display:flex; flex-direction:column; gap:.6rem; }
.ap-reject-label { font-size:.8rem; font-weight:600; color:#374151; }
.dark .ap-reject-label { color:#d1d5db; }
.ap-reject-textarea { width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .65rem; font-size:.83rem; background:#fff; color:#111827; resize:vertical; min-height:72px; outline:none; }
.ap-reject-textarea:focus { border-color:#FBBA00; box-shadow:0 0 0 2px rgba(251,186,0,.2); }
.dark .ap-reject-textarea { background:#374151; border-color:#4b5563; color:#f3f4f6; }
.ap-reject-actions { display:flex; gap:.5rem; flex-wrap:wrap; }

/* Image preview modal */
.ap-preview-overlay { position:fixed; inset:0; z-index:50; background:rgba(0,0,0,.75); backdrop-filter:blur(3px); display:flex; align-items:center; justify-content:center; padding:1rem; }
.ap-preview-inner { position:relative; max-width:56rem; max-height:90vh; width:100%; }
.ap-preview-close { position:absolute; top:-2rem; right:0; color:#fff; font-size:.82rem; background:none; border:none; cursor:pointer; }
.ap-preview-close:hover { text-decoration:underline; }
.ap-preview-img { width:100%; max-height:85vh; object-fit:contain; border-radius:.5rem; }
.ap-preview-file { display:flex; flex-direction:column; align-items:center; gap:1rem; padding:3rem; background:#1f2937; border-radius:.75rem; }
.ap-preview-file-link { color:#FBBA00; font-size:.9rem; text-decoration:underline; }
</style>
</x-filament-panels::page>
