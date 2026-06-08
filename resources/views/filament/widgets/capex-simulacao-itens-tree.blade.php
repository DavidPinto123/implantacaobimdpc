@php
    $formatarMoeda = fn (float $valor): string => 'R$ ' . number_format($valor, 2, ',', '.');
    $estadoInicialExpandidos = collect($idsGrupos)
        ->mapWithKeys(fn (int $id): array => [$id => true])
        ->toJson();
    $idsGruposJson = json_encode(array_values($idsGrupos));
@endphp

<x-filament-widgets::widget>
    <x-filament-actions::modals />

    <x-filament::section>
        <x-slot name="heading">Itens agrupados por Grupo OI</x-slot>
        <x-slot name="description">Edite o valor base ou o toggle "incluir" diretamente nas linhas — totais e percentuais recalculam automaticamente.</x-slot>

        @if ($arvore->isEmpty() && $semGrupo->isEmpty())
            <div class="flex flex-col gap-3">
                <div>
                    {{ $this->inserirEscopoManualAction }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Nenhum item cadastrado nesta simulação.
                </div>
            </div>
        @else
            <div
                x-data="{
                    expandidos: {{ $estadoInicialExpandidos }},
                    idsGrupos: {{ $idsGruposJson }},
                    expandirTudo() {
                        this.idsGrupos.forEach(id => this.expandidos[id] = true);
                    },
                    recolherTudo() {
                        this.idsGrupos.forEach(id => this.expandidos[id] = false);
                    },
                }"
                class="space-y-2"
            >
                <div class="flex items-center justify-between gap-2">
                    <div>
                        {{ $this->inserirEscopoManualAction }}
                    </div>
                    <div class="flex gap-2 text-xs">
                        <button
                            type="button"
                            x-on:click="expandirTudo()"
                            class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                            </svg>
                            Expandir tudo
                        </button>
                        <button
                            type="button"
                            x-on:click="recolherTudo()"
                            class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/>
                            </svg>
                            Recolher tudo
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                            <th class="px-3 py-2 w-1/2">Grupo / Item</th>
                            <th class="px-3 py-2 text-center">Tipo</th>
                            <th class="px-3 py-2 text-center">Incluir</th>
                            <th class="px-3 py-2 text-right">Valor Base</th>
                            <th class="px-3 py-2 text-right">Área (m²)</th>
                            <th class="px-3 py-2 text-right">Custo Estimado</th>
                            <th class="px-3 py-2 text-right">%</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($arvore as $no)
                            @include('filament.widgets.partials.capex-grupo-no', [
                                'no' => $no,
                                'nivel' => 0,
                                'totalGeral' => $totalGeral,
                                'formatarMoeda' => $formatarMoeda,
                                'ancestrais' => [],
                            ])
                        @endforeach

                        @if ($semGrupo->isNotEmpty())
                            <tr class="bg-amber-50 dark:bg-amber-900/20 font-semibold">
                                <td class="px-3 py-2 text-amber-800 dark:text-amber-200">
                                    Sem grupo
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="px-3 py-2 text-right text-amber-800 dark:text-amber-200">
                                    {{ $formatarMoeda($semGrupoTotal) }}
                                </td>
                                <td class="px-3 py-2 text-right text-amber-800 dark:text-amber-200">
                                    {{ $totalGeral > 0 ? number_format($semGrupoTotal / $totalGeral * 100, 2, ',', '.') : '0,00' }}%
                                </td>
                                <td></td>
                            </tr>
                            @foreach ($semGrupo as $item)
                                @php
                                    $valorFormatado = number_format((float) $item->valor_base_m2, 2, ',', '.');
                                    $editado = $item->tipo === 'auto' && $item->valor_base_m2_editado;
                                @endphp
                                <tr class="bg-white dark:bg-gray-900" wire:key="item-sem-grupo-{{ $item->id }}">
                                    <td class="px-3 py-2 pl-10 text-gray-700 dark:text-gray-300">
                                        <span @class(['text-gray-400 line-through' => ! $item->incluir])>
                                            {{ $item->nome_escopo ?? '—' }}
                                        </span>
                                        @if ($editado)
                                            <span class="ml-1 inline-flex items-center rounded bg-yellow-100 px-1.5 py-0.5 text-[10px] font-medium text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">
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
                                                wire:key="valor-base-sem-grupo-{{ $item->id }}-{{ $item->valor_base_m2 }}"
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
                        @endif

                        <tr class="bg-primary-50 dark:bg-primary-900/30 font-bold">
                            <td class="px-3 py-3 text-primary-900 dark:text-primary-200" colspan="5">
                                TOTAL GERAL
                            </td>
                            <td class="px-3 py-3 text-right text-primary-900 dark:text-primary-200">
                                {{ $formatarMoeda($totalGeral) }}
                            </td>
                            <td class="px-3 py-3 text-right text-primary-900 dark:text-primary-200">
                                100,00%
                            </td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
