<x-filament::card>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Conflitos Detectados
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $totalConflitos }} conflito(s) em {{ $obrasComConflito }} obra(s).
                Escolha qual valor manter para cada campo divergente.
            </p>
        </div>
        <div class="flex gap-2">
            <x-filament::button
                wire:click="resolverTodosConflitos('manter')"
                color="gray"
                size="sm"
            >
                Manter Todos Atuais
            </x-filament::button>
            <x-filament::button
                wire:click="resolverTodosConflitos('planilha')"
                size="sm"
            >
                Usar Todos da Planilha
            </x-filament::button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b dark:border-gray-700">
                <tr>
                    <th class="pb-2 pr-4 w-28">Codigo</th>
                    <th class="pb-2 pr-4 w-48">Unidade</th>
                    <th class="pb-2 pr-4 w-40">Campo</th>
                    <th class="pb-2 pr-4 w-40">Valor Atual</th>
                    <th class="pb-2 pr-4 w-40">Valor Planilha</th>
                    <th class="pb-2 w-56 text-center">Acao</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($conflitos as $codigo => $dados)
                    @foreach ($dados['campos'] as $idx => $conflito)
                        <tr @class([
                            'text-gray-700 dark:text-gray-300',
                            'bg-amber-50/50 dark:bg-amber-900/5' => in_array($conflito['campo'], ['inauguracao', 'inicio', 'fim', 'entrada_ponto', 'inicio_real', 'inicio_imp', 'fim_imp']),
                            'bg-red-50/50 dark:bg-red-900/5' => $conflito['campo'] === 'status',
                        ])>
                            @if ($idx === 0)
                                <td class="py-2.5 pr-4 font-medium" rowspan="{{ count($dados['campos']) }}">
                                    {{ $codigo }}
                                </td>
                                <td class="py-2.5 pr-4 text-gray-500 dark:text-gray-400" rowspan="{{ count($dados['campos']) }}">
                                    {{ \Illuminate\Support\Str::limit($dados['unidade'], 30) }}
                                </td>
                            @endif
                            <td class="py-2.5 pr-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ str_replace('_', ' ', $conflito['campo']) }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-4 tabular-nums">
                                {{ \Illuminate\Support\Str::limit($conflito['valor_banco'], 25) }}
                            </td>
                            <td class="py-2.5 pr-4 tabular-nums font-medium">
                                {{ \Illuminate\Support\Str::limit($conflito['valor_planilha'], 25) }}
                            </td>
                            <td class="py-2.5 text-center">
                                <div class="inline-flex items-center gap-4 text-xs">
                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                        <input
                                            type="radio"
                                            wire:model.live="resolucoes.{{ $codigo }}.{{ $conflito['campo'] }}"
                                            value="manter"
                                            class="text-gray-500 focus:ring-gray-400"
                                        />
                                        <span class="text-gray-600 dark:text-gray-400">Manter</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                        <input
                                            type="radio"
                                            wire:model.live="resolucoes.{{ $codigo }}.{{ $conflito['campo'] }}"
                                            value="planilha"
                                            class="text-primary-500 focus:ring-primary-400"
                                        />
                                        <span class="text-primary-600 dark:text-primary-400">Planilha</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                        <input
                                            type="radio"
                                            wire:model.live="resolucoes.{{ $codigo }}.{{ $conflito['campo'] }}"
                                            value="ignorar"
                                            class="text-amber-500 focus:ring-amber-400"
                                        />
                                        <span class="text-amber-600 dark:text-amber-400">Ignorar</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex justify-between items-center mt-6 pt-4 border-t dark:border-gray-700">
        <x-filament::button
            wire:click="$set('currentStep', 5)"
            color="gray"
        >
            Voltar
        </x-filament::button>
        <x-filament::button wire:click="executarImportacao">
            Importar com Resolucoes
        </x-filament::button>
    </div>
</x-filament::card>
