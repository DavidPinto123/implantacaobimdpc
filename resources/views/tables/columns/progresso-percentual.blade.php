@php
    $value = (int) $getState();

    $corConcluido = match (true) {
        $value < 30 => 'rgb(239, 68, 68)',   // vermelho
        $value < 70 => 'rgb(234, 179, 8)',   // amarelo
        default => 'rgb(34, 197, 94)',       // verde
    };
@endphp

<div class="w-full">
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded h-4 overflow-hidden">
        
        <div 
            class="flex items-center justify-center text-white text-xs font-semibold transition-all duration-300"
            style="justify-content: center; background-color: {{ $corConcluido }}; width: {{ $value }}%;">
            
            {{ $value }}%
        
        </div>

    </div>
</div>