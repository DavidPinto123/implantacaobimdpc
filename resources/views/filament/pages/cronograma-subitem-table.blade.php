@php
    $fasesDependencia = $fasesDependencia ?? collect();
    $numPrefix        = $numPrefix ?? null;
    $indentPx         = 18 + $depth * 20;
    $podeTerFilho     = $depth < 2;

    // Duração: filhos → soma; folha → duracao_dias próprio; fallback → datas
    $filhosItem = $item->children;
    if ($filhosItem->isNotEmpty()) {
        $durFilhos  = $filhosItem->sum('duracao_dias');
        $durSubitem = $durFilhos ?: (($item->data_prevista_inicio && $item->data_prevista_fim)
            ? $item->data_prevista_inicio->diffInDays($item->data_prevista_fim) + 1
            : null);
    } else {
        $durSubitem = $item->duracao_dias
            ?: (($item->data_prevista_inicio && $item->data_prevista_fim)
                ? $item->data_prevista_inicio->diffInDays($item->data_prevista_fim) + 1
                : null);
    }

    // Status e percentual
    if ($filhosItem->isNotEmpty()) {
        $totalFilhos    = $filhosItem->count();
        $recebFilhos    = $filhosItem->where('recebido', true)->count();
        $pctItem        = (int) round($recebFilhos / $totalFilhos * 100);
        $statusItemCor  = $pctItem === 100 ? 'var(--cr-concluido, #2dd67c)' : ($pctItem > 0 ? '#f59e0b' : 'var(--cr-nao-iniciado, #6b7280)');
        $statusItemLabel = $pctItem === 100 ? 'Recebido' : ($pctItem > 0 ? 'Parcial' : 'Pendente');
    } else {
        $pctItem        = $item->recebido ? 100 : 0;
        $statusItemCor  = $item->recebido ? 'var(--cr-concluido, #2dd67c)' : 'var(--cr-nao-iniciado, #6b7280)';
        $statusItemLabel = $item->recebido ? 'Recebido' : 'Pendente';
    }

    $usaTriEstado = $item->fase?->fase === \App\Enums\FaseCronograma::LIBERACAO_POSSE;
    if ($usaTriEstado && $item->status_liberacao) {
        $statusItemCor   = $item->status_liberacao->color();
        $statusItemLabel = $item->status_liberacao->label();
    }

    $mostrarFarolEntrega = $item->fase?->fase === \App\Enums\FaseCronograma::ENTREGAS_PROPRIETARIO
        && $item->data_prevista_fim
        && ! $item->recebido;
    if ($mostrarFarolEntrega) {
        $hoje = now()->startOfDay();
        $prazoFim = $item->data_prevista_fim->copy()->startOfDay();
        $diasParaPrazo = (int) $hoje->diffInDays($prazoFim, false);
        if ($diasParaPrazo < 0) {
            $statusItemCor = '#ef4444'; $statusItemLabel = 'Atrasado';
        } elseif ($diasParaPrazo <= 7) {
            $statusItemCor = '#f59e0b'; $statusItemLabel = 'Próximo do prazo';
        } else {
            $statusItemCor = '#22c55e'; $statusItemLabel = 'No prazo';
        }
    }

    // Responsáveis já carregados via eager load
    $responsaveis = $item->relationLoaded('responsaveis') ? $item->responsaveis : collect();
