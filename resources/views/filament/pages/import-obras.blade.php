<x-filament::page>

    {{-- Steps Bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => 'Upload',
                    2 => 'Aba',
                    3 => 'Colunas',
                    4 => 'Valores',
                    5 => 'Validacao',
                    55 => 'Conflitos',
                    6 => 'Resultado',
                ];
                $stepOrder = array_keys($steps);
            @endphp
            @php
                $currentIdx = array_search($currentStep, $stepOrder);
                if ($currentIdx === false) $currentIdx = -1;
            @endphp
            @foreach ($steps as $num => $label)
                @php $thisIdx = array_search($num, $stepOrder); @endphp
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center">
                        <div @class([
                            'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold transition-colors',
                            'bg-primary-600 text-white' => $currentIdx >= $thisIdx,
                            'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => $currentIdx < $thisIdx,
                        ])>
                            @if ($currentIdx > $thisIdx)
                                <x-heroicon-s-check class="w-4 h-4" />
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </div>
                        <span @class([
                            'mt-1.5 text-[11px] font-medium whitespace-nowrap',
                            'text-primary-600 dark:text-primary-400' => $currentIdx >= $thisIdx,
                            'text-gray-400 dark:text-gray-500' => $currentIdx < $thisIdx,
                        ])>
                            {{ $label }}
                        </span>
                    </div>
                    @if (!$loop->last)
                        <div @class([
                            'flex-1 h-0.5 mx-2 mt-[-1rem]',
                            'bg-primary-600' => $currentIdx > $thisIdx,
                            'bg-gray-200 dark:bg-gray-700' => $currentIdx <= $thisIdx,
                        ])></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step 1 — Upload --}}
    @if ($currentStep === 1)
        <x-filament::card>
            <div class="text-center py-8">
                <div class="mx-auto w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-arrow-up-tray class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Importar Planilha de Obras
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Selecione o tipo de planilha e o arquivo para iniciar a importacao.
                </p>

                <div class="flex justify-center gap-4 mb-6 max-w-md mx-auto">
                    <label @class([
                        'flex-1 cursor-pointer rounded-lg border-2 p-4 text-center transition-colors',
                        'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $tipoPlanilha === 'engenharia',
                        'border-gray-200 dark:border-gray-700 hover:border-gray-300' => $tipoPlanilha !== 'engenharia',
                    ])>
                        <input type="radio" wire:model.live="tipoPlanilha" value="engenharia" class="hidden" />
                        <x-heroicon-o-wrench-screwdriver @class([
                            'w-6 h-6 mx-auto mb-1',
                            'text-primary-600 dark:text-primary-400' => $tipoPlanilha === 'engenharia',
                            'text-gray-400' => $tipoPlanilha !== 'engenharia',
                        ]) />
                        <p @class([
                            'text-sm font-medium',
                            'text-primary-700 dark:text-primary-300' => $tipoPlanilha === 'engenharia',
                            'text-gray-600 dark:text-gray-400' => $tipoPlanilha !== 'engenharia',
                        ])>Engenharia</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Execucao de obras</p>
                    </label>
                    <label @class([
                        'flex-1 cursor-pointer rounded-lg border-2 p-4 text-center transition-colors',
                        'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $tipoPlanilha === 'planejamento_estrategico',
                        'border-gray-200 dark:border-gray-700 hover:border-gray-300' => $tipoPlanilha !== 'planejamento_estrategico',
                    ])>
                        <input type="radio" wire:model.live="tipoPlanilha" value="planejamento_estrategico" class="hidden" />
                        <x-heroicon-o-clipboard-document-list @class([
                            'w-6 h-6 mx-auto mb-1',
                            'text-primary-600 dark:text-primary-400' => $tipoPlanilha === 'planejamento_estrategico',
                            'text-gray-400' => $tipoPlanilha !== 'planejamento_estrategico',
                        ]) />
                        <p @class([
                            'text-sm font-medium',
                            'text-primary-700 dark:text-primary-300' => $tipoPlanilha === 'planejamento_estrategico',
                            'text-gray-600 dark:text-gray-400' => $tipoPlanilha !== 'planejamento_estrategico',
                        ])>Planej. Estrategico</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">Pipeline completo</p>
                    </label>
                </div>

                <div class="max-w-md mx-auto">
                    <label class="block w-full cursor-pointer border-2 border-dashed rounded-lg p-8 transition-colors
                        border-gray-300 hover:border-primary-500 dark:border-gray-600 dark:hover:border-primary-500">
                        <input
                            type="file"
                            wire:model="arquivo"
                            accept=".xlsx,.xls,.csv"
                            class="hidden"
                        />
                        <div wire:loading.remove wire:target="arquivo">
                            @if ($arquivoNome)
                                <x-heroicon-o-document-check class="w-10 h-10 mx-auto text-success-500 mb-2" />
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $arquivoNome }}</p>
                                <p class="text-xs text-gray-400 mt-1">Clique para trocar o arquivo</p>
                            @else
                                <x-heroicon-o-cloud-arrow-up class="w-10 h-10 mx-auto text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Arraste o arquivo aqui ou <span class="text-primary-600 font-medium">clique para selecionar</span>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">XLSX, XLS ou CSV (max. 10MB)</p>
                            @endif
                        </div>
                        <div wire:loading wire:target="arquivo" class="text-center">
                            <x-filament::loading-indicator class="w-8 h-8 mx-auto text-primary-500" />
                            <p class="text-sm text-gray-500 mt-2">Carregando arquivo...</p>
                        </div>
                    </label>
                </div>

                @error('arquivo')
                    <p class="text-sm text-danger-600 mt-3">{{ $message }}</p>
                @enderror

                <div class="mt-6">
                    <x-filament::button
                        wire:click="avancarParaAbas"
                        :disabled="!$arquivoPath"
                    >
                        Proximo
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>

        {{-- Importações Anteriores --}}
        @if (count($importacoesAnteriores) > 0)
            <x-filament::card class="mt-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Importações Anteriores
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase border-b dark:border-gray-700">
                            <tr>
                                <th class="pb-2 pr-4">Arquivo</th>
                                <th class="pb-2 pr-4">Status</th>
                                <th class="pb-2 pr-4 text-center">Total</th>
                                <th class="pb-2 pr-4 text-center">Criados</th>
                                <th class="pb-2 pr-4 text-center">Atualizados</th>
                                <th class="pb-2 pr-4 text-center">Erros</th>
                                <th class="pb-2 pr-4">Usuário</th>
                                <th class="pb-2">Data</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($importacoesAnteriores as $imp)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="py-2.5 pr-4 max-w-[200px] truncate" title="{{ $imp['arquivo'] }}">
                                        {{ $imp['arquivo'] }}
                                    </td>
                                    <td class="py-2.5 pr-4">
                                        @php
                                            $statusConfig = match($imp['status']) {
                                                'concluido' => ['label' => 'Concluido', 'class' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-green-600/10 dark:ring-green-500/20'],
                                                'staged' => ['label' => 'Aguardando', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 ring-1 ring-blue-600/10 dark:ring-blue-500/20'],
                                                'confirmando' => ['label' => 'Confirmando', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-500/20'],
                                                'processando' => ['label' => 'Processando', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-500/20'],
                                                'pendente' => ['label' => 'Pendente', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                                                'descartado' => ['label' => 'Descartado', 'class' => 'bg-gray-100 text-gray-500 dark:bg-gray-500/10 dark:text-gray-500 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                                                'erro' => ['label' => 'Erro', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-red-600/10 dark:ring-red-500/20'],
                                                default => ['label' => ucfirst($imp['status']), 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $statusConfig['class'] }}">
                                            {{ $statusConfig['label'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums">{{ $imp['total'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums text-success-600 dark:text-success-400">{{ $imp['criados'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums text-primary-600 dark:text-primary-400">{{ $imp['atualizados'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums {{ ($imp['erros'] ?? 0) > 0 ? 'text-danger-600 dark:text-danger-400 font-medium' : '' }}">{{ $imp['erros'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4">{{ $imp['usuario'] }}</td>
                                    <td class="py-2.5 text-gray-500 dark:text-gray-400">
                                        {{ $imp['data'] }}
                                        @if ($imp['duracao'])
                                            <span class="text-xs text-gray-400 dark:text-gray-500">({{ $imp['duracao'] }})</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        @endif
    @endif

    {{-- Step 2 — Selecao de Aba --}}
    @if ($currentStep === 2)
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                Selecione a Aba
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                O arquivo <strong>{{ $arquivoNome }}</strong> possui {{ count($abas) }} aba(s).
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-w-2xl">
                @foreach ($abas as $aba)
                    <label @class([
                        'flex items-center gap-3 p-4 rounded-lg border-2 cursor-pointer transition-colors',
                        'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $abaSelecionada === $aba,
                        'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' => $abaSelecionada !== $aba,
                    ])>
                        <input
                            type="radio"
                            wire:model="abaSelecionada"
                            value="{{ $aba }}"
                            class="text-primary-600"
                        />
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $aba }}</p>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex gap-3 mt-6">
                <x-filament::button color="gray" wire:click="voltarStep">
                    Voltar
                </x-filament::button>
                <x-filament::button wire:click="avancarParaMapeamento">
                    Proximo
                </x-filament::button>
            </div>
        </x-filament::card>
    @endif

    {{-- Step 3 — Mapeamento de Colunas --}}
    @if ($currentStep === 3)
        <x-filament::card>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Mapeamento de Colunas
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Associe cada coluna da planilha ao campo correspondente no sistema.
                    </p>
                </div>

                {{-- Templates --}}
                <div class="flex items-center gap-2 flex-wrap">
                    @if (count($templates) > 0)
                        <select
                            wire:change="carregarTemplate($event.target.value)"
                            class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        >
                            <option value="">Carregar template...</option>
                            @foreach ($templates as $tpl)
                                <option value="{{ $tpl['id'] }}">{{ $tpl['nome'] }}</option>
                            @endforeach
                        </select>
                    @endif

                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            wire:model="nomeTemplate"
                            placeholder="Nome do template"
                            class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-40"
                        />
                        <x-filament::button size="sm" color="gray" wire:click="salvarTemplate">
                            Salvar
                        </x-filament::button>
                    </div>
                </div>
            </div>

            @php
                $unmappedHeaders = collect($headers)->filter(fn($h) => empty($mapping[$h] ?? '') || ($mapping[$h] ?? '') === '');
                $mappedHeaders = collect($headers)->filter(fn($h) => !empty($mapping[$h] ?? '') && ($mapping[$h] ?? '') !== '');
            @endphp

            @if ($unmappedHeaders->count() > 0)
                <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-700">
                    <div class="flex items-start gap-2">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" />
                        <div>
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
                                {{ $unmappedHeaders->count() }} {{ $unmappedHeaders->count() === 1 ? 'coluna sem' : 'colunas sem' }} equivalente no banco de dados
                            </p>
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                {{ $mappedHeaders->count() }} {{ $mappedHeaders->count() === 1 ? 'coluna mapeada' : 'colunas mapeadas' }} automaticamente.
                                As colunas sem mapeamento serao ignoradas na importacao, a menos que voce selecione um campo manualmente.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @php
                $opcoesJson = collect($camposDisponiveis)->map(fn ($campo) => [
                    'value' => $campo,
                    'label' => $fieldLabels[$campo] ?? $campo,
                ])->values()->toArray();
                $mappingJson = $mapping;
            @endphp
            <div
                x-data="{
                    activeHeader: null,
                    search: '',
                    dropStyle: {},
                    opcoes: @js($opcoesJson),
                    localMapping: @js($mappingJson),
                    get usados() {
                        return Object.values(this.localMapping).filter(v => v && v !== '__calculado__');
                    },
                    getLabel(value) {
                        if (!value) return '';
                        const opt = this.opcoes.find(o => o.value === value);
                        return opt ? opt.label : value;
                    },
                    get filtered() {
                        const term = this.search.toLowerCase();
                        if (!term) return this.opcoes;
                        return this.opcoes.filter(o =>
                            o.label.toLowerCase().includes(term) ||
                            o.value.toLowerCase().includes(term)
                        );
                    },
                    isUsed(value) {
                        const current = this.activeHeader ? (this.localMapping[this.activeHeader] || '') : '';
                        return value !== current && this.usados.includes(value);
                    },
                    select(value) {
                        if (!this.activeHeader) return;
                        this.localMapping[this.activeHeader] = value;
                        $wire.call('updateMapping', this.activeHeader, value);
                        this.close();
                    },
                    clear(header = null) {
                        const h = header || this.activeHeader;
                        if (!h) return;
                        this.localMapping[h] = '';
                        $wire.call('updateMapping', h, '');
                        this.close();
                    },
                    close() {
                        this.activeHeader = null;
                        this.search = '';
                    },
                    toggle(header, event) {
                        if (this.activeHeader === header) { this.close(); return; }
                        this.activeHeader = header;
                        this.search = '';
                        const rect = event.currentTarget.getBoundingClientRect();
                        const spaceBelow = window.innerHeight - rect.bottom;
                        const openUp = spaceBelow < 280;
                        this.dropStyle = {
                            position: 'fixed',
                            left: rect.left + 'px',
                            width: rect.width + 'px',
                            zIndex: 9999,
                            ...(openUp
                                ? { bottom: (window.innerHeight - rect.top + 4) + 'px', top: 'auto' }
                                : { top: (rect.bottom + 4) + 'px', bottom: 'auto' }
                            ),
                        };
                        this.$nextTick(() => {
                            const input = this.$refs.sharedSearchInput;
                            if (input) input.focus();
                        });
                    }
                }"
                x-on:keydown.escape.window="close()"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Coluna da Planilha</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Dados da Planilha</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Campo do Sistema</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Dados do Sistema</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($headers as $index => $header)
                                @php $campoMapeado = $mapping[$header] ?? ''; @endphp
                                <tr wire:key="mapping-row-{{ $index }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $header }}</span>
                                    </td>
                                    <td class="py-3 px-4 max-w-[180px]">
                                        @if (!empty($previewPlanilha[$header]))
                                            <div class="space-y-0.5">
                                                @foreach ($previewPlanilha[$header] as $val)
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $val }}">
                                                        {{ \Illuminate\Support\Str::limit($val, 30) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 min-w-[260px]">
                                        @if ($campoMapeado === '__calculado__')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-md bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700">
                                                <x-heroicon-s-calculator class="w-3 h-3" />
                                                Calculado automaticamente
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                x-on:click="toggle(@js($header), $event)"
                                                :class="localMapping[@js($header)]
                                                    ? 'border-success-300 dark:border-success-600'
                                                    : 'border-gray-300 dark:border-gray-600'"
                                                class="w-full text-sm rounded-lg border dark:bg-gray-800 dark:text-gray-300 bg-white px-3 py-2 text-left flex items-center justify-between gap-2"
                                            >
                                                <span x-show="localMapping[@js($header)]" x-text="getLabel(localMapping[@js($header)])" class="truncate"></span>
                                                <span x-show="!localMapping[@js($header)]" class="text-gray-400 truncate">&mdash; Ignorar &mdash;</span>
                                                <span class="flex items-center gap-1 shrink-0">
                                                    <span
                                                        x-show="localMapping[@js($header)]"
                                                        x-on:click.stop="clear(@js($header))"
                                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer"
                                                    >
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </span>
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                                </span>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 max-w-[180px]">
                                        @if (!empty($campoMapeado) && $campoMapeado !== '__calculado__' && !empty($previewSistema[$campoMapeado]))
                                            <div class="space-y-0.5">
                                                @foreach ($previewSistema[$campoMapeado] as $val)
                                                    <div class="text-xs text-blue-600 dark:text-blue-400 truncate" title="{{ $val }}">
                                                        {{ \Illuminate\Support\Str::limit($val, 30) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif (!empty($campoMapeado) && $campoMapeado !== '__calculado__')
                                            <span class="text-xs text-gray-400 italic">Sem dados</span>
                                        @else
                                            <span class="text-gray-400">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Dropdown compartilhado (unico, teleportado para body) --}}
                <template x-teleport="body">
                    <div
                        x-show="activeHeader !== null"
                        x-transition.opacity.duration.150ms
                        :style="dropStyle"
                        class="fixed z-[9999] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg"
                        style="display: none;"
                        x-on:click.outside="close()"
                    >
                        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                            <input
                                x-ref="sharedSearchInput"
                                x-model="search"
                                type="text"
                                placeholder="Buscar campo..."
                                class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                x-on:keydown.escape="close()"
                            />
                        </div>
                        <ul class="max-h-52 overflow-y-auto py-1">
                            <li
                                x-on:click="clear()"
                                class="px-3 py-1.5 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400"
                            >&mdash; Ignorar &mdash;</li>
                            <template x-for="opt in filtered" :key="opt.value">
                                <li
                                    x-on:click="!isUsed(opt.value) && select(opt.value)"
                                    :class="{
                                        'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium': activeHeader && localMapping[activeHeader] === opt.value,
                                        'text-gray-300 dark:text-gray-600 cursor-not-allowed': isUsed(opt.value),
                                        'text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700': !isUsed(opt.value) && !(activeHeader && localMapping[activeHeader] === opt.value),
                                    }"
                                    class="px-3 py-1.5 text-sm flex items-center justify-between"
                                >
                                    <span x-text="opt.label"></span>
                                    <span x-show="isUsed(opt.value)" class="text-[10px] text-gray-400 dark:text-gray-500 ml-2">(em uso)</span>
                                    <svg x-show="activeHeader && localMapping[activeHeader] === opt.value" class="w-4 h-4 text-primary-600 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </li>
                            </template>
                            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">Nenhum campo encontrado</li>
                        </ul>
                    </div>
                </template>
            </div>

            <div class="flex gap-3 mt-6">
                <x-filament::button color="gray" wire:click="voltarStep">
                    Voltar
                </x-filament::button>
                <x-filament::button wire:click="avancarParaValores">
                    Proximo
                </x-filament::button>
            </div>
        </x-filament::card>
    @endif

    {{-- Step 4 — Mapeamento de Valores --}}
    @if ($currentStep === 4)
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">
                Mapeamento de Valores
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Os campos abaixo possuem valores predefinidos. Associe os valores da planilha aos valores aceitos pelo sistema.
            </p>

            @if (count($enumMappedFields) > 0)
                @foreach ($enumMappedFields as $enumField)
                    @php
                        $field = $enumField['field'];
                        $header = $enumField['header'];
                        $values = $spreadsheetValues[$field] ?? [];
                        $options = $systemEnumOptions[$field] ?? [];
                    @endphp

                    <div class="mb-6 last:mb-0">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            {{ $header }}
                            <span class="text-xs font-normal text-gray-400 ml-1">→ {{ $field }}</span>
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400 w-1/3">Valor na Planilha</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400 w-20">Qtd</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 dark:text-gray-400">Valor no Sistema</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($values as $spreadsheetVal => $count)
                                        @if ($spreadsheetVal === '' || $spreadsheetVal === null) @continue @endif
                                        <tr>
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $spreadsheetVal }}</code>
                                            </td>
                                            <td class="py-2 px-3 text-gray-400">{{ $count }}</td>
                                            <td class="py-2 px-3">
                                                @php
                                                    $enumOpcoesJson = collect($options)->map(fn ($opt) => [
                                                        'value' => $opt,
                                                        'label' => $opt,
                                                    ])->values()->toArray();
                                                    $currentEnumVal = $valueMapping[$field][$spreadsheetVal] ?? '';
                                                @endphp
                                                <div
                                                    x-data="{
                                                        criando: false,
                                                        open: false,
                                                        search: '',
                                                        selected: @entangle('valueMapping.'.$field.'.'.$spreadsheetVal),
                                                        opcoes: @js($enumOpcoesJson),
                                                        dropStyle: {},
                                                        get selectedLabel() {
                                                            if (!this.selected) return '';
                                                            const opt = this.opcoes.find(o => o.value === this.selected);
                                                            return opt ? opt.label : this.selected;
                                                        },
                                                        get filtered() {
                                                            const term = this.search.toLowerCase();
                                                            if (!term) return this.opcoes;
                                                            return this.opcoes.filter(o =>
                                                                o.label.toLowerCase().includes(term) ||
                                                                o.value.toLowerCase().includes(term)
                                                            );
                                                        },
                                                        select(value) {
                                                            this.selected = value;
                                                            this.close();
                                                        },
                                                        clear() {
                                                            this.selected = '';
                                                            this.close();
                                                        },
                                                        close() {
                                                            this.open = false;
                                                            this.search = '';
                                                        },
                                                        positionDropdown() {
                                                            const rect = this.$refs.triggerEnum.getBoundingClientRect();
                                                            const spaceBelow = window.innerHeight - rect.bottom;
                                                            const openUp = spaceBelow < 280;
                                                            this.dropStyle = {
                                                                position: 'fixed',
                                                                left: rect.left + 'px',
                                                                width: rect.width + 'px',
                                                                ...(openUp
                                                                    ? { bottom: (window.innerHeight - rect.top + 4) + 'px' }
                                                                    : { top: (rect.bottom + 4) + 'px' }
                                                                ),
                                                            };
                                                        },
                                                        toggle() {
                                                            if (this.criando) return;
                                                            if (this.open) { this.close(); return; }
                                                            this.positionDropdown();
                                                            this.open = true;
                                                            this.$nextTick(() => this.$refs.searchEnumInput.focus());
                                                        }
                                                    }"
                                                    x-on:click.away="close()"
                                                    x-on:scroll.window="if (open) positionDropdown()"
                                                    class="flex items-center gap-2"
                                                >
                                                    <div x-show="!criando" class="relative flex-1">
                                                        <button
                                                            x-ref="triggerEnum"
                                                            type="button"
                                                            x-on:click="toggle()"
                                                            :class="selected
                                                                ? 'border-success-300 dark:border-success-600'
                                                                : 'border-warning-300 dark:border-warning-600'"
                                                            class="w-full text-sm rounded-lg border dark:bg-gray-800 dark:text-gray-300 bg-white px-3 py-2 text-left flex items-center justify-between gap-2"
                                                        >
                                                            <span x-show="selected" x-text="selectedLabel" class="truncate"></span>
                                                            <span x-show="!selected" class="text-gray-400 truncate">&mdash; Manter original &mdash;</span>
                                                            <span class="flex items-center gap-1 shrink-0">
                                                                <span
                                                                    x-show="selected"
                                                                    x-on:click.stop="clear()"
                                                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer"
                                                                >
                                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                                </span>
                                                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                                            </span>
                                                        </button>

                                                        <template x-teleport="body">
                                                            <div
                                                                x-show="open"
                                                                x-transition.opacity.duration.150ms
                                                                :style="dropStyle"
                                                                class="z-[9999] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg"
                                                                style="display: none;"
                                                                x-on:click.away="close()"
                                                            >
                                                                <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                                                    <input
                                                                        x-ref="searchEnumInput"
                                                                        x-model="search"
                                                                        type="text"
                                                                        placeholder="Buscar valor..."
                                                                        class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                                                        x-on:keydown.escape="close()"
                                                                    />
                                                                </div>
                                                                <ul class="max-h-52 overflow-y-auto py-1">
                                                                    <li
                                                                        x-on:click="clear()"
                                                                        class="px-3 py-1.5 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400"
                                                                    >&mdash; Manter original &mdash;</li>
                                                                    <template x-for="opt in filtered" :key="opt.value">
                                                                        <li
                                                                            x-on:click="select(opt.value)"
                                                                            :class="{
                                                                                'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium': selected === opt.value,
                                                                                'text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700': selected !== opt.value,
                                                                            }"
                                                                            class="px-3 py-1.5 text-sm flex items-center justify-between"
                                                                        >
                                                                            <span x-text="opt.label"></span>
                                                                            <svg x-show="selected === opt.value" class="w-4 h-4 text-primary-600 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                                        </li>
                                                                    </template>
                                                                    <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">Nenhum valor encontrado</li>
                                                                </ul>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <button
                                                        x-show="!criando"
                                                        x-on:click="criando = true"
                                                        type="button"
                                                        class="shrink-0 text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 whitespace-nowrap"
                                                        title="Criar novo valor"
                                                    >
                                                        + Novo
                                                    </button>

                                                    <template x-if="criando">
                                                        <div class="flex items-center gap-2 w-full">
                                                            <input
                                                                type="text"
                                                                wire:model="novoValorEnum.{{ $field }}.{{ $spreadsheetVal }}"
                                                                placeholder="Novo valor..."
                                                                class="w-full text-sm rounded-lg border-primary-300 dark:border-primary-600 dark:bg-gray-800 dark:text-gray-300"
                                                            />
                                                            <button
                                                                type="button"
                                                                wire:click="adicionarValorEnum('{{ $field }}', '{{ addslashes($spreadsheetVal) }}')"
                                                                x-on:click="criando = false"
                                                                class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg"
                                                            >
                                                                <x-heroicon-s-check class="w-3.5 h-3.5" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                x-on:click="criando = false"
                                                                class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg"
                                                            >
                                                                <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if (!$loop->last)
                        <hr class="border-gray-200 dark:border-gray-700 my-4">
                    @endif
                @endforeach
            @else
                <div class="text-center py-8">
                    <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-success-400 mb-3" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Nenhum campo com valores predefinidos foi mapeado. Pode avancar.
                    </p>
                </div>
            @endif
        </x-filament::card>

        <div class="flex gap-3 mt-6">
            <x-filament::button color="gray" wire:click="voltarStep">
                Voltar
            </x-filament::button>
            <x-filament::button wire:click="avancarParaValidacao">
                Validar
            </x-filament::button>
        </div>
    @endif

    {{-- Step 5 — Validacao --}}
    @if ($currentStep === 5)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $resumoValidacao['total'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total de linhas</p>
            </x-filament::card>
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-success-600">{{ $resumoValidacao['novos'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Novos registros</p>
            </x-filament::card>
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-warning-600">{{ $resumoValidacao['atualizacoes'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Atualizacoes</p>
            </x-filament::card>
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-danger-600">{{ $resumoValidacao['erros'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Com erro</p>
            </x-filament::card>
        </div>

        {{-- Projetos faltantes --}}
        @if (count($projetosFaltantes) > 0)
            {{-- Mapeamento Status → Etapa --}}
            @if (count($statusValoresUnicos) > 0)
                <x-filament::card class="mb-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                        Mapeamento de Status para Etapa
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Defina a etapa do projeto com base no status da obra na planilha.
                    </p>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <th class="text-left py-2 px-3 font-medium text-gray-500 w-1/3">Status na Planilha</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500 w-20">Qtd</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Etapa do Projeto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($statusValoresUnicos as $statusVal => $count)
                                    <tr>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $statusVal }}</code>
                                        </td>
                                        <td class="py-2 px-3 text-gray-400">{{ $count }}</td>
                                        <td class="py-2 px-3">
                                            <select
                                                wire:model="statusEtapaMapping.{{ $statusVal }}"
                                                @class([
                                                    'w-full text-sm rounded-lg border dark:bg-gray-800 dark:text-gray-300',
                                                    'border-success-300 dark:border-success-600' => !empty($statusEtapaMapping[$statusVal] ?? ''),
                                                    'border-warning-300 dark:border-warning-600' => empty($statusEtapaMapping[$statusVal] ?? ''),
                                                ])
                                            >
                                                <option value="">— Selecionar etapa —</option>
                                                @foreach ($etapasDisponiveis as $etapaId => $etapaNome)
                                                    <option value="{{ $etapaId }}">{{ $etapaNome }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mt-4">
                        <div class="flex items-center gap-3">
                            <label class="text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">Etapa padrao (para status nao mapeados):</label>
                            <select
                                wire:model="etapaPadrao"
                                class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            >
                                <option value="">— Nenhuma —</option>
                                @foreach ($etapasDisponiveis as $etapaId => $etapaNome)
                                    <option value="{{ $etapaId }}">{{ $etapaNome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-2 sm:ml-auto">
                            <input
                                type="text"
                                wire:model="novaEtapaNome"
                                placeholder="Nova etapa..."
                                class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-44"
                                wire:keydown.enter="criarEtapa"
                            />
                            <x-filament::button size="sm" color="gray" wire:click="criarEtapa">
                                Criar etapa
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::card>
            @endif

            <x-filament::card class="mb-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        Projetos a criar ({{ count($projetosFaltantes) }})
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Selecione quais projetos deseja criar automaticamente.
                    </p>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                <th class="py-2 px-3 w-10">
                                    <input
                                        type="checkbox"
                                        checked
                                        class="rounded border-gray-300 text-primary-600"
                                        x-on:change="
                                            const checkboxes = $el.closest('table').querySelectorAll('tbody input[type=checkbox]');
                                            checkboxes.forEach((cb, i) => {
                                                if (!cb.disabled) {
                                                    cb.checked = $el.checked;
                                                    $wire.set('projetosAprovados.' + cb.dataset.index, $el.checked);
                                                }
                                            });
                                        "
                                    />
                                </th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Codigo</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Nome</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Sigla</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Marca</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Cidade</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">UF</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Pais</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($projetosFaltantes as $i => $proj)
                                <tr @class(['bg-danger-50 dark:bg-danger-900/10' => !$proj['resolvido']])>
                                    <td class="py-2 px-3">
                                        <input
                                            type="checkbox"
                                            wire:model="projetosAprovados.{{ $i }}"
                                            data-index="{{ $i }}"
                                            @disabled(!$proj['resolvido'])
                                            class="rounded border-gray-300 text-primary-600 disabled:opacity-40"
                                        />
                                    </td>
                                    <td class="py-2 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $proj['codigo'] }}</td>
                                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $proj['nome'] }}</td>
                                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $proj['sigla'] }}</td>
                                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $proj['marca'] ?? '—' }}</td>
                                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $proj['cidade_nome'] ?: '—' }}</td>
                                    <td class="py-2 px-3">
                                        @if ($proj['resolvido'])
                                            <span class="text-gray-700 dark:text-gray-300">{{ $proj['uf'] }}</span>
                                        @else
                                            <select
                                                wire:change="atualizarEstadoProjeto({{ $i }}, $event.target.value)"
                                                class="text-xs rounded-lg border-warning-400 dark:border-warning-600 dark:bg-gray-800 dark:text-gray-300 w-full"
                                            >
                                                <option value="">{{ $proj['uf'] ?: 'Selecionar...' }}</option>
                                                @foreach ($estadosDisponiveis as $est)
                                                    <option value="{{ $est['id'] }}">{{ $est['label'] }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $proj['pais_nome'] ?: '—' }}</td>
                                    <td class="py-2 px-3">
                                        @if ($proj['resolvido'])
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-success-600">
                                                <x-heroicon-s-check-circle class="w-4 h-4" /> OK
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-warning-600">
                                                <x-heroicon-s-exclamation-triangle class="w-4 h-4" /> Selecione o estado
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-gray-400 mt-3">
                    Projetos com UF nao encontrada podem ser corrigidos selecionando o estado manualmente na coluna UF.
                </p>
            </x-filament::card>
        @endif

        @if (!empty($resumoValidacao['detalhes']))
            <x-filament::card class="mb-6">
                <h3 class="text-sm font-semibold text-danger-600 mb-3">Detalhes dos erros</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Linha</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-500">Mensagem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($resumoValidacao['detalhes'] as $erro)
                                <tr>
                                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $erro['linha'] ?? '—' }}</td>
                                    <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $erro['msg'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        @endif

        <x-filament::card>
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    A importacao sera processada em segundo plano. Voce recebera uma notificacao ao concluir.
                </p>
                <div class="flex gap-3">
                    <x-filament::button color="gray" wire:click="voltarStep">
                        Voltar
                    </x-filament::button>
                    <x-filament::button color="success" wire:click="avancarParaConflitos">
                        @if ($totalConflitos > 0)
                            Revisar {{ $totalConflitos }} Conflito(s)
                        @else
                            Importar
                        @endif
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>
    @endif

    {{-- Step 5.5 — Conflitos --}}
    @if ($currentStep === 55)
        @include('filament.pages.partials.import-conflitos')
    @endif

    {{-- Step 6 — Resultado --}}
    @if ($currentStep === 6)

        {{-- Processando --}}
        @if (($resultado['status'] ?? '') === 'processando' || ($resultado['status'] ?? '') === 'pendente')
            <x-filament::card class="text-center py-8">
                <div wire:poll.2s="verificarStatus">
                    <div class="mx-auto w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center mb-4">
                        <x-filament::loading-indicator class="w-8 h-8 text-primary-500" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Processando importacao...
                    </h2>

                    <div class="max-w-lg mx-auto my-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Progresso</span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $resultado['percentual'] ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div
                                class="bg-primary-600 h-3 rounded-full transition-all duration-500 ease-out"
                                style="width: {{ $resultado['percentual'] ?? 0 }}%"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            {{ $resultado['processados'] ?? 0 }} de {{ $resultado['total'] ?? 0 }} linhas processadas
                        </p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-2xl mx-auto my-4">
                        <div class="text-center">
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $resultado['total'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Total</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xl font-bold text-success-600">{{ $resultado['criados'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Criados</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xl font-bold text-warning-600">{{ $resultado['atualizados'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Atualizados</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xl font-bold text-danger-600">{{ $resultado['erros'] ?? 0 }}</p>
                            <p class="text-xs text-gray-500">Erros</p>
                        </div>
                    </div>
                </div>
            </x-filament::card>

        {{-- Staged — Revisao antes de confirmar --}}
        @elseif (($resultado['status'] ?? '') === 'staged')
            <x-filament::card class="text-center py-8" wire:poll.2s="verificarStatus">
                <div class="mx-auto w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-clipboard-document-check class="w-10 h-10 text-amber-500" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Dados preparados para importacao
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Revise os dados abaixo antes de confirmar. Nada foi gravado na base ainda.
                </p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-2xl mx-auto mb-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-success-600">{{ $stagingResumo['criar'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">A criar</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-warning-600">{{ $stagingResumo['atualizar'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">A atualizar</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-danger-600">{{ $stagingResumo['erro'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Com erro</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-400">{{ $stagingResumo['ignorar'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Ignoradas</p>
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="flex justify-center gap-2 mb-4">
                    @foreach (['todos' => 'Todos', 'criar' => 'A criar', 'atualizar' => 'A atualizar', 'erro' => 'Erros', 'ignorar' => 'Ignoradas'] as $filtro => $label)
                        <button
                            wire:click="$set('stagingFiltro', '{{ $filtro }}')"
                            @class([
                                'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                'bg-primary-600 text-white' => $stagingFiltro === $filtro,
                                'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $stagingFiltro !== $filtro,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Tabela de staging --}}
                @if (count($stagingRows) > 0)
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 text-left">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Linha</th>
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Codigo</th>
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Unidade</th>
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Acao</th>
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Info</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($stagingRows as $sRow)
                                    <tr>
                                        <td class="py-2 px-3 text-xs text-gray-500 tabular-nums">{{ $sRow['linha'] }}</td>
                                        <td class="py-2 px-3 text-xs font-mono text-gray-700 dark:text-gray-300">{{ $sRow['codigo'] ?? '-' }}</td>
                                        <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">{{ $sRow['unidade'] }}</td>
                                        <td class="py-2 px-3">
                                            @php
                                                $acaoConfig = match($sRow['acao']) {
                                                    'criar' => ['label' => 'Criar', 'class' => 'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400'],
                                                    'atualizar' => ['label' => 'Atualizar', 'class' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400'],
                                                    'erro' => ['label' => 'Erro', 'class' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400'],
                                                    'ignorar' => ['label' => 'Ignorar', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'],
                                                    default => ['label' => $sRow['acao'], 'class' => 'bg-gray-100 text-gray-600'],
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $acaoConfig['class'] }}">
                                                {{ $acaoConfig['label'] }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-3 text-xs text-gray-500">
                                            @if ($sRow['acao'] === 'erro')
                                                {{ $sRow['erro']['msg'] ?? '' }}
                                            @elseif ($sRow['acao'] === 'atualizar')
                                                {{ count($sRow['dados'] ?? []) }} campos
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginacao --}}
                    <div class="flex justify-between items-center mt-3">
                        <x-filament::button
                            wire:click="stagingPaginaAnterior"
                            color="gray"
                            size="sm"
                            :disabled="$stagingPage <= 1"
                        >
                            Anterior
                        </x-filament::button>
                        <span class="text-xs text-gray-500">Pagina {{ $stagingPage }}</span>
                        <x-filament::button
                            wire:click="stagingProximaPagina"
                            color="gray"
                            size="sm"
                            :disabled="count($stagingRows) < 50"
                        >
                            Proxima
                        </x-filament::button>
                    </div>
                @endif

                {{-- Botoes de acao --}}
                <div class="flex justify-center gap-4 mt-6">
                    <x-filament::button wire:click="descartarImportacao" color="danger"
                        wire:confirm="Descartar importacao? Os dados preparados serao removidos.">
                        Descartar
                    </x-filament::button>
                    @if (($stagingResumo['criar'] ?? 0) > 0 || ($stagingResumo['atualizar'] ?? 0) > 0)
                        <x-filament::button wire:click="confirmarImportacao" color="success"
                            wire:confirm="Confirmar importacao? Os dados serao gravados na base.">
                            Confirmar Importacao
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::card>

            {{-- Resolucao de erros de staging --}}
            @if (($stagingResumo['erro'] ?? 0) > 0 && count($projetosCorrecao) > 0)
                <x-filament::card class="mt-6 text-left">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                        Projetos a criar ({{ count($projetosCorrecao) }})
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Crie os projetos faltantes e depois reimporte as linhas com erro.
                    </p>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Codigo</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Nome</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">UF</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Cidade</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Etapa</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500 w-24">Acao</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($projetosCorrecao as $i => $proj)
                                    <tr @class(['bg-success-50/50 dark:bg-success-900/10' => $proj['criado']])>
                                        <td class="py-2 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $proj['codigo'] }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300 text-xs">{{ $proj['nome'] }}</td>
                                        <td class="py-2 px-3">
                                            @if ($proj['criado'] || $proj['estado_id'])
                                                <span class="text-gray-700 dark:text-gray-300 text-xs">{{ $proj['uf'] }}</span>
                                            @else
                                                <select
                                                    wire:change="atualizarEstadoCorrecao({{ $i }}, $event.target.value)"
                                                    class="text-xs rounded-lg border-warning-400 dark:border-warning-600 dark:bg-gray-800 dark:text-gray-300 w-full"
                                                >
                                                    <option value="">Selecionar...</option>
                                                    @foreach ($estadosDisponiveis as $est)
                                                        <option value="{{ $est['id'] }}">{{ $est['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                                            @if (!$proj['criado'] && !$proj['cidade_id'])
                                                <input type="text"
                                                    wire:model.blur="projetosCorrecao.{{ $i }}.cidade_nome"
                                                    class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full"
                                                    placeholder="Nome da cidade"
                                                />
                                            @else
                                                {{ $proj['cidade_nome'] }}
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-xs">
                                            @if (!$proj['criado'])
                                                <select
                                                    wire:model="projetosCorrecao.{{ $i }}.etapa_id"
                                                    class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full"
                                                >
                                                    @foreach ($etapasDisponiveis as $etapaId => $etapaNome)
                                                        <option value="{{ $etapaId }}">{{ $etapaNome }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3">
                                            @if ($proj['criado'])
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-success-600">
                                                    <x-heroicon-s-check-circle class="w-4 h-4" /> Criado
                                                </span>
                                            @else
                                                <x-filament::button
                                                    wire:click="criarProjetoCorrecao({{ $i }})"
                                                    size="xs"
                                                    color="gray"
                                                    :disabled="!$proj['estado_id']"
                                                >
                                                    Criar
                                                </x-filament::button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between mt-4">
                        <x-filament::button wire:click="criarTodosProjetos" color="gray" size="sm">
                            Criar Todos
                        </x-filament::button>
                        @php
                            $todosCriados = collect($projetosCorrecao)->every(fn ($p) => $p['criado']);
                        @endphp
                        @if ($todosCriados)
                            <x-filament::button wire:click="reimportarComErros" color="primary" size="sm">
                                Reimportar {{ $stagingResumo['erro'] ?? 0 }} linhas com erro
                            </x-filament::button>
                        @endif
                    </div>
                </x-filament::card>
            @endif

        {{-- Concluido --}}
        @elseif (($resultado['status'] ?? '') === 'concluido')
            <x-filament::card class="text-center py-8">
                <div class="mx-auto w-16 h-16 rounded-full bg-success-50 dark:bg-success-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-check-circle class="w-10 h-10 text-success-500" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Importacao concluida
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-2xl mx-auto my-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $resultado['total'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Total</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-success-600">{{ $resultado['criados'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Criados</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-warning-600">{{ $resultado['atualizados'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Atualizados</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-danger-600">{{ $resultado['erros'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Erros</p>
                    </div>
                </div>

                @if (($resultado['erros'] ?? 0) === 0)
                    <div class="flex justify-center gap-3">
                        <x-filament::button wire:click="novaImportacao" color="gray">
                            Nova Importacao
                        </x-filament::button>
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Resources\Obras\ObrasResource::getUrl('index') }}"
                        >
                            Ver Obras
                        </x-filament::button>
                    </div>
                @endif
            </x-filament::card>

            {{-- Resolucao de erros --}}
            @if (($resultado['erros'] ?? 0) > 0)

                {{-- Projetos a criar/corrigir --}}
                @if (count($projetosCorrecao) > 0)
                    <x-filament::card class="mt-6 text-left">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                            Projetos a criar ({{ count($projetosCorrecao) }})
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Estes projetos nao existem no sistema. Corrija os dados e crie-os para poder reimportar as linhas.
                        </p>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Codigo</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Nome</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">UF</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Cidade</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Etapa</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-24">Acao</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($projetosCorrecao as $i => $proj)
                                        <tr @class(['bg-success-50/50 dark:bg-success-900/10' => $proj['criado']])>
                                            <td class="py-2 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">
                                                {{ $proj['codigo'] }}
                                            </td>
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300 text-xs">
                                                {{ $proj['nome'] }}
                                            </td>
                                            <td class="py-2 px-3">
                                                @if ($proj['criado'])
                                                    <span class="text-gray-700 dark:text-gray-300 text-xs">{{ $proj['uf'] }}</span>
                                                @elseif ($proj['estado_id'])
                                                    <span class="text-gray-700 dark:text-gray-300 text-xs">{{ $proj['uf'] }}</span>
                                                @else
                                                    <select
                                                        wire:change="atualizarEstadoCorrecao({{ $i }}, $event.target.value)"
                                                        class="text-xs rounded-lg border-warning-400 dark:border-warning-600 dark:bg-gray-800 dark:text-gray-300 w-full"
                                                    >
                                                        <option value="">Selecionar...</option>
                                                        @foreach ($estadosDisponiveis as $est)
                                                            <option value="{{ $est['id'] }}">{{ $est['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                @if ($proj['criado'])
                                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-success-600">
                                                        <x-heroicon-s-check-circle class="w-4 h-4" /> Criado
                                                    </span>
                                                @else
                                                    <input
                                                        type="text"
                                                        wire:model.blur="projetosCorrecao.{{ $i }}.cidade_nome"
                                                        placeholder="Nome da cidade"
                                                        class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full"
                                                    />
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                @if (!$proj['criado'])
                                                    <select
                                                        wire:model="projetosCorrecao.{{ $i }}.etapa_id"
                                                        class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full"
                                                    >
                                                        <option value="">— Etapa —</option>
                                                        @foreach ($etapasDisponiveis as $etapaId => $etapaNome)
                                                            <option value="{{ $etapaId }}">{{ $etapaNome }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="text-xs text-gray-500">{{ $etapasDisponiveis[$proj['etapa_id']] ?? '—' }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                @if (!$proj['criado'])
                                                    <x-filament::button
                                                        wire:click="criarProjetoCorrecao({{ $i }})"
                                                        size="xs"
                                                        color="success"
                                                    >
                                                        Criar
                                                    </x-filament::button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (collect($projetosCorrecao)->where('criado', false)->count() > 0)
                            <div class="flex justify-end mt-3">
                                <x-filament::button wire:click="criarTodosProjetos" size="sm" color="success">
                                    Criar Todos
                                </x-filament::button>
                            </div>
                        @endif
                    </x-filament::card>
                @endif

                {{-- Linhas com projeto nao encontrado --}}
                @if (count($errosAgrupados['projeto_nao_encontrado'] ?? []) > 0)
                    <x-filament::card class="mt-6 text-left">
                        <h3 class="text-sm font-semibold text-warning-600 mb-1">
                            Linhas nao importadas — projeto nao encontrado ({{ count($errosAgrupados['projeto_nao_encontrado']) }})
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            Apos criar os projetos acima, clique em "Reimportar" para processar estas linhas.
                        </p>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-20">Linha</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-28">Codigo</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach (array_slice($errosAgrupados['projeto_nao_encontrado'], 0, 50) as $erro)
                                        <tr>
                                            <td class="py-1.5 px-3 text-xs text-gray-400">{{ $erro['linha'] ?? '—' }}</td>
                                            <td class="py-1.5 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $erro['codigo'] ?? '—' }}</td>
                                            <td class="py-1.5 px-3 text-xs text-gray-500">{{ $erro['msg'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-filament::button wire:click="reimportarComErros" color="warning">
                                Reimportar {{ count($errosAgrupados['projeto_nao_encontrado']) }} linhas
                            </x-filament::button>
                        </div>
                    </x-filament::card>
                @endif

                {{-- Outros erros --}}
                @if (count($errosAgrupados['outro'] ?? []) > 0)
                    <x-filament::card class="mt-6 text-left">
                        <h3 class="text-sm font-semibold text-danger-600 mb-2">
                            Outros erros ({{ count($errosAgrupados['outro']) }})
                        </h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-48 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-20">Linha</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($errosAgrupados['outro'] as $erro)
                                        <tr>
                                            <td class="py-1.5 px-3 text-xs text-gray-400">{{ $erro['linha'] ?? '—' }}</td>
                                            <td class="py-1.5 px-3 text-xs text-gray-500">{{ $erro['msg'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::card>
                @endif

                {{-- Botoes finais --}}
                <div class="flex justify-center gap-3 mt-6">
                    <x-filament::button wire:click="novaImportacao" color="gray">
                        Nova Importacao
                    </x-filament::button>
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Resources\Obras\ObrasResource::getUrl('index') }}"
                    >
                        Ver Obras
                    </x-filament::button>
                </div>
            @endif

        {{-- Erro fatal --}}
        @elseif (($resultado['status'] ?? '') === 'erro')
            <x-filament::card class="text-center py-8">
                <div class="mx-auto w-16 h-16 rounded-full bg-danger-50 dark:bg-danger-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-x-circle class="w-10 h-10 text-danger-500" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Erro na importacao
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Ocorreu um erro ao processar a planilha. Verifique os dados e tente novamente.
                </p>
                <x-filament::button wire:click="novaImportacao" color="gray">
                    Tentar Novamente
                </x-filament::button>
            </x-filament::card>
        @else
            <x-filament::card class="text-center py-8">
                <div wire:poll.2s="verificarStatus">
                    <x-filament::loading-indicator class="w-8 h-8 mx-auto text-primary-500" />
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Carregando resultado...</p>
                </div>
            </x-filament::card>
        @endif
    @endif

</x-filament::page>
