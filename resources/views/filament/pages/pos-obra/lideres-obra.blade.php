<x-filament::page>

<div>

{{-- Formulário de adição --}}
<div class="lo-card" style="margin-bottom:1.5rem;">
    <div class="lo-card-header">
        <h3 class="lo-card-title">Adicionar Líder de Obra</h3>
    </div>
    <div class="lo-card-body">
        {{ $this->adicionarForm }}
        <div style="margin-top:1rem;">
            <button wire:click="adicionar" type="button" class="lo-btn-primary">
                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Adicionar
            </button>
        </div>
    </div>
</div>

{{-- Lista de líderes --}}
<div class="lo-card">
    <div class="lo-card-header">
        <h3 class="lo-card-title">Líderes Cadastrados</h3>
        <span class="lo-badge">{{ count($lideres) }}</span>
    </div>

    @forelse($lideres as $lider)
        <div class="lo-item {{ $editandoId === $lider['id'] ? 'lo-item-editing' : '' }}">
            <div class="lo-item-main">
                <div class="lo-item-info">
                    <div class="lo-avatar">{{ mb_substr($lider['name'], 0, 1) }}</div>
                    <div>
                        <div class="lo-name">{{ $lider['name'] }}</div>
                        <div class="lo-meta">
                            @if($lider['email'])<span>{{ $lider['email'] }}</span>@endif
                            @if($lider['phone'])<span>· {{ $lider['phone'] }}</span>@endif
                        </div>
                    </div>
                </div>
                <div class="lo-item-actions">
                    @if($editandoId !== $lider['id'])
                        <button wire:click="editarObras({{ $lider['id'] }})" type="button" class="lo-btn-secondary" title="Editar obras">
                            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Editar
                        </button>
                        <button wire:click="remover({{ $lider['id'] }})" wire:confirm="Remover {{ $lider['name'] }} como líder de obra?" type="button" class="lo-btn-danger" title="Remover líder">
                            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Obras --}}
            @if($editandoId === $lider['id'])
                <div class="lo-edit-obras">
                    {{ $this->editarForm }}
                    <div style="display:flex; gap:.5rem; margin-top:.75rem;">
                        <button wire:click="salvarObras" type="button" class="lo-btn-primary">Salvar</button>
                        <button wire:click="cancelarEdicao" type="button" class="lo-btn-secondary">Cancelar</button>
                    </div>
                </div>
            @else
                <div class="lo-obras-tags">
                    @forelse($lider['obras'] as $obra)
                        <span class="lo-tag">
                            <strong>{{ $obra['sigla'] }}</strong>
                            @if($obra['unidade'])<span class="lo-tag-sub">{{ $obra['unidade'] }}</span>@endif
                        </span>
                    @empty
                        <span class="lo-no-obras">Nenhuma obra vinculada</span>
                    @endforelse
                </div>
            @endif
        </div>
    @empty
        <div class="lo-empty">Nenhum líder de obra cadastrado.</div>
    @endforelse
</div>

</div>

<style>
/* ── Card ──────────────────────────────────────── */
.lo-card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; overflow:visible; }
.dark .lo-card { background:#1f2937; border-color:#374151; }
.lo-card-header { display:flex; align-items:center; gap:.75rem; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb; }
.dark .lo-card-header { border-bottom-color:#374151; }
.lo-card-title { font-size:1rem; font-weight:700; color:#111827; margin:0; }
.dark .lo-card-title { color:#f3f4f6; }
.lo-card-body { padding:1.25rem; }

/* ── Item ──────────────────────────────────────── */
.lo-item { padding:1rem 1.25rem; border-bottom:1px solid #f3f4f6; }
.lo-item:last-child { border-bottom:none; }
.dark .lo-item { border-bottom-color:#374151; }
.lo-item-editing { background:#fffbeb; }
.dark .lo-item-editing { background:rgba(251,186,0,.05); }
.lo-item-main { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
.lo-item-info { display:flex; align-items:center; gap:.75rem; }
.lo-item-actions { display:flex; gap:.35rem; flex-shrink:0; }

/* ── Avatar ────────────────────────────────────── */
.lo-avatar {
    width:36px; height:36px; border-radius:50%; background:#FBBA00; color:#111;
    display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:.875rem; flex-shrink:0;
}

/* ── Text ──────────────────────────────────────── */
.lo-name { font-size:.875rem; font-weight:600; color:#111827; }
.dark .lo-name { color:#f3f4f6; }
.lo-meta { font-size:.75rem; color:#6b7280; display:flex; gap:.25rem; }
.dark .lo-meta { color:#9ca3af; }

/* ── Tags (obras) ──────────────────────────────── */
.lo-obras-tags { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.6rem; }
.lo-tag {
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.2rem .6rem; border-radius:1rem; font-size:.75rem;
    background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;
}
.dark .lo-tag { background:rgba(59,130,246,.12); color:#93c5fd; border-color:rgba(59,130,246,.25); }
.lo-tag-sub { font-weight:400; color:#3b82f6; font-size:.6875rem; }
.dark .lo-tag-sub { color:#60a5fa; }
.lo-no-obras { font-size:.75rem; color:#9ca3af; font-style:italic; margin-top:.6rem; }
.dark .lo-no-obras { color:#6b7280; }

/* ── Edit obras ────────────────────────────────── */
.lo-edit-obras { margin-top:.75rem; padding-top:.75rem; border-top:1px dashed #e5e7eb; }
.dark .lo-edit-obras { border-top-color:#4b5563; }

/* ── Badge ─────────────────────────────────────── */
.lo-badge {
    font-size:.75rem; font-weight:600; padding:.15rem .5rem;
    border-radius:1rem; background:#fef3c7; color:#92400e; border:1px solid #fde68a;
}
.dark .lo-badge { background:rgba(251,186,0,.12); color:#fbbf24; border-color:rgba(251,186,0,.25); }

/* ── Buttons ───────────────────────────────────── */
.lo-btn-primary {
    display:inline-flex; align-items:center; gap:6px;
    padding:.5rem 1rem; background:#FBBA00; color:#111; border:none;
    border-radius:.5rem; font-size:.8125rem; font-weight:600;
    cursor:pointer; transition:opacity .15s;
}
.lo-btn-primary:hover { opacity:.85; }
.lo-btn-secondary {
    display:inline-flex; align-items:center; gap:4px;
    padding:.35rem .65rem; background:#fff; color:#374151;
    border:1px solid #d1d5db; border-radius:.375rem;
    font-size:.75rem; font-weight:500; cursor:pointer; transition:all .15s;
}
.lo-btn-secondary:hover { background:#f9fafb; border-color:#9ca3af; }
.dark .lo-btn-secondary { background:#374151; border-color:#4b5563; color:#d1d5db; }
.dark .lo-btn-secondary:hover { background:#4b5563; }
.lo-btn-danger {
    display:inline-flex; align-items:center; gap:4px;
    padding:.35rem .5rem; background:#fff; color:#dc2626;
    border:1px solid #fca5a5; border-radius:.375rem;
    font-size:.75rem; cursor:pointer; transition:all .15s;
}
.lo-btn-danger:hover { background:#fef2f2; border-color:#dc2626; }
.dark .lo-btn-danger { background:#374151; border-color:#7f1d1d; color:#fca5a5; }
.dark .lo-btn-danger:hover { background:rgba(220,38,38,.1); }

/* ── Empty ─────────────────────────────────────── */
.lo-empty { padding:2rem; text-align:center; font-size:.875rem; color:#9ca3af; }
.dark .lo-empty { color:#6b7280; }
</style>

</x-filament::page>
