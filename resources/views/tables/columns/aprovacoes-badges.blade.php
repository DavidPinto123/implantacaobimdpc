{{-- resources/views/tables/columns/aprovacoes-badges.blade.php --}}
<div class="flex flex-wrap gap-1">
    @forelse ($getState() as $item)
        <x-filament::badge :color="$item['color']" :icon="$item['icon']">
            {{ $item['role'] }}: {{ $item['status'] }}
        </x-filament::badge>
    @empty
        {{-- opcional: mostrar nada ou uma badge neutra --}}
        {{-- <x-filament::badge color="gray" icon="heroicon-o-minus">Sem aprovações</x-filament::badge> --}}
    @endforelse
</div>
