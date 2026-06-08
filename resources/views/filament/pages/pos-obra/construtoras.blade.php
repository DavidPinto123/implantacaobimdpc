<x-filament::page>

<div>

{{-- Formulário de adição --}}
<div class="cp-card" style="margin-bottom:1.5rem;">
    <div class="cp-card-header">
        <h3 class="cp-card-title">Cadastrar Fornecedor / Prestadora</h3>
    </div>
    <div class="cp-card-body">
        {{ $this->adicionarForm }}
        <div style="margin-top:.75rem;">
            {{ $this->disciplinasForm }}
        </div>
        <div style="margin-top:1rem;">
            <button wire:click="adicionar" type="button" class="cp-btn-primary">
                <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Cadastrar
            </button>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="cp-card" style="margin-bottom:1rem;">
    <div class="cp-card-body" style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
            <input wire:model.live.debounce.300ms="filtroBusca" type="text" class="cp-input" placeholder="Buscar por nome...">
        </div>
        <div>
            <select wire:model.live="filtroTipo" class="cp-select-filter">
                <option value="">Todos os tipos</option>
                @foreach(\App\Enums\PosObra\TipoConstrutora::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </select>
        </div>
        <span class="cp-count">{{ count($construtoras) }} resultado(s)</span>
    </div>
</div>

{{-- Lista --}}
<div class="cp-card">
    <div class="cp-card-header">
        <h3 class="cp-card-title">Construtoras e Prestadoras</h3>
        <span class="cp-badge">{{ count($construtoras) }}</span>
    </div>

    @forelse($construtoras as $c)
        <div class="cp-item {{ $editandoId === $c['id'] ? 'cp-item-editing' : '' }}">
            <div class="cp-item-main">
                <div class="cp-item-info">
                    <div class="cp-avatar {{ $c['tipo'] === 'PRESTADORA_SERVICO' ? 'cp-avatar-blue' : '' }}">
                        @if($c['tipo'] === 'PRESTADORA_SERVICO')
                            <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        @else
                            <svg style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="cp-name">
                            {{ $c['nome'] }}
                            <span class="cp-tipo-badge {{ $c['tipo'] === 'PRESTADORA_SERVICO' ? 'cp-tipo-prestadora' : 'cp-tipo-construtora' }}">
                                {{ $c['tipo_label'] }}
                            </span>
                        </div>
                        <div class="cp-meta">
                            @if($c['cnpj'])<span>{{ $c['cnpj'] }}</span>@endif
                            @if($c['telefone_whatsapp'])<span>· {{ $c['telefone_whatsapp'] }}</span>@endif
                        </div>
                    </div>
                </div>
                <div class="cp-item-actions">
                    @if($editandoId !== $c['id'])
                        <button wire:click="editar({{ $c['id'] }})" type="button" class="cp-btn-secondary">
                            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Editar
                        </button>
                        <button wire:click="remover({{ $c['id'] }})" wire:confirm="Remover {{ $c['nome'] }}?" type="button" class="cp-btn-danger">
                            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    @endif
                </div>
            </div>

            @if($editandoId === $c['id'])
                <div class="cp-edit-area">
                    {{ $this->editarDisciplinasForm }}
                    <div style="display:flex; gap:.5rem; margin-top:.75rem;">
                        <button wire:click="salvarEdicao" type="button" class="cp-btn-primary">Salvar</button>
                        <button wire:click="cancelarEdicao" type="button" class="cp-btn-secondary">Cancelar</button>
                    </div>
                </div>
            @else
                @if(count($c['obras']) > 0 || count($c['disciplinas']) > 0)
                    <div class="cp-tags">
                        @foreach($c['obras'] as $o)
                            <span class="cp-tag-obra">
                                <strong>{{ $o['sigla'] }}</strong>
                                @if($o['unidade'])<span class="cp-tag-sub">{{ $o['unidade'] }}</span>@endif
                            </span>
                        @endforeach
                        @foreach($c['disciplinas'] as $d)
                            <span class="cp-tag">{{ $d['label'] }}</span>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    @empty
        <div class="cp-empty">Nenhum fornecedor/prestador encontrado.</div>
    @endforelse
</div>

</div>

<style>
/* ── Card ──────────────────────────────────────── */
.cp-card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; overflow:visible; }
.dark .cp-card { background:#1f2937; border-color:#374151; }
.cp-card-header { display:flex; align-items:center; gap:.75rem; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb; }
.dark .cp-card-header { border-bottom-color:#374151; }
.cp-card-title { font-size:1rem; font-weight:700; color:#111827; margin:0; }
.dark .cp-card-title { color:#f3f4f6; }
.cp-card-body { padding:1.25rem; }

/* ── Filter ────────────────────────────────────── */
.cp-input {
    width:100%; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .75rem;
    font-size:.8125rem; background:#fff; color:#111827; outline:none;
}
.cp-input:focus { border-color:#FBBA00; box-shadow:0 0 0 2px rgba(251,186,0,.2); }
.dark .cp-input { background:#111827; border-color:#4b5563; color:#f3f4f6; }
.cp-select-filter {
    appearance:auto; border:1px solid #d1d5db; border-radius:.5rem; padding:.45rem .75rem;
    font-size:.8125rem; background:#fff; color:#111827; cursor:pointer;
}
.dark .cp-select-filter { background:#111827; border-color:#4b5563; color:#f3f4f6; }
.cp-count { font-size:.75rem; color:#9ca3af; }
.dark .cp-count { color:#6b7280; }

/* ── Item ──────────────────────────────────────── */
.cp-item { padding:1rem 1.25rem; border-bottom:1px solid #f3f4f6; }
.cp-item:last-child { border-bottom:none; }
.dark .cp-item { border-bottom-color:#374151; }
.cp-item-editing { background:#fffbeb; }
.dark .cp-item-editing { background:rgba(251,186,0,.05); }
.cp-item-main { display:flex; align-items:center; justify-content:space-between; gap:1rem; }
.cp-item-info { display:flex; align-items:center; gap:.75rem; flex:1; min-width:0; }
.cp-item-actions { display:flex; gap:.35rem; flex-shrink:0; }

/* ── Avatar ────────────────────────────────────── */
.cp-avatar {
    width:36px; height:36px; border-radius:50%; background:#FBBA00; color:#111;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.cp-avatar-blue { background:#dbeafe; color:#2563eb; }
.dark .cp-avatar-blue { background:rgba(59,130,246,.2); color:#93c5fd; }

/* ── Text ──────────────────────────────────────── */
.cp-name { font-size:.875rem; font-weight:600; color:#111827; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.dark .cp-name { color:#f3f4f6; }
.cp-meta { font-size:.75rem; color:#6b7280; display:flex; gap:.25rem; }
.dark .cp-meta { color:#9ca3af; }

/* ── Tipo badge ────────────────────────────────── */
.cp-tipo-badge { font-size:.65rem; padding:.1rem .45rem; border-radius:1rem; font-weight:600; }
.cp-tipo-construtora { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.cp-tipo-prestadora { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.dark .cp-tipo-construtora { background:rgba(251,186,0,.12); color:#fbbf24; border-color:rgba(251,186,0,.25); }
.dark .cp-tipo-prestadora { background:rgba(59,130,246,.12); color:#93c5fd; border-color:rgba(59,130,246,.25); }

/* ── Tags (disciplinas) ───────────────────────── */
.cp-tags { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.6rem; }
.cp-tag {
    display:inline-block; padding:.15rem .5rem; border-radius:1rem; font-size:.7rem; font-weight:500;
    background:#ede9fe; color:#5b21b6; border:1px solid #c4b5fd;
}
.dark .cp-tag { background:rgba(139,92,246,.12); color:#c4b5fd; border-color:rgba(139,92,246,.25); }
.cp-tag-obra {
    display:inline-flex; align-items:center; gap:.3rem;
    padding:.15rem .5rem; border-radius:1rem; font-size:.7rem; font-weight:500;
    background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;
}
.dark .cp-tag-obra { background:rgba(59,130,246,.12); color:#93c5fd; border-color:rgba(59,130,246,.25); }
.cp-tag-sub { font-weight:400; font-size:.65rem; }
.dark .cp-tag-sub { color:#60a5fa; }

/* ── Edit area ─────────────────────────────────── */
.cp-edit-area { margin-top:.75rem; padding-top:.75rem; border-top:1px dashed #e5e7eb; }
.dark .cp-edit-area { border-top-color:#4b5563; }

/* ── Badge ─────────────────────────────────────── */
.cp-badge {
    font-size:.75rem; font-weight:600; padding:.15rem .5rem;
    border-radius:1rem; background:#fef3c7; color:#92400e; border:1px solid #fde68a;
}
.dark .cp-badge { background:rgba(251,186,0,.12); color:#fbbf24; border-color:rgba(251,186,0,.25); }

/* ── Buttons ───────────────────────────────────── */
.cp-btn-primary {
    display:inline-flex; align-items:center; gap:6px;
    padding:.5rem 1rem; background:#FBBA00; color:#111; border:none;
    border-radius:.5rem; font-size:.8125rem; font-weight:600;
    cursor:pointer; transition:opacity .15s;
}
.cp-btn-primary:hover { opacity:.85; }
.cp-btn-secondary {
    display:inline-flex; align-items:center; gap:4px;
    padding:.35rem .65rem; background:#fff; color:#374151;
    border:1px solid #d1d5db; border-radius:.375rem;
    font-size:.75rem; font-weight:500; cursor:pointer; transition:all .15s;
}
.cp-btn-secondary:hover { background:#f9fafb; border-color:#9ca3af; }
.dark .cp-btn-secondary { background:#374151; border-color:#4b5563; color:#d1d5db; }
.dark .cp-btn-secondary:hover { background:#4b5563; }
.cp-btn-danger {
    display:inline-flex; align-items:center; gap:4px;
    padding:.35rem .5rem; background:#fff; color:#dc2626;
    border:1px solid #fca5a5; border-radius:.375rem;
    font-size:.75rem; cursor:pointer; transition:all .15s;
}
.cp-btn-danger:hover { background:#fef2f2; border-color:#dc2626; }
.dark .cp-btn-danger { background:#374151; border-color:#7f1d1d; color:#fca5a5; }
.dark .cp-btn-danger:hover { background:rgba(220,38,38,.1); }

/* ── Empty ─────────────────────────────────────── */
.cp-empty { padding:2rem; text-align:center; font-size:.875rem; color:#9ca3af; }
.dark .cp-empty { color:#6b7280; }
</style>

</x-filament::page>
