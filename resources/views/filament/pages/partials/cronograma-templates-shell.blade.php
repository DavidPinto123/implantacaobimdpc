{{-- Shell com Gantt simulado à esquerda + Drawer "Editar fases" à direita.
     Usado no modo individual da Page CronogramaTemplates. --}}

@php
    $simBuf = $bufferSimulacao ?? [];
    // Calcula intervalo de datas para o Gantt simulado.
    $datasIni = [];
    $datasFim = [];
    foreach ($simBuf as $d) {
        if (!empty($d['inicio'])) $datasIni[] = $d['inicio'];
        if (!empty($d['fim']))    $datasFim[] = $d['fim'];
    }
    $minIni = !empty($datasIni) ? min($datasIni) : null;
    $maxFim = !empty($datasFim) ? max($datasFim) : null;
    $diasGrade = [];
    if ($minIni && $maxFim) {
        $diaSemanaPt = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
        $cursor = \Carbon\CarbonImmutable::parse($minIni);
        $fim = \Carbon\CarbonImmutable::parse($maxFim);
        while ($cursor->lessThanOrEqualTo($fim)) {
            $diasGrade[] = [
                'data' => $cursor->toDateString(),
                'dataFmt' => $cursor->format('d/m/Y'),
                'dataLong' => $diaSemanaPt[$cursor->dayOfWeek] . ', ' . $cursor->format('d/m/Y'),
                'dia' => $cursor->day,
                'mes' => $cursor->locale('pt_BR')->isoFormat('MMM'),
                'isWeekend' => $cursor->isWeekend(),
            ];
            $cursor = $cursor->addDay();
        }
    }
@endphp

