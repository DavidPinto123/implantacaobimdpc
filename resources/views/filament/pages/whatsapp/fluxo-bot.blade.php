<div class="fb-root">

    {{-- Card container (same as Filament table card) --}}
    <div class="fb-card">

        {{-- Header: tabs left, info right --}}
        <div class="fb-card-header">
            <div class="fb-tabs">
                @foreach($this->getModulos() as $modKey => $mod)
                    <button
                        wire:click="setModule('{{ $modKey }}')"
                        type="button"
                        class="fb-tab {{ $activeModule === $modKey ? 'active' : '' }}"
                    >{{ $mod['label'] }}</button>
                @endforeach
            </div>
        </div>

        {{-- Body: list + editor side by side --}}
        <div class="fb-body">

            {{-- Left: message list --}}
            <div class="fb-list">
                @foreach($this->getMensagens()[$activeModule] as $node)
                    <button
                        wire:click="selecionarNo('{{ $node['key'] }}')"
                        type="button"
                        class="fb-row {{ $chaveAtiva === $node['key'] ? 'active' : '' }}"
                    >
                        <div class="fb-row-top">
                            <span class="fb-row-dot fb-dot-{{ $node['tipo'] }}"></span>
                            <span class="fb-row-label">{{ $node['label'] }}</span>
                            <div class="fb-row-badges">
                                @if($node['custom'])
                                    <span class="fb-badge fb-badge-amber">personalizado</span>
                                @endif
                                @php
                                    $tipoLabel = ['start' => 'Início', 'normal' => 'Mensagem', 'success' => 'Sucesso', 'error' => 'Desvio'];
                                    $formatoLabel = ['texto' => null, 'botoes' => 'Botões', 'lista' => 'Lista'];
                                @endphp
                                @if(($node['formato'] ?? 'texto') !== 'texto')
                                    <span class="fb-badge fb-badge-formato">{{ $formatoLabel[$node['formato']] ?? $node['formato'] }}</span>
                                @endif
                                <span class="fb-badge fb-badge-{{ $node['tipo'] }}">{{ $tipoLabel[$node['tipo']] ?? $node['tipo'] }}</span>
                            </div>
                        </div>
                        @if($node['fase'])
                            <div class="fb-row-fase">{{ $node['fase'] }}</div>
                        @endif
                        <div class="fb-row-preview">{{ mb_substr(str_replace("\n", ' ', $node['text']), 0, 120) }}{{ mb_strlen($node['text']) > 120 ? '…' : '' }}</div>
                        @if(!empty($node['botoes']))
                            <div class="fb-row-botoes">
                                @foreach($node['botoes'] as $btn)
                                    <span class="fb-btn-preview">{{ $btn }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($node['vars']))
                            <div class="fb-row-vars">
                                @foreach($node['vars'] as $var)
                                    <code class="fb-var">{{ $var }}</code>
                                @endforeach
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- Right: editor --}}
            <div class="fb-editor">
                @if($chaveAtiva)
                    <div class="fb-editor-head">
                        <div class="fb-editor-title">{{ $noAtivo['label'] ?? '' }}</div>
                        @if(!empty($noAtivo['fase']))
                            <div class="fb-editor-fase">{{ $noAtivo['fase'] }}</div>
                        @endif
                        <code class="fb-var">{{ $chaveAtiva }}</code>
                    </div>

                    <div class="fb-editor-content">
                        @php
                            $formato = $noAtivo['formato'] ?? 'texto';
                            $formatoLabels = ['texto' => 'Texto', 'botoes' => 'Botões interativos', 'lista' => 'Lista interativa'];
                        @endphp

                        @if($formato !== 'texto')
                            <div class="fb-editor-formato">
                                <span class="fb-badge fb-badge-formato">{{ $formatoLabels[$formato] ?? $formato }}</span>
                                <span class="fb-editor-formato-hint">
                                    @if($formato === 'botoes')
                                        O usuário verá botões clicáveis no WhatsApp
                                    @else
                                        O usuário verá uma lista expandível no WhatsApp
                                    @endif
                                </span>
                            </div>
                        @endif

                        <label class="fb-field-label">Texto da mensagem</label>
                        <textarea
                            class="fb-textarea"
                            wire:model="textoEditando"
                            placeholder="Digite a mensagem do bot..."
                        ></textarea>

                        @if(!empty($noAtivo['botoes']))
                            <div class="fb-editor-botoes">
                                <label class="fb-field-label">{{ $formato === 'lista' ? 'Itens da lista (automáticos)' : 'Botões exibidos' }}</label>
                                <div class="fb-editor-botoes-list">
                                    @foreach($noAtivo['botoes'] as $btn)
                                        <span class="fb-btn-preview fb-btn-preview-lg">{{ $btn }}</span>
                                    @endforeach
                                </div>
                                <p class="fb-editor-botoes-hint">Os botões são definidos pelo sistema e não podem ser editados aqui.</p>
                            </div>
                        @endif

                        @if(!empty($noAtivo['vars']))
                            <div class="fb-vars-box">
                                <label class="fb-field-label">Variáveis disponíveis</label>
                                <div class="fb-vars-list">
                                    @foreach($noAtivo['vars'] as $var)
                                        <code class="fb-var">{{ $var }}</code>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="fb-actions">
                            <button wire:click="salvar" type="button" class="fb-btn-primary">Salvar</button>
                            <button wire:click="restaurarPadrao" wire:confirm="Restaurar o texto padrão desta mensagem?" type="button" class="fb-btn-secondary">Restaurar padrão</button>
                        </div>
                    </div>
                @else
                    <div class="fb-editor-empty">
                        Selecione uma mensagem na lista para editar.
                    </div>
                @endif
            </div>

        </div>
    </div>

