<x-filament::page>

    <x-filament::card class="mb-6">
        {{ $this->form }}
    </x-filament::card>

    {{-- 🔹 DISCIPLINAS --}}
    <x-filament::card>

        {{-- 🔹 HEADER SUPERIOR --}}
        <div class="flex items-center justify-between mb-6">

            <div>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Disciplinas
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Simulação detalhada de CAPEX
                </p>
            </div>

            <div class="flex gap-2">

                <x-filament::button wire:click="gerarOi" color="primary">
                    Gerar OI
                </x-filament::button>

                <x-filament::button wire:click="exportarPdf" color="gray">
                    Exportar PDF
                </x-filament::button>

                @if(!$modoEdicao)
                <x-filament::button wire:click="habilitarEdicao">
                    Editar
                </x-filament::button>
                @else

                {{ $this->inserirGrupoAction }}

                <x-filament::button wire:click="salvar" color="success">
                    Salvar
                </x-filament::button>

                <x-filament::button wire:click="cancelarEdicao" color="gray">
                    Cancelar
                </x-filament::button>

                @endif

            </div>
        </div>

        {{-- 🔹 TABELA --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">

            <table class="w-full text-sm">

                {{-- CABEÇALHO --}}
                <thead>
                    <tr class="bg-gray-200 dark:bg-gray-800 text-xs uppercase tracking-wider text-gray-800 dark:text-white font-semibold">
                        <th class="text-left py-4 px-4 w-1/4">Disciplinas</th>
                        <th class="text-right py-4 px-4 w-1/8">R$ (Padrão)</th>
                        <th class="text-right py-4 px-4 w-1/8">R$ (AD.)</th>
                        <th class="text-right py-4 px-4 w-1/8">R$ (Total)</th>
                        <th class="text-right py-4 px-4 w-1/12">%</th>
                        <th class="text-right py-4 px-4 w-1/8">R$/m²</th>
                        <th class="text-left py-4 px-4 w-1/4">Considerações</th>
                    </tr>
                </thead>

                @php
                $grupos = collect($linhas)
                ->map(function ($linha, $index) {
                    $linha['_index'] = $index;
                    return $linha;
                })
                ->groupBy('grupo');
                @endphp

                @foreach($grupos as $nomeGrupo => $itens)

                @php
                $totalGrupo = $itens->sum(fn($l) => (float) ($l['padrao'] ?? 0) + (float) ($l['ad'] ?? 0));
                @endphp

                <tbody x-data="{ aberto: true }" class="bg-white dark:bg-gray-900">

                    {{-- LINHA DO GRUPO --}}
                    <tr class="bg-gray-100 dark:bg-gray-800 font-semibold text-gray-900 dark:text-white border-y">

                        <td class="py-3 px-4">

                            <div class="flex items-center justify-between">

                                {{-- Accordion --}}
                                <div class="flex items-center gap-2 cursor-pointer"
                                    @click="aberto = !aberto">

                                    <x-heroicon-m-chevron-right
                                        x-show="!aberto"
                                        class="w-4 h-4 transition-transform" />

                                    <x-heroicon-m-chevron-down
                                        x-show="aberto"
                                        class="w-4 h-4 transition-transform" />

                                    {{ $nomeGrupo }}
                                </div>

                                {{-- Botão Subitem --}}
                                @if($modoEdicao)
                                {{ ($this->inserirSubitemAction)(['grupo' => $nomeGrupo]) }}
                                @endif

                            </div>

                        </td>

                        <td></td>
                        <td></td>

                        <td class="text-right py-3 px-4">
                            R$ {{ number_format($totalGrupo, 2, ',', '.') }}
                        </td>

                        <td></td>
                        <td></td>
                        <td></td>

                    </tr>

                    {{-- SUBITENS --}}
                    @foreach($itens as $linha)

                    @php
                        $padrao = is_numeric($linha['padrao'] ?? null) ? (float)$linha['padrao'] : 0;
                        $ad = is_numeric($linha['ad'] ?? null) ? (float)$linha['ad'] : 0;

                        $totalLinha = $padrao + $ad;
                    @endphp

                    <tr x-show="aberto"
                        x-transition.duration.200ms
                        class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">

                        <td class="pl-10 py-3 px-4 text-gray-900 dark:text-white">
                            {{ $linha['nome'] }}
                        </td>

                        <td class="text-right py-3 px-4">
                            @if($modoEdicao)
                            <input type="number"
                                step="0.01"
                                wire:model.live="linhas.{{ $linha['_index'] }}.padrao"
                                class="w-24 text-right border rounded px-2 py-1 dark:bg-gray-800">
                            @else
                            R$ {{ number_format(is_numeric($linha['padrao'] ?? null) ? (float)$linha['padrao'] : 0, 2, ',', '.') }}
                            @endif
                        </td>

                        <td class="text-right py-3 px-4">
                            @if($modoEdicao)
                            <input type="number"
                                step="0.01"
                                wire:model.live="linhas.{{ $linha['_index'] }}.ad"
                                class="w-24 text-right border rounded px-2 py-1 dark:bg-gray-800">
                            @else
                            R$ {{ number_format(is_numeric($linha['ad'] ?? null) ? (float)$linha['ad'] : 0, 2, ',', '.') }}
                            @endif
                        </td>

                        <td class="text-right py-3 px-4 font-medium">
                            R$ {{ number_format($totalLinha, 2, ',', '.') }}
                        </td>

                        <td class="text-right py-3 px-4 text-gray-500">
                            {{ number_format(($totalLinha / max($this->totalGeral,1))*100, 2) }}%
                        </td>

                        <td class="text-right py-3 px-4 text-gray-500">
                            R$ {{ number_format($totalLinha / max($this->data['area_unidade'] ?? 1,1), 2, ',', '.') }}
                        </td>

                        <td class="py-3 px-4">
                            @if($modoEdicao)
                            <input type="text"
                                wire:model.live="linhas.{{ $linha['_index'] }}.consideracoes"
                                class="w-full border rounded px-2 py-1 dark:bg-gray-800">
                            @else
                            {{ $linha['consideracoes'] }}
                            @endif
                        </td>

                    </tr>

                    @endforeach

                </tbody>

                @endforeach

            </table>

        </div>

        {{-- TOTAL --}}
        <div class="mt-6 border-t pt-5 text-right">
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                Total Geral: R$ {{ number_format($this->totalGeral, 2, ',', '.') }}
            </div>

            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                R$/m² Geral:
                R$ {{ number_format($this->totalGeral / max($this->data['area_unidade'] ?? 1,1), 2, ',', '.') }}
            </div>
        </div>

    </x-filament::card>

</x-filament::page>