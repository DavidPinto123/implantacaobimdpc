@php
    $fasesDependencia = $fasesDependencia ?? collect();
    $depth = $depth ?? 0;
    $paddingLeft = $depth * 12;
@endphp
<div wire:key="ct-si-{{ $item->id }}" x-data="{ open: false }" style="margin-bottom:2px;">

    {{-- Linha principal: título + remover --}}
    <div style="display:flex;align-items:center;gap:4px;padding-left:{{ $paddingLeft }}px;">
        <button type="button" @click="open = !open"
                style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-faint);font-size:0.6rem;line-height:1;padding:1px 3px;flex-shrink:0;width:14px;text-align:center;"
                x-text="open ? '▴' : '▾'"></button>
        <input type="text"
               value="{{ $item->titulo }}"
               wire:change="salvarTituloTemplateFaseItem({{ $item->id }}, $event.target.value)"
               style="flex:1;border:none;border-bottom:1px solid transparent;background:transparent;font-size:0.72rem;color:var(--vo-text);padding:1px 2px;outline:none;min-width:0;"
               onfocus="this.style.borderBottomColor='var(--vo-accent)'"
               onblur="this.style.borderBottomColor='transparent'">
        @if($item->children->isNotEmpty())
            <span style="font-size:0.58rem;color:var(--vo-text-faint);flex-shrink:0;">({{ $item->children->count() }})</span>
        @endif
        <button type="button"
                wire:click="removerTemplateFaseItem({{ $item->id }})"
                wire:confirm="Remover este subitem e todos seus filhos?"
                style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-faint);font-size:0.82rem;line-height:1;padding:0 2px;flex-shrink:0;">
            ×
        </button>
    </div>

    {{-- Painel expansível: dependências + sub-subitens --}}
    <div x-show="open" x-cloak
         style="margin-left:{{ $paddingLeft + 14 }}px;margin-top:3px;margin-bottom:4px;padding:6px 8px;background:var(--vo-bg-subtle);border-left:2px solid var(--vo-border);border-radius:0 .25rem .25rem 0;">

        {{-- Dependências do subitem --}}
        <div style="font-size:0.62rem;font-weight:600;color:var(--vo-text-muted);margin-bottom:3px;">Dependências</div>

        @forelse($item->dependencias as $dep)
            <div style="display:grid;grid-template-columns:1fr 72px 42px 16px;gap:2px;align-items:center;margin-bottom:2px;"
                 wire:key="ct-dep-{{ $dep->id }}">
                <select wire:change="salvarAlvoDependenciaTemplateFaseItem({{ $dep->id }}, $event.target.value)"
                        style="padding:2px 3px;border:1px solid var(--vo-border);border-radius:.2rem;font-size:0.6rem;background:var(--vo-bg);">
                    <option value="">Sem dependência</option>
                    <optgroup label="Fases">
                        @foreach($fasesDependencia->where('id', '!=', $item->cronograma_template_fase_id)->sortBy(fn ($f) => $f->ordem ?? $f->fase->ordem()) as $faseOpcao)
                            <option value="fase:{{ $faseOpcao->id }}" @selected($dep->depende_de_template_fase_id === $faseOpcao->id)>
                                {{ $faseOpcao->fase->label() }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Subitens">
                        @foreach($fasesDependencia->sortBy(fn ($f) => $f->ordem ?? $f->fase->ordem()) as $faseOpcao)
                            @foreach($faseOpcao->itens->where('id', '!=', $item->id)->sortBy('ordem') as $opcao)
                                <option value="item:{{ $opcao->id }}" @selected($dep->depende_de_item_id === $opcao->id)>
                                    {{ $faseOpcao->fase->label() }} / {{ $opcao->titulo }}
                                </option>
                            @endforeach
                        @endforeach
                    </optgroup>
                </select>
                <select wire:change="salvarGatilhoDependenciaTemplateFaseItem({{ $dep->id }}, $event.target.value)"
                        style="padding:2px 3px;border:1px solid var(--vo-border);border-radius:.2rem;font-size:0.6rem;background:var(--vo-bg);">
                    @foreach(\App\Enums\GatilhoTemplateFase::cases() as $gatilho)
                        <option value="{{ $gatilho->value }}" @selected(($dep->gatilho?->value ?? (string) $dep->gatilho) === $gatilho->value)>
                            {{ $gatilho->labelCurto() }}
                        </option>
                    @endforeach
                </select>
                <input type="number"
                       value="{{ $dep->gap_dias }}"
                       wire:change="salvarGapDependenciaTemplateFaseItem({{ $dep->id }}, $event.target.value)"
                       style="width:100%;padding:2px 3px;border:1px solid var(--vo-border);border-radius:.2rem;font-size:0.6rem;background:var(--vo-bg);">
                <button type="button"
                        wire:click="removerDependenciaTemplateFaseItem({{ $dep->id }})"
                        style="background:transparent;border:none;color:var(--vo-text-faint);cursor:pointer;font-size:0.8rem;line-height:1;padding:0;text-align:center;">
                    ×
                </button>
            </div>
        @empty
            <span style="font-size:0.6rem;color:var(--vo-text-faint);display:block;margin-bottom:2px;">Sem dependência</span>
        @endforelse

        <button type="button"
                wire:click="adicionarDependenciaTemplateFaseItem({{ $item->id }})"
                style="background:transparent;border:1px solid var(--vo-border);border-radius:.2rem;color:var(--vo-text-secondary);cursor:pointer;font-size:0.6rem;padding:1px 6px;margin-top:2px;">
            + dep
        </button>

        {{-- Sub-subitens recursivos --}}
        <div style="margin-top:6px;border-top:1px solid var(--vo-border-light);padding-top:4px;">
            <div style="font-size:0.62rem;font-weight:600;color:var(--vo-text-muted);margin-bottom:2px;">Sub-subitens</div>

            @foreach($item->children->sortBy('ordem') as $child)
                @include('filament.pages.cronograma-templates-editor-subitem', [
                    'item'             => $child,
                    'depth'            => $depth + 1,
                    'fasesDependencia' => $fasesDependencia,
                ])
            @endforeach

            @if($expandindoFilhosDeItemId === $item->id)
                <div style="display:flex;gap:3px;margin-top:3px;">
                    <input type="text"
                           wire:model="novoFilhoTitulo"
                           wire:keydown.enter.prevent="adicionarSubitemTemplateFaseItem({{ $item->id }})"
                           placeholder="Título do sub-item…"
                           style="flex:1;padding:2px 5px;border:1px solid var(--vo-border);border-radius:.2rem;font-size:0.62rem;background:var(--vo-bg);">
                    <button type="button"
                            wire:click="adicionarSubitemTemplateFaseItem({{ $item->id }})"
                            style="padding:1px 7px;border:1px solid var(--vo-border);border-radius:.2rem;font-size:0.6rem;background:var(--vo-bg);cursor:pointer;color:var(--vo-text-secondary);">
                        Adicionar
                    </button>
                    <button type="button"
                            wire:click="$set('expandindoFilhosDeItemId', null)"
                            style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-faint);font-size:0.8rem;line-height:1;padding:0 2px;">
                        ×
                    </button>
                </div>
            @else
                <button type="button"
                        wire:click="alternarAdicionarFilho({{ $item->id }})"
                        style="background:transparent;border:1px solid var(--vo-border);border-radius:.2rem;color:var(--vo-text-secondary);cursor:pointer;font-size:0.6rem;padding:1px 6px;margin-top:2px;">
                    + sub-item
                </button>
            @endif
        </div>

    </div>
</div>
