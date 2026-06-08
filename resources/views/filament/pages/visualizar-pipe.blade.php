<x-filament-panels::page>
    <x-filament::card>
        <div class="not-prose">  {{-- impede o "prose" do card de reformatar seu conteúdo --}}
            <div class="flex gap-x-4 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-4 h-4 block rounded" style="background-color: #FBBF24;"></span>
                    <span class="text-xs">Obras</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-4 h-4 block rounded" style="background-color: #EF4444;"></span>
                    <span class="text-xs">Em processo</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-4 h-4 block rounded" style="background-color: #10B981;"></span>
                    <span class="text-xs">Inaugurada</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="w-4 h-4 block rounded" style="background-color: #9CA3AF;"></span>
                    <span class="text-xs">Sem status</span>
                </div>
            </div>

            <div class="flex gap-x-4 p-2 overflow-x-auto">
                @php
                    $meses = [
                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março',
                        4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
                        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro',
                        10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
                    ];
                @endphp

                @foreach ($meses as $numero => $nome)
                    @php
                        $registrosVar = "registros{$nome}";
                        $contadorVar = "contador{$nome}";
                    @endphp

                    <div class="border-r border-gray-300 dark:border-gray-700 last:border-r-0 pr-1">
                        <div class="font-bold text-sm uppercase mb-2 text-center">
                            {{ $nome }} ({{ $$contadorVar }})
                        </div>

                        <div class="space-y-2 overflow-y-auto pr-1">
                            @foreach ($$registrosVar as $registro)
                                @php
                                    $status = strtolower($registro['status'] ?? '');
                                    $borderColor = match (true) {
                                        str_contains($status, 'obra') => '#FBBF24',
                                        str_contains($status, 'processo') => '#EF4444',
                                        str_contains($status, 'inaugurada') => '#10B981',
                                        default => '#9CA3AF',
                                    };
                                @endphp

                                <div
                                    class="w-full shadow bg-white dark:bg-gray-800 p-2 rounded border-2 cursor-pointer transition text-[9px] leading-snug"
                                    style="border-color: {{ $borderColor }}; --hover-color: {{ $borderColor }};"
                                    onmouseover="this.style.backgroundColor=this.style.getPropertyValue('--hover-color')"
                                    onmouseout="this.style.backgroundColor=''"
                                    onclick="window.location.href='{{ \App\Filament\Resources\ProjetoResource::getUrl('view', ['record' => $registro['id']]) }}'">
                                    <p class="font-bold text-sm">{{ $registro['nome'] }}</p>
                                    <p class="text-xs">Nova Sigla: {{ $registro['nova_sigla'] }}</p>
                                    <p class="text-xs">I.O: {{ $registro['inicio_obra'] }}</p>
                                    <p class="text-xs">E.O: <span class="font-bold">{{ $registro['entrega_obra'] }}</span></p>
                                    <p class="text-xs">Inauguração: {{ $registro['inauguracao'] }}</p>
                                    <p class="font-bold text-xs">Status: {{ $registro['status'] ?? 'N/A' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::card>
</x-filament-panels::page>