<div class="ct-editor-shell">
    {{-- ────── Coluna principal: banner + Gantt ────── --}}
    <div class="ct-editor-main">

        {{-- Banner: alterações não salvas --}}
        @if($bufferDirty)
            <div class="ct-dirty-banner">
                <span>● Alterações não salvas no template — preview do Gantt já reflete as mudanças.</span>
                <div class="ct-dirty-actions">
                    <button type="button" class="ct-dirty-discard" wire:click="$set('mostrarConfirmacaoDescarte', true)">
                        Descartar
                    </button>
                    <button type="button" class="ct-dirty-save" wire:click="salvarBuffer">
                        Salvar template
                    </button>
                </div>
            </div>
        @endif

        @if($bufferErroSimulacao)
            <div class="ct-error-banner">
                ⚠ Erro na simulação: {{ $bufferErroSimulacao }}
            </div>
        @endif

        {{-- Header do Gantt: data-âncora + toggle drawer --}}
        <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--vo-border);background:var(--vo-bg-subtle);">
            <span style="font-size:0.75rem;color:var(--vo-text-muted);font-weight:600;">Simulação:</span>
            <div class="ct-gantt-anchor-input">
                <span style="color:var(--vo-text-muted);">Âncora</span>
                <input type="date" wire:model.live="bufferSimulacaoAncora">
            </div>
            <span style="font-size:0.7rem;color:var(--vo-text-faint);">
                {{ count($diasGrade) }} dia(s) · {{ count($simBuf) }} fase(s)
            </span>
            <button type="button"
                    wire:click="$toggle('mostrarEditorFases')"
                    class="vo-btn-outline"
                    style="margin-left:auto;{{ $mostrarEditorFases ? 'background:var(--vo-accent);color:#111;border-color:var(--vo-accent);' : '' }}">
                {{ $mostrarEditorFases ? '← Fechar painel de fases' : 'Editar fases →' }}
            </button>
        </div>

        {{-- Gantt simulado --}}
        <div class="ct-gantt-wrap">
            @if(empty($diasGrade) || empty($simBuf))
                <div class="ct-empty">
                    @if(empty($fases))
                        Este template ainda não tem fases. Use o drawer "Editar fases" para adicionar.
                    @else
                        Nenhuma simulação disponível. Defina uma fase âncora.
                    @endif
                </div>
            @else
                <div x-data="{
                    hoveredRow: null,
                    hoveredCol: null,
                    selectedRow: null,
                    selectedCol: null,
                    isRowOn(r) { return this.hoveredRow === r || this.selectedRow === r; },
                    isColOn(c) { return this.hoveredCol === c || this.selectedCol === c; },
                    selectCell(r, c) {
                        // toggle: clicar de novo na mesma cruz desfaz
                        if (this.selectedRow === r && this.selectedCol === c) {
                            this.selectedRow = null; this.selectedCol = null;
                        } else {
                            this.selectedRow = r; this.selectedCol = c;
                        }
                    }
                }">
                <table class="ct-gantt-table">
                    <thead>
                        {{-- Linha 1: contagem absoluta de dias do cronograma (1, 2, 3, ...) --}}
                        <tr>
                            <th class="ct-gantt-fase-col" style="font-size:0.6rem;color:var(--vo-text-faint);text-transform:uppercase;letter-spacing:.05em;">Dia do cronograma</th>
                            @foreach($diasGrade as $idx => $dia)
                                @php $absDia = $idx + 1; @endphp
                                <th class="ct-gantt-abs-cell {{ $dia['isWeekend'] ? 'ct-gantt-day-weekend' : '' }}"
                                    :class="{ 'ct-gantt-col-on': isColOn({{ $idx }}) }"
                                    @mouseenter="hoveredCol = {{ $idx }}"
                                    @mouseleave="hoveredCol = null">
                                    {{ $absDia }}
                                </th>
                            @endforeach
                        </tr>
                        {{-- Linha 2: dia do mês --}}
                        <tr>
                            <th class="ct-gantt-fase-col">Fase</th>
                            @foreach($diasGrade as $idx => $dia)
                                <th class="ct-gantt-day-cell {{ $dia['isWeekend'] ? 'ct-gantt-day-weekend' : '' }}"
                                    :class="{ 'ct-gantt-col-on': isColOn({{ $idx }}) }"
                                    @mouseenter="hoveredCol = {{ $idx }}"
                                    @mouseleave="hoveredCol = null"
                                    title="{{ $dia['dataLong'] }}">
                                    {{ $dia['dia'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fases as $rowIdx => $fase)
                            @php
                                $value = $fase->fase->value;
                                $simData = $simBuf[$value] ?? null;
                                $iniIso = $simData['inicio'] ?? null;
                                // Fonte de verdade pra visível/âncora vem do buffer
                                // (preview reflete edições antes de salvar).
                                $bufFase = $bufferTemplate[$fase->id] ?? null;
                                $oculta  = $bufFase ? empty($bufFase['visivel']) : ! $fase->visivel;
                                $ehAncora = $bufFase ? ! empty($bufFase['is_ancora']) : (bool) $fase->is_ancora;
                            @endphp
                            <tr class="{{ $oculta ? 'ct-gantt-row-oculta' : '' }}"
                                :class="{ 'ct-gantt-row-on': isRowOn({{ $rowIdx }}) }"
                                @mouseenter="hoveredRow = {{ $rowIdx }}"
                                @mouseleave="hoveredRow = null">
                                <td class="ct-gantt-fase-col">
                                    @if($fase->fase->marco())
                                        <span style="display:inline-block;width:7px;height:7px;transform:rotate(45deg);background:var(--vo-accent);margin-right:6px;"></span>
                                    @endif
                                    {{-- Buffer pode ter título personalizado editado pendente --}}
                                    @php
                                        $tituloBuf = trim((string) ($bufFase['titulo_personalizado'] ?? ''));
                                        $tituloDisplay = $tituloBuf !== ''
                                            ? $tituloBuf
                                            : ((string) ($fase->titulo_personalizado ?? '') !== ''
                                                ? $fase->titulo_personalizado
                                                : $fase->fase->label());
                                    @endphp
                                    {{ $tituloDisplay }}
                                    @if($ehAncora)
                                        <span style="font-size:0.55rem;color:var(--vo-accent);margin-left:4px;">⚓</span>
                                    @endif
                                    @if($oculta)
                                        <span style="font-size:0.55rem;padding:1px 5px;background:#fef9c3;color:#854d0e;border:1px solid #fde68a;border-radius:99px;font-weight:600;margin-left:4px;">oculta</span>
                                    @endif
                                </td>
                                @foreach($diasGrade as $idx => $dia)
                                    @php
                                        $ativo = $simData
                                            && $dia['data'] >= $simData['inicio']
                                            && $dia['data'] <= $simData['fim'];
                                        $diaFase = null;
                                        if ($ativo && $iniIso) {
                                            $diaFase = (int) (\Carbon\CarbonImmutable::parse($iniIso)
                                                ->diffInDays(\Carbon\CarbonImmutable::parse($dia['data']), absolute: true)) + 1;
                                        }
                                    @endphp
                                    <td class="ct-gantt-day-cell {{ $ativo ? 'ct-gantt-day-active' : ($dia['isWeekend'] ? 'ct-gantt-day-weekend' : '') }}"
                                        :class="{ 'ct-gantt-col-on': isColOn({{ $idx }}), 'ct-gantt-cell-selected': selectedRow === {{ $rowIdx }} && selectedCol === {{ $idx }} }"
                                        @mouseenter="hoveredCol = {{ $idx }}"
                                        @click="selectCell({{ $rowIdx }}, {{ $idx }})"
                                        title="{{ $dia['dataLong'] }}">
                                        @if($diaFase !== null)
                                            <span class="ct-gantt-cell-num">{{ $diaFase }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ────── Drawer "Editar fases" ────── --}}
    <div class="ct-editor-fases-panel {{ $mostrarEditorFases ? 'open' : '' }}">
        <div class="ct-editor-panel-header">
            <div style="display:flex;align-items:center;gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--vo-text-muted);"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="10" y2="18"/><circle cx="19" cy="15" r="3"/></svg>
                <span style="font-weight:700;font-size:0.9rem;color:var(--vo-text);">Editar fases</span>
                @if($bufferDirty)
                    <span style="font-size:0.62rem;padding:1px 6px;background:#fef3c7;color:#92400e;border-radius:99px;font-weight:600;">não salvas</span>
                @endif
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <button type="button"
                        wire:click="salvarBuffer"
                        @if(!$bufferDirty) disabled @endif
                        style="padding:5px 16px;font-size:0.78rem;font-weight:600;background:var(--vo-accent);color:#111;border:1px solid var(--vo-accent);border-radius:.375rem;cursor:pointer;{{ $bufferDirty ? '' : 'opacity:.5;cursor:not-allowed;' }}">
                    Salvar
                </button>
                <button type="button" wire:click="fecharEditorFases"
                        style="background:transparent;border:none;cursor:pointer;color:var(--vo-text-muted);font-size:1.2rem;line-height:1;padding:3px 6px;border-radius:.25rem;">
                    ×
                </button>
            </div>
        </div>

        <div class="ct-editor-panel-body">
            <div x-data="{
                openCards: {},
                isOpen(id)     { return !!this.openCards[id]; },
                toggleCard(id) { this.openCards[id] = !this.openCards[id]; },
                dragSrc: null,
                dragTarget: null,
                onDrop(targetId) {
                    if (this.dragSrc !== null && this.dragSrc !== targetId) {
                        const ids = Array.from(this.$root.querySelectorAll('[data-fase-id]')).map(el => el.dataset.faseId);
                        const srcIdx = ids.indexOf(String(this.dragSrc));
                        const tgtIdx = ids.indexOf(String(targetId));
                        if (srcIdx !== -1 && tgtIdx !== -1) {
                            const [moved] = ids.splice(srcIdx, 1);
                            ids.splice(tgtIdx, 0, moved);
                            $wire.atualizarBufferOrdem(ids);
                        }
                    }
                    this.dragSrc = null; this.dragTarget = null;
                }
            }" style="display:flex;flex-direction:column;gap:8px;">

                @php
                    $bufferOrdenado = collect($bufferTemplate ?? [])
                        ->sortBy(fn ($d) => $d['ordem'] ?? 99);
                @endphp

                @foreach($bufferOrdenado as $faseId => $bufFase)
                    @php
                        $faseEnum = \App\Enums\FaseCronograma::tryFrom($bufFase['fase'] ?? '');
                        $tituloPersBuf = trim((string) ($bufFase['titulo_personalizado'] ?? ''));
                        $nomeFase = $tituloPersBuf !== ''
                            ? $tituloPersBuf
                            : ($faseEnum?->label() ?? '—');
                        $ehPersonalizada = $faseEnum === \App\Enums\FaseCronograma::PERSONALIZADA;
                        $cor      = $faseEnum?->color() ?? 'gray';
                        $oculta   = empty($bufFase['visivel']);
                        $modeloFase = $fases->firstWhere('id', is_int($faseId) || ctype_digit((string) $faseId) ? (int) $faseId : -1);
                        $itensFase  = $modeloFase?->itens ?? collect();
                    @endphp
                    <div class="{{ $oculta ? 'ct-ef-card ct-ef-card--oculta' : 'ct-ef-card' }}"
                         wire:key="ct-ef-card-{{ $templateSelecionadoId }}-{{ $faseId }}"
                         draggable="true"
                         data-fase-id="{{ $faseId }}"
                         :class="{ 'ct-ef-card--over': dragTarget === '{{ $faseId }}' && dragSrc !== '{{ $faseId }}', 'ct-ef-card--dragging': dragSrc === '{{ $faseId }}' }"
                         @dragstart="dragSrc = '{{ $faseId }}'"
                         @dragover.prevent="dragTarget = '{{ $faseId }}'"
                         @drop.prevent="onDrop('{{ $faseId }}')"
                         @dragend="dragSrc = null; dragTarget = null">

                        {{-- Cabeçalho --}}
                        <div class="ct-ef-card-head" @click="toggleCard('{{ $faseId }}')">
                            <span class="ct-ef-drag-handle" title="Arrastar para reposicionar" @click.stop>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <circle cx="9" cy="5"  r="1.5"/><circle cx="15" cy="5"  r="1.5"/>
                                    <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                                    <circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/>
                                </svg>
                            </span>

                            @if($faseEnum?->marco())
                                <span style="display:inline-block;width:7px;height:7px;transform:rotate(45deg);background:var(--vo-accent);"></span>
                            @endif

                            <span style="flex:1;font-size:0.83rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--vo-text);">
                                {{ $nomeFase }}
                            </span>

                            <div style="display:flex;gap:4px;align-items:center;flex-shrink:0;" @click.stop>
                                @if(!empty($bufFase['is_ancora']))
                                    <span style="font-size:0.6rem;padding:1px 6px;background:var(--vo-accent-light,#ede9fe);color:var(--vo-accent);border-radius:99px;font-weight:600;">⚓ âncora</span>
                                @endif
                                @if(!empty($bufFase['regra_elastica']))
                                    <span style="font-size:0.6rem;padding:1px 6px;background:#dbeafe;color:#1e40af;border-radius:99px;font-weight:600;">elástica</span>
                                @endif
                                @if($oculta)
                                    <span style="font-size:0.6rem;padding:1px 6px;background:#fef9c3;color:#854d0e;border-radius:99px;font-weight:600;border:1px solid #fde68a;">oculta</span>
                                @endif
                                @if($itensFase->isNotEmpty())
                                    <span style="font-size:0.6rem;padding:1px 6px;background:var(--vo-bg-subtle);color:var(--vo-text-muted);border-radius:99px;border:1px solid var(--vo-border);">
                                        {{ $itensFase->count() }} {{ $itensFase->count() === 1 ? 'item' : 'itens' }}
                                    </span>
                                @endif
                                <button type="button"
                                        wire:click="bufferRemoverFase('{{ $faseId }}')"
                                        wire:confirm="Remover esta fase do template?"
                                        class="ct-ef-btn-icon"
                                        style="color:#b91c1c;"
                                        title="Remover fase">
                                    ×
                                </button>
                            </div>

                            <span style="font-size:0.7rem;color:var(--vo-text-faint);flex-shrink:0;margin-left:2px;" x-text="isOpen('{{ $faseId }}') ? '▴' : '▾'"></span>
                        </div>

                        {{-- Corpo expansível --}}
                        <div class="ct-ef-card-body" x-show="isOpen('{{ $faseId }}')" x-cloak>

                            {{-- Título personalizado (apenas para fase PERSONALIZADA) --}}
                            @if($ehPersonalizada)
                                <div>
                                    <div class="ct-ef-section-label">Título da fase</div>
                                    <input type="text"
                                           value="{{ $bufFase['titulo_personalizado'] ?? '' }}"
                                           wire:change="atualizarBufferTituloPersonalizado('{{ $faseId }}', $event.target.value)"
                                           placeholder="Nome da fase personalizada"
                                           class="ct-ef-input">
                                </div>
                            @endif

                            {{-- Checkbox elástica --}}
                            <label style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--vo-text-secondary);cursor:pointer;">
                                <input type="checkbox"
                                       wire:change="atualizarBufferElastica('{{ $faseId }}', $event.target.checked)"
                                       @checked(!empty($bufFase['regra_elastica']))>
                                <span>Fase elástica (duração emerge das dependências)</span>
                            </label>

                            {{-- Duração --}}
                            <div>
                                <div class="ct-ef-section-label">Duração</div>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <input type="number" min="0"
                                           value="{{ $bufFase['duracao'] ?? 0 }}"
                                           wire:change="atualizarBufferDuracao('{{ $faseId }}', $event.target.value)"
                                           @disabled(!empty($bufFase['regra_elastica']))
                                           class="ct-ef-input"
                                           style="width:72px;">
                                    <span style="font-size:0.78rem;color:var(--vo-text-muted);">dias</span>
                                    <select wire:change="atualizarBufferTipoDias('{{ $faseId }}', $event.target.value)"
                                            class="ct-ef-input" style="flex:1;">
                                        @foreach($tipoDiasOptions as $td)
                                            <option value="{{ $td->value }}" @selected(($bufFase['tipo_dias'] ?? '') === $td->value)>{{ $td->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Visibilidade + Âncora --}}
                            <div style="display:flex;gap:14px;align-items:center;">
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.74rem;color:var(--vo-text-secondary);cursor:pointer;">
                                    <input type="checkbox"
                                           wire:change="atualizarBufferVisivel('{{ $faseId }}', $event.target.checked)"
                                           @checked(!empty($bufFase['visivel']))>
                                    <span>Visível</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.74rem;color:var(--vo-text-secondary);cursor:pointer;">
                                    <input type="checkbox"
                                           wire:change="atualizarBufferAncora('{{ $faseId }}', $event.target.checked)"
                                           @checked(!empty($bufFase['is_ancora']))>
                                    <span>⚓ Âncora do template</span>
                                </label>
                            </div>

                            {{-- Observações --}}
                            <div>
                                <div class="ct-ef-section-label">Observações</div>
                                <textarea rows="2"
                                          wire:change="atualizarBufferObservacoes('{{ $faseId }}', $event.target.value)"
                                          class="ct-ef-input"
                                          style="resize:vertical;min-height:46px;">{{ $bufFase['observacoes'] ?? '' }}</textarea>
                            </div>

                            {{-- Dependências --}}
                            <div>
                                <div class="ct-ef-section-label">Dependências da fase</div>

                                @if(empty($bufFase['deps']))
                                    <p style="font-size:0.73rem;color:var(--vo-text-faint);margin:0 0 6px;">Nenhuma dependência</p>
                                @endif

                                @foreach(($bufFase['deps'] ?? []) as $idx => $dep)
                                    <div wire:key="ct-buf-dep-{{ $faseId }}-{{ $idx }}" class="ct-ef-dep-row" style="margin-bottom:4px;">
                                        <select wire:change="bufferAtualizarDep('{{ $faseId }}', {{ $idx }}, 'alvo', $event.target.value)"
                                                class="ct-ef-input">
                                            <option value="">— selecione —</option>
                                            @foreach($bufferOrdenado as $outroId => $outroFase)
                                                @if($outroId !== $faseId && ($outroFase['fase'] ?? '') !== ($bufFase['fase'] ?? ''))
                                                    <option value="fase:{{ $outroFase['fase'] }}" @selected(($dep['alvo'] ?? '') === 'fase:'.$outroFase['fase'])>
                                                        {{ \App\Enums\FaseCronograma::tryFrom($outroFase['fase'])?->label() ?? $outroFase['fase'] }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <select wire:change="bufferAtualizarDep('{{ $faseId }}', {{ $idx }}, 'gatilho', $event.target.value)"
                                                class="ct-ef-input">
                                            @foreach($gatilhoOptions as $g)
                                                <option value="{{ $g->value }}" @selected(($dep['gatilho'] ?? '') === $g->value)>{{ $g->labelCurto() }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number"
                                               value="{{ $dep['gap'] ?? 0 }}"
                                               wire:change="bufferAtualizarDep('{{ $faseId }}', {{ $idx }}, 'gap', $event.target.value)"
                                               class="ct-ef-input"
                                               placeholder="dias">
                                        <button type="button"
                                                wire:click="bufferRemoverDep('{{ $faseId }}', {{ $idx }})"
                                                class="ct-ef-btn-remove">×</button>
                                    </div>
                                @endforeach

                                <button type="button"
                                        wire:click="bufferAdicionarDep('{{ $faseId }}')"
                                        class="ct-ef-btn-ghost">
                                    + dependência
                                </button>
                            </div>

                            {{-- Subitens (persistem direto no DB, não vão pro buffer) --}}
                            @if(is_int($faseId) || ctype_digit((string) $faseId))
                                <div style="border-top:1px solid var(--vo-border);padding-top:10px;">
                                    <div class="ct-ef-section-label">Subitens</div>

                                    @foreach($itensFase->whereNull('parent_id')->sortBy('ordem') as $subitem)
                                        @include('filament.pages.cronograma-templates-editor-subitem', [
                                            'item'             => $subitem,
                                            'depth'            => 0,
                                            'fasesDependencia' => $fases,
                                        ])
                                    @endforeach

                                    @if($itensFase->whereNull('parent_id')->isEmpty())
                                        <p style="font-size:0.73rem;color:var(--vo-text-faint);margin:0 0 6px;">Nenhum subitem</p>
                                    @endif

                                    <div style="display:flex;gap:6px;margin-top:6px;">
                                        <input type="text"
                                               wire:model="novoSubitemTitulos.{{ $faseId }}"
                                               wire:keydown.enter.prevent="adicionarTemplateFaseItem({{ $faseId }})"
                                               placeholder="Adicionar subitem…"
                                               class="ct-ef-input">
                                        <button type="button"
                                                wire:click="adicionarTemplateFaseItem({{ $faseId }})"
                                                class="ct-ef-btn-ghost"
                                                style="white-space:nowrap;">+ item</button>
                                    </div>
                                </div>
                            @else
                                <div style="border-top:1px solid var(--vo-border);padding-top:10px;font-size:0.7rem;color:var(--vo-text-muted);">
                                    Subitens disponíveis após salvar a fase.
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>{{-- fim wrapper drag x-data --}}

            {{-- Adicionar nova fase --}}
            <div style="padding:12px 14px;border:1px solid var(--vo-border);border-radius:.5rem;background:var(--vo-bg-subtle);display:flex;flex-direction:column;gap:10px;">

                {{-- Catálogo (fases do enum) --}}
                <div>
                    <div class="ct-ef-section-label" style="margin-bottom:6px;">Adicionar fase do catálogo</div>
                    <div style="display:flex;gap:6px;">
                        <select wire:model="bufferNovaFaseEnum" class="ct-ef-input" style="flex:1;">
                            <option value="">— selecione fase —</option>
                            @foreach($fasesAdicionaveis as $fa)
                                @if($fa !== \App\Enums\FaseCronograma::PERSONALIZADA)
                                    <option value="{{ $fa->value }}">{{ $fa->label() }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button"
                                wire:click="bufferAdicionarFase"
                                class="ct-ef-btn-ghost"
                                style="white-space:nowrap;">
                            + Adicionar
                        </button>
                    </div>
                </div>

                {{-- Personalizada (nome livre) --}}
                @php
                    $jaTemPersonalizada = collect($bufferTemplate ?? [])
                        ->contains(fn ($d) => ($d['fase'] ?? null) === \App\Enums\FaseCronograma::PERSONALIZADA->value);
                @endphp
                <div style="border-top:1px dashed var(--vo-border);padding-top:10px;">
                    <div class="ct-ef-section-label" style="margin-bottom:6px;">Adicionar fase personalizada</div>
                    <div style="display:flex;gap:6px;">
                        <input type="text"
                               wire:model="bufferNovaFasePersonalizadaTitulo"
                               wire:keydown.enter.prevent="bufferAdicionarFasePersonalizada"
                               placeholder="Título da fase personalizada…"
                               class="ct-ef-input"
                               style="flex:1;"
                               @disabled($jaTemPersonalizada)>
                        <button type="button"
                                wire:click="bufferAdicionarFasePersonalizada"
                                class="ct-ef-btn-ghost"
                                style="white-space:nowrap;{{ $jaTemPersonalizada ? 'opacity:.4;cursor:not-allowed;' : '' }}"
                                @disabled($jaTemPersonalizada)>
                            + Adicionar
                        </button>
                    </div>
                    @if($jaTemPersonalizada)
                        <p style="font-size:0.68rem;color:var(--vo-text-faint);margin:4px 0 0;">Limite de 1 fase personalizada por template.</p>
                    @endif
                </div>

                @if(empty($fasesAdicionaveis))
                    <p style="font-size:0.7rem;color:var(--vo-text-faint);margin:0;">Todas as fases do catálogo já estão neste template.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ────── Modal de confirmação de descarte ────── --}}
@if($mostrarConfirmacaoDescarte)
    <div style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);"
         wire:click.self="cancelarDescarteBuffer">
        <div style="background:var(--vo-bg);border-radius:.75rem;padding:24px 28px;max-width:480px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,0.28);border:1px solid var(--vo-border);">
            <h3 style="font-size:1rem;font-weight:700;color:var(--vo-text);margin:0 0 10px;">Descartar alterações?</h3>
            <p style="font-size:0.82rem;color:var(--vo-text-muted);margin:0 0 18px;">
                Você tem edições no buffer que ainda não foram salvas. Continuar descartará todas as mudanças.
            </p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" wire:click="cancelarDescarteBuffer" class="vo-btn-outline">Cancelar</button>
                <button type="button" wire:click="descartarBuffer" class="ct-btn-danger">Descartar mudanças</button>
            </div>
        </div>
    </div>
