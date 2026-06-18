<x-filament-panels::page>

    {{-- ── Toolbar: Toggle + Filtro Projeto + Agrupamento ─────────────── --}}
    @php $filtroProjetosOpcoes = $this->getFiltroProjetosOptions(); @endphp
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">

        {{-- Toggle Tabela / Kanban --}}
        <div style="display:flex;gap:2px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:0.5rem;padding:2px;">
            <button wire:click="$set('visualizacao','tabela')"
                    style="padding:5px 14px;border:none;border-radius:0.375rem;cursor:pointer;font-size:0.78rem;font-family:inherit;{{ $visualizacao === 'tabela' ? 'background:#f59e0b;color:#111;font-weight:700;' : 'background:transparent;color:#6b7280;' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="16" y2="12"/><line x1="4" y1="18" x2="12" y2="18"/></svg>
                Tabela
            </button>
            <button wire:click="$set('visualizacao','kanban')"
                    style="padding:5px 14px;border:none;border-radius:0.375rem;cursor:pointer;font-size:0.78rem;font-family:inherit;{{ $visualizacao === 'kanban' ? 'background:#f59e0b;color:#111;font-weight:700;' : 'background:transparent;color:#6b7280;' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="13" rx="1"/><rect x="17" y="3" width="5" height="16" rx="1"/></svg>
                Kanban
            </button>
        </div>

        {{-- Filtro por Planejamento / Projeto (sempre visível) --}}
        @if(count($filtroProjetosOpcoes) > 0)
        <div style="display:flex;align-items:center;gap:5px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5" style="flex-shrink:0;"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
            <select x-on:change="$wire.set('filtroProjetoId', $event.target.value ? parseInt($event.target.value) : null)"
                    style="font-size:0.78rem;padding:4px 8px;border:1px solid #e5e7eb;border-radius:0.375rem;background:#fff;color:#374151;cursor:pointer;font-family:inherit;max-width:220px;">
                <option value="" {{ !$filtroProjetoId ? 'selected' : '' }}>Todos os planejamentos</option>
                @foreach($filtroProjetosOpcoes as $pid => $pnome)
                    <option value="{{ $pid }}" {{ $filtroProjetoId == $pid ? 'selected' : '' }}>{{ $pnome }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Agrupamento Kanban --}}
        @if($visualizacao === 'kanban')
        <div style="display:flex;align-items:center;gap:5px;">
            <span style="font-size:0.72rem;color:#9ca3af;font-weight:600;">Agrupar:</span>
            <button wire:click="$set('kanbanAgrupamento','status')"
                    style="font-size:0.72rem;padding:3px 10px;border-radius:1rem;cursor:pointer;font-family:inherit;{{ $kanbanAgrupamento === 'status' ? 'background:#3b82f6;color:#fff;border:1px solid #3b82f6;font-weight:700;' : 'background:transparent;color:#6b7280;border:1px solid #e5e7eb;' }}">
                Status
            </button>
            <button wire:click="$set('kanbanAgrupamento','profissional')"
                    style="font-size:0.72rem;padding:3px 10px;border-radius:1rem;cursor:pointer;font-family:inherit;{{ $kanbanAgrupamento === 'profissional' ? 'background:#3b82f6;color:#fff;border:1px solid #3b82f6;font-weight:700;' : 'background:transparent;color:#6b7280;border:1px solid #e5e7eb;' }}">
                Profissional
            </button>
        </div>
        @endif
    </div>

    @if($visualizacao === 'kanban')
    {{-- ── KANBAN ───────────────────────────────────────────────────── --}}
    @php
        $tkCores = [
            'previstas'    => '#8b5cf6',
            'pendente'     => '#f59e0b',
            'em_andamento' => '#3b82f6',
            'atrasadas'    => '#ef4444',
            'concluida'    => '#22c55e',
            'cancelada'    => '#6b7280',
        ];
        $tkLabels = [
            'previstas'    => 'Previstas',
            'pendente'     => 'Não iniciada',
            'em_andamento' => 'Em andamento',
            'atrasadas'    => 'Atrasadas',
            'concluida'    => 'Concluída',
            'cancelada'    => 'Cancelada',
        ];
        $tkDropTargets = ['pendente', 'em_andamento', 'concluida', 'cancelada'];
        $tkTarefas     = $this->getKanbanTarefas();
        $tkUsuarios    = $this->getKanbanUsuarios();
    @endphp

    <style>
        .tk-kanban-board { display:flex;gap:12px;overflow-x:auto;padding:4px 0 20px;align-items:flex-start;min-height:calc(100vh - 320px); }
        .tk-kanban-col { flex-shrink:0;width:260px;border-radius:.75rem;background:var(--fi-bg,#f9fafb);border:1px solid #e5e7eb;overflow:hidden; }
        .dark .tk-kanban-col { background:#18181b;border-color:#3f3f46; }
        .tk-kanban-col-header { display:flex;justify-content:space-between;align-items:center;padding:10px 12px; }
        .tk-kanban-count { border-radius:1rem;padding:1px 8px;font-size:.65rem;font-weight:700; }
        .tk-kanban-cards { padding:8px;display:flex;flex-direction:column;gap:8px;min-height:60px;transition:background .15s; }
        .tk-kanban-drop-target { background:rgba(0,0,0,.06); }
        .tk-kanban-card { border-radius:.5rem;padding:11px 12px;cursor:grab;user-select:none;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:transform .12s,opacity .12s; }
        .tk-kanban-card:active { cursor:grabbing;opacity:.85;transform:scale(.98); }
        .tk-kanban-card-nome { font-weight:700;font-size:0.78rem;color:#fff;line-height:1.3;margin-bottom:5px; }
        .tk-kanban-card-projeto { display:inline-block;font-size:0.6rem;font-weight:700;color:rgba(255,255,255,.95);background:rgba(0,0,0,.22);border-radius:3px;padding:1px 5px;margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%; }
        .tk-kanban-card-resp { font-size:0.66rem;color:rgba(255,255,255,.9);font-weight:600;display:flex;align-items:center;gap:3px; }
        .tk-kanban-card-resp::before { content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.6);flex-shrink:0; }
        .tk-kanban-card-datas { font-size:0.63rem;color:rgba(255,255,255,.78);margin-top:5px; }
        .tk-kanban-card-status { font-size:0.6rem;color:rgba(255,255,255,.7);margin-top:3px;font-style:italic; }
        .tk-kanban-card-fase { font-size:0.58rem;color:rgba(255,255,255,.65);font-style:italic;margin-bottom:3px; }
        .tk-kanban-empty { text-align:center;padding:18px;font-size:.72rem;color:#9ca3af; }
        .tk-kanban-no-drop { cursor: default; }
    </style>

    @if($kanbanAgrupamento === 'status')
    {{-- KANBAN POR STATUS --}}
    <div class="tk-kanban-board" x-data="{ draggingId: null, draggingStatus: null }">
        @foreach(['previstas','pendente','em_andamento','atrasadas','concluida','cancelada'] as $tkStatus)
            @php
                $tkCor      = $tkCores[$tkStatus];
                $tkLabel    = $tkLabels[$tkStatus];
                $tkCards    = $tkTarefas->get($tkStatus, collect());
                $isDrop     = in_array($tkStatus, $tkDropTargets);
            @endphp
            <div class="tk-kanban-col{{ ! $isDrop ? ' tk-kanban-no-drop' : '' }}"
                 @if($isDrop)
                     @dragover.prevent="draggingStatus = '{{ $tkStatus }}'"
                     @drop.prevent="if (draggingId !== null) { $wire.moverTarefaKanban(draggingId, '{{ $tkStatus }}'); draggingId = null; draggingStatus = null; }"
                 @endif>
                <div class="tk-kanban-col-header" style="background:{{ $tkCor }}22;border-bottom:3px solid {{ $tkCor }};">
                    <span style="color:{{ $tkCor }};font-weight:800;font-size:0.78rem;text-transform:uppercase;letter-spacing:.04em;">{{ $tkLabel }}</span>
                    <span class="tk-kanban-count" style="background:{{ $tkCor }};color:#fff;">{{ $tkCards->count() }}</span>
                </div>
                <div class="tk-kanban-cards"
                     @if($isDrop):class="draggingStatus === '{{ $tkStatus }}' ? 'tk-kanban-drop-target' : ''"@endif>
                    @foreach($tkCards as $tkCard)
                        @php
                            $tkFaseLabel = null;
                            if ($tkCard->cronogramaFaseItem?->fase) {
                                $cf = $tkCard->cronogramaFaseItem->fase;
                                $tkFaseLabel = ($cf->fase?->value === 'personalizada')
                                    ? $cf->titulo_personalizado
                                    : $cf->fase?->label();
                            }
                        @endphp
                        <div class="tk-kanban-card" style="background:{{ $tkCor }};"
                             draggable="true"
                             @dragstart="draggingId = {{ $tkCard->id }}; draggingStatus = '{{ $tkStatus }}'"
                             @dragend="draggingId = null; draggingStatus = null">
                            @if($tkCard->projeto)
                                <div class="tk-kanban-card-projeto">{{ $tkCard->projeto->nome }}</div>
                            @endif
                            @if($tkFaseLabel)
                                <div class="tk-kanban-card-fase">{{ $tkFaseLabel }}</div>
                            @endif
                            <div class="tk-kanban-card-nome">{{ $tkCard->title }}</div>
                            @if($tkCard->responsavel)
                                <div class="tk-kanban-card-resp">{{ \Illuminate\Support\Str::before($tkCard->responsavel->name, ' ') }}</div>
                            @endif
                            @if($tkCard->termino_programado)
                                <div class="tk-kanban-card-datas">
                                    @if($tkCard->inicio) {{ $tkCard->inicio->format('d/m') }} → @endif
                                    {{ $tkCard->termino_programado->format('d/m/y') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                    @if($tkCards->isEmpty())
                        <div class="tk-kanban-empty">Sem tarefas</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @else
    {{-- KANBAN POR PROFISSIONAL --}}
    <div class="tk-kanban-board" x-data="{ draggingId: null, draggingUserId: null }">
        {{-- Sem Responsável --}}
        @php $tkSemResp = $tkTarefas->get(0, collect()); @endphp
        <div class="tk-kanban-col"
             @dragover.prevent="draggingUserId = 0"
             @drop.prevent="if (draggingId !== null) { $wire.moverTarefaResponsavel(draggingId, null); draggingId = null; draggingUserId = null; }">
            <div class="tk-kanban-col-header" style="background:#6b728022;border-bottom:3px solid #6b7280;">
                <span style="color:#6b7280;font-weight:800;font-size:0.78rem;text-transform:uppercase;letter-spacing:.04em;">Sem Responsável</span>
                <span class="tk-kanban-count" style="background:#6b7280;color:#fff;">{{ $tkSemResp->count() }}</span>
            </div>
            <div class="tk-kanban-cards" :class="draggingUserId === 0 ? 'tk-kanban-drop-target' : ''">
                @foreach($tkSemResp as $tkCard)
                    @php
                        $tkIsAtrasada = $tkCard->termino_programado
                            && $tkCard->termino_programado->lt(today())
                            && ! in_array($tkCard->status, ['concluida', 'cancelada']);
                        $tkCor = $tkIsAtrasada ? $tkCores['atrasadas'] : ($tkCores[$tkCard->status] ?? '#9ca3af');
                        $tkFaseLabel = null;
                        if ($tkCard->cronogramaFaseItem?->fase) {
                            $cf = $tkCard->cronogramaFaseItem->fase;
                            $tkFaseLabel = ($cf->fase?->value === 'personalizada') ? $cf->titulo_personalizado : $cf->fase?->label();
                        }
                    @endphp
                    <div class="tk-kanban-card" style="background:{{ $tkCor }};"
                         draggable="true"
                         @dragstart="draggingId = {{ $tkCard->id }}; draggingUserId = 0"
                         @dragend="draggingId = null; draggingUserId = null">
                        @if($tkCard->projeto)
                            <div class="tk-kanban-card-projeto">{{ $tkCard->projeto->nome }}</div>
                        @endif
                        @if($tkFaseLabel)
                            <div class="tk-kanban-card-fase">{{ $tkFaseLabel }}</div>
                        @endif
                        <div class="tk-kanban-card-nome">{{ $tkCard->title }}</div>
                        <div class="tk-kanban-card-status">{{ $tkIsAtrasada ? 'Atrasada' : ($tkLabels[$tkCard->status] ?? $tkCard->status) }}</div>
                        @if($tkCard->termino_programado)
                            <div class="tk-kanban-card-datas">Prazo: {{ $tkCard->termino_programado->format('d/m/y') }}</div>
                        @endif
                    </div>
                @endforeach
                @if($tkSemResp->isEmpty())
                    <div class="tk-kanban-empty">Sem tarefas</div>
                @endif
            </div>
        </div>
        {{-- Por usuário --}}
        @foreach($tkUsuarios as $tkUser)
            @php $tkUserCards = $tkTarefas->get($tkUser->id, collect()); @endphp
            <div class="tk-kanban-col"
                 @dragover.prevent="draggingUserId = {{ $tkUser->id }}"
                 @drop.prevent="if (draggingId !== null) { $wire.moverTarefaResponsavel(draggingId, '{{ $tkUser->id }}'); draggingId = null; draggingUserId = null; }">
                <div class="tk-kanban-col-header" style="background:#3b82f622;border-bottom:3px solid #3b82f6;">
                    <span style="color:#3b82f6;font-weight:800;font-size:0.78rem;text-transform:uppercase;letter-spacing:.04em;" title="{{ $tkUser->name }}">{{ \Illuminate\Support\Str::before($tkUser->name, ' ') }}</span>
                    <span class="tk-kanban-count" style="background:#3b82f6;color:#fff;">{{ $tkUserCards->count() }}</span>
                </div>
                <div class="tk-kanban-cards" :class="draggingUserId === {{ $tkUser->id }} ? 'tk-kanban-drop-target' : ''">
                    @foreach($tkUserCards as $tkCard)
                        @php
                            $tkIsAtrasada = $tkCard->termino_programado
                                && $tkCard->termino_programado->lt(today())
                                && ! in_array($tkCard->status, ['concluida', 'cancelada']);
                            $tkCor = $tkIsAtrasada ? $tkCores['atrasadas'] : ($tkCores[$tkCard->status] ?? '#9ca3af');
                            $tkFaseLabel = null;
                            if ($tkCard->cronogramaFaseItem?->fase) {
                                $cf = $tkCard->cronogramaFaseItem->fase;
                                $tkFaseLabel = ($cf->fase?->value === 'personalizada') ? $cf->titulo_personalizado : $cf->fase?->label();
                            }
                        @endphp
                        <div class="tk-kanban-card" style="background:{{ $tkCor }};"
                             draggable="true"
                             @dragstart="draggingId = {{ $tkCard->id }}; draggingUserId = {{ $tkUser->id }}"
                             @dragend="draggingId = null; draggingUserId = null">
                            @if($tkCard->projeto)
                                <div class="tk-kanban-card-projeto">{{ $tkCard->projeto->nome }}</div>
                            @endif
                            @if($tkFaseLabel)
                                <div class="tk-kanban-card-fase">{{ $tkFaseLabel }}</div>
                            @endif
                            <div class="tk-kanban-card-nome">{{ $tkCard->title }}</div>
                            <div class="tk-kanban-card-status">{{ $tkIsAtrasada ? 'Atrasada' : ($tkLabels[$tkCard->status] ?? $tkCard->status) }}</div>
                            @if($tkCard->termino_programado)
                                <div class="tk-kanban-card-datas">Prazo: {{ $tkCard->termino_programado->format('d/m/y') }}</div>
                            @endif
                        </div>
                    @endforeach
                    @if($tkUserCards->isEmpty())
                        <div class="tk-kanban-empty">Sem tarefas</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

    @else
    {{-- ── TABELA (padrão Filament) ─────────────────────────────────── --}}
    {{ $this->table }}
    @endif

</x-filament-panels::page>
