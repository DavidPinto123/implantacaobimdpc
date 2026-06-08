<x-filament-panels::page>
    @php
        $selectedControle = $this->selectedControleResumo;
        $record = $this->selectedControleRecord?->loadMissing(['obra']);
        $rows = collect($this->sheetRows);
        $rowsPrincipais = $rows->filter(fn (array $row): bool => ($row['source'] ?? null) === 'item')->values();
        $rowsAdicionais = $rows->filter(fn (array $row): bool => ($row['source'] ?? null) === 'auxiliar')->values();

        $statusLabel = match ($record?->status) {
            'rascunho' => 'Rascunho',
            'aguardando_construtora' => 'Aguardando fornecedor',
            'aguardando_financeiro' => 'Aguardando financeiro',
            'aprovado' => 'Aprovado',
            'reprovado' => 'Reprovado',
            'encerrado' => 'Encerrado',
            null => '-',
            default => (string) $record->status,
        };

        $statusTone = match ($record?->status) {
            'aprovado' => 'success',
            'reprovado' => 'danger',
            'aguardando_construtora', 'aguardando_financeiro' => 'warning',
            'encerrado' => 'neutral',
            default => 'neutral',
        };

        $toFloat = static function (mixed $value): float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (! is_string($value)) {
                return 0.0;
            }

            $normalized = trim($value);

            if ($normalized === '') {
                return 0.0;
            }

            $normalized = str_replace(['R$', ' '], '', $normalized);

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
            }

            return is_numeric($normalized) ? (float) $normalized : 0.0;
        };

        $calculateRowMetrics = static function (array $row) use ($toFloat): array {
            $statusComImpactoNoSaldo = \App\Enums\StatusControleNotaFiscalNota::comImpactoNoSaldo();
            $valorTotal = $toFloat($row['valor_global_a'] ?? 0);
            $percentualDireto = $toFloat($row['percentual_faturamento_mao_obra'] ?? 60);
            $percentualIndireto = $toFloat($row['percentual_faturamento_material'] ?? 40);

            $acumuladoDireto = collect($row['notas_mao_obra'] ?? [])
                ->filter(fn (array $nota): bool => in_array($nota['status'] ?? null, $statusComImpactoNoSaldo, true))
                ->sum(fn (array $nota): float => $toFloat($nota['valor_acumulado_medido_nf'] ?? 0));
            $acumuladoIndireto = collect($row['notas_material'] ?? [])
                ->filter(fn (array $nota): bool => in_array($nota['status'] ?? null, $statusComImpactoNoSaldo, true))
                ->sum(fn (array $nota): float => $toFloat($nota['valor_acumulado_medido_nf'] ?? 0));

            $faturamentoDiretoTotal = $valorTotal * ($percentualDireto / 100);
            $faturamentoIndiretoTotal = $valorTotal * ($percentualIndireto / 100);
            $acumuladoTotal = $acumuladoDireto + $acumuladoIndireto;
            $saldoGeral = $valorTotal - $acumuladoTotal;
            $saldoDireto = $faturamentoDiretoTotal - $acumuladoDireto;
            $saldoIndireto = $faturamentoIndiretoTotal - $acumuladoIndireto;

            return [
                'faturamento_direto_total' => $faturamentoDiretoTotal,
                'faturamento_indireto_total' => $faturamentoIndiretoTotal,
                'total_medicao_a_menos_b' => $acumuladoTotal,
                'saldo_direto' => $saldoDireto,
                'saldo_indireto' => $saldoIndireto,
                'saldo_geral' => $saldoGeral,
                'percentual_saldo_direto' => $faturamentoDiretoTotal > 0 ? ($saldoDireto / $faturamentoDiretoTotal) * 100 : 0.0,
                'percentual_saldo_indireto' => $faturamentoIndiretoTotal > 0 ? ($saldoIndireto / $faturamentoIndiretoTotal) * 100 : 0.0,
                'percentual_saldo_geral' => $valorTotal > 0 ? ($saldoGeral / $valorTotal) * 100 : 0.0,
            ];
        };

        $allRows = $rowsPrincipais->concat($rowsAdicionais);
        $allMetrics = $allRows->map(fn (array $row): array => $calculateRowMetrics($row));
        $totais = [
            'linhas' => $allRows->count(),
            'valor_global' => $allRows->sum(fn (array $row): float => $toFloat($row['valor_global_a'] ?? 0)),
            'saldo' => $allMetrics->sum('saldo_geral'),
            'saldo_negativo' => $allMetrics->sum('saldo_geral') < 0,
        ];
    @endphp

    <div class="cnf-fornecedor-root space-y-6">
        <section class="cnf-fornecedor-panel">
            <div>
                {{ $this->form }}
            </div>
        </section>

        @if ($record && $selectedControle)
            <div class="cmed-shell">
                <section class="cmed-card">
                    <header class="cmed-card-header">
                        <div>
                            <h2 class="cmed-title">{{ $selectedControle['label'] }}</h2>
                            <p class="cmed-subtitle">Visualização somente leitura dos escopos vinculados ao seu fornecedor.</p>
                        </div>
                        <span class="cmed-badge cmed-badge-{{ $statusTone }}">{{ $statusLabel }}</span>
                    </header>

                    <div class="cmed-header-grid">
                        <div class="cmed-header-cell">
                            <strong>Unidade</strong>
                            {{ $selectedControle['unidade'] }}
                        </div>
                        <div class="cmed-header-cell">
                            <strong>Sigla</strong>
                            {{ $selectedControle['sigla'] }}
                        </div>
                        <div class="cmed-header-cell">
                            <strong>Escopos</strong>
                            {{ $totais['linhas'] }}
                        </div>
                    </div>

                    <div class="cmed-stats">
                        <div class="cmed-stat">
                            <div class="cmed-stat-label">Valor global</div>
                            <div class="cmed-stat-value">R$ {{ number_format((float) $selectedControle['valor_global_total'], 2, ',', '.') }}</div>
                        </div>
                        <div class="cmed-stat {{ $totais['saldo_negativo'] ? 'cmed-stat-danger' : '' }}">
                            <div class="cmed-stat-label">Saldo total</div>
                            <div class="cmed-stat-value">R$ {{ number_format((float) $totais['saldo'], 2, ',', '.') }}</div>
                        </div>
                        <div class="cmed-stat">
                            <div class="cmed-stat-label">Controle</div>
                            <div class="cmed-stat-value">#{{ $selectedControle['id'] }}</div>
                        </div>
                    </div>
                </section>

                <div class="cmed-sections">
                    <section class="cmed-section">
                        <header class="cmed-section-header">Itens contratuais</header>
                        <div class="cmed-list">
                            @foreach ($rowsPrincipais as $row)
                                @include('filament.pages.partials.construtora-controles-nota-fiscal-row', ['row' => $row, 'calculateRowMetrics' => $calculateRowMetrics, 'toFloat' => $toFloat])
                            @endforeach
                        </div>
                    </section>

                    <section class="cmed-section">
                        <header class="cmed-section-header">Itens extra contratuais (adicionais)</header>
                        <div class="cmed-list">
                            @foreach ($rowsAdicionais as $row)
                                @include('filament.pages.partials.construtora-controles-nota-fiscal-row', ['row' => $row, 'calculateRowMetrics' => $calculateRowMetrics, 'toFloat' => $toFloat])
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>
        @elseif (filled($this->selectedObraId))
            <section class="cnf-fornecedor-empty">
                Nenhum controle encontrado para a unidade selecionada.
            </section>
        @endif
    </div>
</x-filament-panels::page>