@endif

{{-- ────── Modal de conflito ao ocultar/remover fase com dependentes ────── --}}
@if($mostrarModalConflitoDepBuffer)
    <div style="position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);overflow-y:auto;padding:24px;"
         wire:click.self="cancelarOcultarReconfigurarDeps">
        <div style="background:var(--vo-bg);border-radius:.75rem;padding:24px 28px;max-width:520px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,0.28);border:1px solid var(--vo-border);max-height:90vh;overflow-y:auto;">

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span style="font-size:1.2rem;">⚠️</span>
                <span style="font-size:1rem;font-weight:700;color:var(--vo-text);">
                    {{ $acaoConflitoFase === 'remover' ? 'Remover fase — reconfigurar dependências' : 'Ocultar fase — reconfigurar dependências' }}
                </span>
            </div>

            <p style="font-size:0.84rem;color:var(--vo-text-secondary);margin:0 0 16px;">
                As fases abaixo dependem desta. Escolha para cada uma a fase que vai substituí-la ou deixe em branco para remover a dependência.
            </p>

            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                @foreach($fasesConflitantesBuffer as $idx => $conf)
                    <div wire:key="ct-conf-{{ $idx }}" style="background:var(--vo-bg-subtle);border:1px solid var(--vo-border);border-radius:.5rem;padding:10px 14px;">

                        <div style="font-size:0.8rem;font-weight:600;color:var(--vo-text);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                            <span style="color:#f59e0b;">→</span>
                            {{ $conf['fase_nome'] }}
                        </div>

                        <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 10px;align-items:center;">
                            <label style="font-size:0.72rem;color:var(--vo-text-muted);white-space:nowrap;">Depender de</label>
                            <select wire:model="fasesConflitantesBuffer.{{ $idx }}.substituir_por"
                                    style="padding:4px 8px;border:1px solid var(--vo-border);border-radius:.3rem;font-size:0.78rem;background:var(--vo-bg);color:var(--vo-text);">
                                <option value="">— Remover dependência —</option>
                                @foreach($bufferTemplate as $bufKey => $bufFaseOpt)
                                    @php $optEnum = \App\Enums\FaseCronograma::tryFrom($bufFaseOpt['fase'] ?? ''); @endphp
                                    @if($optEnum && ($bufFaseOpt['fase'] ?? '') !== $faseConflitoEnum && ! empty($bufFaseOpt['visivel']))
                                        <option value="{{ $optEnum->value }}">{{ $optEnum->label() }}</option>
                                    @endif
                                @endforeach
                            </select>

                            <label style="font-size:0.72rem;color:var(--vo-text-muted);white-space:nowrap;">Gatilho</label>
                            <select wire:model="fasesConflitantesBuffer.{{ $idx }}.gatilho"
                                    style="padding:4px 8px;border:1px solid var(--vo-border);border-radius:.3rem;font-size:0.78rem;background:var(--vo-bg);color:var(--vo-text);">
                                @foreach($gatilhoOptions as $g)
                                    <option value="{{ $g->value }}" @selected(($conf['gatilho'] ?? '') === $g->value)>
                                        {{ $g->label() }}
                                    </option>
                                @endforeach
                            </select>

                            <label style="font-size:0.72rem;color:var(--vo-text-muted);white-space:nowrap;">Deslocamento (dias)</label>
                            <input type="number"
                                   wire:model="fasesConflitantesBuffer.{{ $idx }}.gap_dias"
                                   style="padding:4px 8px;border:1px solid var(--vo-border);border-radius:.3rem;font-size:0.78rem;background:var(--vo-bg);color:var(--vo-text);width:100%;">
                        </div>

                    </div>
                @endforeach
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" wire:click="cancelarOcultarReconfigurarDeps" class="vo-btn-outline">
                    Cancelar
                </button>
                <button type="button" wire:click="confirmarOcultarReconfigurarDeps"
                        style="padding:7px 18px;border:1px solid #dc2626;border-radius:.4rem;background:#dc2626;color:#fff;cursor:pointer;font-size:0.84rem;font-weight:600;">
                    Confirmar
                </button>
            </div>

        </div>
    </div>
@endif