<style>
/* ── Root ──────────────────────────────────────────── */
.fb-root { min-height: 0; }

/* ── Card (matches Filament table card) ───────────── */
.fb-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: .75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.dark .fb-card { background: #1f2937; border-color: #374151; }

/* ── Header ───────────────────────────────────────── */
.fb-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
}
.dark .fb-card-header { border-bottom-color: #374151; }

.fb-tabs { display: flex; gap: .35rem; }
.fb-tab {
    padding: .4rem .85rem;
    border-radius: .375rem;
    font-size: .8125rem;
    font-weight: 500;
    color: #6b7280;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .15s;
}
.fb-tab:hover { background: #f3f4f6; color: #374151; }
.dark .fb-tab { color: #9ca3af; }
.dark .fb-tab:hover { background: #374151; color: #e5e7eb; }
.fb-tab.active {
    background: #FBBA00;
    color: #111827;
    font-weight: 600;
}
.dark .fb-tab.active { background: #FBBA00; color: #111827; }

/* ── Body: split layout ───────────────────────────── */
.fb-body {
    display: flex;
    height: calc(100vh - 16rem);
}

/* ── List ──────────────────────────────────────────── */
.fb-list {
    flex: 1;
    overflow-y: auto;
    border-right: 1px solid #e5e7eb;
    min-width: 0;
}
.dark .fb-list { border-right-color: #374151; }

.fb-row {
    display: block;
    width: 100%;
    text-align: left;
    padding: .75rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    background: transparent;
    border-left: none;
    border-right: none;
    border-top: none;
    cursor: pointer;
    transition: background .1s;
}
.fb-row:last-child { border-bottom-color: transparent; }
.fb-row:hover { background: #f9fafb; }
.fb-row.active { background: #fffbeb; }
.dark .fb-row { border-bottom-color: #374151; }
.dark .fb-row:hover { background: rgba(255,255,255,.03); }
.dark .fb-row.active { background: rgba(251,186,0,.06); }

.fb-row-top {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .2rem;
}
.fb-row-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.fb-dot-start   { background: #22c55e; }
.fb-dot-normal  { background: #3b82f6; }
.fb-dot-success { background: #22c55e; }
.fb-dot-error   { background: #f59e0b; }

.fb-row-label {
    font-size: .8125rem;
    font-weight: 600;
    color: #111827;
    flex: 1;
    min-width: 0;
}
.dark .fb-row-label { color: #f3f4f6; }

.fb-row-badges { display: flex; gap: .25rem; flex-shrink: 0; }

.fb-row-fase {
    font-size: .6875rem;
    color: #9ca3af;
    margin-bottom: .15rem;
}
.dark .fb-row-fase { color: #6b7280; }

.fb-row-preview {
    font-size: .75rem;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}
.dark .fb-row-preview { color: #9ca3af; }

.fb-row-vars { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .35rem; }

/* ── Badges ────────────────────────────────────────── */
.fb-badge {
    font-size: .6875rem;
    padding: .1rem .4rem;
    border-radius: .25rem;
    font-weight: 500;
    white-space: nowrap;
    border: 1px solid;
}
.fb-badge-start,
.fb-badge-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
.fb-badge-normal  { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
.fb-badge-error   { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.fb-badge-amber   { background: #fef3c7; color: #92400e; border-color: #fde68a; font-weight: 700; }
.dark .fb-badge-start,
.dark .fb-badge-success { background: rgba(34,197,94,.12); color: #86efac; border-color: rgba(34,197,94,.25); }
.dark .fb-badge-normal  { background: rgba(59,130,246,.12); color: #93c5fd; border-color: rgba(59,130,246,.25); }
.dark .fb-badge-error   { background: rgba(245,158,11,.12); color: #fcd34d; border-color: rgba(245,158,11,.25); }
.dark .fb-badge-amber   { background: rgba(251,186,0,.12); color: #fbbf24; border-color: rgba(251,186,0,.25); }

.fb-var {
    font-size: .6875rem;
    padding: .1rem .4rem;
    border-radius: .25rem;
    background: #f3f4f6;
    color: #374151;
    font-family: ui-monospace, monospace;
    border: 1px solid #e5e7eb;
}
.dark .fb-var { background: #374151; color: #d1d5db; border-color: #4b5563; }

/* ── Editor ────────────────────────────────────────── */
.fb-editor {
    width: 380px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.fb-editor-head {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}
.dark .fb-editor-head { border-bottom-color: #374151; }

.fb-editor-title {
    font-size: .9375rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: .15rem;
}
.dark .fb-editor-title { color: #f3f4f6; }

.fb-editor-fase {
    font-size: .75rem;
    color: #9ca3af;
    margin-bottom: .35rem;
}
.dark .fb-editor-fase { color: #6b7280; }

.fb-editor-content { padding: 1rem 1.25rem; flex: 1; }

.fb-editor-empty {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8125rem;
    color: #9ca3af;
    padding: 2rem;
}
.dark .fb-editor-empty { color: #6b7280; }

/* ── Form ──────────────────────────────────────────── */
.fb-field-label {
    display: block;
    font-size: .75rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: .375rem;
}
.dark .fb-field-label { color: #d1d5db; }

.fb-textarea {
    width: 100%;
    min-height: 180px;
    padding: .625rem .75rem;
    border: 1px solid #d1d5db;
    border-radius: .375rem;
    font-size: .8125rem;
    font-family: ui-monospace, monospace;
    line-height: 1.6;
    resize: vertical;
    background: #ffffff;
    color: #111827;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.dark .fb-textarea { background: #111827; border-color: #4b5563; color: #f3f4f6; }
.fb-textarea:focus { border-color: #FBBA00; box-shadow: 0 0 0 2px rgba(251,186,0,.2); }

.fb-vars-box { margin-top: .75rem; }
.fb-vars-list { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .25rem; }

/* ── Formato badge ─────────────────────────────────── */
.fb-badge-formato { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
.dark .fb-badge-formato { background: rgba(139,92,246,.12); color: #c4b5fd; border-color: rgba(139,92,246,.25); }

/* ── Botões preview (na lista) ────────────────────── */
.fb-row-botoes { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .35rem; }
.fb-btn-preview {
    font-size: .6875rem;
    padding: .15rem .5rem;
    border-radius: 1rem;
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
    font-weight: 500;
}
.dark .fb-btn-preview { background: rgba(14,165,233,.12); color: #7dd3fc; border-color: rgba(14,165,233,.25); }

/* ── Editor: formato hint ─────────────────────────── */
.fb-editor-formato {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .75rem;
    padding: .5rem .75rem;
    background: #f5f3ff;
    border: 1px solid #e9d5ff;
    border-radius: .375rem;
}
.dark .fb-editor-formato { background: rgba(139,92,246,.08); border-color: rgba(139,92,246,.2); }
.fb-editor-formato-hint { font-size: .75rem; color: #6b7280; }
.dark .fb-editor-formato-hint { color: #9ca3af; }

/* ── Editor: botões preview ───────────────────────── */
.fb-editor-botoes { margin-top: .75rem; }
.fb-editor-botoes-list { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .25rem; }
.fb-btn-preview-lg {
    font-size: .8125rem;
    padding: .35rem .75rem;
}
.fb-editor-botoes-hint {
    font-size: .6875rem;
    color: #9ca3af;
    margin-top: .35rem;
}
.dark .fb-editor-botoes-hint { color: #6b7280; }

.fb-actions { display: flex; gap: .5rem; margin-top: 1rem; }
.fb-btn-primary {
    padding: .5rem 1rem;
    border-radius: .375rem;
    font-size: .8125rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: #FBBA00;
    color: #111827;
    transition: opacity .15s;
}
.fb-btn-primary:hover { opacity: .85; }
.fb-btn-secondary {
    padding: .5rem .85rem;
    border-radius: .375rem;
    font-size: .8125rem;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    transition: all .15s;
}
.fb-btn-secondary:hover { background: #f9fafb; border-color: #9ca3af; }
.dark .fb-btn-secondary { background: #374151; border-color: #4b5563; color: #d1d5db; }
.dark .fb-btn-secondary:hover { background: #4b5563; }
</style>
</div>
