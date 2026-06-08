<x-filament-panels::page>
    @php
        $record = $this->record;
        $totalGeral = $record->itens->sum('valor_total_geral');
        $firstPath = fn ($value) => is_array($value) ? ($value[0] ?? null) : $value;
        $statusFluxo = match ($record->status_fluxo) {
            'elaboracao' => 'Elaboração',
            'em_aprovacao_gestor' => 'Em aprovação do gestor',
            'em_aprovacao_orcamento' => 'Em aprovação do orçamento',
            'aprovado' => 'Aprovado',
            'reprovado_gestor' => 'Reprovado pelo gestor',
            'reprovado_orcamento' => 'Reprovado pelo orçamento',
            default => 'Elaboracao',
        };
    @endphp

    <style>
        .fi-main .fi-page{max-width:none!important}
        .ea-view{width:100%;max-width:100%;overflow-x:hidden}
        .ea-wrap{width:100%;max-width:100%;border:1px solid #d6d9df;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(15,23,42,.06);overflow:hidden}
        .ea-head{padding:18px 22px;border-bottom:1px solid #e8ebf0;background:#ffffff}
        .ea-status{display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;background:#e0f2fe;color:#075985}
        .ea-rej{margin-top:10px;padding:10px 12px;border-radius:10px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;font-size:13px}
        .ea-meta{margin-top:12px;font-size:15px;line-height:1.7;color:#1f2937}
        .ea-evi{margin-top:14px;padding-top:12px;border-top:1px solid #e8ebf0}
        .ea-evi-title{font-size:12px;font-weight:900;letter-spacing:.05em;text-transform:uppercase;color:#475569}
        .ea-evi-grid{margin-top:10px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .ea-evi-card{border:1px solid #e5e7eb;border-radius:12px;padding:10px;background:#f8fafc}
        .ea-evi-label{font-size:12px;font-weight:800;color:#0f172a}
        .ea-evi-img{margin-top:8px;width:100%;height:110px;object-fit:contain;border-radius:10px;background:#ffffff;border:1px solid #e5e7eb}
        .ea-evi-link{margin-top:6px;font-size:12px;text-decoration:underline;color:#0f172a;display:inline-block}
        .ea-evi-empty{margin-top:6px;font-size:12px;color:#64748b}
        .ea-table-wrap{margin:16px;max-width:calc(100% - 32px);border:1px solid #d8dbe2;border-radius:14px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;overscroll-behavior-x:contain}
        .ea-table{width:100%;min-width:1100px;border-collapse:collapse;background:#fff}
        .ea-table th{font-size:12px;text-transform:uppercase;letter-spacing:.03em;text-align:center;color:#334155;background:#f1f5f9;padding:12px 8px;border-bottom:1px solid #d9e0ea}
        .ea-table td{padding:10px 8px;border-bottom:1px solid #e8edf4}
        .ea-right{text-align:right}
        .ea-center{text-align:center}
        .ea-total-label{font-size:16px;font-weight:900;color:#111827;padding:12px 8px}
        .ea-total-value{font-size:clamp(20px,2.4vw,28px);font-weight:900;color:#111827;text-align:right;white-space:nowrap;padding:12px 8px}
        .ea-total-row{background:#f8fafc}
        @media (max-width:1024px){.ea-total-value{font-size:24px}.ea-total-label{font-size:15px}}
        @media (max-width:768px){
            .ea-head{padding:14px}
            .ea-meta{font-size:14px;line-height:1.6}
            .ea-evi-grid{grid-template-columns:1fr}
            .ea-table-wrap{margin:10px;max-width:calc(100% - 20px)}
            .ea-table{min-width:760px}
            .ea-table th,.ea-table td{padding:8px 6px}
            .ea-table th{font-size:11px}
            .ea-total-label{font-size:14px}
            .ea-total-value{font-size:20px}
        }
        html.dark .ea-wrap{background:var(--gray-900);border-color:#6b7280;box-shadow:0 10px 30px rgba(0,0,0,.35)}
        html.dark .ea-head{background:var(--gray-900);border-color:#6b7280}
        html.dark .ea-status{background:#6b7280;color:#f3f4f6}
        html.dark .ea-rej{border-color:#7f1d1d;background:#3a1212;color:#fecaca}
        html.dark .ea-meta{color:#f3f4f6}
        html.dark .ea-evi{border-color:#6b7280}
        html.dark .ea-evi-title{color:#e5e7eb}
        html.dark .ea-evi-card{border-color:#6b7280;background:var(--gray-800)}
        html.dark .ea-evi-label{color:#f8fafc}
        html.dark .ea-evi-img{background:var(--gray-900);border-color:#6b7280}
        html.dark .ea-evi-link{color:#f8fafc}
        html.dark .ea-evi-empty{color:#cbd5e1}
        html.dark .ea-table-wrap{border-color:#9ca3af}
        html.dark .ea-table{background:var(--gray-900)}
        html.dark .ea-table th{background:var(--gray-800);color:#e5e7eb;border-color:#6b7280}
        html.dark .ea-table td{color:#f1f5f9;border-color:#6b7280}
        html.dark .ea-total-row{background:var(--gray-800)}
        html.dark .ea-total-label, html.dark .ea-total-value{color:#f8fafc}
    </style>

    <div class="ea-view">
        <div class="ea-wrap">
            <div class="ea-head">
                <span class="ea-status">Status do fluxo: {{ $statusFluxo }}</span>

                @if($record->status_fluxo === 'reprovado_gestor' && filled($record->justificativa_reprovacao_gestor))
                    <div class="ea-rej"><strong>Justificativa da reprovação (gestor):</strong> {{ $record->justificativa_reprovacao_gestor }}</div>
                @endif

                @if($record->status_fluxo === 'reprovado_orcamento' && filled($record->justificativa_reprovacao_orcamento))
                    <div class="ea-rej"><strong>Justificativa da reprovação (orçamento):</strong> {{ $record->justificativa_reprovacao_orcamento }}</div>
                @endif

                <div class="ea-meta">
                    <div><strong>EXPANSÃO/ ORÇAMENTOS</strong></div>
                    <div><strong>OBRA:</strong> {{ $record->obra?->unidade ?? '-' }}</div>
                    <div><strong>GESTOR:</strong> {{ $record->gestor?->name ?? '-' }}</div>
                    <div><strong>DATA:</strong> {{ optional($record->data)?->format('d/m/Y') ?? \Carbon\Carbon::parse($record->data)->format('d/m/Y') }}</div>
                    <div><strong>REF. SERVIÇO:</strong> {{ $record->asEscopo?->escopo ?? '-' }}</div>
                    <div><strong>FORNECEDOR:</strong> {{ $record->construtora?->nome ?? '-' }}</div>
                </div>

                <div class="ea-evi">
                    <div class="ea-evi-title">Evidências</div>
                    <div class="ea-evi-grid">
                        @php
                            $evidencias = [
                                'Foto antes' => $record->foto_antes,
                                'Foto depois (caso já executado)' => $record->foto_depois,
                                'Projeto orçado' => $record->projeto_orcado,
                                'Projeto revisado' => $record->projeto_revisado,
                                'Escopo contratado' => $record->escopo_contratado,
                                'Escopo real' => $record->escopo_real,
                            ];
                        @endphp

                        @foreach ($evidencias as $label => $arquivos)
                            <div class="ea-evi-card">
                                <div class="ea-evi-label">{{ $label }}</div>

                                @php
                                    $arquivos = is_array($arquivos) ? array_filter($arquivos) : (is_string($arquivos) ? [$arquivos] : []);
                                @endphp

                                @if (count($arquivos) > 0)
                                    <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 12px; margin-top: 12px;">
                                            @foreach ($arquivos as $path)
                                            @php
                                                $url = Storage::disk((string) config('filesystems.media_disk', 'r2'))->url($path);
                                                $extensao = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                                $isImage = in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                            @endphp

                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                                @if ($isImage)
                                                    <a href="{{ $url }}" target="_blank" title="Clique para ampliar">
                                                        <img class="ea-evi-img" src="{{ $url }}" alt="{{ $label }}"
                                                             style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; display: block; transition: opacity 0.2s;"
                                                             onmouseover="this.style.opacity=0.8"
                                                             onmouseout="this.style.opacity=1">
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="ea-evi-empty">Sem arquivo</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="ea-table-wrap">
                <table class="ea-table">
                    <thead>
                        <tr>
                            <th style="width:64px">Item</th>
                            <th style="width:460px;text-align:left">Descrição do serviço</th>
                            <th style="width:90px">QT.</th>
                            <th style="width:90px">UND.</th>
                            <th style="width:140px">R$ MAT. (UNIT.)</th>
                            <th style="width:140px">R$ M.O. (UNIT.)</th>
                            <th style="width:160px">TOTAL UNIT (MAT + M.O.)</th>
                            <th style="width:170px">R$ TOTAL GERAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($record->itens as $item)
                            <tr>
                                <td class="ea-center">{{ $item->item }}</td>
                                <td>{{ $item->descricao_servico }}</td>
                                <td class="ea-right">{{ number_format((float) $item->quantidade, 2, ',', '.') }}</td>
                                <td class="ea-center">{{ $item->unidade }}</td>
                                <td class="ea-right">R$ {{ number_format((float) $item->valor_material_unitario, 2, ',', '.') }}</td>
                                <td class="ea-right">R$ {{ number_format((float) $item->valor_mao_obra_unitario, 2, ',', '.') }}</td>
                                <td class="ea-right">R$ {{ number_format((float) $item->total_unitario, 2, ',', '.') }}</td>
                                <td class="ea-right">R$ {{ number_format((float) $item->valor_total_geral, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="ea-center" style="padding:24px;color:#64748b">Nenhum item encontrado.</td></tr>
                        @endforelse
                        <tr class="ea-total-row">
                            <td colspan="6"></td>
                            <td class="ea-total-label">TOTAL GERAL</td>
                            <td class="ea-total-value">R$ {{ number_format((float) $totalGeral, 2, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
