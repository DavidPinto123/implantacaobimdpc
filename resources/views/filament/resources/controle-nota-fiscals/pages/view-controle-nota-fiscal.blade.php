<x-filament-panels::page>
    @php
        $record = $this->record->loadMissing(['obra']);

        $rows = collect($this->sheetRows);
        // Itens contratuais: qualquer item (com ou sem as_escopo_id), incluindo escopos vazios criados manualmente
        $rowsPrincipais = $rows->filter(fn (array $row): bool => ($row['source'] ?? null) === 'item')->values();
        $rowsAdicionais = $rows->filter(fn (array $row): bool => ($row['source'] ?? null) === 'auxiliar')->values();

        $statusLabel = match ($record->status) {
            'ativo' => 'Ativo',
            'aguardando_construtora' => 'Aguardando fornecedor',
            'aguardando_financeiro' => 'Aguardando financeiro',
            'aprovado' => 'Aprovado',
            'reprovado' => 'Reprovado',
            'encerrado' => 'Encerrado',
            default => (string) $record->status,
        };

        $statusTone = match ($record->status) {
            'ativo' => 'info',
            'aprovado' => 'success',
            'reprovado' => 'danger',
            'aguardando_construtora', 'aguardando_financeiro' => 'warning',
            'encerrado' => 'neutral',
            default => 'neutral',
        };

        $tipoUnidadeLabel = $record->tipo_unidade_label;
        $tipoUnidadeTone = $record->isRetrofit() ? 'warning' : 'info';

        $isEditable = false;
        $canEditNotas = false;

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
            $valorTotal = $toFloat($row['valor_global_a'] ?? 0);
            $percentualDireto = $toFloat($row['percentual_faturamento_mao_obra'] ?? 60);
            $percentualIndireto = $toFloat($row['percentual_faturamento_material'] ?? 40);

            $acumuladoDireto = collect($row['notas_mao_obra'] ?? [])
                ->filter(fn (array $nota): bool => ($nota['status'] ?? null) === \App\Enums\StatusControleNotaFiscalNota::APROVADO->value)
                ->sum(fn (array $nota): float => $toFloat($nota['valor_acumulado_medido_nf'] ?? 0));
            $acumuladoIndireto = collect($row['notas_material'] ?? [])
                ->filter(fn (array $nota): bool => ($nota['status'] ?? null) === \App\Enums\StatusControleNotaFiscalNota::APROVADO->value)
                ->sum(fn (array $nota): float => $toFloat($nota['valor_acumulado_medido_nf'] ?? 0));

            $faturamentoDiretoTotal = $valorTotal * ($percentualDireto / 100);
            $faturamentoIndiretoTotal = $valorTotal * ($percentualIndireto / 100);
            $acumuladoTotal = $acumuladoDireto + $acumuladoIndireto;
            $totalMedicao = $acumuladoTotal;
            $saldoGeral = $valorTotal - $totalMedicao;
            $saldoDireto = $faturamentoDiretoTotal - $acumuladoDireto;
            $saldoIndireto = $faturamentoIndiretoTotal - $acumuladoIndireto;

            $percentualSaldoDireto = $faturamentoDiretoTotal > 0 ? ($saldoDireto / $faturamentoDiretoTotal) * 100 : 0.0;
            $percentualSaldoIndireto = $faturamentoIndiretoTotal > 0 ? ($saldoIndireto / $faturamentoIndiretoTotal) * 100 : 0.0;
            $percentualSaldoGeral = $valorTotal > 0 ? ($saldoGeral / $valorTotal) * 100 : 0.0;

            return [
                'faturamento_direto_total' => $faturamentoDiretoTotal,
                'faturamento_indireto_total' => $faturamentoIndiretoTotal,
                'total_medicao_a_menos_b' => $totalMedicao,
                'acumulado_direto' => $acumuladoDireto,
                'acumulado_indireto' => $acumuladoIndireto,
                'valor_acumulado_medido' => $acumuladoTotal,
                'saldo_direto' => $saldoDireto,
                'saldo_indireto' => $saldoIndireto,
                'saldo_geral' => $saldoGeral,
                'percentual_saldo_direto' => $percentualSaldoDireto,
                'percentual_saldo_indireto' => $percentualSaldoIndireto,
                'percentual_saldo_geral' => $percentualSaldoGeral,
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

        $blankNote = static function (array $nota): bool {
            return blank($nota['empresa'] ?? null)
                && blank($nota['numero_nf'] ?? null)
                && blank($nota['cnpj_faturamento'] ?? null)
                && blank($nota['valor_acumulado_medido_nf'] ?? null)
                && blank($nota['emissao'] ?? null)
                && blank($nota['recebimento'] ?? null)
                && blank($nota['envio'] ?? null)
                && blank($nota['status'] ?? null)
                && blank($nota['arquivo_path'] ?? null)
                && blank($nota['observacoes'] ?? null);
        };
    @endphp


    <div class="cmed-shell">
        <div class="cmed-card">
            <div class="cmed-card-header">
                <div>
                    <h2 class="cmed-title">CMED · Controle de Medição</h2>
                    <p class="cmed-subtitle">Visualização consolidada para leitura rápida e navegação por escopo.</p>
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.35rem;">
                    <span class="cmed-badge cmed-badge-{{ $statusTone }}">{{ $statusLabel }}</span>
                    <span class="cmed-badge cmed-badge-{{ $tipoUnidadeTone }}">{{ $tipoUnidadeLabel }}</span>
                </div>
            </div>
            <div class="cmed-header-grid">
                <div class="cmed-header-cell"><strong>Unidade</strong>{{ $record->obra?->unidade ?? $record->unidade ?? '-' }}</div>
                <div class="cmed-header-cell"><strong>Sigla</strong>{{ $record->obra?->sigla ?? $record->sigla ?? '-' }}</div>
                <div class="cmed-header-cell"><strong>Tipo da unidade</strong>{{ $tipoUnidadeLabel }}</div>
                <div class="cmed-header-cell"><strong>Data base</strong>{{ optional($record->data_base)->format('d/m/Y') ?? '-' }}</div>
            </div>
            <div class="cmed-stats">
                <div class="cmed-stat">
                    <div class="cmed-stat-label">Total de linhas</div>
                    <div class="cmed-stat-value">{{ number_format($totais['linhas'], 0, ',', '.') }}</div>
                </div>
                <div class="cmed-stat">
                    <div class="cmed-stat-label">Valor Global</div>
                    <div class="cmed-stat-value">R$ {{ number_format($totais['valor_global'], 2, ',', '.') }}</div>
                </div>
                <div class="cmed-stat {{ $totais['saldo_negativo'] ? 'cmed-stat-danger' : '' }}">
                    <div class="cmed-stat-label">Saldo Total</div>
                    <div class="cmed-stat-value">R$ {{ number_format($totais['saldo'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>

        @php
            $renderMainRows = function ($rowsCollection, int $rowOffset = 0) use ($isEditable, $canEditNotas, $blankNote, $calculateRowMetrics, $toFloat) {
                foreach ($rowsCollection as $position => $row) {
                    $rowIndex = $rowOffset + $position;
                    $expanded = (bool) ($this->expandedRows[$rowIndex] ?? false);
                    $notasMaoObra = collect($row['notas_mao_obra'] ?? []);
                    $notasMaterial = collect($row['notas_material'] ?? []);
                    $isAsScopeRow = filled($row['as_escopo_id'] ?? null);
                    $rowMetrics = $calculateRowMetrics($row);
                    $saldoPercentual = $rowMetrics['percentual_saldo_geral'];
                    $rowHasNegativeSaldo = $rowMetrics['saldo_geral'] < 0
                        || $rowMetrics['saldo_direto'] < 0
                        || $rowMetrics['saldo_indireto'] < 0;
                    // Tolerância de 0.01 p.p. — valores como 9.9995% ainda são considerados "= 10%".
                    if ($rowHasNegativeSaldo) {
                        $saldoClass = 'cmed-metric-danger';
                        $saldoAviso = 'SALDO NEGATIVO';
                    } elseif (abs($saldoPercentual - 10) < 0.01) {
                        $saldoClass = 'cmed-metric-warn';
                        $saldoAviso = 'ATENÇÃO: 10% ALCANÇADO';
                    } elseif ($saldoPercentual < 10) {
                        $saldoClass = 'cmed-metric-danger';
                        $saldoAviso = 'ATENÇÃO: INFERIOR A 10%';
                    } else {
                        $saldoClass = '';
                        $saldoAviso = null;
                    }

                    $rowKey = 'cmed-row-'.$row['source'].'-'.$row['id'].'-'.$rowIndex;

                    $autoUnlock = $isEditable && ($this->autoEditRowIndex ?? null) === $rowIndex;
                    echo '<article class="cmed-row-card" wire:key="'.e($rowKey).'" x-data="{ expanded: '.($expanded ? 'true' : 'false').', unlocked: '.($autoUnlock ? 'true' : 'false').' }">';
                    echo '<div class="cmed-row-head">';
                    echo '<div class="cmed-expand-cell"><button type="button" class="cmed-expand-btn" wire:key="'.e($rowKey).'-toggle" @click="expanded = !expanded" aria-label="Expandir/Recolher"><span x-text="expanded ? \'-\' : \'+\'">'.($expanded ? '-' : '+').'</span></button></div>';
                    echo '<div class="cmed-row-body">';

                    // Coluna esquerda: Grupo, A.S., Complemento, Escopo, Empresa
                    $isEmptyItemRow = $isEditable && ($row['source'] ?? null) === 'item' && blank($row['as_escopo_id'] ?? null);
                    echo '<div class="cmed-ident-col">';
                    echo '<div class="cmed-ident-grid">';
                    foreach ([
                        ['grupo', 'Grupo'],
                        ['numero_as', 'A.S.', 80],
                        ['numero_complemento', 'Complemento', null, 'complemento'],
                        ['escopo', 'Escopo'],
                        ['empresa', 'Empresa'],
                    ] as $fieldConfig) {
                        $field = $fieldConfig[0];
                        $label = $fieldConfig[1];
                        $maxWidth = $fieldConfig[2] ?? null;

                        $fieldLocked = $isEditable && $isAsScopeRow && in_array($field, ['grupo', 'numero_as', 'escopo'], true);

                        // Para escopos vazios criados manualmente: A.S. vira select, grupo/escopo são preenchidos via servidor
                        $isAsSelectField = $isEmptyItemRow && $field === 'numero_as';
                        $isAutoFilledField = $isEmptyItemRow && in_array($field, ['grupo', 'escopo'], true);

                        echo '<div class="cmed-field"'.($maxWidth ? ' style="max-width: '.$maxWidth.'px;"' : '').'>';
                        echo '<span class="cmed-field-label">'.$label.'</span>';
                        echo '<div class="cmed-field-value">';

                        if ($isAsSelectField) {
                            // Select de A.S. com as opções disponíveis
                            // onchange chama uma função JS externa que usa $wire.call e recarrega
                            // a página, evitando re-render do Livewire que quebra a view.
                            $opcoes = $this->getAsEscoposDisponiveis();
                            echo '<span x-show="!unlocked">—</span>';
                            echo '<select x-show="unlocked" x-cloak class="cmed-input cmed-as-select" data-row-index="'.$rowIndex.'" onchange="cmedSelectAsEscopo(this)" style="max-width: 60px;">';
                            echo '<option value="">—</option>';
                            foreach ($opcoes as $opcao) {
                                echo '<option value="'.e($opcao['id']).'" title="'.e($opcao['label']).'">'.e($opcao['label']).'</option>';
                            }
                            echo '</select>';
                        } elseif ($isAutoFilledField) {
                            // Mostra placeholder até A.S. ser selecionada
                            $valor = (string) ($row[$field] ?? '');
                            if (filled($valor)) {
                                if ($field === 'escopo') {
                                    echo '<span class="cmed-field-stack">';
                                    echo '<span>'.e($valor).'</span>';
                                    if ($rowHasNegativeSaldo) {
                                        echo '<span class="cmed-inline-badge cmed-inline-badge-danger">Saldo negativo</span>';
                                    }
                                    echo '</span>';
                                } else {
                                    echo e($valor);
                                }
                            } else {
                                echo '<span class="cmed-muted" style="font-size: .7rem; font-style: italic;">Selecione A.S.</span>';
                            }
                        } elseif ($isEditable && ! $fieldLocked && $field === 'empresa') {
                            $styleAttr = $maxWidth ? ' max-width: '.$maxWidth.'px;' : '';
                            $valorAtual = (string) ($row[$field] ?? '');
                            echo '<span x-show="!unlocked">'.e($valorAtual !== '' ? $valorAtual : '-').'</span>';
                            echo '<select x-show="unlocked" x-cloak class="cmed-input" wire:model.defer="sheetRows.'.$rowIndex.'.'.$field.'" style="'.$styleAttr.'">';
                            echo '<option value="">—</option>';
                            foreach ($this->getConstrutoraOptions() as $construtoraNome) {
                                $selected = $construtoraNome === $valorAtual ? ' selected' : '';
                                echo '<option value="'.e($construtoraNome).'"'.$selected.'>'.e($construtoraNome).'</option>';
                            }
                            echo '</select>';
                        } elseif ($isEditable && ! $fieldLocked) {
                            $styleAttr = $maxWidth ? ' max-width: '.$maxWidth.'px;' : '';

                            if ($field === 'escopo') {
                                echo '<span x-show="!unlocked" class="cmed-field-stack">';
                                echo '<span>'.e($row[$field] ?? '-').'</span>';
                                if ($rowHasNegativeSaldo) {
                                    echo '<span class="cmed-inline-badge cmed-inline-badge-danger">Saldo negativo</span>';
                                }
                                echo '</span>';

                                echo '<span x-show="unlocked" x-cloak class="cmed-field-stack">';
                                echo '<input class="cmed-input" wire:model.defer="sheetRows.'.$rowIndex.'.'.$field.'" type="text" style="'.$styleAttr.'">';
                                if ($rowHasNegativeSaldo) {
                                    echo '<span class="cmed-inline-badge cmed-inline-badge-danger">Saldo negativo</span>';
                                }
                                echo '</span>';
                            } else {
                                $valorAtual = (string) ($row[$field] ?? '');
                                echo '<span x-show="!unlocked">'.e($valorAtual !== '' ? $valorAtual : '-').'</span>';
                                echo '<input x-show="unlocked" x-cloak class="cmed-input" wire:model.defer="sheetRows.'.$rowIndex.'.'.$field.'" type="text" style="'.$styleAttr.'">';
                            }
                        } else {
                            if ($field === 'numero_complemento') {
                                $valorComplemento = (string) ($row[$field] ?? '');
                                echo e($valorComplemento !== '' ? $valorComplemento : '-');
                            } elseif ($field === 'escopo') {
                                echo '<span class="cmed-field-stack">';
                                echo '<span>'.e($row[$field] ?? '-').'</span>';
                                if ($rowHasNegativeSaldo) {
                                    echo '<span class="cmed-inline-badge cmed-inline-badge-danger">Saldo negativo</span>';
                                }
                                echo '</span>';
                            } else {
                                echo e($row[$field] ?? '-');
                            }
                        }
                        echo '</div></div>';
                    }
                    echo '</div>';

                    // Escopo Complementar segue o Controle de AS: sempre visível em linhas principais.
                    $mostrarEscopoComplementar = ($row['source'] ?? null) === 'item';
                    if ($mostrarEscopoComplementar) {
                        echo '<div class="cmed-escopo-complementar-wrap">';
                        echo '<div class="cmed-field" style="width: 100%;">';
                        echo '<span class="cmed-field-label">Escopo Complementar</span>';
                        echo '<div class="cmed-field-value">';
                        if ($isEditable) {
                            $valorEc = (string) ($row['escopo_complementar'] ?? '');
                            echo '<span x-show="!unlocked">'.e($valorEc !== '' ? $valorEc : '-').'</span>';
                            if (filled($row['numero_complemento'] ?? null)) {
                                echo '<input x-show="unlocked" x-cloak class="cmed-input" wire:model.defer="sheetRows.'.$rowIndex.'.escopo_complementar" type="text" style="width: 100%;" placeholder="Descreva o escopo complementar">';
                            } else {
                                echo '<span x-show="unlocked" x-cloak>-</span>';
                            }
                        } else {
                            echo e(($row['escopo_complementar'] ?? '') !== '' ? $row['escopo_complementar'] : '-');
                        }
                        echo '</div></div>';
                        echo '</div>';
                    }
                    echo '</div>'; // /cmed-ident-col

                    // Coluna do meio: Mão de obra e Material percentuais (em coluna)
                    $valorGlobal = $toFloat($row['valor_global_a'] ?? 0);
                    $moValue = (float) ($row['percentual_faturamento_mao_obra'] ?? 60);
                    $matValue = (float) ($row['percentual_faturamento_material'] ?? 40);
                    echo '<div class="cmed-percentual-grid" data-valor-global="'.$valorGlobal.'">';
                    echo '<div class="cmed-field" style="margin: 0; width: 100%;">';
                    echo '<span class="cmed-field-label">Mão de obra</span>';
                    echo '<div class="cmed-field-value" style="display: flex; align-items: center; gap: 4px;">';
                    if ($isEditable) {
                        echo '<span x-show="!unlocked">'.number_format($moValue, 2, ',', '.').'%</span>';
                        echo '<input x-show="unlocked" x-cloak class="cmed-input cmed-percentual-mo" wire:model.defer="sheetRows.'.$rowIndex.'.percentual_faturamento_mao_obra" type="number" inputmode="decimal" step="0.01" min="0" max="100" style="flex: 1; width: auto;" value="'.$moValue.'">';
                        echo '<span x-show="unlocked" x-cloak style="color: var(--cmed-text-muted); font-size: 0.75rem; white-space: nowrap;">%</span>';
                    } else {
                        echo number_format($moValue, 2, ',', '.').'%';
                    }
                    echo '</div></div>';
                    echo '<div class="cmed-field" style="margin: 0; width: 100%;">';
                    echo '<span class="cmed-field-label">Material</span>';
                    echo '<div class="cmed-field-value" style="display: flex; align-items: center; gap: 4px;">';
                    if ($isEditable) {
                        echo '<span x-show="!unlocked">'.number_format($matValue, 2, ',', '.').'%</span>';
                        echo '<input x-show="unlocked" x-cloak class="cmed-input cmed-percentual-mat" wire:model.defer="sheetRows.'.$rowIndex.'.percentual_faturamento_material" type="number" inputmode="decimal" step="0.01" min="0" max="100" style="flex: 1; width: auto;" value="'.$matValue.'">';
                        echo '<span x-show="unlocked" x-cloak style="color: var(--cmed-text-muted); font-size: 0.75rem; white-space: nowrap;">%</span>';
                    } else {
                        echo number_format($matValue, 2, ',', '.').'%';
                    }
                    echo '</div></div>';
                    echo '</div>';

                    // Métricas
                    echo '<div class="cmed-metrics-bottom">';
                    echo '<div class="cmed-metric"><div class="cmed-metric-label">Valor global (A)</div><div class="cmed-metric-value">';
                    $valorGlobalFormatado = 'R$ '.number_format($toFloat($row['valor_global_a'] ?? 0), 2, ',', '.');
                    if ($isEditable) {
                        echo '<span x-show="!unlocked">'.$valorGlobalFormatado.'</span>';
                        echo '<input x-show="unlocked" x-cloak class="cmed-input cmed-valor-global cmed-money-mask" type="text" inputmode="decimal" value="'.$valorGlobalFormatado.'" data-target="vg-'.$rowIndex.'">';
                        echo '<input type="hidden" id="vg-'.$rowIndex.'" wire:model.defer="sheetRows.'.$rowIndex.'.valor_global_a">';
                    } else {
                        echo $valorGlobalFormatado;
                    }
                    echo '</div></div>';
                    echo '<div class="cmed-metric cmed-metric-warn"><div class="cmed-metric-label">Total medido</div><div class="cmed-metric-value cmed-total-medido">R$ '.number_format($rowMetrics['total_medicao_a_menos_b'], 2, ',', '.').'</div></div>';
                    echo '<div class="cmed-metric '.$saldoClass.'"><div class="cmed-metric-label">Saldo geral ('.number_format($saldoPercentual, 2, ',', '.').'%)'.($saldoAviso ? '<br><small>'.$saldoAviso.'</small>' : '').'</div><div class="cmed-metric-value cmed-saldo-geral">R$ '.number_format($rowMetrics['saldo_geral'], 2, ',', '.').'</div></div>';
                    echo '</div>';

                    echo '</div></div>';

                    // Expand/collapse is handled on the client side (Alpine) to avoid Livewire snapshot errors.
                    echo '<div class="cmed-row-expanded" x-show="expanded" x-cloak>';

                        $renderNotas = function ($tipo, $notas) use ($rowKey, $rowIndex, $canEditNotas, $blankNote, $rowMetrics, $toFloat) {
                            $titulo = $tipo === 'mao_obra' ? 'MÃO DE OBRA' : 'MATERIAL';
                            $wireKey = $tipo === 'mao_obra' ? 'notas_mao_obra' : 'notas_material';
                            $totalValorAcumulado = $notas
                                ->filter(fn (array $nota): bool => ($nota['status'] ?? null) === \App\Enums\StatusControleNotaFiscalNota::APROVADO->value)
                                ->sum(fn (array $nota): float => $toFloat($nota['valor_acumulado_medido_nf'] ?? 0));
                            $isDireto = $tipo === 'mao_obra';
                            $totalFaturamento = $isDireto ? $rowMetrics['faturamento_direto_total'] : $rowMetrics['faturamento_indireto_total'];
                            $saldoFaturamento = $isDireto ? $rowMetrics['saldo_direto'] : $rowMetrics['saldo_indireto'];
                            $percentualSaldo = $isDireto ? $rowMetrics['percentual_saldo_direto'] : $rowMetrics['percentual_saldo_indireto'];

                            $tipoClass = $isDireto ? 'cmed-section-mo' : 'cmed-section-mat';
                            $saldoResumoClass = $saldoFaturamento < 0 ? 'cmed-finance-header-summary-danger' : '';
                            // Percentual do acumulado sobre o faturamento (para "TOTAL MEDIDO X%")
                            $percentualMedido = $totalFaturamento > 0 ? ($totalValorAcumulado / $totalFaturamento) * 100 : 0.0;

                            echo '<div class="cmed-finance-header '.$tipoClass.'" data-acumulado="'.$totalValorAcumulado.'" data-faturamento="'.$totalFaturamento.'">';
                            echo '<span class="cmed-finance-header-title">'.$titulo.'</span>';
                            echo '<span class="cmed-finance-header-summary '.$saldoResumoClass.'">';
                            echo '<strong>TOTAL MEDIDO:</strong> <span class="cmed-acum-value">R$ '.number_format($totalValorAcumulado, 2, ',', '.').'</span> <span class="cmed-pct-medido-value">'.number_format($percentualMedido, 2, ',', '.').'%</span>';
                            echo '<span class="cmed-finance-header-sep">|</span>';
                            echo '<strong>SALDO</strong> <span class="cmed-saldo-value">R$ '.number_format($saldoFaturamento, 2, ',', '.').'</span> <span class="cmed-pct-saldo-value">'.number_format($percentualSaldo, 2, ',', '.').'%</span>';
                            echo '</span>';
                            echo '</div>';

                            echo '<div class="cmed-notas-wrap" style="margin-bottom:.6rem">';
                            echo '<table class="cmed-table">';
                            echo '<thead>';
                            echo '<tr><th style="width:34px"></th><th>Anexo</th><th>Fornecedor</th><th>CNPJ Fornecedor</th><th>Nº NF</th><th>Valor</th><th>Emissão</th><th>Recebimento</th><th>Validação</th><th>Status</th><th style="width:120px">Observação</th></tr>';
                            echo '</thead><tbody>';

                            if ($notas->isEmpty()) {
                                echo '<tr><td class="cmed-expand-cell">';
                                echo '</td><td colspan="10" class="cmed-muted">Sem linhas.</td></tr>';
                            } else {
                                foreach ($notas as $notaIndex => $nota) {
                                    $isLastNotaRow = $notaIndex === $notas->count() - 1;
                                    $isBlankNotaRow = $blankNote($nota);
                                    $hasTemporaryUpload = filled(data_get($this->notaUploads ?? [], $rowIndex.'.'.$wireKey.'.'.$notaIndex));
                                    $notaKey = $rowKey.'-'.$wireKey.'-'.($nota['id'] ?? 'new').'-'.$notaIndex;

                                    if ($hasTemporaryUpload) {
                                        $isBlankNotaRow = false;
                                    }

                                    echo '<tr wire:key="'.e($notaKey).'">';
                                    // 1. Ações
                                    echo '<td class="cmed-expand-cell">';
                                    echo '</td>';

                                    // 2. Anexo
                                    echo '<td>';
                                    if ($canEditNotas) {
                                        echo '<div class="cmed-actions-stack">';
                                        echo '<input type="file" wire:key="'.e($notaKey).'-file" wire:model="notaUploads.'.$rowIndex.'.'.$wireKey.'.'.$notaIndex.'" accept=".pdf,.xml,application/pdf,application/xml,text/xml">';
                                        if (filled($nota['arquivo_path'] ?? null)) {
                                            $arquivoUrl = \Illuminate\Support\Facades\Storage::disk((string) config('filesystems.media_disk', 'r2'))->url($nota['arquivo_path']);
                                            echo '<a href="'.e($arquivoUrl).'" target="_blank" rel="noopener noreferrer">Abrir arquivo</a>';
                                        }
                                        echo '</div>';
                                    } elseif (filled($nota['arquivo_path'] ?? null)) {
                                        $arquivoUrl = \Illuminate\Support\Facades\Storage::disk((string) config('filesystems.media_disk', 'r2'))->url($nota['arquivo_path']);
                                        echo '<a href="'.e($arquivoUrl).'" target="_blank" rel="noopener noreferrer">Abrir arquivo</a>';
                                    } else {
                                        echo '<span class="cmed-muted">Sem anexo</span>';
                                    }
                                    echo '</td>';

                                    // 3–10: Fornecedor, CNPJ, Nº NF, Valor, Emissão, Recebimento, Validação (Envio), Status
                                    foreach ([
                                        ['empresa', 'text'],
                                        ['cnpj_faturamento', 'text'],
                                        ['numero_nf', 'text'],
                                        ['valor_acumulado_medido_nf', 'money'],
                                        ['emissao', 'date'],
                                        ['recebimento', 'date'],
                                        ['envio', 'date'],
                                        ['status', 'status'],
                                    ] as [$field, $type]) {
                                        echo '<td>';
                                        if ($canEditNotas) {
                                            $inputType = $type === 'date' ? 'date' : 'text';
                                            echo '<input class="cmed-input" wire:model.defer="sheetRows.'.$rowIndex.'.'.$wireKey.'.'.$notaIndex.'.'.$field.'" type="'.$inputType.'">';
                                        } else {
                                            $raw = $nota[$field] ?? null;

                                            if ($type === 'money') {
                                                echo blank($raw) ? '-' : 'R$ '.number_format((float) $raw, 2, ',', '.');
                                            } elseif ($type === 'date') {
                                                if (blank($raw)) {
                                                    echo '-';
                                                } else {
                                                    try {
                                                        echo e(\Illuminate\Support\Carbon::parse($raw)->format('d/m/Y'));
                                                    } catch (\Throwable $e) {
                                                        echo e((string) $raw);
                                                    }
                                                }
                                            } elseif ($type === 'status') {
                                                echo e(match ((string) $raw) {
                                                    'pendente' => 'Pendente',
                                                    'em_analise' => 'Aguardando Aprovação do Gestor',
                                                    'aprovado' => 'Aprovado',
                                                    'reprovado' => 'Reprovado',
                                                    '' => '-',
                                                    default => ucfirst(str_replace('_', ' ', (string) $raw)),
                                                });

                                            } else {
                                                echo e($raw ?? '-');
                                            }
                                        }
                                        echo '</td>';
                                    }

                                    // 11. Observação
                                    echo '<td>';
                                    if ($canEditNotas) {
                                        echo '<input class="cmed-input" wire:model.defer="sheetRows.'.$rowIndex.'.'.$wireKey.'.'.$notaIndex.'.observacoes" type="text">';
                                    } else {
                                        echo e($nota['observacoes'] ?? '-');
                                    }
                                    echo '</td>';

                                    echo '</tr>';
                                }
                            }

                            echo '<tr>';
                            echo '<td class="cmed-th-black" colspan="5">TOTAL - '.strtoupper($titulo).'</td>';
                            echo '<td class="cmed-th-black">R$ '.number_format($totalValorAcumulado, 2, ',', '.').'</td>';
                            echo '<td class="cmed-th-black"></td>';
                            echo '<td class="cmed-th-black"></td>';
                            echo '<td class="cmed-th-black"></td>';
                            echo '<td class="cmed-th-black"></td>';
                            echo '<td class="cmed-th-black"></td>';
                            echo '</tr>';
                            echo '</tbody></table></div>';
                        };

                        $renderNotas('mao_obra', $notasMaoObra);
                        $renderNotas('material', $notasMaterial);

                        echo '</div>';

                    echo '</article>';
                }
            };
        @endphp

        <div class="cmed-sections">
            <section class="cmed-section">
                <header class="cmed-section-header">Itens contratuais</header>
                <div class="cmed-list">
                    @php $renderMainRows($rowsPrincipais, 0); @endphp
                </div>
            </section>

            <section class="cmed-section">
                <header class="cmed-section-header">Itens extra contratuais (adicionais)</header>
                <div class="cmed-list">
                    @php $renderMainRows($rowsAdicionais, $rowsPrincipais->count()); @endphp
                </div>
            </section>
        </div>

    </div>

</x-filament-panels::page>
