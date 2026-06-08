@php
    $rowMetrics = $calculateRowMetrics($row);
    $saldoPercentual = $rowMetrics['percentual_saldo_geral'];
    $rowHasNegativeSaldo = $rowMetrics['saldo_geral'] < 0;
    $saldoClass = $rowHasNegativeSaldo ? 'cmed-metric-danger' : ($saldoPercentual <= 10 ? 'cmed-metric-warn' : '');
    $saldoAviso = $rowHasNegativeSaldo ? 'ATENÇÃO: NEGATIVO' : ($saldoPercentual <= 10 ? 'ATENÇÃO: INFERIOR A 10%' : null);
    $notasMaoObra = collect($row['notas_mao_obra'] ?? []);
    $notasMaterial = collect($row['notas_material'] ?? []);
    $statusComImpactoNoSaldo = \App\Enums\StatusControleNotaFiscalNota::comImpactoNoSaldo();
@endphp

<article class="cmed-row-card" x-data="{ expanded: false }">
    <div class="cmed-row-head">
        <div class="cmed-expand-cell">
            <button type="button" class="cmed-expand-btn" @click="expanded = !expanded" aria-label="Expandir/Recolher">
                <span x-text="expanded ? '-' : '+'">+</span>
            </button>
        </div>
        <div class="cmed-row-body">
            <div class="cmed-ident-col">
                <div class="cmed-ident-grid">
                    @foreach ([['grupo', 'Grupo'], ['numero_as', 'A.S.'], ['numero_complemento', 'Complemento'], ['escopo', 'Escopo'], ['empresa', 'Empresa']] as [$field, $label])
                        @continue($field === 'numero_complemento' && blank($row['numero_complemento'] ?? null))
                        <div class="cmed-field">
                            <span class="cmed-field-label">{{ $label }}</span>
                            <div class="cmed-field-value">
                                @if ($field === 'escopo')
                                    <span class="cmed-field-stack">
                                        <span>{{ $row[$field] ?: '-' }}</span>
                                        @if ($rowHasNegativeSaldo)
                                            <span class="cmed-inline-badge cmed-inline-badge-danger">Saldo negativo</span>
                                        @endif
                                    </span>
                                @else
                                    {{ $row[$field] ?: '-' }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (($row['source'] ?? null) === 'item' && filled($row['numero_complemento'] ?? null))
                    <div class="cmed-escopo-complementar-wrap">
                        <div class="cmed-field">
                            <span class="cmed-field-label">Escopo Complementar</span>
                            <div class="cmed-field-value">{{ $row['escopo_complementar'] ?: '-' }}</div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="cmed-percentual-grid">
                <div class="cmed-field">
                    <span class="cmed-field-label">Mão de obra</span>
                    <div class="cmed-field-value">{{ number_format((float) ($row['percentual_faturamento_mao_obra'] ?? 0), 2, ',', '.') }}%</div>
                </div>
                <div class="cmed-field">
                    <span class="cmed-field-label">Material</span>
                    <div class="cmed-field-value">{{ number_format((float) ($row['percentual_faturamento_material'] ?? 0), 2, ',', '.') }}%</div>
                </div>
            </div>

            <div class="cmed-metrics-bottom">
                <div class="cmed-metric">
                    <div class="cmed-metric-label">Valor global (A)</div>
                    <div class="cmed-metric-value">R$ {{ number_format($toFloat($row['valor_global_a'] ?? 0), 2, ',', '.') }}</div>
                </div>
                <div class="cmed-metric cmed-metric-warn">
                    <div class="cmed-metric-label">Total medido</div>
                    <div class="cmed-metric-value">R$ {{ number_format($rowMetrics['total_medicao_a_menos_b'], 2, ',', '.') }}</div>
                </div>
                <div class="flex flex-col gap-2">
                    <div class="cmed-metric {{ $saldoClass }}">
                        <div class="cmed-metric-label">
                            Saldo geral ({{ number_format($saldoPercentual, 2, ',', '.') }}%)
                            @if ($saldoAviso)
                                <small>{{ $saldoAviso }}</small>
                            @endif
                        </div>
                        <div class="cmed-metric-value">R$ {{ number_format($rowMetrics['saldo_geral'], 2, ',', '.') }}</div>
                    </div>
                    @if (filled($row['importacao_url'] ?? null))
                        <div class="flex justify-end pt-1">
                            <a
                                href="{{ $row['importacao_url'] }}"
                                class="inline-flex items-center rounded-md border border-amber-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-amber-900 shadow-sm transition hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200 dark:hover:bg-amber-900/60"
                            >
                                Importar nota fiscal
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <div class="cmed-row-expanded" x-show="expanded" x-cloak>
        @foreach ([
            'mao_obra' => ['titulo' => 'MÃO DE OBRA', 'notas' => $notasMaoObra, 'direto' => true],
            'material' => ['titulo' => 'MATERIAL', 'notas' => $notasMaterial, 'direto' => false],
        ] as $config)
            @php
                $totalValorAcumulado = $config['notas']
                    ->filter(fn (array $nota): bool => in_array($nota['status'] ?? null, $statusComImpactoNoSaldo, true))
                    ->sum(fn (array $nota): float => $toFloat($nota['valor_acumulado_medido_nf'] ?? 0));
                $totalFaturamento = $config['direto'] ? $rowMetrics['faturamento_direto_total'] : $rowMetrics['faturamento_indireto_total'];
                $saldoFaturamento = $config['direto'] ? $rowMetrics['saldo_direto'] : $rowMetrics['saldo_indireto'];
                $percentualSaldo = $config['direto'] ? $rowMetrics['percentual_saldo_direto'] : $rowMetrics['percentual_saldo_indireto'];
                $percentualMedido = $totalFaturamento > 0 ? ($totalValorAcumulado / $totalFaturamento) * 100 : 0.0;
            @endphp

            <div class="cmed-finance-header">
                <span class="cmed-finance-header-title">{{ $config['titulo'] }}</span>
                <span class="cmed-finance-header-summary {{ $saldoFaturamento < 0 ? 'cmed-finance-header-summary-danger' : '' }}">
                    <strong>TOTAL MEDIDO:</strong>
                    <span>R$ {{ number_format($totalValorAcumulado, 2, ',', '.') }}</span>
                    <span>{{ number_format($percentualMedido, 2, ',', '.') }}%</span>
                    <span class="cmed-finance-header-sep">|</span>
                    <strong>SALDO</strong>
                    <span>R$ {{ number_format($saldoFaturamento, 2, ',', '.') }}</span>
                    <span>{{ number_format($percentualSaldo, 2, ',', '.') }}%</span>
                </span>
            </div>

            <div class="cmed-notas-wrap">
                <table class="cmed-table">
                    <thead>
                        <tr>
                            <th>Anexo</th>
                            <th>Fornecedor</th>
                            <th>CNPJ Fornecedor</th>
                            <th>Nº NF</th>
                            <th>Valor</th>
                            <th>Emissão</th>
                            <th>Recebimento</th>
                            <th>Validação</th>
                            <th>Status</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($config['notas'] as $nota)
                            <tr>
                                <td>
                                    @if (filled($nota['arquivo_path']))
                                        <a href="{{ \App\Models\ControleNotaFiscalNota::getFileUrl($nota['arquivo_path']) }}" target="_blank" rel="noopener noreferrer">Abrir arquivo</a>
                                    @else
                                        <span class="cmed-muted">Sem anexo</span>
                                    @endif
                                </td>
                                <td>{{ $nota['empresa'] ?: '-' }}</td>
                                <td>{{ $nota['cnpj_faturamento'] ?: '-' }}</td>
                                <td>{{ $nota['numero_nf'] ?: '-' }}</td>
                                <td>{{ filled($nota['valor_acumulado_medido_nf']) ? 'R$ '.number_format((float) $nota['valor_acumulado_medido_nf'], 2, ',', '.') : '-' }}</td>
                                <td>{{ filled($nota['emissao']) ? \Illuminate\Support\Carbon::parse($nota['emissao'])->format('d/m/Y') : '-' }}</td>
                                <td>{{ filled($nota['recebimento']) ? \Illuminate\Support\Carbon::parse($nota['recebimento'])->format('d/m/Y') : '-' }}</td>
                                <td>{{ filled($nota['envio']) ? \Illuminate\Support\Carbon::parse($nota['envio'])->format('d/m/Y') : '-' }}</td>
                                <td>{{ match ((string) ($nota['status'] ?? '')) {
                                    \App\Enums\StatusControleNotaFiscalNota::PENDENTE->value => \App\Enums\StatusControleNotaFiscalNota::PENDENTE->label(),
                                    \App\Enums\StatusControleNotaFiscalNota::EM_ANALISE->value => \App\Enums\StatusControleNotaFiscalNota::EM_ANALISE->label(),
                                    \App\Enums\StatusControleNotaFiscalNota::APROVADO->value => \App\Enums\StatusControleNotaFiscalNota::APROVADO->label(),
                                    \App\Enums\StatusControleNotaFiscalNota::REPROVADO->value => \App\Enums\StatusControleNotaFiscalNota::REPROVADO->label(),
                                    default => '-',
                                } }}</td>
                                <td>{{ $nota['observacoes'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="cmed-muted">Sem linhas.</td>
                            </tr>
                        @endforelse
                        <tr>
                            <td class="cmed-th-black" colspan="4">TOTAL - {{ $config['titulo'] }}</td>
                            <td class="cmed-th-black">R$ {{ number_format($totalValorAcumulado, 2, ',', '.') }}</td>
                            <td class="cmed-th-black"></td>
                            <td class="cmed-th-black"></td>
                            <td class="cmed-th-black"></td>
                            <td class="cmed-th-black"></td>
                            <td class="cmed-th-black"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</article>
