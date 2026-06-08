<x-filament::page>

    <div class="mb-8">
        <div class="flex items-center justify-between">
            @php
                $steps = [
                    1 => 'Upload',
                    2 => 'Aba',
                    3 => 'Colunas',
                    4 => 'Valores',
                    5 => 'Validação',
                    55 => 'Conflitos',
                    6 => 'Resultado',
                ];
                $stepOrder = array_keys($steps);
                $currentIdx = array_search($currentStep, $stepOrder);
                if ($currentIdx === false) {
                    $currentIdx = -1;
                }
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
                    @if (! $loop->last)
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

    @if ($currentStep === 1)
        <x-filament::card>
            <div class="text-center py-8">
                <div class="mx-auto w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-arrow-up-tray class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    Importar Planilha de CNPJs
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Faça upload de uma planilha CSV/XLSX no formato da base de CNPJs.
                </p>

                <div class="max-w-md mx-auto">
                    <label class="block w-full cursor-pointer border-2 border-dashed rounded-lg p-8 transition-colors border-gray-300 hover:border-primary-500 dark:border-gray-600 dark:hover:border-primary-500">
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
                    <x-filament::button wire:click="avancarParaAbas" :disabled="!$arquivoPath">
                        Próximo
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>

        @if (count($importacoesAnteriores) > 0)
            <x-filament::card class="mt-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Importações Anteriores</h3>

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
                                <th class="pb-2 pl-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($importacoesAnteriores as $imp)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="py-2.5 pr-4 max-w-[200px] truncate" title="{{ $imp['arquivo'] }}">{{ $imp['arquivo'] }}</td>
                                    <td class="py-2.5 pr-4">
                                        @php
                                            $statusConfig = match($imp['status']) {
                                                'concluido' => ['label' => 'Concluído', 'class' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-green-600/10 dark:ring-green-500/20'],
                                                'staged' => ['label' => 'Aguardando', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 ring-1 ring-blue-600/10 dark:ring-blue-500/20'],
                                                'confirmando' => ['label' => 'Confirmando', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-500/20'],
                                                'processando' => ['label' => 'Processando', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-500/20'],
                                                'pendente' => ['label' => 'Pendente', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                                                'descartado' => ['label' => 'Descartado', 'class' => 'bg-gray-100 text-gray-500 dark:bg-gray-500/10 dark:text-gray-500 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                                                'erro' => ['label' => 'Erro', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-red-600/10 dark:ring-red-500/20'],
                                                default => ['label' => ucfirst($imp['status']), 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums">{{ $imp['total'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums text-success-600 dark:text-success-400">{{ $imp['criados'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums text-primary-600 dark:text-primary-400">{{ $imp['atualizados'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4 text-center tabular-nums {{ ($imp['erros'] ?? 0) > 0 ? 'text-danger-600 dark:text-danger-400 font-medium' : '' }}">{{ $imp['erros'] ?? 0 }}</td>
                                    <td class="py-2.5 pr-4">{{ $imp['usuario'] }}</td>
                                    <td class="py-2.5 text-gray-500 dark:text-gray-400">{{ $imp['data'] }} @if ($imp['duracao'])<span class="text-xs text-gray-400 dark:text-gray-500">({{ $imp['duracao'] }})</span>@endif</td>
                                    <td class="py-2.5 pl-4 text-right">
                                        <x-filament::button wire:click="acompanharImportacao({{ $imp['id'] }})" color="gray" size="sm">
                                            Ver detalhes
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        @endif
    @endif

    @if ($currentStep === 2)
        <x-filament::card>
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Seleção de Aba</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">O arquivo <strong>{{ $arquivoNome }}</strong> possui {{ count($abas) }} aba(s).</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-w-2xl">
                @foreach ($abas as $aba)
                    <label @class([
                        'flex items-center gap-3 p-4 rounded-lg border-2 cursor-pointer transition-colors',
                        'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $abaSelecionada === $aba,
                        'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' => $abaSelecionada !== $aba,
                    ])>
                        <input type="radio" wire:model="abaSelecionada" value="{{ $aba }}" class="text-primary-600" />
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $aba }}</p>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex gap-3 mt-6">
                <x-filament::button color="gray" wire:click="voltarStep">Voltar</x-filament::button>
                <x-filament::button wire:click="avancarParaMapeamento">Próximo</x-filament::button>
            </div>
        </x-filament::card>
    @endif

    @if ($currentStep === 3)
        <x-filament::card>
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mapeamento de Colunas</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Associe cada coluna da planilha ao campo correspondente no sistema.</p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        @if (count($templates) > 0)
                            <select wire:change="carregarTemplate($event.target.value)" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <option value="">Carregar template...</option>
                                @foreach ($templates as $tpl)
                                    <option value="{{ $tpl['id'] }}">{{ $tpl['nome'] }}</option>
                                @endforeach
                            </select>
                        @endif

                        <div class="flex items-center gap-2">
                            <input type="text" wire:model="nomeTemplate" placeholder="Nome do template" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-40" />
                            <x-filament::button size="sm" color="gray" wire:click="salvarTemplate">Salvar</x-filament::button>
                        </div>
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
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-300">{{ $unmappedHeaders->count() }} {{ $unmappedHeaders->count() === 1 ? 'coluna sem' : 'colunas sem' }} equivalente no banco de dados</p>
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">{{ $mappedHeaders->count() }} {{ $mappedHeaders->count() === 1 ? 'coluna mapeada' : 'colunas mapeadas' }} automaticamente. As colunas sem mapeamento serão ignoradas na importação, a menos que você selecione um campo manualmente.</p>
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
                    get usados() { return Object.values(this.localMapping).filter(v => v && v !== '__calculado__'); },
                    getLabel(value) { if (!value) return ''; const opt = this.opcoes.find(o => o.value === value); return opt ? opt.label : value; },
                    get filtered() { const term = this.search.toLowerCase(); if (!term) return this.opcoes; return this.opcoes.filter(o => o.label.toLowerCase().includes(term) || o.value.toLowerCase().includes(term)); },
                    isUsed(value) { const current = this.activeHeader ? (this.localMapping[this.activeHeader] || '') : ''; return value !== current && this.usados.includes(value); },
                    select(value) { if (!this.activeHeader || this.isUsed(value)) return; this.localMapping[this.activeHeader] = value; $wire.call('updateMapping', this.activeHeader, value); this.close(); },
                    clear(header = null) { const h = header || this.activeHeader; if (!h) return; this.localMapping[h] = ''; $wire.call('updateMapping', h, ''); this.close(); },
                    close() { this.activeHeader = null; this.search = ''; },
                    toggle(header, event) { if (this.activeHeader === header) { this.close(); return; } this.activeHeader = header; this.search = ''; const rect = event.currentTarget.getBoundingClientRect(); const spaceBelow = window.innerHeight - rect.bottom; const openUp = spaceBelow < 280; this.dropStyle = { position: 'fixed', left: rect.left + 'px', width: rect.width + 'px', zIndex: 9999, ...(openUp ? { bottom: (window.innerHeight - rect.top + 4) + 'px', top: 'auto' } : { top: (rect.bottom + 4) + 'px', bottom: 'auto' }) }; this.$nextTick(() => { const input = this.$refs.sharedSearchInput; if (input) input.focus(); }); }
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
                            <tr wire:key="mapping-cnpj-{{ $index }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-3 px-4">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $header }}</span>
                                </td>
                                <td class="py-3 px-4 max-w-[180px]">
                                    @if (! empty($previewPlanilha[$header]))
                                        <div class="space-y-0.5">
                                            @foreach ($previewPlanilha[$header] as $val)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $val }}">{{ \Illuminate\Support\Str::limit($val, 30) }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">&mdash;</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 min-w-[240px]">
                                    <div class="relative">
                                        <button type="button" x-on:click="toggle(@js($header), $event)" :class="(localMapping[@js($header)] ?? '') ? 'border-success-300 dark:border-success-600' : 'border-warning-300 dark:border-warning-600'" class="w-full text-sm rounded-lg border dark:bg-gray-800 dark:text-gray-300 bg-white px-3 py-2 text-left flex items-center justify-between gap-2">
                                            <span x-show="localMapping[@js($header)]" x-text="getLabel(localMapping[@js($header)])" class="truncate"></span>
                                            <span x-show="!localMapping[@js($header)]" class="text-gray-400 truncate">— Ignorar —</span>
                                            <span class="flex items-center gap-1 shrink-0">
                                                <span x-show="localMapping[@js($header)]" x-on:click.stop="clear(@js($header))" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </span>
                                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                            </span>
                                        </button>
                                        <template x-teleport="body">
                                            <div x-show="activeHeader === @js($header)" x-transition.opacity.duration.150ms :style="dropStyle" class="z-[9999] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg" style="display:none" x-on:click.away="close()">
                                                <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                                    <input x-ref="sharedSearchInput" x-model="search" type="text" placeholder="Buscar campo..." class="w-full text-sm border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-primary-500 focus:border-primary-500" x-on:keydown.escape="close()" />
                                                </div>
                                                <ul class="max-h-52 overflow-y-auto py-1">
                                                    <li x-on:click="clear(@js($header))" class="px-3 py-1.5 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400">— Ignorar —</li>
                                                    <template x-for="opt in filtered" :key="opt.value">
                                                        <li x-on:click="select(opt.value)" :class="{'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium': localMapping[@js($header)] === opt.value, 'text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700': localMapping[@js($header)] !== opt.value, 'opacity-50': isUsed(opt.value)}" class="px-3 py-1.5 text-sm flex items-center justify-between">
                                                            <span x-text="opt.label"></span>
                                                            <svg x-show="localMapping[@js($header)] === opt.value" class="w-4 h-4 text-primary-600 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                        </li>
                                                    </template>
                                                    <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400 text-center">Nenhum campo encontrado</li>
                                                </ul>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="py-3 px-4 max-w-[180px]">
                                    @if (! empty($campoMapeado) && ! empty($previewSistema[$campoMapeado]))
                                        <div class="space-y-0.5">
                                            @foreach ($previewSistema[$campoMapeado] as $val)
                                                <div class="text-xs text-blue-600 dark:text-blue-400 truncate" title="{{ $val }}">{{ \Illuminate\Support\Str::limit($val, 30) }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            </div>
            <div class="flex gap-3 mt-6">
                <x-filament::button color="gray" wire:click="voltarStep">Voltar</x-filament::button>
                <x-filament::button wire:click="avancarParaValores">Próximo</x-filament::button>
            </div>
        </x-filament::card>
    @endif

    @if ($currentStep === 4)
        <x-filament::card>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Mapeamento de Valores</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Associe os valores da planilha aos valores aceitos pelo sistema.</p>

            @if (count($enumMappedFields) > 0)
                @foreach ($enumMappedFields as $enumField)
                    @php
                        $field = $enumField['field'];
                        $header = $enumField['header'];
                        $values = $spreadsheetValues[$field] ?? [];
                        $options = $systemEnumOptions[$field] ?? [];
                    @endphp
                    <div class="mb-6 last:mb-0">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ $header }} <span class="text-xs font-normal text-gray-400 ml-1">→ {{ $field }}</span></h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-1/3">Valor na Planilha</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-20">Qtd</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Valor no Sistema</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($values as $spreadsheetVal => $count)
                                        <tr>
                                            <td class="py-2 px-3"><code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ $spreadsheetVal }}</code></td>
                                            <td class="py-2 px-3 text-gray-400">{{ $count }}</td>
                                            <td class="py-2 px-3">
                                                <div class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_13rem_auto] lg:items-center">
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input.select wire:model="valueMapping.{{ $field }}.{{ $spreadsheetVal }}">
                                                            <option value="">— Manter original —</option>
                                                            @foreach ($options as $option)
                                                                <option value="{{ $option }}">{{ $option }}</option>
                                                            @endforeach
                                                        </x-filament::input.select>
                                                    </x-filament::input.wrapper>

                                                    <x-filament::input.wrapper>
                                                        <x-filament::input
                                                            type="text"
                                                            wire:model="novoValorEnum.{{ $field }}.{{ $spreadsheetVal }}"
                                                            placeholder="Novo valor..."
                                                        />
                                                    </x-filament::input.wrapper>

                                                    <x-filament::button
                                                        size="sm"
                                                        color="gray"
                                                        wire:click="adicionarValorEnum('{{ $field }}', '{{ addslashes($spreadsheetVal) }}')"
                                                        class="lg:justify-self-start"
                                                    >
                                                        Adicionar
                                                    </x-filament::button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-8">
                    <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-success-400 mb-3" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum campo com valores predefinidos foi mapeado. Pode avançar.</p>
                </div>
            @endif

            <div class="flex gap-3 mt-6">
                <x-filament::button color="gray" wire:click="voltarStep">Voltar</x-filament::button>
                <x-filament::button wire:click="avancarParaValidacao">Validar</x-filament::button>
            </div>
        </x-filament::card>
    @endif

    @if ($currentStep === 5)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $resumoValidacao['total'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total de linhas</p>
            </x-filament::card>
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-success-600">{{ $resumoValidacao['atualizacoes'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Prontas para importar</p>
            </x-filament::card>
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-danger-600">{{ $resumoValidacao['erros'] ?? 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Precisam de correção</p>
            </x-filament::card>
            <x-filament::card class="text-center">
                <p class="text-2xl font-bold text-primary-600">{{ count($linhasPreparadas) + count($linhasCorrigir) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Linhas analisadas</p>
            </x-filament::card>
        </div>

        @php
            $validationInitialTab = count($linhasCorrigir) > 0
                ? 'corrigir'
                : 'projetos';
            $totalLinhasProjetosCorrecao = collect($projetosCorrecao)->sum('linhas_afetadas');
            $pendingCorrectionsCount = collect($linhasCorrigir)->filter(fn ($linha) => ! ($linha['resolved'] ?? false))->count();
            $reviewConflictCount = count($linhasCorrigir) > 0
                ? count($linhasCorrigir)
                : $totalConflitos;
        @endphp

        @if (count($linhasCorrigir) > 0 || count($projetosCorrecao) > 0)
            <div x-data="{ activeTab: '{{ $validationInitialTab }}' }" class="mb-6 space-y-4">
                <x-filament::card>
                    <div class="flex flex-wrap gap-2">
                        @if (count($linhasCorrigir) > 0)
                            <button
                                type="button"
                                x-on:click="activeTab = 'corrigir'"
                                :class="activeTab === 'corrigir'
                                    ? 'border-danger-600 bg-danger-600 text-white shadow-sm dark:border-danger-500 dark:bg-danger-500'
                                    : 'border-gray-300 bg-gray-50 text-gray-700 shadow-sm hover:border-gray-400 hover:bg-white dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'"
                                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition"
                            >
                                <span>Linhas para corrigir</span>
                                <span
                                    :class="activeTab === 'corrigir'
                                        ? 'bg-white/20 text-white'
                                        : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200'"
                                    class="rounded-md px-2 py-0.5 text-xs"
                                >
                                    {{ count($linhasCorrigir) }}
                                </span>
                            </button>
                        @endif

                        @if (count($projetosCorrecao) > 0)
                            <button
                                type="button"
                                x-on:click="activeTab = 'projetos'"
                                :class="activeTab === 'projetos'
                                    ? 'border-success-600 bg-success-600 text-white shadow-sm dark:border-success-500 dark:bg-success-500'
                                    : 'border-gray-300 bg-gray-50 text-gray-700 shadow-sm hover:border-gray-400 hover:bg-white dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'"
                                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition"
                            >
                                <span>Projetos a criar antes da importação</span>
                                <span
                                    :class="activeTab === 'projetos'
                                        ? 'bg-white/20 text-white'
                                        : 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-200'"
                                    class="rounded-md px-2 py-0.5 text-xs"
                                >
                                    {{ $totalLinhasProjetosCorrecao }}
                                </span>
                            </button>
                        @endif
                    </div>
                </x-filament::card>

                @if (count($linhasCorrigir) > 0)
                    <x-filament::card x-show="activeTab === 'corrigir'" x-cloak class="text-left">
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Linhas para corrigir ({{ count($linhasCorrigir) }})</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Associe o projeto e ajuste a localização quando necessário antes de importar.</p>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Linha</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Projeto</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Nova Sigla</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">CNPJ</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Status</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">UF</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Cidade</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Situação</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Erro</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($linhasCorrigir as $i => $linha)
                                        <tr @class(['bg-danger-50 dark:bg-danger-900/10' => ! $linha['resolved']])>
                                            <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">{{ $linha['linha'] }}</td>
                                            <td class="py-2 px-3 min-w-[240px]">
                                                <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                                                    <div class="flex items-start gap-2">
                                                        <x-filament::input.wrapper class="flex-1">
                                                            <x-filament::input
                                                                type="text"
                                                                wire:model.live.debounce.300ms="buscaProjetos.{{ $i }}"
                                                                x-on:focus="open = true"
                                                                x-on:input="open = true"
                                                                placeholder="Buscar por nome, código ou sigla..."
                                                                autocomplete="off"
                                                            />
                                                        </x-filament::input.wrapper>

                                                        @if (filled($linha['projeto_id'] ?? null))
                                                            <button
                                                                type="button"
                                                                wire:click="limparProjetoCorrecao({{ $i }})"
                                                                class="shrink-0 rounded-lg border border-gray-300 px-2 py-1 text-[11px] text-gray-500 transition hover:border-gray-400 hover:text-gray-700 dark:border-gray-600 dark:text-gray-400 dark:hover:border-gray-500 dark:hover:text-gray-200"
                                                                title="Limpar projeto selecionado"
                                                            >
                                                                Limpar
                                                            </button>
                                                        @endif
                                                    </div>

                                                    @if (! empty($resultadosBuscaProjetos[$i] ?? []))
                                                        <div
                                                            x-show="open"
                                                            x-transition.opacity.duration.100ms
                                                            class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                                        >
                                                            @foreach (($resultadosBuscaProjetos[$i] ?? []) as $projeto)
                                                                <button
                                                                    type="button"
                                                                    wire:click="selecionarProjetoCorrecao({{ $i }}, {{ $projeto['id'] }})"
                                                                    x-on:click="open = false"
                                                                    class="flex w-full items-start px-3 py-2 text-left text-xs text-gray-700 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                                                >
                                                                    {{ $projeto['label'] }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    @elseif (mb_strlen(trim((string) ($buscaProjetos[$i] ?? ''))) >= 2)
                                                        <p class="mt-1 text-[11px] text-gray-400">
                                                            Nenhum projeto encontrado. Continue digitando para refinar a busca.
                                                        </p>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-2 px-3 min-w-[150px]">
                                                <x-filament::input.wrapper>
                                                    <x-filament::input type="text" wire:model.blur="linhasCorrigir.{{ $i }}.nova_sigla" />
                                                </x-filament::input.wrapper>
                                            </td>
                                            <td class="py-2 px-3 min-w-[180px]">
                                                <x-filament::input.wrapper>
                                                    <x-filament::input type="text" wire:model.blur="linhasCorrigir.{{ $i }}.cnpj_formatado" placeholder="CNPJ" />
                                                </x-filament::input.wrapper>
                                            </td>
                                            <td class="py-2 px-3 min-w-[160px]">
                                                <x-filament::input.wrapper>
                                                    <x-filament::input.select wire:model="linhasCorrigir.{{ $i }}.status_cnpj">
                                                        <option value="">Selecionar...</option>
                                                        <option value="definitivo">CNPJ Definitivo</option>
                                                        <option value="provisorio">CNPJ Provisório</option>
                                                    </x-filament::input.select>
                                                </x-filament::input.wrapper>
                                            </td>
                                            <td class="py-2 px-3 min-w-[160px]">
                                                <x-filament::input.wrapper>
                                                    <x-filament::input.select wire:change="atualizarEstadoCorrecao({{ $i }}, $event.target.value)">
                                                        <option value="">Selecionar...</option>
                                                        @foreach ($estadosDisponiveis as $estado)
                                                            <option value="{{ $estado['id'] }}" @selected(($linha['estado_id'] ?? null) == $estado['id'])>{{ $estado['label'] }}</option>
                                                        @endforeach
                                                    </x-filament::input.select>
                                                </x-filament::input.wrapper>
                                            </td>
                                            <td class="py-2 px-3 min-w-[180px]">
                                                <x-filament::input.wrapper>
                                                    <x-filament::input type="text" wire:model.blur="linhasCorrigir.{{ $i }}.cidade_nome" placeholder="Nome da cidade" />
                                                </x-filament::input.wrapper>
                                            </td>
                                            <td class="py-2 px-3">
                                                @if ($linha['resolved'])
                                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-success-600"><x-heroicon-s-check-circle class="w-4 h-4" /> OK</span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-warning-600"><x-heroicon-s-exclamation-triangle class="w-4 h-4" /> Corrigir</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                                                {{ ($linha['errors'][0] ?? '—') }}
                                            </td>
                                            <td class="py-2 px-3">
                                                <x-filament::button wire:click="abrirLinhaCorrecaoModal({{ $i }})" size="xs" color="gray">
                                                    Ver e Corrigir
                                                </x-filament::button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::card>
                @endif

                @if (count($projetosCorrecao) > 0)
                    <x-filament::card x-show="activeTab === 'projetos'" x-cloak class="text-left">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Projetos a criar antes da importação ({{ count($projetosCorrecao) }} projeto(s) / {{ $totalLinhasProjetosCorrecao }} linha(s))</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Crie os projetos faltantes agora para evitar que essas linhas caiam em erro no staging. Quando várias linhas apontam para o mesmo projeto ausente, elas aparecem agrupadas em uma única sugestão.</p>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Código</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Nome</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">UF</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Cidade</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Linhas</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Etapa</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-24">Ação</th>
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
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input.select wire:change="atualizarEstadoProjetoCorrecao({{ $i }}, $event.target.value)">
                                                            <option value="">Selecionar...</option>
                                                            @foreach ($estadosDisponiveis as $est)
                                                                <option value="{{ $est['id'] }}">{{ $est['label'] }}</option>
                                                            @endforeach
                                                        </x-filament::input.select>
                                                    </x-filament::input.wrapper>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                                                @if (! $proj['criado'] && ! $proj['cidade_id'])
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input type="text" wire:model.blur="projetosCorrecao.{{ $i }}.cidade_nome" placeholder="Nome da cidade" />
                                                    </x-filament::input.wrapper>
                                                @else
                                                    {{ $proj['cidade_nome'] }}
                                                @endif
                                            </td>
                                            <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                                                {{ $proj['linhas_afetadas'] ?? 1 }}
                                            </td>
                                            <td class="py-2 px-3 text-xs">
                                                @if (! $proj['criado'])
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input.select wire:model="projetosCorrecao.{{ $i }}.etapa_id">
                                                            @foreach ($etapasDisponiveis as $etapaId => $etapaNome)
                                                                <option value="{{ $etapaId }}">{{ $etapaNome }}</option>
                                                            @endforeach
                                                        </x-filament::input.select>
                                                    </x-filament::input.wrapper>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                @if ($proj['criado'])
                                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-success-600"><x-heroicon-s-check-circle class="w-4 h-4" /> Criado</span>
                                                @else
                                                    <x-filament::button wire:click="criarProjetoCorrecao({{ $i }})" size="xs" color="gray" :disabled="!$proj['estado_id']">Criar</x-filament::button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-filament::button wire:click="criarTodosProjetos" color="gray" size="sm">Criar Todos</x-filament::button>
                        </div>
                    </x-filament::card>
                @endif
            </div>
        @endif

        @if ($pendingCorrectionsCount > 0)
            <x-filament::card class="mb-6">
                <label class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        wire:model.live="ignorarLinhasComErro"
                        class="mt-1 rounded border-gray-300 text-primary-600"
                    />
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            Ignorar {{ $pendingCorrectionsCount }} {{ $pendingCorrectionsCount === 1 ? 'linha com erro' : 'linhas com erro' }} e importar somente as restantes
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            As linhas pendentes serão registradas como ignoradas no resultado desta importação, sem bloquear as linhas válidas.
                        </p>
                    </div>
                </label>
            </x-filament::card>
        @endif

        @php
            $linhaModal = filled($linhaCorrecaoModalIndex) ? ($linhasCorrigir[$linhaCorrecaoModalIndex] ?? null) : null;
        @endphp

        <x-filament::modal id="linha-correcao-modal" width="4xl">
            @if ($linhaModal)
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Linha {{ $linhaModal['linha'] }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Revise os problemas desta linha e, se necessário, crie o projeto individualmente.
                        </p>
                    </div>

                    <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-950/20">
                        <h4 class="text-sm font-semibold text-danger-700 dark:text-danger-300">Problemas identificados</h4>
                        <ul class="mt-3 space-y-2">
                            @foreach (($linhaModal['errors'] ?? []) as $erro)
                                <li class="text-sm text-danger-700 dark:text-danger-300">
                                    {{ $erro }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Dados atuais da linha</h4>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">Unidade</dt>
                                    <dd class="text-right text-gray-900 dark:text-gray-100">{{ $linhaModal['unidade'] ?: '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">Nova sigla</dt>
                                    <dd class="text-right text-gray-900 dark:text-gray-100">{{ $linhaModal['nova_sigla'] ?: '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">Sigla antiga</dt>
                                    <dd class="text-right text-gray-900 dark:text-gray-100">{{ $linhaModal['sigla_antiga'] ?: '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">Empresa</dt>
                                    <dd class="text-right text-gray-900 dark:text-gray-100">{{ $linhaModal['empresa'] ?: '—' }}</dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">UF / Cidade</dt>
                                    <dd class="text-right text-gray-900 dark:text-gray-100">{{ $linhaModal['uf'] ?: '—' }} / {{ $linhaModal['cidade_nome'] ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Vincular projeto existente</h4>
                            <div class="mt-4">
                                <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="text"
                                            wire:model.live.debounce.300ms="buscaProjetoModal"
                                            x-on:focus="open = true"
                                            x-on:input="open = true"
                                            placeholder="Buscar por nome, código ou sigla..."
                                            autocomplete="off"
                                        />
                                    </x-filament::input.wrapper>

                                    @if (! empty($resultadosBuscaProjetoModal))
                                        <div
                                            x-show="open"
                                            x-transition.opacity.duration.100ms
                                            class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                                        >
                                            @foreach ($resultadosBuscaProjetoModal as $projeto)
                                                <button
                                                    type="button"
                                                    wire:click="vincularProjetoExistenteDaLinhaCorrecao({{ $projeto['id'] }})"
                                                    x-on:click="open = false"
                                                    class="flex w-full items-start px-3 py-2 text-left text-xs text-gray-700 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                                >
                                                    {{ $projeto['label'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @elseif (mb_strlen(trim($buscaProjetoModal)) >= 2)
                                        <p class="mt-2 text-xs text-gray-400">
                                            Nenhum projeto encontrado. Você pode criar um novo logo abaixo.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 md:col-span-2">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Criar projeto desta linha</h4>
                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Código</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input type="text" wire:model.blur="projetoCorrecaoModal.codigo" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Nome</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input type="text" wire:model.blur="projetoCorrecaoModal.nome" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Marca</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input type="text" wire:model.blur="projetoCorrecaoModal.marca" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">UF</label>
                                        <x-filament::input.wrapper>
                                            <x-filament::input.select wire:change="atualizarEstadoProjetoCorrecaoModal($event.target.value)">
                                                <option value="">Selecionar...</option>
                                                @foreach ($estadosDisponiveis as $estado)
                                                    <option value="{{ $estado['id'] }}" @selected(($projetoCorrecaoModal['estado_id'] ?? null) == $estado['id'])>{{ $estado['label'] }}</option>
                                                @endforeach
                                            </x-filament::input.select>
                                        </x-filament::input.wrapper>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Cidade</label>
                                        <x-filament::input.wrapper>
                                            <x-filament::input type="text" wire:model.blur="projetoCorrecaoModal.cidade_nome" />
                                        </x-filament::input.wrapper>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Etapa</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input.select wire:model="projetoCorrecaoModal.etapa_id">
                                            @foreach ($etapasDisponiveis as $etapaId => $etapaNome)
                                                <option value="{{ $etapaId }}">{{ $etapaNome }}</option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'linha-correcao-modal' })">
                            Fechar
                        </x-filament::button>
                        <x-filament::button wire:click="criarProjetoDaLinhaCorrecao" color="success">
                            Criar Projeto Desta Linha
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </x-filament::modal>

        <x-filament::card>
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    As linhas válidas atualizarão os projetos existentes com os dados fiscais da planilha.
                    @if ($ignorarLinhasComErro && $pendingCorrectionsCount > 0)
                        {{ $pendingCorrectionsCount }} {{ $pendingCorrectionsCount === 1 ? 'linha será ignorada' : 'linhas serão ignoradas' }} nesta execução.
                    @endif
                </p>
                <div class="flex gap-3">
                    <x-filament::button color="gray" wire:click="voltarStep">Voltar</x-filament::button>
                    <x-filament::button
                        color="success"
                        wire:click="avancarParaConflitos"
                        wire:loading.attr="disabled"
                        wire:target="avancarParaConflitos"
                    >
                        <span wire:loading.remove wire:target="avancarParaConflitos">
                            @if ($reviewConflictCount > 0)
                                Revisar {{ $reviewConflictCount }} Conflito(s)
                            @else
                                Importar
                            @endif
                        </span>

                        <span wire:loading.inline-flex wire:target="avancarParaConflitos" class="items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Processando...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>
    @endif

    @if ($currentStep === 55)
        @include('filament.pages.partials.import-cnpj-conflitos')
    @endif

    @if ($currentStep === 6)
        @if ($importacaoSelecionada !== [])
            @php
                $statusCabecalho = $importacaoSelecionada['status'] ?? ($resultado['status'] ?? null);
                $statusCabecalhoConfig = match($statusCabecalho) {
                    'concluido' => ['label' => 'Concluído', 'class' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-green-600/10 dark:ring-green-500/20'],
                    'staged' => ['label' => 'Aguardando confirmação', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 ring-1 ring-blue-600/10 dark:ring-blue-500/20'],
                    'confirmando' => ['label' => 'Confirmando', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-500/20'],
                    'processando' => ['label' => 'Processando', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-amber-600/10 dark:ring-amber-500/20'],
                    'pendente' => ['label' => 'Pendente', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                    'descartado' => ['label' => 'Descartado', 'class' => 'bg-gray-100 text-gray-500 dark:bg-gray-500/10 dark:text-gray-500 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                    'erro' => ['label' => 'Erro', 'class' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-red-600/10 dark:ring-red-500/20'],
                    default => ['label' => ucfirst((string) $statusCabecalho), 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400 ring-1 ring-gray-600/10 dark:ring-gray-500/20'],
                };
            @endphp

            <x-filament::card class="mb-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                Importação #{{ $importacaoSelecionada['id'] }}
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $statusCabecalhoConfig['class'] }}">
                                {{ $statusCabecalhoConfig['label'] }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Arquivo</p>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $importacaoSelecionada['arquivo'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Usuário</p>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $importacaoSelecionada['usuario'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Criada em</p>
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $importacaoSelecionada['data'] ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Execução</p>
                                <p class="font-medium text-gray-800 dark:text-gray-100">
                                    {{ $importacaoSelecionada['iniciado_em'] ?? 'Ainda não iniciada' }}
                                    @if (!empty($importacaoSelecionada['duracao']))
                                        <span class="text-xs text-gray-400 dark:text-gray-500">({{ $importacaoSelecionada['duracao'] }})</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if (in_array($statusCabecalho, ['pendente', 'processando', 'staged'], true))
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                A tela atualiza automaticamente a cada 2 segundos enquanto houver processamento ou revisão pendente.
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-filament::button wire:click="verificarStatus" color="gray">
                            Atualizar agora
                        </x-filament::button>
                        @if ($visualizandoHistorico)
                            <x-filament::button wire:click="voltarAoHistorico" color="gray" outlined>
                                Voltar ao histórico
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </x-filament::card>
        @endif

        @if (($resultado['status'] ?? '') === 'processando' || ($resultado['status'] ?? '') === 'pendente')
            <x-filament::card class="text-center py-8">
                <div wire:poll.2s="verificarStatus">
                    <div class="mx-auto w-16 h-16 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center mb-4">
                        <x-filament::loading-indicator class="w-8 h-8 text-primary-500" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Processando importação...</h2>

                    <div class="max-w-lg mx-auto my-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Progresso</span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $resultado['percentual'] ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                            <div class="bg-primary-600 h-3 rounded-full transition-all duration-500 ease-out" style="width: {{ ($resultado['percentual'] ?? 0) }}%;"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">{{ $resultado['processados'] ?? 0 }} de {{ $resultado['total'] ?? 0 }} linhas processadas</p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-2xl mx-auto my-4">
                        <div class="text-center"><p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $resultado['total'] ?? 0 }}</p><p class="text-xs text-gray-500">Total</p></div>
                        <div class="text-center"><p class="text-xl font-bold text-success-600">{{ $resultado['criados'] ?? 0 }}</p><p class="text-xs text-gray-500">Criados</p></div>
                        <div class="text-center"><p class="text-xl font-bold text-warning-600">{{ $resultado['atualizados'] ?? 0 }}</p><p class="text-xs text-gray-500">Atualizados</p></div>
                        <div class="text-center"><p class="text-xl font-bold text-danger-600">{{ $resultado['erros'] ?? 0 }}</p><p class="text-xs text-gray-500">Erros</p></div>
                    </div>

                    @if (($stagingResumo['total'] ?? 0) > 0)
                        <div class="grid grid-cols-3 gap-4 max-w-xl mx-auto mb-6">
                            <div class="text-center"><p class="text-xl font-bold text-warning-600">{{ $stagingResumo['atualizar'] ?? 0 }}</p><p class="text-xs text-gray-500">Linhas preparadas</p></div>
                            <div class="text-center"><p class="text-xl font-bold text-danger-600">{{ $stagingResumo['erro'] ?? 0 }}</p><p class="text-xs text-gray-500">Falhas detectadas</p></div>
                            <div class="text-center"><p class="text-xl font-bold text-gray-400">{{ $stagingResumo['ignorar'] ?? 0 }}</p><p class="text-xs text-gray-500">Ignoradas</p></div>
                        </div>
                    @endif

                    @if (count($atividadeRecente) > 0)
                        <div class="max-w-4xl mx-auto mt-6 text-left">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Atividade recente</h3>
                                <span class="text-xs text-gray-400 dark:text-gray-500">Últimas {{ count($atividadeRecente) }} linhas processadas</span>
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                            <th class="py-2 px-3 font-medium text-gray-500 text-xs">Hora</th>
                                            <th class="py-2 px-3 font-medium text-gray-500 text-xs">Linha</th>
                                            <th class="py-2 px-3 font-medium text-gray-500 text-xs">Código</th>
                                            <th class="py-2 px-3 font-medium text-gray-500 text-xs">Unidade</th>
                                            <th class="py-2 px-3 font-medium text-gray-500 text-xs">Ação</th>
                                            <th class="py-2 px-3 font-medium text-gray-500 text-xs">Info</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach ($atividadeRecente as $linhaRecente)
                                            <tr>
                                                <td class="py-2 px-3 text-xs text-gray-500 tabular-nums">{{ $linhaRecente['processado_em'] ?? '—' }}</td>
                                                <td class="py-2 px-3 text-xs text-gray-500 tabular-nums">{{ $linhaRecente['linha'] }}</td>
                                                <td class="py-2 px-3 text-xs font-mono text-gray-700 dark:text-gray-300">{{ $linhaRecente['codigo'] ?? '-' }}</td>
                                                <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">{{ $linhaRecente['unidade'] }}</td>
                                                <td class="py-2 px-3">
                                                    @php
                                                        $acaoRecenteConfig = match($linhaRecente['acao']) {
                                                            'atualizar' => ['label' => 'Atualizar', 'class' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400'],
                                                            'erro' => ['label' => 'Erro', 'class' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400'],
                                                            'ignorar' => ['label' => 'Ignorar', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'],
                                                            default => ['label' => $linhaRecente['acao'], 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'],
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $acaoRecenteConfig['class'] }}">
                                                        {{ $acaoRecenteConfig['label'] }}
                                                    </span>
                                                </td>
                                                <td class="py-2 px-3 text-xs text-gray-500">
                                                    @if ($linhaRecente['acao'] === 'erro')
                                                        {{ $linhaRecente['erro']['msg'] ?? 'Erro ao preparar a linha.' }}
                                                    @else
                                                        {{ count($linhaRecente['dados'] ?? []) }} campos mapeados
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::card>
        @elseif (($resultado['status'] ?? '') === 'staged')
            <x-filament::card class="text-center py-8" wire:poll.2s="verificarStatus">
                @php
                    $stagingFilters = ['todos' => 'Todos'];

                    foreach ([
                        'criar' => 'Criar',
                        'atualizar' => 'A atualizar',
                        'atualizado' => 'Atualizadas',
                        'erro' => 'Erros',
                        'ignorar' => 'Ignoradas',
                        'outros' => 'Outras',
                    ] as $filtro => $label) {
                        if (($stagingResumo[$filtro] ?? 0) > 0) {
                            $stagingFilters[$filtro] = $label;
                        }
                    }
                @endphp

                <div class="mx-auto w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-clipboard-document-check class="w-10 h-10 text-amber-500" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Dados preparados para importação</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Revise os dados abaixo antes de confirmar. Nada foi gravado na base ainda.</p>

                    <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto mb-6">
                        <div class="text-center"><p class="text-2xl font-bold text-warning-600">{{ $stagingResumo['atualizar'] ?? 0 }}</p><p class="text-xs text-gray-500">A atualizar</p></div>
                        <div class="text-center"><p class="text-2xl font-bold text-danger-600">{{ $stagingResumo['erro'] ?? 0 }}</p><p class="text-xs text-gray-500">Com erro</p></div>
                        <div class="text-center"><p class="text-2xl font-bold text-gray-400">{{ $stagingResumo['ignorar'] ?? 0 }}</p><p class="text-xs text-gray-500">Ignoradas</p></div>
                    </div>

                    <div class="flex justify-center gap-2 mb-4">
                        @foreach ($stagingFilters as $filtro => $label)
                            <button wire:click="$set('stagingFiltro', '{{ $filtro }}')" @class([
                                'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                'bg-primary-600 text-white' => $stagingFiltro === $filtro,
                            'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $stagingFiltro !== $filtro,
                        ])>{{ $label }}</button>
                    @endforeach
                </div>

                @if (count($stagingRows) > 0)
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 text-left">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Linha</th>
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Código</th>
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Unidade</th>
                                    <th class="py-2 px-3 font-medium text-gray-500 text-xs">Ação</th>
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
                                                    'criar' => ['label' => 'Criar', 'class' => 'bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400'],
                                                    'atualizar' => ['label' => 'Atualizar', 'class' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400'],
                                                    'atualizado' => ['label' => 'Atualizada', 'class' => 'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400'],
                                                    'erro' => ['label' => 'Erro', 'class' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400'],
                                                    'ignorar' => ['label' => 'Ignorada', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'],
                                                    default => ['label' => \Illuminate\Support\Str::headline($sRow['acao']), 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'],
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $acaoConfig['class'] }}">{{ $acaoConfig['label'] }}</span>
                                        </td>
                                        <td class="py-2 px-3 text-xs text-gray-500">
                                            @if ($sRow['acao'] === 'erro')
                                                {{ $sRow['erro']['msg'] ?? '' }}
                                            @elseif ($sRow['acao'] === 'atualizado')
                                                Importada com sucesso
                                            @elseif ($sRow['acao'] === 'atualizar')
                                                {{ count($sRow['dados'] ?? []) }} campos
                                            @elseif ($sRow['acao'] === 'ignorar')
                                                Linha pulada na importação
                                            @elseif ($sRow['acao'] === 'criar')
                                                Preparada para criação
                                            @else
                                                {{ $sRow['processado_em'] ? 'Processada às '.$sRow['processado_em'] : 'Processada' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center mt-3">
                        <x-filament::button wire:click="stagingPaginaAnterior" color="gray" size="sm" :disabled="$stagingPage <= 1">Anterior</x-filament::button>
                        <span class="text-xs text-gray-500">Página {{ $stagingPage }}</span>
                        <x-filament::button wire:click="stagingProximaPagina" color="gray" size="sm" :disabled="count($stagingRows) < 50">Próxima</x-filament::button>
                    </div>
                @endif

                <div class="flex justify-center gap-4 mt-6">
                    <x-filament::button wire:click="descartarImportacao" color="danger" wire:confirm="Descartar importação? Ela será encerrada e ficará disponível apenas para consulta no histórico.">Descartar</x-filament::button>
                    @if (($stagingResumo['criar'] ?? 0) > 0 || ($stagingResumo['atualizar'] ?? 0) > 0)
                        <x-filament::button wire:click="confirmarImportacao" color="success" wire:confirm="Confirmar importação? Os dados serão gravados na base.">Confirmar Importação</x-filament::button>
                    @endif
                </div>
            </x-filament::card>

            @if (($stagingResumo['erro'] ?? 0) > 0 && count($projetosCorrecao) > 0)
                <x-filament::card class="mt-6 text-left">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Projetos a criar ({{ count($projetosCorrecao) }})</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Crie os projetos faltantes e depois reimporte as linhas com erro.</p>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Código</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Nome</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">UF</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Cidade</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Etapa</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500 w-24">Ação</th>
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
                                                <select wire:change="atualizarEstadoProjetoCorrecao({{ $i }}, $event.target.value)" class="text-xs rounded-lg border-warning-400 dark:border-warning-600 dark:bg-gray-800 dark:text-gray-300 w-full">
                                                    <option value="">Selecionar...</option>
                                                    @foreach ($estadosDisponiveis as $est)
                                                        <option value="{{ $est['id'] }}">{{ $est['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-xs text-gray-700 dark:text-gray-300">
                                            @if (!$proj['criado'] && !$proj['cidade_id'])
                                                <input type="text" wire:model.blur="projetosCorrecao.{{ $i }}.cidade_nome" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full" placeholder="Nome da cidade" />
                                            @else
                                                {{ $proj['cidade_nome'] }}
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-xs">
                                            @if (!$proj['criado'])
                                                <select wire:model="projetosCorrecao.{{ $i }}.etapa_id" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full">
                                                    @foreach ($etapasDisponiveis as $etapaId => $etapaNome)
                                                        <option value="{{ $etapaId }}">{{ $etapaNome }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3">
                                            @if ($proj['criado'])
                                                <span class="inline-flex items-center gap-1 text-xs font-medium text-success-600"><x-heroicon-s-check-circle class="w-4 h-4" /> Criado</span>
                                            @else
                                                <x-filament::button wire:click="criarProjetoCorrecao({{ $i }})" size="xs" color="gray" :disabled="!$proj['estado_id']">Criar</x-filament::button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between mt-4">
                        <x-filament::button wire:click="criarTodosProjetos" color="gray" size="sm">Criar Todos</x-filament::button>
                        <div class="flex gap-2">
                            @if (count($errosAgrupados['projeto_nao_criado'] ?? []) > 0 && $this->canRetryProjectErrors('projeto_nao_criado'))
                                <x-filament::button wire:click="reimportarComErros('projeto_nao_criado')" color="primary" size="sm">Reimportar {{ count($errosAgrupados['projeto_nao_criado']) }} linhas não criadas</x-filament::button>
                            @endif
                            @if (count($errosAgrupados['projeto_nao_encontrado'] ?? []) > 0 && $this->canRetryProjectErrors('projeto_nao_encontrado'))
                                <x-filament::button wire:click="reimportarComErros('projeto_nao_encontrado')" color="primary" size="sm">Reimportar {{ count($errosAgrupados['projeto_nao_encontrado']) }} linhas não encontradas</x-filament::button>
                            @endif
                            @if (($stagingResumo['erro'] ?? 0) > 0)
                                <x-filament::button wire:click="reimportarComErros" color="gray" size="sm">Reimportar todos os erros</x-filament::button>
                            @endif
                        </div>
                    </div>
                </x-filament::card>
            @endif
        @elseif (($resultado['status'] ?? '') === 'concluido')
            <x-filament::card class="text-center py-8">
                @php
                    $stagingFilters = ['todos' => 'Todos'];

                    foreach ([
                        'criar' => 'Criar',
                        'atualizar' => 'Pendentes',
                        'atualizado' => 'Atualizadas',
                        'erro' => 'Erros',
                        'ignorar' => 'Ignoradas',
                        'outros' => 'Outras',
                    ] as $filtro => $label) {
                        if (($stagingResumo[$filtro] ?? 0) > 0) {
                            $stagingFilters[$filtro] = $label;
                        }
                    }
                @endphp

                <div class="mx-auto w-16 h-16 rounded-full bg-success-50 dark:bg-success-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-check-circle class="w-10 h-10 text-success-500" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Importação concluída</h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto my-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $resultado['total'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Total</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-success-600">{{ $resultado['atualizados'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Atualizados</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-500 dark:text-gray-300">{{ $stagingResumo['ignorar'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Ignoradas</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-danger-600">{{ $resultado['erros'] ?? 0 }}</p>
                        <p class="text-xs text-gray-500">Erros</p>
                    </div>
                </div>

                @if (! empty($resultado['detalhes_erros']))
                    <div class="max-w-4xl mx-auto text-left mt-6 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Linha</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-500">Mensagem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($resultado['detalhes_erros'] as $erro)
                                    <tr>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $erro['linha'] ?? '—' }}</td>
                                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $erro['msg'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if (($stagingResumo['total'] ?? 0) > 0)
                    <div class="max-w-5xl mx-auto text-left mt-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Linhas processadas</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Consulte todas as linhas desta importação, incluindo atualizadas, puladas, com erro e demais situações.</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @foreach ($stagingFilters as $filtro => $label)
                                    <button wire:click="$set('stagingFiltro', '{{ $filtro }}')" @class([
                                        'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                        'bg-primary-600 text-white' => $stagingFiltro === $filtro,
                                        'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $stagingFiltro !== $filtro,
                                    ])>{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>

                        @if (count($stagingRows) > 0)
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                            <th class="text-left py-2 px-3 font-medium text-gray-500">Linha</th>
                                            <th class="text-left py-2 px-3 font-medium text-gray-500">Código</th>
                                            <th class="text-left py-2 px-3 font-medium text-gray-500">Unidade</th>
                                            <th class="text-left py-2 px-3 font-medium text-gray-500">Situação</th>
                                            <th class="text-left py-2 px-3 font-medium text-gray-500">Detalhe</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach ($stagingRows as $sRow)
                                            <tr>
                                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300 tabular-nums">{{ $sRow['linha'] }}</td>
                                                <td class="py-2 px-3 font-mono text-gray-700 dark:text-gray-300">{{ $sRow['codigo'] ?? '-' }}</td>
                                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300">{{ $sRow['unidade'] }}</td>
                                                <td class="py-2 px-3">
                                                    @php
                                                        $acaoConfig = match($sRow['acao']) {
                                                            'criar' => ['label' => 'Criar', 'class' => 'bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400'],
                                                            'atualizar' => ['label' => 'Pendente', 'class' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400'],
                                                            'atualizado' => ['label' => 'Atualizada', 'class' => 'bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400'],
                                                            'erro' => ['label' => 'Erro', 'class' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400'],
                                                            'ignorar' => ['label' => 'Ignorada', 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'],
                                                            default => ['label' => \Illuminate\Support\Str::headline($sRow['acao']), 'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400'],
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $acaoConfig['class'] }}">{{ $acaoConfig['label'] }}</span>
                                                </td>
                                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300">
                                                    @if ($sRow['acao'] === 'erro')
                                                        {{ $sRow['erro']['msg'] ?? 'Erro ao processar a linha.' }}
                                                    @elseif ($sRow['acao'] === 'ignorar')
                                                        Linha pulada na importação
                                                    @elseif ($sRow['acao'] === 'atualizado')
                                                        Importada com sucesso
                                                    @elseif ($sRow['acao'] === 'atualizar')
                                                        Aguardava confirmação manual
                                                    @elseif ($sRow['acao'] === 'criar')
                                                        Preparada para criação
                                                    @else
                                                        {{ $sRow['processado_em'] ? 'Processada às '.$sRow['processado_em'] : 'Linha processada' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-between items-center mt-3">
                                <x-filament::button wire:click="stagingPaginaAnterior" color="gray" size="sm" :disabled="$stagingPage <= 1">Anterior</x-filament::button>
                                <span class="text-xs text-gray-500">Página {{ $stagingPage }}</span>
                                <x-filament::button wire:click="stagingProximaPagina" color="gray" size="sm" :disabled="count($stagingRows) < 50">Próxima</x-filament::button>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="max-w-2xl mx-auto mt-6 text-sm text-gray-500 dark:text-gray-400">
                        O detalhamento linha a linha não está disponível para esta importação antiga.
                    </p>
                @endif

                <div class="flex justify-center gap-3 mt-6">
                    <x-filament::button color="gray" wire:click="novaImportacao">Nova Importação</x-filament::button>
                    <x-filament::button tag="a" href="{{ url('/admin/cadastrar-cnpj') }}">Voltar ao Cadastro de CNPJ</x-filament::button>
                </div>
            </x-filament::card>

            @if (($resultado['erros'] ?? 0) > 0)
                @if (count($projetosCorrecao) > 0)
                    <x-filament::card class="mt-6 text-left">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">Projetos a criar ({{ count($projetosCorrecao) }})</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Estes projetos não existem no sistema. Corrija os dados e crie-os para poder reimportar as linhas.</p>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Código</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Nome</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">UF</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Cidade</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Etapa</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-24">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($projetosCorrecao as $i => $proj)
                                        <tr @class(['bg-success-50/50 dark:bg-success-900/10' => $proj['criado']])>
                                            <td class="py-2 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $proj['codigo'] }}</td>
                                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300 text-xs">{{ $proj['nome'] }}</td>
                                            <td class="py-2 px-3">
                                                @if ($proj['criado'])
                                                    <span class="text-gray-700 dark:text-gray-300 text-xs">{{ $proj['uf'] }}</span>
                                                @elseif ($proj['estado_id'])
                                                    <span class="text-gray-700 dark:text-gray-300 text-xs">{{ $proj['uf'] }}</span>
                                                @else
                                                    <select wire:change="atualizarEstadoProjetoCorrecao({{ $i }}, $event.target.value)" class="text-xs rounded-lg border-warning-400 dark:border-warning-600 dark:bg-gray-800 dark:text-gray-300 w-full">
                                                        <option value="">Selecionar...</option>
                                                        @foreach ($estadosDisponiveis as $est)
                                                            <option value="{{ $est['id'] }}">{{ $est['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                @if ($proj['criado'])
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">{{ $proj['cidade_nome'] }}</span>
                                                @else
                                                    <input type="text" wire:model.blur="projetosCorrecao.{{ $i }}.cidade_nome" placeholder="Nome da cidade" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full" />
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                @if (!$proj['criado'])
                                                    <select wire:model="projetosCorrecao.{{ $i }}.etapa_id" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 w-full">
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
                                                    <x-filament::button wire:click="criarProjetoCorrecao({{ $i }})" size="xs" color="success">Criar</x-filament::button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if (collect($projetosCorrecao)->where('criado', false)->count() > 0)
                            <div class="flex justify-end mt-3">
                                <x-filament::button wire:click="criarTodosProjetos" size="sm" color="success">Criar Todos</x-filament::button>
                            </div>
                        @endif
                    </x-filament::card>
                @endif

                @if (count($errosAgrupados['projeto_nao_criado'] ?? []) > 0)
                    <x-filament::card class="mt-6 text-left">
                        <h3 class="text-sm font-semibold text-warning-600 mb-1">Linhas não importadas — projeto não criado ({{ count($errosAgrupados['projeto_nao_criado']) }})</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Após criar os projetos acima, clique em “Reimportar” para processar estas linhas.</p>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-20">Linha</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-28">Código</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500">Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach (array_slice($errosAgrupados['projeto_nao_criado'], 0, 50) as $erro)
                                        <tr>
                                            <td class="py-1.5 px-3 text-xs text-gray-400">{{ $erro['linha'] ?? '—' }}</td>
                                            <td class="py-1.5 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $erro['codigo'] ?? '—' }}</td>
                                            <td class="py-1.5 px-3 text-xs text-gray-500">{{ $erro['msg'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($this->canRetryProjectErrors('projeto_nao_criado'))
                            <div class="flex justify-end mt-4">
                                <x-filament::button wire:click="reimportarComErros('projeto_nao_criado')" color="warning">Reimportar {{ count($errosAgrupados['projeto_nao_criado']) }} linhas</x-filament::button>
                            </div>
                        @endif
                    </x-filament::card>
                @endif

                @if (count($errosAgrupados['projeto_nao_encontrado'] ?? []) > 0)
                    <x-filament::card class="mt-6 text-left">
                        <h3 class="text-sm font-semibold text-warning-600 mb-1">Linhas não importadas — projeto não encontrado ({{ count($errosAgrupados['projeto_nao_encontrado']) }})</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Após criar os projetos acima, clique em “Reimportar” para processar estas linhas.</p>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-20">Linha</th>
                                        <th class="text-left py-2 px-3 font-medium text-gray-500 w-28">Código</th>
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

                        @if ($this->canRetryProjectErrors('projeto_nao_encontrado'))
                            <div class="flex justify-end mt-4">
                                <x-filament::button wire:click="reimportarComErros('projeto_nao_encontrado')" color="warning">Reimportar {{ count($errosAgrupados['projeto_nao_encontrado']) }} linhas</x-filament::button>
                            </div>
                        @endif
                    </x-filament::card>
                @endif

                @if (count($errosAgrupados['outro'] ?? []) > 0)
                    <x-filament::card class="mt-6 text-left">
                        <h3 class="text-sm font-semibold text-danger-600 mb-2">Outros erros ({{ count($errosAgrupados['outro']) }})</h3>
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
            @endif

        @elseif (($resultado['status'] ?? '') === 'erro')
            <x-filament::card class="text-center py-8">
                <div class="mx-auto w-16 h-16 rounded-full bg-danger-50 dark:bg-danger-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-x-circle class="w-10 h-10 text-danger-500" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Erro na importação</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Ocorreu um erro ao processar a planilha. Verifique os dados e tente novamente.</p>
                <x-filament::button wire:click="novaImportacao" color="gray">Tentar novamente</x-filament::button>
            </x-filament::card>

        @else
            <x-filament::card class="text-center py-8">
                <div class="mx-auto w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-900/20 flex items-center justify-center mb-4">
                    <x-heroicon-o-information-circle class="w-10 h-10 text-gray-400" />
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Aguardando atualização</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Atualize o status da importação para continuar acompanhando.</p>
                <div class="flex justify-center gap-3">
                    <x-filament::button wire:click="verificarStatus">Atualizar status</x-filament::button>
                    <x-filament::button color="gray" wire:click="novaImportacao">Nova Importação</x-filament::button>
                </div>
            </x-filament::card>
        @endif
    @endif
</x-filament::page>
