<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ($stats as $stat)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 flex flex-col gap-1 shadow-sm">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $stat['label'] }}</span>
                    <span class="text-3xl font-bold" style="color: {{ $stat['color'] }}">{{ $stat['value'] }}</span>
                    <span class="text-xs text-gray-400">{{ $stat['sub'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Tabela de mensagens recentes --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Mensagens recentes</h2>
                <span class="text-xs text-gray-400">Últimas {{ count($mensagens) }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Direção</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Telefone</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Mensagem</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($mensagens as $msg)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3">
                                    @if ($msg->direcao === 'ENVIADA')
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                            Enviada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            Recebida
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono text-xs">
                                    {{ $msg->telefone }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                    {{ $msg->mensagem }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusMap = [
                                            'ENVIADA'   => ['bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', 'Enviada'],
                                            'ENTREGUE'  => ['bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', 'Entregue'],
                                            'LIDA'      => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'Lida'],
                                            'FALHA'     => ['bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'Falha'],
                                        ];
                                        $s = $statusMap[$msg->status_entrega] ?? ['bg-gray-100 text-gray-600', $msg->status_entrega ?? '—'];
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $s[0] }}">{{ $s[1] }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                                    {{ $msg->created_at->setTimezone('America/Sao_Paulo')->format('d/m/y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-400 text-sm">Nenhuma mensagem registrada ainda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
