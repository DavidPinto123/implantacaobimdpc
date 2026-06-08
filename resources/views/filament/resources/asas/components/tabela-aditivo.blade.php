@php
    if (! $record) {
        return;
    }

    $record->loadMissing([
        'projeto',
        'gestor',
        'elaboracaoAditivo.obra',
        'elaboracaoAditivo.construtora',
        'elaboracaoAditivo.asEscopo',
        'elaboracaoAditivo.itens',
        'itens',
    ]);

    $aditivo = $record->elaboracaoAditivo;
    $itensAditivo = $aditivo?->itens ?? collect();
    $usaItensAditivo = $itensAditivo->isNotEmpty();

    $itens = $usaItensAditivo ? $itensAditivo : ($record->itens ?? collect());

    $totalGeral = $usaItensAditivo
        ? (float) $itensAditivo->sum('valor_total_geral')
        : (float) ($record->itens?->sum('valor_total') ?? 0);

    $obra = $aditivo?->obra?->unidade ?? $record->projeto?->nome ?? '-';
    $gestor = $record->gestor?->name ?? $aditivo?->gestor?->name ?? '-';
    $data = optional($record->data_solicitacao)?->format('d/m/Y') ?? '-';
    $refServico = $aditivo?->asEscopo?->escopo ?? $record->descricao ?? '-';
    $gerenciadora = $aditivo?->construtora?->nome ?? $record->solicitante ?? '-';
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="mb-4 space-y-1 text-sm">
        <div><strong>EXPANSÃO / ORÇAMENTOS</strong></div>
        <div><strong>OBRA:</strong> {{ $obra }}</div>
        <div><strong>GESTOR:</strong> {{ $gestor }}</div>
        <div><strong>DATA:</strong> {{ $data }}</div>
        <div><strong>REF. SERVICO:</strong> {{ $refServico }}</div>
        <div><strong>FORNECEDOR:</strong> {{ $gerenciadora }}</div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="bg-gray-100 text-center dark:bg-gray-800">
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">Item</th>
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">Descrição do serviço</th>
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">QT.</th>
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">UND.</th>
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">R$ MAT. (UNIT.)</th>
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">R$ M.O. (UNIT.)</th>
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">TOTAL UNIT</th>
                    <th class="border border-gray-300 px-2 py-2 dark:border-gray-700">R$ TOTAL GERAL</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($itens as $item)
                    <tr>
                        <td class="border border-gray-300 px-2 py-2 text-center dark:border-gray-700">
                            {{ $item->item ?? '-' }}
                        </td>
                        <td class="border border-gray-300 px-2 py-2 dark:border-gray-700">
                            {{ $usaItensAditivo ? ($item->descricao_servico ?? '-') : ($item->descricao ?? '-') }}
                        </td>
                        <td class="border border-gray-300 px-2 py-2 text-right dark:border-gray-700">
                            {{ number_format((float) ($item->quantidade ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="border border-gray-300 px-2 py-2 text-center dark:border-gray-700">
                            {{ $item->unidade ?? '-' }}
                        </td>
                        <td class="border border-gray-300 px-2 py-2 text-right dark:border-gray-700">
                            @if ($usaItensAditivo)
                                R$ {{ number_format((float) ($item->valor_material_unitario ?? 0), 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="border border-gray-300 px-2 py-2 text-right dark:border-gray-700">
                            @if ($usaItensAditivo)
                                R$ {{ number_format((float) ($item->valor_mao_obra_unitario ?? 0), 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="border border-gray-300 px-2 py-2 text-right dark:border-gray-700">
                            @if ($usaItensAditivo)
                                R$ {{ number_format((float) ($item->total_unitario ?? 0), 2, ',', '.') }}
                            @else
                                R$ {{ number_format((float) ($item->valor_unitario ?? 0), 2, ',', '.') }}
                            @endif
                        </td>
                        <td class="border border-gray-300 px-2 py-2 text-right dark:border-gray-700">
                            @if ($usaItensAditivo)
                                R$ {{ number_format((float) ($item->valor_total_geral ?? 0), 2, ',', '.') }}
                            @else
                                R$ {{ number_format((float) ($item->valor_total ?? 0), 2, ',', '.') }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border border-gray-300 px-2 py-4 text-center dark:border-gray-700">
                            Nenhum item encontrado.
                        </td>
                    </tr>
                @endforelse

                <tr class="bg-gray-100 font-semibold dark:bg-gray-800">
                    <td colspan="7" class="border border-gray-300 px-2 py-2 dark:border-gray-700">
                        TOTAL GERAL
                    </td>
                    <td class="border border-gray-300 px-2 py-2 text-right dark:border-gray-700">
                        R$ {{ number_format((float) $totalGeral, 2, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
