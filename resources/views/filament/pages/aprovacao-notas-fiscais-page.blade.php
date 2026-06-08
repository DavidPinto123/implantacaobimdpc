<x-filament-panels::page>
    @php
        $linhasPendentes = $this->linhasPendentes;
    @endphp

    <div class="nf-approval-root" wire:poll.30s="loadData">
        <section class="cmed-section nf-summary-card">
            <div class="nf-section-stack nf-section-stack-summary">
                <div class="nf-table-header">
                    <div>
                        <p class="nf-table-subtitle">Selecione um controle na tabela abaixo para abrir somente as linhas com nota em análise.</p>
                    </div>
                </div>

                {{ $this->table }}

                @if (blank($selectedControleId))
                    <div class="nf-empty-state nf-empty-state-compact">
                        <h3 class="nf-empty-title">Selecione um controle para continuar</h3>
                        <p class="nf-empty-subtitle">A tabela acima exibe apenas controles que ainda possuem notas fiscais pendentes de aprovação.</p>
                    </div>
                @else
                    <div class="nf-selected-control-bar">
                        <div class="nf-selected-control-grid">
                            <div class="nf-selected-control-item">
                                <span class="nf-selected-control-label">Unidade</span>
                                <span class="nf-selected-control-value">{{ $this->selectedControleSummary['unidade'] ?? '-' }}</span>
                            </div>

                            <div class="nf-selected-control-item">
                                <span class="nf-selected-control-label">Sigla</span>
                                <span class="nf-selected-control-value">{{ $this->selectedControleSummary['sigla'] ?? '-' }}</span>
                            </div>

                            <div class="nf-selected-control-item">
                                <span class="nf-selected-control-label">Valor Global</span>
                                <span class="nf-selected-control-value">R$ {{ number_format((float) ($this->selectedControleSummary['valor_global'] ?? 0), 2, ',', '.') }}</span>
                            </div>

                            <div class="nf-selected-control-item">
                                <span class="nf-selected-control-label">Saldo Total</span>
                                <span class="nf-selected-control-value">R$ {{ number_format((float) ($this->selectedControleSummary['saldo_total'] ?? 0), 2, ',', '.') }}</span>
                            </div>
                        </div>

                        <button wire:click="limparSelecaoControle" class="nf-btn nf-btn-outline" type="button">
                            Limpar seleção
                        </button>
                    </div>

                @endif
            </div>
        </section>

        @if (filled($selectedControleId))
        <div class="cmed-sections nf-main-sections">
            <section class="cmed-section">
                <div class="cmed-section-header">Linhas com pendência de aprovação</div>

                <div class="nf-section-stack">
                    @if ($linhasPendentes->isEmpty())
                        <div class="nf-empty-state">
                            <h3 class="nf-empty-title">Nenhuma linha aguardando aprovação</h3>
                            <p class="nf-empty-subtitle">Todas as notas em análise já foram processadas para a obra selecionada.</p>
                        </div>
                    @else
                        <div class="nf-card-list">
                            @foreach ($linhasPendentes as $linha)
                                @php
                                    $rowMetrics = $this->calculatePendingRowMetrics($linha);
                                    $saldoPercentual = $rowMetrics['percentual_saldo_geral'];
                                    $saldoAviso = null;

                                    if (abs($saldoPercentual - 10) < 0.01) {
                                        $saldoAviso = 'ATENÇÃO: 10% ALCANÇADO';
                                    } elseif ($saldoPercentual < 10) {
                                        $saldoAviso = 'ATENÇÃO: INFERIOR A 10%';
                                    }

                                    $notasMaoObra = collect($linha['notas_mao_obra_todas'] ?? []);
                                    $notasMaterial = collect($linha['notas_material_todas'] ?? []);
                                @endphp

                                <article class="nf-card" x-data="{ expanded: false }">
                                    <button type="button" class="nf-collapsible-trigger nf-note-card-trigger" @click="expanded = ! expanded" x-bind:aria-expanded="expanded.toString()">
                                        <div>
                                            <div class="nf-card-title-row">
                                                <span class="nf-card-title">{{ $linha['grupo'] !== '' ? $linha['grupo'] : '-' }}</span>
                                                <span class="nf-badge nf-badge-neutral">{{ $linha['source'] === 'auxiliar' ? 'Adicional' : 'Principal' }}</span>
                                                <span class="nf-badge nf-badge-warning">{{ $linha['pendencias'] }} pendência(s)</span>
                                            </div>
                                            <p class="nf-card-meta nf-card-meta-status">
                                                <span>{{ $linha['escopo'] !== '' ? $linha['escopo'] : '-' }}</span>
                                                <span>•</span>
                                                <span>{{ $linha['empresa'] !== '' ? $linha['empresa'] : '-' }}</span>
                                                @if (filled($linha['numero_complemento']))
                                                    <span>•</span>
                                                    <span>Complemento {{ $linha['numero_complemento'] }}</span>
                                                @endif
                                            </p>
                                        </div>

                                        <span class="nf-collapsible-affordance nf-note-card-affordance">
                                            <span class="nf-collapsible-state" x-text="expanded ? 'Ocultar' : 'Expandir'"></span>
                                            <svg class="nf-collapsible-icon" :class="{ 'is-open': expanded }" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                <path d="M7 4L13 10L7 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </button>

                                    <div class="nf-card-body" x-cloak x-show="expanded" x-collapse>
                                        <div class="nf-detail-shell">
                                            <div class="nf-detail-shell-header">
                                                <div>
                                                    <div class="nf-detail-shell-title">Detalhamento do escopo</div>
                                                    <div class="nf-detail-shell-subtitle">Todas as notas do escopo, com destaque na nota pendente.</div>
                                                </div>

                                                <div class="nf-detail-shell-summary">
                                                    <span class="nf-badge nf-badge-neutral">{{ $linha['source'] === 'auxiliar' ? 'Adicional' : 'Principal' }}</span>
                                                    <span class="nf-badge nf-badge-warning">{{ $linha['pendencias'] }} pendência(s)</span>
                                                </div>
                                            </div>

                                            <div class="nf-detail-shell-body">
                                                <div class="nf-grid nf-grid-4 nf-expanded-summary-grid">
                                                    <div class="nf-info-block">
                                                        <span class="nf-label">Tipo</span>
                                                        <span class="nf-value">{{ $linha['source'] === 'auxiliar' ? 'Adicional' : 'Principal' }}</span>
                                                    </div>
                                                    <div class="nf-info-block">
                                                        <span class="nf-label">Mão de obra</span>
                                                        <span class="nf-value">{{ number_format((float) $linha['percentual_faturamento_mao_obra'], 2, ',', '.') }}%</span>
                                                    </div>
                                                    <div class="nf-info-block">
                                                        <span class="nf-label">Material</span>
                                                        <span class="nf-value">{{ number_format((float) $linha['percentual_faturamento_material'], 2, ',', '.') }}%</span>
                                                    </div>
                                                    <div class="nf-info-block">
                                                        <span class="nf-label">Saldo geral</span>
                                                        <span class="nf-value">R$ {{ number_format((float) $rowMetrics['saldo_geral'], 2, ',', '.') }}</span>
                                                    </div>
                                                </div>

                                                <div class="nf-grid nf-grid-3 nf-expanded-summary-grid-secondary">
                                                    <div class="nf-info-block">
                                                        <span class="nf-label">Valor global (A)</span>
                                                        <span class="nf-value">R$ {{ number_format((float) $linha['valor_global_a'], 2, ',', '.') }}</span>
                                                    </div>
                                                    <div class="nf-info-block">
                                                        <span class="nf-label">Total medido</span>
                                                        <span class="nf-value">R$ {{ number_format((float) $rowMetrics['total_medicao_a_menos_b'], 2, ',', '.') }}</span>
                                                    </div>
                                                    <div class="nf-info-block">
                                                        <span class="nf-label">Saldo %</span>
                                                        <span class="nf-value">{{ number_format((float) $saldoPercentual, 2, ',', '.') }}% @if($saldoAviso) · {{ $saldoAviso }} @endif</span>
                                                    </div>
                                                </div>

                                                <div class="cmed-row-actions cmed-row-actions-readonly">
                                                    @if ($linha['controle_url'])
                                                        <a href="{{ $linha['controle_url'] }}" class="cmed-row-action cmed-row-action-link">Ver controle</a>
                                                    @endif
                                                </div>

                                                <div class="cmed-row-expanded nf-expanded-notes-shell">
                                                    @foreach ([
                                                        'MÃO DE OBRA' => ['notas' => $notasMaoObra, 'saldo' => $rowMetrics['saldo_direto'], 'faturamento' => $rowMetrics['faturamento_direto_total']],
                                                        'MATERIAL' => ['notas' => $notasMaterial, 'saldo' => $rowMetrics['saldo_indireto'], 'faturamento' => $rowMetrics['faturamento_indireto_total']],
                                                    ] as $titulo => $bloco)
                                                        @php
                                                            $totalValorAcumulado = $bloco['notas']
                                                                ->whereIn('status', [
                                                                    \App\Enums\StatusControleNotaFiscalNota::APROVADO->value,
                                                                    \App\Enums\StatusControleNotaFiscalNota::EM_ANALISE->value,
                                                                    \App\Enums\StatusControleNotaFiscalNota::PENDENTE->value,
                                                                ])
                                                                ->sum('valor');
                                                            $percentualMedido = $bloco['faturamento'] > 0 ? ($totalValorAcumulado / $bloco['faturamento']) * 100 : 0;
                                                            $percentualSaldo = $bloco['faturamento'] > 0 ? ($bloco['saldo'] / $bloco['faturamento']) * 100 : 0;
                                                        @endphp

                                                        <section class="nf-notes-scope-block">
                                                            <div class="cmed-finance-header {{ $titulo === 'MÃO DE OBRA' ? 'cmed-section-mo' : 'cmed-section-mat' }}">
                                                                <span class="cmed-finance-header-title">{{ $titulo }}</span>
                                                                <span class="cmed-finance-header-summary">
                                                                    <strong>Total medido:</strong> <span>R$ {{ number_format((float) $totalValorAcumulado, 2, ',', '.') }}</span>
                                                                    <span>{{ number_format((float) $percentualMedido, 2, ',', '.') }}%</span>
                                                                    <span class="cmed-finance-header-sep">|</span>
                                                                    <strong>Saldo</strong> <span>R$ {{ number_format((float) $bloco['saldo'], 2, ',', '.') }}</span>
                                                                    <span>{{ number_format((float) $percentualSaldo, 2, ',', '.') }}%</span>
                                                                </span>
                                                            </div>

                                                            <div class="cmed-notas-wrap nf-notes-table-wrap" style="margin-bottom:.75rem">
                                                                <table class="cmed-table nf-scope-notes-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th style="width:140px">Ações</th>
                                                                            <th style="width:100px">Anexo</th>
                                                                            <th>Fornecedor</th>
                                                                            <th style="width:140px">CNPJ Fornecedor</th>
                                                                            <th style="width:96px">Nº NF</th>
                                                                            <th style="width:116px">Valor</th>
                                                                            <th style="width:98px">Emissão</th>
                                                                            <th style="width:110px">Recebimento</th>
                                                                            <th style="width:98px">Validação</th>
                                                                            <th style="width:170px">Status</th>
                                                                            <th style="width:190px">Observação</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @forelse ($bloco['notas'] as $nota)
                                                                            <tr class="{{ $nota['is_pendente'] ? 'nf-note-row-pending' : '' }}">
                                                                                <td>
                                                                                    <div class="cmed-actions-stack cmed-actions-stack-approval">
                                                                                        @if ($nota['is_pendente'])
                                                                                            @if ($nota['arquivo_url'])
                                                                                                <a
                                                                                                    href="{{ $nota['arquivo_url'] }}"
                                                                                                    target="_blank"
                                                                                                    rel="noreferrer"
                                                                                                    wire:click="marcarNotaComoVisualizada({{ $nota['id'] }})"
                                                                                                    class="cmed-action-btn {{ $nota['foi_visualizada'] ? 'cmed-action-btn-opened' : '' }}"
                                                                                                >
                                                                                                    Abrir NF
                                                                                                </a>
                                                                                            @else
                                                                                                <span class="cmed-action-btn cmed-action-btn-disabled">NF sem anexo</span>
                                                                                            @endif
                                                                                        @elseif ($nota['arquivo_url'])
                                                                                            <a
                                                                                                href="{{ $nota['arquivo_url'] }}"
                                                                                                target="_blank"
                                                                                                rel="noreferrer"
                                                                                                class="cmed-action-btn"
                                                                                            >
                                                                                                Abrir NF
                                                                                            </a>
                                                                                        @endif

                                                                                        @if ($nota['boleto_url'])
                                                                                            <a href="{{ $nota['boleto_url'] }}" target="_blank" rel="noreferrer" class="cmed-action-btn">Abrir boleto</a>
                                                                                        @endif

                                                                                        @if ($this->canProcessNotas() && $nota['is_pendente'])
                                                                                            <button
                                                                                                wire:click="reprovar({{ $nota['id'] }})"
                                                                                                wire:confirm="Confirma a reprovação da nota fiscal {{ $nota['numero_nf'] }}?"
                                                                                                class="cmed-action-btn cmed-action-btn-danger"
                                                                                                type="button"
                                                                                            >
                                                                                                Reprovar
                                                                                            </button>

                                                                                            <button
                                                                                                wire:click="aprovar({{ $nota['id'] }})"
                                                                                                wire:confirm="Confirma a aprovação da nota fiscal {{ $nota['numero_nf'] }}?"
                                                                                                class="cmed-action-btn cmed-action-btn-success {{ $nota['foi_visualizada'] ? '' : 'opacity-60 cursor-not-allowed' }}"
                                                                                                type="button"
                                                                                                @disabled(! $nota['foi_visualizada'])
                                                                                            >
                                                                                                Aprovar
                                                                                            </button>
                                                                                        @endif
                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    @if ($nota['arquivo_url'])
                                                                                        <a
                                                                                            href="{{ $nota['arquivo_url'] }}"
                                                                                            target="_blank"
                                                                                            rel="noreferrer"
                                                                                            wire:click="marcarNotaComoVisualizada({{ $nota['id'] }})"
                                                                                        >Abrir arquivo</a>
                                                                                    @else
                                                                                        <span class="cmed-muted">Sem anexo</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td>{{ $nota['empresa'] }}</td>
                                                                                <td>{{ $nota['cnpj_fornecedor'] }}</td>
                                                                                <td>{{ $nota['numero_nf'] }}</td>
                                                                                <td>R$ {{ number_format((float) $nota['valor'], 2, ',', '.') }}</td>
                                                                                <td>{{ $nota['emissao'] }}</td>
                                                                                <td>{{ $nota['recebimento'] }}</td>
                                                                                <td>{{ $nota['envio'] }}</td>
                                                                                <td>
                                                                                    <div class="nf-status-stack">
                                                                                        <span class="nf-status-text nf-status-text-{{ $nota['status_tone'] }}">{{ $nota['is_pendente'] ? 'Pendente' : $nota['status_label'] }}</span>
                                                                                    </div>
                                                                                </td>
                                                                                <td>{{ filled($nota['observacoes']) ? $nota['observacoes'] : '-' }}</td>
                                                                            </tr>
                                                                            @if ($nota['is_pendente'] && ! $nota['foi_visualizada'] && $this->canProcessNotas())
                                                                                <tr>
                                                                                    <td colspan="11" class="cmed-muted nf-note-alert-row">
                                                                                        Para aprovar a NF {{ $nota['numero_nf'] }}, clique em <strong>Abrir NF</strong> antes de confirmar a aprovação.
                                                                                    </td>
                                                                                </tr>
                                                                            @endif
                                                                        @empty
                                                                            <tr>
                                                                                <td colspan="11" class="cmed-muted">Sem notas neste bloco.</td>
                                                                            </tr>
                                                                        @endforelse
                                                                        <tr>
                                                                            <td class="cmed-th-black" colspan="5">TOTAL - {{ $titulo }}</td>
                                                                            <td class="cmed-th-black">R$ {{ number_format((float) $totalValorAcumulado, 2, ',', '.') }}</td>
                                                                            <td class="cmed-th-black"></td>
                                                                            <td class="cmed-th-black"></td>
                                                                            <td class="cmed-th-black"></td>
                                                                            <td class="cmed-th-black"></td>
                                                                            <td class="cmed-th-black"></td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </section>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

        </div>
        @endif
    </div>
</x-filament-panels::page>
