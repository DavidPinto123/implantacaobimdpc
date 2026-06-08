<x-filament-panels::page>
    <x-filament::card wire:ignore.self>

        <div class="flex gap-x-4 mb-4">
            <div class="flex items-center space-x-2">
                <span class="w-4 h-4 block rounded" style="background-color: #10B981;"></span>
                <span class="text-xs">Realizado</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-4 h-4 block rounded" style="background-color: #EF4444;"></span>
                <span class="text-xs">Não Realizado</span>
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

            @foreach($meses as $numero => $nome)
                @php
                    $registrosVar = "registros{$nome}";
                    $contadorVar = "contador{$nome}";

                    $registros = $$registrosVar ?? [];
                    $contador = ${$contadorVar} ?? 0;
                @endphp

                <div class="border-r border-gray-300 dark:border-gray-700 last:border-r-0 pr-2">
                    <div class="font-bold text-sm uppercase mb-2 text-center">
                        {{ $nome }} ({{ $contador }})
                    </div>

                    <div class="space-y-2 overflow-y-auto pr-1">
                        @foreach($registros as $registro)
                            @php
                                // pega o status em minúsculo e sem espaços
                                $status = mb_strtolower(trim($registro['posse_status'] ?? ''));

                                $borderColor = match(true) {
                                    str_contains($status, 'não realizado') => '#EF4444',  // vermelho
                                    str_contains($status, 'realizado')     => '#10B981',  // verde
                                    default                                => '#9CA3AF',  // cinza
                                };                        // cinza

                                 // Data da posse (aceita d/m/Y e Y-m-d)
                                $posseData = null;
                                if (!empty($registro['posse_data'])) {
                                    try {
                                        $posseData = \Carbon\Carbon::createFromFormat('d/m/Y', $registro['posse_data']);
                                    } catch (\Exception $e) {
                                        try {
                                            $posseData = \Carbon\Carbon::parse($registro['posse_data']);
                                        } catch (\Exception $e) {
                                            $posseData = null;
                                        }
                                    }
                                }

                                $dias = null;
                                if ($posseData) {
                                    if ($posseData->isToday()) {
                                        $dias = 0;
                                    } else {
                                        $dias = \Carbon\Carbon::now()->startOfDay()->diffInDays($posseData->startOfDay(), false);
                                    }
                                }
                            @endphp

                            <div
                                class="w-full shadow bg-white dark:bg-gray-800 p-2 rounded border-2 cursor-pointer transition text-[9px] leading-snug"
                                style="border-color: {{ $borderColor }}; --hover-color: {{ $borderColor }};"
                                onmouseover="this.style.backgroundColor=this.style.getPropertyValue('--hover-color')"
                                onmouseout="this.style.backgroundColor=''"
                                onclick="window.location.href='{{ \App\Filament\Resources\ProjetoResource::getUrl('view', ['record' => $registro['id']]) }}'">
                                
                                <p class="font-bold text-sm"> {{ $registro['nome'] }}</p>
                                <p class="text-xs">Nova Sigla: {{ $registro['nova_sigla'] }}</p>

                                @if($posseData)
                                    <p class="text-xs">
                                        Data de Posse:
                                        <span class="font-bold">{{ $posseData->format('d/m/Y') }}</span>
                                    </p>

                                    <p class="text-xs">
                                        @if($dias > 0)
                                            Faltam <span class="font-bold">{{ $dias }}</span> dias para a posse
                                        @elseif($dias === 0)
                                            A posse é<span class="font-bold text-green-600"> hoje</span>
                                        @else
                                            <span class="font-bold text-red-600">Prazo vencido</span>
                                        @endif
                                    </p>
                                @else
                                    <p class="text-xs italic text-gray-500">Sem data de posse</p>
                                @endif

                                
                                <p class="text-xs">Status da Posse: <span class="font-bold">{{ $registro['posse_status'] ?? '-' }}</span></p>
                                <p class="text-xs">Engenharia: <span class="font-bold">{{ $registro['posse_engenharia'] ?? '-' }}</span></p>
                                <p class="text-xs">Legalização: <span class="font-bold">{{ $registro['posse_legalizacao'] ?? '-' }}</span></p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::card>
</x-filament-panels::page>
