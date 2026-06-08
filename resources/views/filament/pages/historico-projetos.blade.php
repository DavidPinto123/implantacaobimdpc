<x-filament-panels::page>
    <style>
        .hp-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
            padding: 14px 16px;
            border: 1px solid var(--vo-border, #e5e7eb);
            border-radius: .5rem;
            background: var(--vo-bg, #fff);
            margin-bottom: 14px;
        }
        .hp-filtros label {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: .68rem;
            color: var(--vo-text-muted, #6b7280);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .hp-filtros select,
        .hp-filtros input {
            padding: 6px 10px;
            border: 1px solid var(--vo-border, #d1d5db);
            border-radius: .375rem;
            background: var(--vo-bg, #fff);
            color: var(--vo-text, #111);
            font-size: .78rem;
            min-width: 160px;
        }
        .hp-lotes {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    </style>

    {{-- Filtros --}}
    <div class="hp-filtros">
        <label>
            Projeto
            <select wire:model.live="filtroProjetoId">
                <option value="">— todos —</option>
                @foreach($projetos as $id => $nome)
                    <option value="{{ $id }}">{{ $nome }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Campo alterado
            <select wire:model.live="filtroCampo">
                <option value="">— todos —</option>
                <option value="data_prevista_inicio">Início previsto</option>
                <option value="data_prevista_fim">Fim previsto</option>
                <option value="projeto.data_posse">Data de Posse</option>
                <option value="template">Aplicação de Template</option>
                <option value="regra_duracao_dias">Duração</option>
                <option value="status">Status</option>
            </select>
        </label>

        <label>
            Usuário
            <select wire:model.live="filtroUsuarioId">
                <option value="">— todos —</option>
                @foreach($usuarios as $id => $nome)
                    <option value="{{ $id }}">{{ $nome }}</option>
                @endforeach
            </select>
        </label>

        <label>
            De
            <input type="date" wire:model.live="filtroDataInicio">
        </label>

        <label>
            Até
            <input type="date" wire:model.live="filtroDataFim">
        </label>

        <button type="button" wire:click="limparFiltros"
                style="padding:7px 12px;border:1px solid var(--vo-border);background:transparent;border-radius:.375rem;color:var(--vo-text-muted);font-size:.72rem;cursor:pointer;height:33px;">
            Limpar filtros
        </button>

        <div style="margin-left:auto;font-size:.72rem;color:var(--vo-text-muted);">
            {{ $totalRegistros }} registro(s) — limite {{ $limit }}
        </div>
    </div>

    {{-- Lista de lotes (mesma UX do histórico do Cronograma) --}}
    @if($registros->isEmpty())
        <div style="padding:32px;text-align:center;color:var(--vo-text-muted);font-size:0.82rem;border:1px solid var(--vo-border);border-radius:.5rem;">
            Nenhum registro encontrado com os filtros aplicados.
        </div>
    @else
        <div class="hp-lotes">
            @foreach($lotes as $loteEntries)
                @php
                    $primeiro = $loteEntries->first();
                    $templateEntries = $loteEntries->where('campo_alterado', 'template');
                    $nonTemplateEntries = $loteEntries->where('campo_alterado', '!=', 'template');
                    $manuais = $nonTemplateEntries->where('automatico', false);
                    $cascatas = $nonTemplateEntries->where('automatico', true);
                    $temCascata = $cascatas->isNotEmpty();
                    $totalFasesAfetadas = $cascatas->pluck('cronograma_fase_id')->unique()->count();

                    $projetoPrimeiro = $primeiro->cronogramaFase?->projeto ?? $primeiro->projeto;
                    $projetoLabel = $projetoPrimeiro
                        ? trim(($projetoPrimeiro->codigo ? "[{$projetoPrimeiro->codigo}] " : '').$projetoPrimeiro->nome)
                        : '—';
                @endphp

                {{-- Entrada de mudança de template --}}
                @foreach($templateEntries as $tplEntry)
                    <div style="border:1px solid var(--vo-accent, #fbba00);border-radius:.5rem;overflow:hidden;background:rgba(251,186,0,.04);">
                        <div style="padding:12px 16px;display:flex;align-items:center;gap:12px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--vo-accent, #fbba00);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.7rem;color:var(--vo-text-muted);">{{ $projetoLabel }}</div>
                                <div style="font-size:0.8rem;font-weight:600;color:var(--vo-text);">
                                    {{ $tplEntry->motivo }}
                                </div>
                                <div style="font-size:0.7rem;color:var(--vo-text-muted);margin-top:2px;display:flex;align-items:center;gap:8px;">
                                    @if($tplEntry->valor_anterior)
                                        <span style="padding:1px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;text-decoration:line-through;">{{ $tplEntry->valor_anterior }}</span>
                                        <span>&rarr;</span>
                                    @endif
                                    <span style="padding:1px 6px;background:var(--vo-bg);border:1px solid var(--vo-accent);border-radius:.25rem;font-weight:600;">{{ $tplEntry->valor_novo }}</span>
                                </div>
                            </div>
                            <div style="text-align:right;flex-shrink:0;">
                                <div style="font-size:0.68rem;color:var(--vo-text-muted);">{{ $tplEntry->created_at->format('d/m/Y H:i') }}</div>
                                <div style="font-size:0.68rem;color:var(--vo-text-muted);">{{ $tplEntry->usuario?->name ?? 'Sistema' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($nonTemplateEntries->isEmpty()) @continue @endif

                <div style="border:1px solid var(--vo-border);border-radius:.5rem;overflow:hidden;">
                    {{-- Cabeçalho do lote --}}
                    <div style="padding:10px 14px;background:var(--vo-bg-subtle);border-bottom:1px solid var(--vo-border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;">
                            <span style="font-size:.7rem;color:var(--vo-text-muted);font-weight:600;padding:2px 8px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:1rem;white-space:nowrap;">
                                {{ $projetoLabel }}
                            </span>
                            @if($primeiro->automatico)
                                <span style="padding:1px 8px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:1rem;font-size:0.62rem;font-weight:600;color:var(--vo-text-muted);white-space:nowrap;">Auto</span>
                            @else
                                <span style="font-size:0.75rem;font-weight:600;color:var(--vo-text);">{{ $primeiro->usuario?->name ?? 'Sistema' }}</span>
                            @endif
                            @if($primeiro->motivo)
                                <span style="font-size:0.72rem;color:var(--vo-text-secondary);font-style:italic;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $primeiro->motivo }}</span>
                            @endif
                        </div>
                        <span style="font-size:0.68rem;color:var(--vo-text-muted);white-space:nowrap;">{{ $primeiro->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    {{-- Alterações manuais --}}
                    @foreach($manuais as $h)
                        @php
                            $delta = ($h->valor_anterior && $h->valor_novo)
                                ? (int) \Carbon\Carbon::parse($h->valor_anterior)->diffInDays(\Carbon\Carbon::parse($h->valor_novo), false)
                                : null;
                        @endphp
                        <div style="padding:8px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--vo-border-light, #e5e7eb);font-size:0.73rem;flex-wrap:wrap;">
                            <span style="font-weight:600;color:var(--vo-text);min-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $h->cronogramaFase?->fase->label() ?? ($h->campo_alterado === 'projeto.data_posse' ? 'Projeto · Data de Posse' : '—') }}
                            </span>
                            <span style="color:var(--vo-text-secondary);min-width:90px;">
                                {{ str_replace(['data_prevista_inicio', 'data_prevista_fim', 'projeto.data_posse'], ['Início prev.', 'Fim prev.', 'Posse'], $h->campo_alterado) }}
                            </span>
                            <span style="padding:2px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.7rem;">
                                {{ $h->valor_anterior ? \Carbon\Carbon::parse($h->valor_anterior)->format('d/m/Y') : '—' }}
                            </span>
                            <span style="color:var(--vo-text-faint);">&rarr;</span>
                            <span style="padding:2px 6px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.7rem;font-weight:600;">
                                {{ $h->valor_novo ? \Carbon\Carbon::parse($h->valor_novo)->format('d/m/Y') : '—' }}
                            </span>
                            @if($delta !== null && $delta !== 0)
                                <span style="padding:2px 6px;border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.68rem;font-weight:600;background:{{ $delta > 0 ? 'rgba(239,68,68,.12)' : 'rgba(34,197,94,.12)' }};color:{{ $delta > 0 ? '#ef4444' : '#22c55e' }};">
                                    {{ $delta > 0 ? '+'.$delta.'d' : $delta.'d' }}
                                </span>
                            @endif
                        </div>
                    @endforeach

                    {{-- Cascatas agrupadas --}}
                    @if($temCascata)
                        <div x-data="{ expanded: false }">
                            <button type="button" @click="expanded = !expanded"
                                    style="width:100%;padding:6px 14px;background:rgba(251,186,0,.06);border:none;border-bottom:1px solid var(--vo-border-light);cursor:pointer;display:flex;align-items:center;gap:6px;font-size:0.7rem;color:var(--vo-text-muted);text-align:left;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                     :style="expanded ? 'transform:rotate(90deg)' : ''" style="transition:transform .15s;flex-shrink:0;"><path d="M9 18l6-6-6-6"/></svg>
                                Recálculo em cascata — {{ $totalFasesAfetadas }} fase(s) afetada(s)
                            </button>
                            <div x-show="expanded" x-cloak>
                                @foreach($cascatas as $h)
                                    @php
                                        $deltaC = ($h->valor_anterior && $h->valor_novo)
                                            ? (int) \Carbon\Carbon::parse($h->valor_anterior)->diffInDays(\Carbon\Carbon::parse($h->valor_novo), false)
                                            : null;
                                    @endphp
                                    <div style="padding:6px 14px 6px 36px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--vo-border-light);font-size:0.7rem;color:var(--vo-text-secondary);flex-wrap:wrap;">
                                        <span style="font-weight:500;min-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $h->cronogramaFase?->fase->label() ?? '—' }}
                                        </span>
                                        <span style="min-width:90px;">
                                            {{ str_replace(['data_prevista_inicio', 'data_prevista_fim'], ['Início prev.', 'Fim prev.'], $h->campo_alterado) }}
                                        </span>
                                        <span style="padding:1px 5px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.68rem;">
                                            {{ $h->valor_anterior ? \Carbon\Carbon::parse($h->valor_anterior)->format('d/m/Y') : '—' }}
                                        </span>
                                        <span style="color:var(--vo-text-faint);">&rarr;</span>
                                        <span style="padding:1px 5px;background:var(--vo-bg);border:1px solid var(--vo-border);border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.68rem;font-weight:600;">
                                            {{ $h->valor_novo ? \Carbon\Carbon::parse($h->valor_novo)->format('d/m/Y') : '—' }}
                                        </span>
                                        @if($deltaC !== null && $deltaC !== 0)
                                            <span style="padding:1px 5px;border-radius:.25rem;font-variant-numeric:tabular-nums;font-size:0.66rem;font-weight:600;background:{{ $deltaC > 0 ? 'rgba(239,68,68,.12)' : 'rgba(34,197,94,.12)' }};color:{{ $deltaC > 0 ? '#ef4444' : '#22c55e' }};">
                                                {{ $deltaC > 0 ? '+'.$deltaC.'d' : $deltaC.'d' }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
