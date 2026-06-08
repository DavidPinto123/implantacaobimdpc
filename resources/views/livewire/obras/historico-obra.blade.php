<div wire:poll.15s class="ho-root">
    <style>
        .ho-root {
            --ho-bg: #ffffff;
            --ho-bg-subtle: #f9fafb;
            --ho-text: #111827;
            --ho-text-secondary: #374151;
            --ho-text-muted: #6b7280;
            --ho-text-faint: #9ca3af;
            --ho-border: #e5e7eb;
            --ho-border-light: #f3f4f6;
            --ho-info-text: #1e40af;
            --ho-success-text: #166534;
            --ho-danger-text: #991b1b;
            --ho-accent: #FBBA00;
        }
        .dark .ho-root {
            --ho-bg: #111113;
            --ho-bg-subtle: #0a0a0c;
            --ho-text: #e5e7eb;
            --ho-text-secondary: #d1d5db;
            --ho-text-muted: #9ca3af;
            --ho-text-faint: #6b7280;
            --ho-border: #1f2023;
            --ho-border-light: #1a1a1e;
            --ho-info-text: #93c5fd;
            --ho-success-text: #86efac;
            --ho-danger-text: #fca5a5;
        }
        .ho-root { color: var(--ho-text); }
        .ho-toolbar {
            display: flex; gap: 8px; padding: 10px 16px;
            border-bottom: 1px solid var(--ho-border-light);
            align-items: center; flex-wrap: wrap;
        }
        .ho-toolbar input, .ho-toolbar select {
            font-size: 0.8rem; padding: 6px 10px;
            border: 1px solid var(--ho-border); border-radius: 0.375rem;
            background: var(--ho-bg-subtle); color: var(--ho-text);
            font-family: inherit;
        }
        .ho-toolbar input { flex: 1; min-width: 160px; }
        .ho-toolbar input:focus, .ho-toolbar select:focus {
            outline: none; border-color: var(--ho-accent);
            box-shadow: 0 0 0 2px rgba(251,186,0,.15);
        }
        .ho-feed-input {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 14px 16px; border-bottom: 1px solid var(--ho-border-light);
        }
        .ho-feed-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; flex-shrink: 0; margin-top: 2px;
        }
        .ho-feed-input-main { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .ho-feed-input-field {
            flex: 1; width: 100%; box-sizing: border-box;
            border: 1px solid var(--ho-border); border-radius: 1.5rem;
            padding: 10px 20px; font-size: 0.85rem; background: var(--ho-bg-subtle);
            color: var(--ho-text); font-family: inherit; resize: vertical;
            min-height: 44px; line-height: 1.5; transition: all .2s;
        }
        .ho-feed-input-field::placeholder { color: var(--ho-text-faint); }
        .ho-feed-input-field:focus {
            outline: none; border-color: var(--ho-accent);
            box-shadow: 0 0 0 3px rgba(251,186,0,.2); background: var(--ho-bg);
        }
        .ho-feed-submit {
            background: var(--ho-accent); color: #111; border: none;
            font-size: 0.75rem; padding: 6px 20px; border-radius: 1.5rem;
            cursor: pointer; font-weight: 700;
        }
        .ho-feed-item { padding: 12px 16px; border-bottom: 1px solid var(--ho-border-light); }
        .ho-feed-item:last-child { border-bottom: none; }
        .ho-feed-row { display: flex; gap: 10px; align-items: flex-start; }
        .ho-feed-content { flex: 1; min-width: 0; }
        .ho-feed-author { font-size: 0.8rem; font-weight: 700; color: var(--ho-text); }
        .ho-feed-date { font-size: 0.7rem; color: var(--ho-text-faint); margin-left: 6px; }
        .ho-feed-text { font-size: 0.8rem; color: var(--ho-text-secondary); margin-top: 3px; line-height: 1.5; }
        .ho-feed-obra {
            font-size: 0.65rem; color: var(--ho-text-muted);
            background: var(--ho-border-light); padding: 1px 8px;
            border-radius: 1rem; margin-left: 4px;
        }
        .ho-feed-tag {
            display: inline-block; font-size: 0.65rem; padding: 2px 10px;
            border-radius: 1rem; margin-top: 6px; font-weight: 600;
        }
        .ho-feed-actions { display: flex; gap: 12px; margin-top: 6px; font-size: 0.7rem; }
        .ho-feed-action-btn {
            background: none; border: none; cursor: pointer;
            color: var(--ho-text-muted); font-size: 0.7rem; padding: 0; font-family: inherit;
        }
        .ho-feed-action-btn:hover { color: var(--ho-accent); }
        .ho-feed-respostas {
            margin-left: 44px; margin-top: 8px;
            border-left: 2px solid var(--ho-border); padding-left: 12px;
        }
        .ho-feed-resposta-input {
            margin-left: 44px; margin-top: 8px;
            display: flex; gap: 6px; align-items: flex-start;
        }
        .ho-feed-resposta-input textarea {
            flex: 1; border: 1px solid var(--ho-border); border-radius: 0.375rem;
            padding: 6px 10px; font-size: 0.75rem; resize: none; min-height: 36px;
            font-family: inherit; background: var(--ho-bg-subtle); color: var(--ho-text);
        }
        .ho-btn-sm {
            font-size: 0.7rem; padding: 5px 12px; border-radius: 0.375rem; border: none;
            background: var(--ho-accent); color: #111; cursor: pointer; font-weight: 600; white-space: nowrap;
        }
        .ho-mencao { color: var(--ho-info-text); font-weight: 600; }
        .ho-fixado { border-left: 3px solid var(--ho-accent); background: rgba(251,186,0,.04); }
        .ho-badge-auto {
            font-size: 0.575rem; background: var(--ho-border-light); color: var(--ho-text-muted);
            padding: 1px 6px; border-radius: 1rem; margin-left: 4px;
        }
        .ho-mencao-dropdown {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--ho-bg); border: 1px solid var(--ho-border);
            border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,.15); z-index: 50;
            max-height: 160px; overflow-y: auto; margin-top: 2px;
        }
        .ho-mencao-item { padding: 6px 12px; font-size: 0.8rem; cursor: pointer; color: var(--ho-text); }
        .ho-mencao-item:hover { background: rgba(251,186,0,.1); }
        .ho-campo-diff {
            margin-top: 4px; padding: 6px 10px; border-radius: 0.5rem;
            background: var(--ho-bg-subtle); border: 1px solid var(--ho-border-light);
            font-size: 0.75rem;
        }
    </style>

    {{-- Filtros --}}
    <div class="ho-toolbar">
        <input
            type="search"
            wire:model.live.debounce.400ms="buscaTexto"
            placeholder="Buscar por texto, campo ou valor..."
        />
        <select wire:model.live="categoriaFiltro">
            <option value="">Todas as categorias</option>
            @foreach($categoriasOptions as $value => $meta)
                <option value="{{ $value }}">{{ $meta['label'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- Input de comentário (apenas em modo obra específica) --}}
    @if(! $this->isGlobal())
        <div class="ho-feed-input">
            <div class="ho-feed-avatar" style="background: var(--ho-accent); color: #111;">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
            </div>
            <div class="ho-feed-input-main"
                 x-data="{
                     detectMencao(el) {
                         const before = el.value.slice(0, el.selectionStart);
                         const m = before.match(/@(\w[\w ]*)$/);
                         if (m) {
                             $wire.buscarUsuarios(m[1].trimEnd());
                         } else {
                             $wire.set('sugestoesMencao', []);
                         }
                     },
                     inserirMencao(nome) {
                         const el = $refs.txComentario;
                         const pos = el.selectionStart;
                         const before = el.value.slice(0, pos);
                         const after = el.value.slice(pos);
                         const novo = before.replace(/@(\w[\w ]*)$/, '@' + nome + ' ') + after;
                         el.value = novo;
                         $wire.set('novoComentario', novo);
                         $wire.set('sugestoesMencao', []);
                         el.focus();
                     }
                 }">
                <div style="position: relative;">
                    <textarea
                        x-ref="txComentario"
                        wire:model="novoComentario"
                        @input="detectMencao($el)"
                        placeholder="Escrever um comentário... Use @ para mencionar"
                        class="ho-feed-input-field"
                        rows="2"
                    ></textarea>
                    @if(! empty($sugestoesMencao))
                        <div class="ho-mencao-dropdown">
                            @foreach($sugestoesMencao as $user)
                                <div class="ho-mencao-item"
                                     @mousedown.prevent="inserirMencao('{{ addslashes($user['name']) }}')">
                                    {{ $user['name'] }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" wire:click="postarComentario" class="ho-feed-submit">Publicar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Lista --}}
    @forelse($atualizacoes as $atualizacao)
        @php
            $coresCat = [
                'info' => '#3B82F6', 'success' => '#22C55E', 'warning' => '#F59E0B',
                'danger' => '#EF4444', 'purple' => '#8B5CF6', 'gray' => '#6B7280',
                'blue' => '#3B82F6', 'cyan' => '#06B6D4', 'indigo' => '#6366F1',
                'orange' => '#F97316',
            ];
            $corCategoria = $coresCat[$atualizacao->categoria->color()] ?? '#6B7280';
            $iniciais = strtoupper(substr($atualizacao->usuario?->name ?? 'S', 0, 2));
            $obraLabel = $this->isGlobal()
                ? ($atualizacao->obra?->projeto?->nome
                    ?? $atualizacao->obra?->projeto?->sigla
                    ?? $atualizacao->obra?->codigo
                    ?? ('Obra #'.$atualizacao->obra_id))
                : null;
        @endphp
        <div class="ho-feed-item {{ $atualizacao->fixado ? 'ho-fixado' : '' }}">
            <div class="ho-feed-row">
                <div class="ho-feed-avatar" style="background: {{ $corCategoria }}20; color: {{ $corCategoria }};">
                    {{ $iniciais }}
                </div>
                <div class="ho-feed-content">
                    <div>
                        <span class="ho-feed-author">{{ $atualizacao->usuario?->name ?? 'Sistema' }}</span>
                        <span class="ho-feed-date">{{ $atualizacao->created_at->format('d \d\e M. \d\e Y, H:i') }}</span>
                        @if($atualizacao->automatico)
                            <span class="ho-badge-auto">auto</span>
                        @endif
                        @if($atualizacao->fixado)
                            <span class="ho-badge-auto" style="background: rgba(251,186,0,.15); color: var(--ho-accent);">fixado</span>
                        @endif
                        @if($obraLabel)
                            <span class="ho-feed-obra">{{ $obraLabel }}</span>
                        @endif
                    </div>

                    @if($atualizacao->campo_alterado)
                        <div class="ho-campo-diff">
                            <div style="color: var(--ho-text-muted); font-size: 0.65rem; margin-bottom: 4px;">
                                Campo: <strong style="color: var(--ho-text);">{{ $atualizacao->titulo }}</strong>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                @if($atualizacao->valor_anterior !== null && $atualizacao->valor_anterior !== '')
                                    <span style="color: var(--ho-danger-text); text-decoration: line-through;">{{ $atualizacao->valor_anterior }}</span>
                                    <span style="color: var(--ho-text-faint);">&rarr;</span>
                                @endif
                                <span style="color: var(--ho-success-text); font-weight: 600;">{{ $atualizacao->valor_novo ?? '(vazio)' }}</span>
                            </div>
                        </div>
                    @elseif($atualizacao->conteudo)
                        <div class="ho-feed-text">
                            {!! nl2br(preg_replace('/@(\w[\w\s]*?)(?=\s@|\s*$|[,;.!?])/', '<span class="ho-mencao">@$1</span>', e($atualizacao->conteudo))) !!}
                        </div>
                    @elseif($atualizacao->titulo)
                        <div style="margin-top:4px;font-size:0.78rem;color:var(--ho-text-secondary);">
                            {{ $atualizacao->titulo }}
                        </div>
                    @endif

                    <span class="ho-feed-tag" style="background: {{ $corCategoria }}15; color: {{ $corCategoria }};">
                        {{ $atualizacao->categoria->label() }}
                    </span>

                    <div class="ho-feed-actions">
                        @if(! $this->isGlobal())
                            <button type="button" wire:click="abrirResposta({{ $atualizacao->id }})" class="ho-feed-action-btn">Responder</button>
                        @endif
                        @if(! $atualizacao->automatico && ($atualizacao->usuario_id === auth()->id() || auth()->user()?->can('Delete:AtualizacaoObra')))
                            <button type="button"
                                    wire:click="excluirAtualizacao({{ $atualizacao->id }})"
                                    class="ho-feed-action-btn"
                                    style="color: var(--ho-danger-text);"
                                    wire:confirm="Tem certeza que deseja excluir?">Excluir</button>
                        @endif
                        <button type="button" wire:click="fixarAtualizacao({{ $atualizacao->id }})" class="ho-feed-action-btn">{{ $atualizacao->fixado ? 'Desfixar' : 'Fixar' }}</button>
                    </div>

                    @if($atualizacao->respostas->count() > 0)
                        <div class="ho-feed-respostas">
                            @foreach($atualizacao->respostas as $resposta)
                                @php $rIniciais = strtoupper(substr($resposta->usuario?->name ?? 'S', 0, 2)); @endphp
                                <div style="padding: 6px 0; {{ ! $loop->last ? 'border-bottom: 1px solid var(--ho-border-light);' : '' }}">
                                    <div class="ho-feed-row">
                                        <div class="ho-feed-avatar" style="width: 26px; height: 26px; font-size: 0.55rem; background: var(--ho-border); color: var(--ho-text-muted);">{{ $rIniciais }}</div>
                                        <div class="ho-feed-content">
                                            <div>
                                                <span class="ho-feed-author" style="font-size: 0.75rem;">{{ $resposta->usuario?->name ?? 'Sistema' }}</span>
                                                <span class="ho-feed-date">{{ $resposta->created_at->format('d \d\e M. \d\e Y, H:i') }}</span>
                                            </div>
                                            <div class="ho-feed-text" style="font-size: 0.75rem;">
                                                {!! preg_replace('/@(\w[\w\s]*?)(?=\s@|\s*$|[,;.!?])/', '<span class="ho-mencao">@$1</span>', e($resposta->conteudo)) !!}
                                            </div>
                                            @if(! $resposta->automatico && ($resposta->usuario_id === auth()->id() || auth()->user()?->can('Delete:AtualizacaoObra')))
                                                <button type="button"
                                                        wire:click="excluirAtualizacao({{ $resposta->id }})"
                                                        class="ho-feed-action-btn"
                                                        style="color: var(--ho-danger-text); font-size: 0.625rem;"
                                                        wire:confirm="Tem certeza que deseja excluir?">Excluir</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(! $this->isGlobal() && $respondendoA === $atualizacao->id)
                        <div class="ho-feed-resposta-input"
                             x-data="{
                                 detectMencaoResp(el) {
                                     const before = el.value.slice(0, el.selectionStart);
                                     const m = before.match(/@(\w[\w ]*)$/);
                                     if (m) {
                                         $wire.buscarUsuarios(m[1].trimEnd());
                                     } else {
                                         $wire.set('sugestoesMencao', []);
                                     }
                                 },
                                 inserirMencaoResp(nome) {
                                     const el = $refs.txResposta;
                                     const pos = el.selectionStart;
                                     const before = el.value.slice(0, pos);
                                     const after = el.value.slice(pos);
                                     const novo = before.replace(/@(\w[\w ]*)$/, '@' + nome + ' ') + after;
                                     el.value = novo;
                                     $wire.set('respostaTexto', novo);
                                     $wire.set('sugestoesMencao', []);
                                     el.focus();
                                 }
                             }">
                            <div style="position: relative; flex: 1;">
                                <textarea
                                    x-ref="txResposta"
                                    wire:model="respostaTexto"
                                    @input="detectMencaoResp($el)"
                                    placeholder="Escrever resposta... Use @ para mencionar"
                                    rows="1"
                                ></textarea>
                                @if(! empty($sugestoesMencao))
                                    <div class="ho-mencao-dropdown">
                                        @foreach($sugestoesMencao as $user)
                                            <div class="ho-mencao-item"
                                                 @mousedown.prevent="inserirMencaoResp('{{ addslashes($user['name']) }}')">
                                                {{ $user['name'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <button type="button" wire:click="responder" class="ho-btn-sm">Enviar</button>
                            <button type="button" wire:click="fecharResposta" class="ho-btn-sm" style="background: var(--ho-border); color: var(--ho-text-muted);">Cancelar</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 32px 16px; color: var(--ho-text-faint); font-size: 0.8rem;">
            Nenhuma atualização encontrada.
        </div>
    @endforelse

    @if($atualizacoes->hasPages())
        <div style="padding: 12px 16px;">{{ $atualizacoes->links() }}</div>
    @endif
</div>
