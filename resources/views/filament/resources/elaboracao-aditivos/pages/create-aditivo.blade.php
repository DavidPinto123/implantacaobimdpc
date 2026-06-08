<x-filament-panels::page>
    <style>
        .fi-main .fi-page{max-width:none!important}
        .ea-shell{width:100%;max-width:100%;overflow-x:hidden}
        .ea-card{width:100%;max-width:100%;border:1px solid #d6d9df;border-radius:18px;background:#fff;box-shadow:0 8px 28px rgba(15,23,42,.06);overflow:hidden}
        .ea-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:20px 22px;border-bottom:1px solid #e8ebf0;background:#ffffff}
        .ea-title{margin:0;font-size:36px;line-height:1.1;font-weight:800;letter-spacing:-.02em;color:#0f172a}
        .ea-sub{margin-top:4px;font-size:14px;color:#475569}
        .ea-section{margin:16px;max-width:calc(100% - 32px);border:1px solid #d8dbe2;border-radius:14px;overflow:hidden}
        .ea-bar{padding:10px 14px;font-size:13px;font-weight:800;letter-spacing:.03em;text-transform:uppercase;background:#fef3c7;color:#92400e}
        .ea-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:14px;background:#f8fafc}
        .ea-field label{display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px}
        .ea-input{width:100%;height:42px;border:1px solid #c8ced8;border-radius:10px;background:#fff;color:#111827;font-size:14px;padding:0 12px;outline:none}
        .ea-input--tight{padding:0 10px}
        .ea-input--num{text-align:right;font-variant-numeric:tabular-nums}
        .ea-input:focus{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.18)}
        .ea-input[disabled], .ea-input[readonly]{background:#eef2f7;color:#667085}
        .ea-table-wrap{margin:0 16px 16px;max-width:calc(100% - 32px);border:1px solid #d8dbe2;border-radius:14px;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch;overscroll-behavior-x:contain}
        .ea-table{width:100%;min-width:1100px;border-collapse:collapse;background:#fff}
        .ea-table th{font-size:12px;text-transform:uppercase;letter-spacing:.03em;text-align:center;color:#334155;background:#f1f5f9;padding:12px 8px;border-bottom:1px solid #d9e0ea;white-space:nowrap}
        .ea-table td{padding:8px;border-bottom:1px solid #e8edf4;white-space:nowrap}
        .ea-table td .ea-input{height:38px}
        .ea-num{text-align:right;font-weight:700;color:#0f172a;padding-right:10px}
        .ea-del{border:0;background:transparent;color:#dc2626;font-weight:700;cursor:pointer}
        .ea-foot{padding:14px 16px;background:#f8fafc;border-top:1px solid #d8dbe2}
        .ea-add{display:flex;align-items:center;justify-content:center;width:min(520px,100%);height:34px;margin:0 auto;border:1px solid #e7c878;border-radius:999px;background:#fff8e6;color:#9a6700;font-weight:800;cursor:pointer}
        .ea-total-row{display:flex;justify-content:space-between;align-items:center;margin-top:12px}
        .ea-total-label{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#475569;font-weight:800}
        .ea-total-box{min-width:200px;background:#facc15;color:#111827;font-weight:900;font-size:22px;line-height:1;padding:8px 12px;border-radius:12px;text-align:right}
        .ea-err{font-size:12px;color:#dc2626;margin-top:4px}
        @media (max-width: 1024px){.ea-grid{grid-template-columns:1fr}.ea-title{font-size:30px}}
        @media (max-width: 768px){
            .ea-head{padding:14px;flex-direction:column;align-items:stretch}
            .ea-title{font-size:26px}
            .ea-section{margin:10px}
            .ea-grid{padding:10px}
            .ea-table-wrap{margin:0 10px 10px;max-width:calc(100% - 20px)}
            .ea-table{min-width:760px}
            .ea-table th,.ea-table td{padding:8px 6px}
            .ea-table th{font-size:11px}
            .ea-input{height:40px;font-size:13px}
            .ea-foot{padding:10px}
            .ea-total-row{flex-direction:column;align-items:stretch;gap:8px}
            .ea-total-box{min-width:0;width:100%;font-size:20px;text-align:center}
        }
        html.dark .ea-card{background:var(--gray-900);border-color:#6b7280;box-shadow:0 10px 30px rgba(0,0,0,.35)}
        html.dark .ea-head{background:var(--gray-900);border-color:#6b7280}
        html.dark .ea-title{color:#f8fafc}
        html.dark .ea-sub{color:#e5e7eb}
        html.dark .ea-section,html.dark .ea-table-wrap{border-color:#9ca3af}
        html.dark .ea-bar{background:#3f3f46;color:#fef3c7}
        html.dark .ea-grid,html.dark .ea-foot{background:var(--gray-900)}
        html.dark .ea-field label{color:#cbd5e1}
        html.dark .ea-input{background:var(--gray-900);border-color:#9ca3af;color:#f8fafc}
        html.dark .ea-input[disabled],html.dark .ea-input[readonly]{background:var(--gray-800);color:#e5e7eb}
        html.dark .ea-table{background:var(--gray-900)}
        html.dark .ea-table th{background:var(--gray-800);color:#e5e7eb;border-color:#6b7280}
        html.dark .ea-table td{border-color:#6b7280}
        html.dark .ea-num{color:#f8fafc}
        html.dark .ea-add{background:#52525b;border-color:#a1a1aa;color:#f3f4f6}
        html.dark .ea-total-label{color:#e5e7eb}

    </style>

    <div
        class="ea-shell"
        x-data="{
            normalizePtBrNumber(value) {
                if (value === null || value === undefined) return ''
                value = String(value).trim()
                if (value === '') return ''
                value = value.replace(/\\s+/g, '')

                // If there is a comma, treat it as decimal separator and remove dot thousand separators.
                if (value.includes(',')) {
                    value = value.replace(/\\./g, '').replace(',', '.')
                } else {
                    // Otherwise, allow dot decimals and strip comma thousand separators.
                    value = value.replace(/,/g, '')
                }

                value = value.replace(/[^0-9.]/g, '')
                return value
            },
        }"
    >
        <div class="ea-card">
            <div class="ea-head">
                <div>
                    <h1 class="ea-title">ELABORACAO DE ADITIVOS DE OBRA</h1>
                    <div class="ea-sub">Preencha os dados na planilha abaixo.</div>
                </div>
                <x-filament::button color="warning" wire:click="save">Salvar aditivo</x-filament::button>
            </div>

            <div class="ea-section">
                {{ $this->form }}
            </div>

            <div class="ea-table-wrap">
                <table class="ea-table">
                    <thead>
                        <tr>
                            <th style="width:64px">Item</th>
                            <th style="width:370px;text-align:left">Descrição do serviço</th>
                            <th style="width:110px">QT.</th>
                            <th style="width:110px">UND.</th>
                            <th style="width:140px">R$ MAT. UNIT.</th>
                            <th style="width:140px">R$ M.O. UNIT.</th>
                            <th style="width:130px">TOTAL UNIT</th>
                            <th style="width:150px">R$ TOTAL GERAL</th>
                            <th style="width:90px">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($itens as $index => $item)
                            <tr>
                                <td><input wire:model.live="itens.{{ $index }}.item" class="ea-input"></td>
                                <td><input wire:model.live="itens.{{ $index }}.descricao_servico" class="ea-input" placeholder="Descrição do serviço"></td>
                                <td>
                                    <input
                                        inputmode="decimal"
                                        min="0"
                                        wire:model.blur="itens.{{ $index }}.quantidade"
                                        x-on:input="$event.target.value = $event.target.value.replace(/-/g, '')"
                                        x-on:blur="const v = normalizePtBrNumber($event.target.value); $event.target.value = v; $wire.set('itens.{{ $index }}.quantidade', v)"
                                        class="ea-input ea-input--tight ea-input--num"
                                        placeholder="0"
                                    >
                                </td>
                                <td><input wire:model.blur="itens.{{ $index }}.unidade" class="ea-input ea-input--tight"></td>
                                <td>
                                    <input
                                        inputmode="decimal"
                                        min="0"
                                        wire:model.blur="itens.{{ $index }}.valor_material_unitario"
                                        x-on:input="$event.target.value = $event.target.value.replace(/-/g, '')"
                                        x-on:blur="const v = normalizePtBrNumber($event.target.value); $event.target.value = v; $wire.set('itens.{{ $index }}.valor_material_unitario', v)"
                                        class="ea-input ea-input--num"
                                        placeholder="0,00"
                                    >
                                </td>
                                <td>
                                    <input
                                        inputmode="decimal"
                                        min="0"
                                        wire:model.blur="itens.{{ $index }}.valor_mao_obra_unitario"
                                        x-on:input="$event.target.value = $event.target.value.replace(/-/g, '')"
                                        x-on:blur="const v = normalizePtBrNumber($event.target.value); $event.target.value = v; $wire.set('itens.{{ $index }}.valor_mao_obra_unitario', v)"
                                        class="ea-input ea-input--num"
                                        placeholder="0,00"
                                    >
                                </td>
                                <td class="ea-num">{{ number_format((float) ($item['total_unitario'] ?? 0), 2, ',', '.') }}</td>
                                <td class="ea-num">{{ number_format((float) ($item['valor_total_geral'] ?? 0), 2, ',', '.') }}</td>
                                <td style="text-align:center">
                                    <button type="button" wire:click="removeLinha({{ $index }})" class="ea-del">Excluir</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="padding:24px;text-align:center;color:#64748b">Nenhum item informado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="ea-foot">
                    <button type="button" wire:click="addLinha" class="ea-add">+ Adicionar linha</button>
                    <div class="ea-total-row">
                        <div class="ea-total-label">Total geral</div>
                        <div class="ea-total-box">R$ {{ number_format($this->totalGeral, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
