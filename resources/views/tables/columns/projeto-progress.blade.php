@php
    $registros = \App\Models\ControlePedido::where('projeto_id', $record->id)
        ->select('pedidos')
        ->get();

    $total = 0;
    $contratados = 0;

    foreach ($registros as $registro) {
        if (!is_array($registro->pedidos)) {
            continue;
        }

        foreach ($registro->pedidos as $valor) {
            $total++;

            if ($valor === true) {
                $contratados++;
            }
        }
    }

    $percentual = $total > 0
        ? intval(($contratados / $total) * 100)
        : 0;

    // Cores fixas (HEX) para evitar purge do Tailwind
    $color = match(true) {
        $percentual < 30 => '#ef4444',  // vermelho
        $percentual < 70 => '#f59e0b',  // amarelo
        default => '#10b981',          // verde
    };
@endphp

<div class="w-full">
    <div class="flex justify-between text-xs mb-1">
        <span>{{ $contratados }} / {{ $total }}</span>
        <span>{{ $percentual }}%</span>
    </div>

    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
        <div
            class="h-3 rounded-full transition-all duration-500"
            style="width: {{ $percentual }}%; background-color: {{ $color }};">
        </div>
    </div>
</div>