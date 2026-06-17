@php
    $fasesDependencia = $fasesDependencia ?? collect();
    $podeTerFilho     = $depth < 2;
    $numPrefix        = $numPrefix ?? null;
@endphp
@php
    $si_prevI_str = $item->data_prevista_inicio  ? \Carbon\Carbon::parse($item->data_prevista_inicio)->format('d/m/y')  : null;
    $si_prevF_str = $item->data_prevista_fim     ? \Carbon\Carbon::parse($item->data_prevista_fim)->format('d/m/y')     : null;
    $si_realI_str = $item->data_realizada_inicio ? \Carbon\Carbon::parse($item->data_realizada_inicio)->format('d/m/y') : null;
    $si_realF_str = $item->data_realizada_fim    ? \Carbon\Carbon::parse($item->data_realizada_fim)->format('d/m/y')    : null;
@endphp
<div class="cr-row-left cr-subitem-gantt-row" wire:key="sg-{{ $item->id }}">
    <div class="cr-col-fase" style="padding-left: {{ $depth * 16 }}px">
        <span class="cr-subitem-tree">└</span>
        @if($numPrefix)
            <span style="color:var(--vo-text-faint);font-size:0.68rem;font-weight:700;flex-shrink:0;white-space:nowrap;">{{ $numPrefix }}</span>
        @endif
        @if($podeTerFilho && ($podeEditar ?? true))
            <button type="button"
                    wire:click="alternarAdicionarFilho({{ $item->id }})"
                    title="Adicionar subitem dentro deste"
                    class="cr-subitem-child-btn">
                +
            </button>
        @endif
        @if($podeEditar ?? true)
        <input type="checkbox"
               @checked($item->recebido)
               wire:click="alternarRecebidoSubitem({{ $item->id }})"
               style="width:13px;height:13px;cursor:pointer;flex-shrink:0;">
        @else
        <span style="width:13px;height:13px;flex-shrink:0;border:2px solid var(--vo-border);border-radius:2px;display:inline-block;{{ $item->recebido ? 'background:var(--cr-concluido);border-color:var(--cr-concluido);' : '' }}"></span>
        @endif
        @if($podeEditar ?? true)
        <textarea x-data
                  x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                  @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                  @focus="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                  wire:change="salvarTituloSubitem({{ $item->id }}, $event.target.value)"
                  rows="1"
                  class="cr-subitem-titulo-inline {{ $item->recebido ? 'cr-subitem-done' : '' }}">{{ $item->titulo }}</textarea>
        @else
        <span class="cr-subitem-titulo-inline {{ $item->recebido ? 'cr-subitem-done' : '' }}" style="cursor:default;">{{ $item->titulo }}</span>
        @endif
        <span class="cr-subitem-status-badge" style="background:{{ $item->recebido ? 'var(--cr-concluido, #2dd67c)' : 'var(--cr-nao-iniciado, #6b7280)' }};">
            {{ $item->recebido ? 'Ok' : '—' }}
        </span>
        @if($podeEditar ?? true)
        <button type="button" wire:click="removerSubitem({{ $item->id }})"
                title="Remover subitem"
                x-show="!mostrarDeps" x-cloak
                style="margin-left:auto;background:transparent;border:none;color:var(--vo-text-faint);cursor:pointer;padding:2px 6px;font-size:0.85rem;line-height:1;">
            ×
        </button>
        @endif
    </div>
    <div class="cr-gantt-col cr-gantt-col-status" x-show="ganttCols.status" x-cloak>
        <span class="cr-status-pill" style="background:{{ $item->recebido ? 'var(--cr-concluido)' : 'var(--cr-nao-iniciado)' }};font-size:0.6rem;padding:2px 8px;">
            {{ $item->recebido ? 'Concluído' : 'Pendente' }}
        </span>
    </div>
    <div class="cr-gantt-col cr-gantt-col-pct" x-show="ganttCols.pct" x-cloak
         style="font-weight:700;font-size:0.72rem;color:{{ $item->recebido ? 'var(--cr-concluido)' : 'var(--vo-text-faint)' }}">
        {{ $item->recebido ? '100%' : '—' }}
    </div>
    <div class="cr-gantt-col cr-gantt-col-plan" x-show="ganttCols.planejado" x-cloak
         style="font-variant-numeric:tabular-nums;font-size:0.7rem;">
        @if($si_prevI_str && $si_prevF_str)
            {{ $si_prevI_str }} <span style="color:var(--vo-text-faint);">–</span> {{ $si_prevF_str }}
        @else
            <span style="color:var(--vo-text-faint);">—</span>
        @endif
    </div>
    <div class="cr-gantt-col cr-gantt-col-real" x-show="ganttCols.realizado" x-cloak
         style="font-variant-numeric:tabular-nums;font-size:0.7rem;">
        @if($si_realI_str && $si_realF_str)
            {{ $si_realI_str }} <span style="color:var(--vo-text-faint);">–</span> {{ $si_realF_str }}
        @elseif($si_realI_str)
            {{ $si_realI_str }} <span style="color:var(--vo-text-faint);">– em curso</span>
        @else
            <span style="color:var(--vo-text-faint);">—</span>
        @endif
    </div>
    <div class="cr-col-deps" x-show="mostrarDeps" x-cloak>
        @if($podeTerFilho && ($podeEditar ?? true))
            <button type="button"
                    wire:click="alternarAdicionarFilho({{ $item->id }})"
                    title="Adicionar subitem dentro deste"
                    class="cr-subitem-child-btn cr-subitem-child-btn-deps">
                +
            </button>
        @endif
        <div style="display:flex;flex-direction:column;gap:3px;min-width:0;flex:1;">
            @if($podeEditar ?? true)
            @forelse($item->dependencias as $dep)
                <div style="display:flex;align-items:center;gap:3px;min-width:0;">
                    <select wire:change="salvarAlvoDependenciaSubitem({{ $dep->id }}, $event.target.value)"
                            title="Dependência do subitem"
                            class="cr-subitem-dep-select">
                        <option value="">Sem dependência</option>
                        <optgroup label="Fases">
                            @foreach($fasesDependencia->where('id', '!=', $item->cronograma_fase_id)->sortBy('ordem') as $faseOpcao)
                                <option value="fase:{{ $faseOpcao->id }}" @selected($dep->depende_de_fase_id === $faseOpcao->id)>
                                    {{ $faseOpcao->label_exibicao }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Subitens">
                            @foreach($fasesDependencia->sortBy('ordem') as $faseOpcao)
                                @foreach($faseOpcao->itens->where('id', '!=', $item->id)->sortBy('ordem') as $opcao)
                                    <option value="item:{{ $opcao->id }}" @selected($dep->depende_de_item_id === $opcao->id)>
                                        {{ $faseOpcao->label_exibicao }} / {{ $opcao->titulo }}
                                    </option>
                                @endforeach
                            @endforeach
                        </optgroup>
                    </select>
                    <select wire:change="salvarGatilhoDependenciaSubitem({{ $dep->id }}, $event.target.value)"
                            class="cr-subitem-dep-trigger"
                            title="Gatilho">
                        @foreach(\App\Enums\GatilhoTemplateFase::cases() as $gatilho)
                            <option value="{{ $gatilho->value }}" @selected(($dep->gatilho?->value ?? (string) $dep->gatilho) === $gatilho->value)>
                                {{ $gatilho->labelCurto() }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number"
                           value="{{ $dep->gap_dias }}"
                           wire:change="salvarGapDependenciaSubitem({{ $dep->id }}, $event.target.value)"
                           class="cr-subitem-dep-gap"
                           title="Deslocamento em dias">
                    <button type="button" wire:click="removerDependenciaSubitem({{ $dep->id }})"
                            title="Remover dependência"
                            style="background:transparent;border:none;color:var(--vo-text-faint);cursor:pointer;padding:1px 4px;line-height:1;">
                        ×
                    </button>
                </div>
            @empty
                <span style="color:var(--vo-text-faint);font-size:0.65rem;">Sem dependência</span>
            @endforelse
            @else
            @forelse($item->dependencias as $dep)
                <span style="font-size:0.65rem;color:var(--vo-text-secondary);">← {{ $dep->dependeDeFase?->label_exibicao ?? $dep->dependeDeItem?->titulo ?? '?' }}</span>
            @empty
                <span style="color:var(--vo-text-faint);font-size:0.65rem;">Sem dependência</span>
            @endforelse
            @endif
        </div>
        @if($podeEditar ?? true)
        <button type="button" wire:click="adicionarDependenciaSubitem({{ $item->id }})"
                title="Adicionar dependência"
                style="background:transparent;border:1px solid var(--vo-border);border-radius:4px;color:var(--vo-text-secondary);cursor:pointer;font-size:.7rem;line-height:1;padding:2px 5px;">
            dep+
        </button>
        @endif
        @if($podeEditar ?? true)
        <button type="button" wire:click="removerSubitem({{ $item->id }})"
                title="Remover subitem"
                style="background:transparent;border:none;color:var(--vo-text-faint);cursor:pointer;padding:2px 6px;font-size:0.85rem;line-height:1;">
            ×
        </button>
        @endif
    </div>
</div>
@if($podeTerFilho && ($podeEditar ?? true) && $expandindoFilhosDeItemId === $item->id)
    <div class="cr-row-left cr-subitem-gantt-row cr-subitem-add-row" wire:key="sg-add-{{ $item->id }}">
        <div class="cr-col-fase" style="padding-left: {{ ($depth + 1) * 16 }}px">
            <span class="cr-subitem-tree">+</span>
            <input type="text"
                   wire:model="novoFilhoTitulo"
                   wire:keydown.enter.prevent="adicionarFilhoItem({{ $item->id }})"
                   placeholder="Título do sub-item…"
                   class="cr-subitem-titulo-inline">
            <button type="button" wire:click="adicionarFilhoItem({{ $item->id }})" class="cr-subitem-add-btn">
                Adicionar
            </button>
            <button type="button" wire:click="$set('expandindoFilhosDeItemId', null)"
                    style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-faint);padding:2px 4px;line-height:1;">
                ×
            </button>
        </div>
    </div>
@endif
@foreach($item->children as $child)
    @include('filament.pages.cronograma-subitem-gantt', ['item' => $child, 'depth' => $depth + 1, 'fasesDependencia' => $fasesDependencia, 'numPrefix' => $numPrefix ? $numPrefix . '.' . $loop->iteration : null])
@endforeach
