@php
    $value = (int) $getState();
    $value = max(0, min(100, $value)); // garante entre 0 e 100

    $restante = 100 - $value;

    // Cor do concluído (dinâmica)
    $corConcluido =  '#22c55e';

    // Restante sempre vermelho
    $corRestante = '#ef4444';
@endphp

<div class="w-full">
    <div class="w-full flex rounded h-4 overflow-hidden text-xs font-semibold">

        {{-- Parte concluída --}}
        @if($value > 0)
            <div
                class="flex items-center justify-center text-white transition-all duration-500"
                style="justify-content: center; background-color: {{ $corConcluido }}; width: {{ $value }}%;">
                {{ $value }}%
            </div>
        @endif

        {{-- Parte restante --}}
        @if($restante > 0)
            <div
                class="flex items-center justify-center text-white"
                style="justify-content: center; background-color: {{ $corRestante }}; width: {{ $restante }}%;">
                {{ $restante }}%
            </div>
        @endif

    </div>
</div>