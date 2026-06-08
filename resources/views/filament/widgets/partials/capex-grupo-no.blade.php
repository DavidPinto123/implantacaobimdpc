@php
    /** @var array $no */
    /** @var int $nivel */
    /** @var float $totalGeral */
    /** @var \Closure $formatarMoeda */
    /** @var array<int> $ancestrais */

    $grupo = $no['grupo'];
    $filhos = $no['filhos'];
    $itens = $no['itens'];
    $total = (float) $no['total'];
    $ancestrais ??= [];

    $estilosLinha = [
        0 => 'bg-primary-100 dark:bg-primary-900/40 text-primary-900 dark:text-primary-100 text-base',
        1 => 'bg-primary-50 dark:bg-primary-900/20 text-primary-800 dark:text-primary-200',
        2 => 'bg-gray-100 dark:bg-gray-800/60 text-gray-800 dark:text-gray-200',
    ];
    $classeLinha = $estilosLinha[$nivel] ?? 'bg-gray-50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300';

    $indent = $nivel * 1.25;
    $percentual = $totalGeral > 0 ? ($total / $totalGeral * 100) : 0;

    $ancestraisJs = '[' . implode(',', $ancestrais) . ']';
    $proximoAncestrais = array_merge($ancestrais, [$grupo->id]);
    $proximoAncestraisJs = '[' . implode(',', $proximoAncestrais) . ']';
@endphp

<tr
    class="{{ $classeLinha }} font-semibold"
    x-show="{{ $ancestraisJs }}.every(id => expandidos[id])"
    x-cloak
>
    <td class="px-3 py-2" style="padding-left: {{ $indent + 0.25 }}rem;">
        <div class="inline-flex items-center gap-2">
            <button
                type="button"
                x-on:click="expandidos[{{ $grupo->id }}] = ! expandidos[{{ $grupo->id }}]"
                class="inline-flex h-5 w-5 items-center justify-center rounded hover:bg-black/5 dark:hover:bg-white/10"
                :title="expandidos[{{ $grupo->id }}] ? 'Recolher' : 'Expandir'"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    class="h-3.5 w-3.5 transition-transform"
                    :class="expandidos[{{ $grupo->id }}] ? 'rotate-90' : ''"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </button>
            
            <span>{{ $grupo->nome }}</span>
        </div>
    </td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td class="px-3 py-2 text-right">
        {{ $formatarMoeda($total) }}
    </td>
    <td class="px-3 py-2 text-right">
        {{ number_format($percentual, 2, ',', '.') }}%
    </td>
    <td></td>
</tr>

@foreach ($itens as $item)
    @php
        $valorFormatado = number_format((float) $item->valor_base_m2, 2, ',', '.');
        $editado = $item->tipo === 'auto' && $item->valor_base_m2_editado;
    @endphp
    <tr
        class="bg-white dark:bg-gray-900"
        wire:key="item-{{ $item->id }}"
        x-show="{{ $proximoAncestraisJs }}.every(id => expandidos[id])"
        x-cloak
    >
        <td class="px-3 py-2 text-gray-700 dark:text-gray-300" style="padding-left: {{ $indent + 2.25 }}rem;">
            <span @class(['text-gray-400 line-through' => ! $item->incluir])>
                {{ $item->nome_escopo ?? $item->escopo?->escopo ?? '—' }}
            </span>
            @if (filled($item->numero_complemento))
                <span class="ml-1 inline-flex items-center rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                    {{ $item->numero_complemento }}
                </span>
            @endif
            @if ($editado)
                <span class="ml-1 inline-flex items-center rounded bg-yellow-100 px-1.5 py-0.5 text-[10px] font-medium text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300" title="Valor editado manualmente">
                    editado
                </span>
            @endif
        </td>
        <td class="px-3 py-2 text-center">
            @if ($item->tipo === 'auto')
                <span class="inline-flex items-center rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-medium uppercase text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                    auto
                </span>
            @else
                <span class="inline-flex items-center rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-medium uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    manual
                </span>
            @endif
        </td>
        <td class="px-3 py-2 text-center">
            <input
                type="checkbox"
                @checked($item->incluir)
                wire:click="alternarIncluir({{ $item->id }})"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 cursor-pointer"
            />
        </td>
        <td class="px-3 py-2 text-right">
            <div class="flex items-center justify-end gap-1">
                <span class="text-gray-500 text-xs">R$</span>
                <input
                    type="text"
                    inputmode="decimal"
                    value="{{ $valorFormatado }}"
                    wire:key="valor-base-{{ $item->id }}-{{ $item->valor_base_m2 }}"
                    wire:change="atualizarValorBase({{ $item->id }}, $event.target.value)"
                    x-data
                    x-on:input="
                        const digits = String($el.value ?? '').replace(/\D/g, '');
                        if (digits === '') { $el.value = ''; return; }
                        const cents = digits.slice(-2).padStart(2, '0');
                        const integer = digits.slice(0, -2).replace(/^0+(?=\d)/, '');
                        const formattedInteger = (integer === '' ? '0' : integer).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        $el.value = formattedInteger + ',' + cents;
                    "
                    x-on:keydown.enter.prevent.stop="$el.blur()"
                    class="w-28 rounded border-gray-300 px-2 py-1 text-right text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                />
            </div>
        </td>
        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
            @if ($item->tipo === 'manual' || blank($item->area))
                <span class="text-gray-400">N/A</span>
            @else
                {{ number_format((float) $item->area, 2, ',', '.') }}
            @endif
        </td>
        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
            {{ $formatarMoeda((float) $item->custo_estimado) }}
        </td>
        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300">
            {{ number_format((float) $item->percentual, 2, ',', '.') }}%
        </td>
        <td class="px-3 py-2 text-right">
            <div class="inline-flex items-center gap-1">
                @if ($item->tipo === 'auto')
                    <button
                        type="button"
                        wire:click="mountAction('converterParaManual', { itemId: {{ $item->id }} })"
                        title="Converter para manual"
                        class="inline-flex h-6 w-6 items-center justify-center rounded text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 1 0-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 0 1 3.15 0v1.5m-3.15 0 .075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 0 1 3.15 0V15M6.9 7.575a1.575 1.575 0 1 0-3.15 0v8.175a6.75 6.75 0 0 0 6.75 6.75h2.018a5.25 5.25 0 0 0 3.712-1.538l1.732-1.732a5.25 5.25 0 0 0 1.538-3.712l.003-2.024a.668.668 0 0 1 .198-.471 1.575 1.575 0 1 0-2.228-2.228 3.818 3.818 0 0 0-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0 1 16.35 15m.002 0h-.002"/>
                        </svg>
                    </button>
                @endif
                @if ($editado)
                    <button
                        type="button"
                        wire:click="restaurarValorOriginal({{ $item->id }})"
                        title="Restaurar valor original"
                        class="inline-flex h-6 w-6 items-center justify-center rounded text-yellow-700 hover:bg-yellow-100 dark:text-yellow-300 dark:hover:bg-yellow-900/40"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </button>
                @endif
            </div>
        </td>
    </tr>
@endforeach

@foreach ($filhos as $filho)
    @include('filament.widgets.partials.capex-grupo-no', [
        'no' => $filho,
        'nivel' => $nivel + 1,
        'totalGeral' => $totalGeral,
        'formatarMoeda' => $formatarMoeda,
        'ancestrais' => $proximoAncestrais,
    ])
@endforeach
