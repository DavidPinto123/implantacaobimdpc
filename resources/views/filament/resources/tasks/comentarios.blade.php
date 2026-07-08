@php
    $hasRecord = isset($this->record) && $this->record;
    $comentarios = $hasRecord ? $this->record->comentarios()->with('usuario')->get() : collect();
@endphp
@if($hasRecord)
<style>
.tk-comments { margin: 0 0 8px; }
.tk-comments-title { font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;margin:0 0 12px; }
.tk-comment { display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #27272a; }
.tk-comment:last-of-type { border-bottom:none; }
.tk-comment-avatar { width:32px;height:32px;border-radius:50%;background:#374151;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#9ca3af;flex-shrink:0; }
.tk-comment-body { flex:1;min-width:0; }
.tk-comment-meta { display:flex;align-items:center;gap:8px;margin-bottom:4px; }
.tk-comment-autor { font-size:.78rem;font-weight:600;color:#e4e4e7; }
.tk-comment-data { font-size:.68rem;color:#52525b; }
.tk-comment-whatsapp { font-size:.6rem;background:#16a34a22;color:#4ade80;padding:2px 6px;border-radius:99px;font-weight:700; }
.tk-comment-texto { font-size:.82rem;color:#a1a1aa;line-height:1.5; }
.tk-empty { font-size:.78rem;color:#52525b;padding:8px 0; }
.tk-add-form { display:flex;gap:8px;align-items:flex-end;margin-top:14px;padding-top:14px;border-top:1px solid #27272a; }
.tk-add-textarea { flex:1;background:#09090b;border:1px solid #3f3f46;color:#e4e4e7;border-radius:.375rem;padding:8px 10px;font-size:.82rem;font-family:inherit;resize:vertical;min-height:60px;outline:none; }
.tk-add-textarea:focus { border-color:#6366f1; }
.tk-add-btn { background:#2563eb;color:#fff;border:none;border-radius:.375rem;padding:9px 18px;font-size:.8rem;font-weight:600;cursor:pointer;font-family:inherit;white-space:nowrap; }
.tk-add-btn:hover { background:#1d4ed8; }
.tk-add-btn:disabled { opacity:.5;cursor:default; }
</style>

<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="margin-bottom:1rem;">
    <div class="fi-section-content p-6">
        <div class="tk-comments">
            <p class="tk-comments-title">💬 Comentários ({{ $comentarios->count() }})</p>

            @forelse($comentarios as $c)
            @php
                $nome = $c->usuario?->name ?? 'Sistema';
                $inicial = strtoupper(substr($nome, 0, 1));
                $isWhatsapp = str_contains($c->conteudo, '📱');
                $texto = $isWhatsapp ? preg_replace('/^📱 \*Via WhatsApp:\* /', '', $c->conteudo) : $c->conteudo;
            @endphp
            <div class="tk-comment">
                <div class="tk-comment-avatar">{{ $inicial }}</div>
                <div class="tk-comment-body">
                    <div class="tk-comment-meta">
                        <span class="tk-comment-autor">{{ $nome }}</span>
                        @if($isWhatsapp)
                            <span class="tk-comment-whatsapp">📱 WhatsApp</span>
                        @endif
                        <span class="tk-comment-data">{{ $c->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="tk-comment-texto">{{ $texto }}</div>
                </div>
            </div>
            @empty
            <p class="tk-empty">Nenhum comentário ainda.</p>
            @endforelse

            {{-- Formulário para adicionar comentário --}}
            <div class="tk-add-form">
                <textarea
                    class="tk-add-textarea"
                    wire:model="novoComentario"
                    placeholder="Adicionar comentário..."
                    rows="2"
                ></textarea>
                <button
                    class="tk-add-btn"
                    wire:click="adicionarComentario"
                    wire:loading.attr="disabled"
                    wire:target="adicionarComentario">
                    <span wire:loading.remove wire:target="adicionarComentario">Comentar</span>
                    <span wire:loading wire:target="adicionarComentario">...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
