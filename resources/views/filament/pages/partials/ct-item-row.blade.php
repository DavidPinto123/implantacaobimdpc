@php $isChild = $depth > 0; @endphp
<div style="display:flex;flex-direction:column;gap:4px;{{ $isChild ? 'margin-left:20px;padding-left:8px;border-left:2px solid var(--vo-border);' : '' }}">
    <div style="display:flex;flex-direction:column;gap:5px;padding:5px 8px;background:{{ $isChild ? 'var(--vo-bg)' : 'var(--vo-bg-subtle)' }};border:1px solid var(--vo-border);border-radius:.375rem;">

        {{-- Cabeçalho do item --}}
        <div style="display:flex;align-items:center;gap:6px;">
            @if(!$isChild)
                <span style="font-size:0.65rem;color:var(--vo-text-faint);">▸</span>
            @else
                <span style="font-size:0.65rem;color:var(--vo-text-faint);">·</span>
            @endif
            <input type="text"
                   value="{{ $item->titulo }}"
                   wire:change="salvarTituloTemplateFaseItem({{ $item->id }}, $event.target.value)"
                   style="flex:1;font-size:0.8rem;font-weight:{{ $isChild ? '400' : '500' }};background:transparent;border:none;border-bottom:1px dashed transparent;outline:none;padding:1px 2px;color:var(--vo-text);"
                   onfocus="this.style.borderBottomColor='var(--vo-border)'"
                   onblur="this.style.borderBottomColor='transparent'">
            <button type="button"
                    wire:click="adicionarSubitemTemplateFaseItem({{ $item->id }})"
                    class="vo-btn-outline"
                    style="padding:2px 7px;font-size:0.65rem;">
                + subitem
            </button>
            <button type="button"
                    wire:click="adicionarDependenciaTemplateFaseItem({{ $item->id }})"
                    class="vo-btn-outline"
                    style="padding:2px 7px;font-size:0.65rem;">
                + dep
            </button>
            <button type="button"
                    wire:click="removerTemplateFaseItem({{ $item->id }})"
                    title="Remover item"
                    style="width:22px;height:22px;border:1px solid #fca5a5;background:transparent;color:#b91c1c;border-radius:.25rem;cursor:pointer;font-size:0.85rem;line-height:1;flex-shrink:0;">
                ×
            </button>
        </div>

        {{-- Dependências do item --}}
        @foreach($item->dependencias as $depItem)
            <div style="display:grid;grid-template-columns:1fr 82px 70px 24px;gap:4px;align-items:center;padding-left:14px;">
                <select wire:change="salvarAlvoDependenciaTemplateFaseItem({{ $depItem->id }}, $event.target.value)"
                        title="Dependência do item"
                        style="font-size:0.68rem;padding:3px 5px;border:1px solid var(--vo-border);border-radius:.25rem;background:var(--vo-bg);color:var(--vo-text-secondary);">
                    <option value="">Sem dependência</option>
                    <optgroup label="Fases">
                        @foreach($fasesOrdenadas->where('id', '!=', $editingFaseId)->sortBy('ordem') as $faseOpcao)
                            <option value="fase:{{ $faseOpcao->id }}" @selected($depItem->depende_de_template_fase_id === $faseOpcao->id)>
                                {{ $faseOpcao->fase->label() }}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Itens">
                        @foreach($fasesOrdenadas as $faseOpcao)
                            @foreach($faseOpcao->itens->where('id', '!=', $item->id)->sortBy('ordem') as $opcao)
                                @php
                                    $partes = [$faseOpcao->fase->label()];
                                    if ($opcao->parent_id && $opcao->parent) {
                                        $partes[] = $opcao->parent->titulo;
                                    }
                                    $partes[] = $opcao->titulo;
                                    $caminhoItem = implode(' › ', $partes);
                                @endphp
                                <option value="item:{{ $opcao->id }}" @selected($depItem->depende_de_item_id === $opcao->id)>
                                    {{ $caminhoItem }}
                                </option>
                            @endforeach
                        @endforeach
                    </optgroup>
                </select>
                <select wire:change="salvarGatilhoDependenciaTemplateFaseItem({{ $depItem->id }}, $event.target.value)"
                        style="font-size:0.68rem;padding:3px 5px;border:1px solid var(--vo-border);border-radius:.25rem;background:var(--vo-bg);color:var(--vo-text-secondary);">
                    @foreach($gatilhoOptions as $g)
                        <option value="{{ $g->value }}" @selected(($depItem->gatilho?->value ?? (string) $depItem->gatilho) === $g->value)>
                            {{ $g->labelCurto() }}
                        </option>
                    @endforeach
                </select>
                <input type="number"
                       value="{{ $depItem->gap_dias }}"
                       wire:change="salvarGapDependenciaTemplateFaseItem({{ $depItem->id }}, $event.target.value)"
                       style="font-size:0.68rem;padding:3px 5px;border:1px solid var(--vo-border);border-radius:.25rem;background:var(--vo-bg);color:var(--vo-text-secondary);">
                <button type="button"
                        wire:click="removerDependenciaTemplateFaseItem({{ $depItem->id }})"
                        title="Remover dependência"
                        style="background:transparent;border:none;color:#b91c1c;cursor:pointer;font-size:0.85rem;line-height:1;">
                    ×
                </button>
            </div>
        @endforeach
    </div>

    {{-- Filhos (profundidade ilimitada) --}}
    @if($item->children->isNotEmpty())
        @foreach($item->children as $child)
            @include('filament.pages.partials.ct-item-row', ['item' => $child, 'fasesOrdenadas' => $fasesOrdenadas ?? [], 'gatilhoOptions' => $gatilhoOptions, 'editingFaseId' => $editingFaseId, 'depth' => $depth + 1])
        @endforeach
    @endif
</div>