@endphp
<tr class="cr-subitem-tr" wire:key="st-{{ $item->id }}">
    {{-- Fase / Título --}}
    <td class="cr-td-sticky cr-col-fase">
        <span style="display:flex;align-items:flex-start;gap:6px;padding-left:{{ $indentPx }}px;">
            <span class="cr-subitem-tree" style="margin-top:2px;">└</span>
            @if($numPrefix)
                <span style="color:var(--vo-text-faint);font-size:0.68rem;font-weight:700;flex-shrink:0;white-space:nowrap;margin-top:3px;">{{ $numPrefix }}</span>
            @endif
            @if($podeTerFilho)
                <button type="button"
                        wire:click="alternarAdicionarFilho({{ $item->id }})"
                        title="Adicionar subitem dentro deste"
                        class="cr-subitem-child-btn"
                        style="margin-top:1px;">+</button>
            @endif
            @if($usaTriEstado)
                <span style="display:inline-flex;gap:3px;flex-shrink:0;margin-top:1px;">
                    @foreach(\App\Enums\StatusLiberacaoPosse::cases() as $st)
                        @php $ativo = $item->status_liberacao === $st; @endphp
                        <button type="button"
                                wire:click="alterarStatusLiberacao({{ $item->id }}, '{{ $st->value }}')"
                                title="{{ $st->label() }}"
                                style="padding:2px 7px;font-size:0.62rem;font-weight:700;border-radius:99px;cursor:pointer;border:1px solid {{ $ativo ? $st->color() : 'var(--vo-border)' }};background:{{ $ativo ? $st->color() : 'transparent' }};color:{{ $ativo ? '#fff' : $st->color() }};line-height:1;">
                            {{ strtoupper(substr($st->label(), 0, 1)) }}
                        </button>
                    @endforeach
                </span>
            @else
                <input type="checkbox"
                       @checked($item->recebido)
                       wire:click="alternarRecebidoSubitem({{ $item->id }})"
                       style="width:13px;height:13px;cursor:pointer;flex-shrink:0;margin-top:3px;">
            @endif
            <textarea x-data
                      x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                      @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                      @focus="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                      wire:change="salvarTituloSubitem({{ $item->id }}, $event.target.value)"
                      rows="1"
                      class="cr-subitem-titulo-inline {{ $item->recebido ? 'cr-subitem-done' : '' }}">{{ $item->titulo }}</textarea>
        </span>
    </td>

    {{-- Status --}}
    <td class="cr-td-sticky cr-col-status">
        <span class="cr-subitem-status-badge" style="background:{{ $statusItemCor }}">
            {{ $statusItemLabel }}
        </span>
    </td>

    {{-- Planejado --}}
    <td x-show="cols.planejado" style="font-variant-numeric:tabular-nums;color:var(--vo-text-secondary);">
        <div style="display:flex;gap:4px;align-items:center;"
             x-data="{ pi: '{{ $item->data_prevista_inicio?->toDateString() }}', pf: '{{ $item->data_prevista_fim?->toDateString() }}' }">
            <input type="date" x-model="pi"
                   @blur="if (pi) $wire.salvarDataInlineSubitem({{ $item->id }}, 'data_prevista_inicio', pi)"
                   class="cr-inline-date">
            <button type="button" class="cr-date-copy-btn" :disabled="!pi"
                    @click="if (pi) { pf = pi; $wire.salvarDataInlineSubitem({{ $item->id }}, 'data_prevista_fim', pi) }"
                    title="Copiar data de início para fim">→</button>
            <input type="date" x-model="pf"
                   @blur="if (pf) $wire.salvarDataInlineSubitem({{ $item->id }}, 'data_prevista_fim', pf)"
                   class="cr-inline-date">
        </div>
    </td>

    {{-- Duração --}}
    <td x-show="cols.durplan" class="cr-td-center" style="font-variant-numeric:tabular-nums;">
        @if($filhosItem->isNotEmpty())
            {{-- Pai: mostra soma dos filhos (read-only) --}}
            @if($durSubitem !== null)
                <span title="Soma da duração dos subitens">{{ $durSubitem }} dias</span>
            @else
                <span style="color:var(--vo-text-faint)">-</span>
            @endif
        @else
            {{-- Folha: campo editável de duracao_dias --}}
            <input type="number"
                   value="{{ $item->duracao_dias ?? '' }}"
                   min="0"
                   wire:change="salvarDuracaoSubitem({{ $item->id }}, $event.target.value)"
                   title="Duração em dias"
                   style="width:52px;text-align:center;border:1px solid var(--vo-border);border-radius:.25rem;background:var(--vo-bg);color:var(--vo-text);font-size:0.75rem;padding:2px 4px;">
            <span style="font-size:0.65rem;color:var(--vo-text-faint);"> d</span>
        @endif
    </td>

    {{-- Realizado --}}
    <td x-show="cols.realizado" style="font-variant-numeric:tabular-nums;color:var(--vo-text-secondary);">
        <div style="display:flex;gap:4px;align-items:center;"
             x-data="{ ri: '{{ $item->data_realizada_inicio?->toDateString() }}', rf: '{{ $item->data_realizada_fim?->toDateString() }}' }">
            <input type="date" x-model="ri"
                   @blur="if (ri) $wire.salvarDataInlineSubitem({{ $item->id }}, 'data_realizada_inicio', ri)"
                   class="cr-inline-date">
            <button type="button" class="cr-date-copy-btn" :disabled="!ri"
                    @click="if (ri) { rf = ri; $wire.salvarDataInlineSubitem({{ $item->id }}, 'data_realizada_fim', ri) }"
                    title="Copiar data de início para fim">→</button>
            <input type="date" x-model="rf"
                   @blur="if (rf) $wire.salvarDataInlineSubitem({{ $item->id }}, 'data_realizada_fim', rf)"
                   class="cr-inline-date">
        </div>
    </td>

    {{-- % --}}
    <td x-show="cols.pct" class="cr-td-center">
        <span style="font-weight:600;font-size:0.7rem;color:{{ $statusItemCor }}">{{ $pctItem }}%</span>
    </td>

    {{-- Valor (apenas quem tem permissão) --}}
    @can('ver_valores_planejamento')
    <td x-show="cols.valor" style="text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;font-size:0.78rem;">
        @if($item->valor)
            <span style="color:var(--vo-text-secondary);font-size:0.7rem;">R$</span>
            <span style="font-weight:600;">{{ number_format($item->valor, 2, ',', '.') }}</span>
        @else
            <span style="color:var(--vo-text-faint);">—</span>
        @endif
    </td>
    @endcan

    {{-- Responsáveis --}}
    <td x-show="cols.responsaveis" style="min-width:140px;">
        <div x-data="{ aberto: false }" style="display:flex;flex-wrap:wrap;gap:4px;align-items:center;">
            @foreach($responsaveis as $resp)
                <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 6px;background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:99px;font-size:0.65rem;white-space:nowrap;">
                    <span style="font-weight:600;">{{ \Illuminate\Support\Str::limit($resp->name, 18) }}</span>
                    <button type="button"
                            wire:click="removerResponsavelSubitem({{ $item->id }}, {{ $resp->id }})"
                            title="Remover responsável"
                            style="background:none;border:none;cursor:pointer;color:var(--vo-text-faint);padding:0 1px;line-height:1;font-size:0.75rem;">×</button>
                </span>
            @endforeach
            <div style="position:relative;">
                <button type="button" @click="aberto = !aberto"
                        title="Adicionar responsável"
                        style="padding:2px 6px;font-size:0.65rem;border:1px dashed var(--vo-border);border-radius:99px;background:transparent;cursor:pointer;color:var(--vo-text-secondary);">
                    + resp.
                </button>
                <div x-show="aberto" x-cloak @click.outside="aberto = false"
                     style="position:absolute;z-index:50;top:100%;left:0;margin-top:4px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.4rem;padding:6px;min-width:180px;box-shadow:0 4px 12px rgba(0,0,0,.15);">
                    <select @change="$wire.adicionarResponsavelSubitem({{ $item->id }}, parseInt($event.target.value)); aberto = false; $event.target.value = ''"
                            style="width:100%;border:1px solid var(--vo-border);border-radius:.25rem;background:var(--vo-bg);color:var(--vo-text);font-size:0.75rem;padding:4px;">
                        <option value="">— selecione —</option>
                        @foreach($usuarios ?? [] as $u)
                            @if(! $responsaveis->contains('id', $u->id))
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </td>

    {{-- Dependências --}}
    <td x-show="cols.deps">
        <div style="display:flex;flex-direction:column;gap:3px;">
            @forelse($item->dependencias as $dep)
                <div style="display:flex;align-items:center;gap:3px;">
                    <select wire:change="salvarAlvoDependenciaSubitem({{ $dep->id }}, $event.target.value)"
                            title="Dependência do subitem" class="cr-subitem-dep-select">
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
                            class="cr-subitem-dep-trigger" title="Gatilho">
                        @foreach(\App\Enums\GatilhoTemplateFase::cases() as $gatilho)
                            <option value="{{ $gatilho->value }}" @selected(($dep->gatilho?->value ?? (string) $dep->gatilho) === $gatilho->value)>
                                {{ $gatilho->labelCurto() }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" value="{{ $dep->gap_dias }}"
                           wire:change="salvarGapDependenciaSubitem({{ $dep->id }}, $event.target.value)"
                           class="cr-subitem-dep-gap" title="Deslocamento em dias">
                    <button type="button" wire:click="removerDependenciaSubitem({{ $dep->id }})"
                            title="Remover dependência"
                            style="background:transparent;border:none;color:var(--vo-text-faint);cursor:pointer;padding:1px 4px;line-height:1;">×</button>
                </div>
            @empty
                <span style="color:var(--vo-text-faint);font-size:0.65rem;">Sem dependência</span>
            @endforelse
            <button type="button" wire:click="adicionarDependenciaSubitem({{ $item->id }})"
                    style="align-self:flex-start;background:transparent;border:1px solid var(--vo-border);border-radius:4px;color:var(--vo-text-secondary);cursor:pointer;font-size:.68rem;line-height:1;padding:2px 6px;">
                + dependência
            </button>
        </div>
    </td>

    {{-- Observações --}}
    <td x-show="cols.comentarios">
        <textarea x-data
                  x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                  @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                  @focus="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                  wire:change="salvarObservacoesSubitem({{ $item->id }}, $event.target.value)"
                  placeholder="Observação…" rows="1"
                  class="cr-subitem-obs-inline">{{ $item->observacoes }}</textarea>
    </td>

    {{-- Remover --}}
    <td style="text-align:center;">
        <button type="button" wire:click="removerSubitem({{ $item->id }})" title="Remover subitem"
                style="padding:3px 5px;border:1px solid var(--vo-border);background:transparent;border-radius:.25rem;cursor:pointer;color:var(--vo-text-muted);line-height:1;">×</button>
    </td>
</tr>

{{-- Linha de adição de filho --}}
@if($podeTerFilho && $expandindoFilhosDeItemId === $item->id)
    <tr class="cr-subitem-add-tr" wire:key="st-add-{{ $item->id }}">
        <td class="cr-td-sticky cr-col-fase" colspan="2">
            <div style="display:flex;gap:6px;padding-left:{{ $indentPx + 20 }}px;align-items:center;">
                <span class="cr-subitem-tree">+</span>
                <input type="text"
                       wire:model="novoFilhoTitulo"
                       wire:keydown.enter.prevent="adicionarFilhoItem({{ $item->id }})"
                       placeholder="Título do sub-item…"
                       class="cr-subitem-titulo-inline">
                <button type="button" wire:click="adicionarFilhoItem({{ $item->id }})" class="cr-subitem-add-btn">Adicionar</button>
                <button type="button" wire:click="$set('expandindoFilhosDeItemId', null)"
                        style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-faint);padding:2px 4px;line-height:1;">×</button>
            </div>
        </td>
        <td colspan="20"></td>
    </tr>
@endif

{{-- Filhos recursivos --}}
@foreach($item->children->sortBy('ordem') as $child)
    @php $childNum = $loop->iteration; @endphp
    @include('filament.pages.cronograma-subitem-table', [
        'item'             => $child,
        'depth'            => $depth + 1,
        'fasesDependencia' => $fasesDependencia,
        'numPrefix'        => $numPrefix ? $numPrefix . '.' . $childNum : null,
        'usuarios'         => $usuarios ?? [],
    ])
@endforeach
