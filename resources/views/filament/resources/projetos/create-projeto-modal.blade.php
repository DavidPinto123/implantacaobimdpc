<x-filament::page>
    <!-- Aqui vai o seu form do Filament -->

    <x-filament::modal wire:model="abrirModalDuplicados" width="lg">
        <x-slot name="heading">Projetos já cadastrados</x-slot>

        <div class="space-y-2">
            @foreach($projetosDuplicados as $proj)
                <div class="flex items-center justify-between p-2 border rounded">
                    <span>{{ $proj['nome'] }} ({{ $proj['codigo'] }})</span>
                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.resources.projetos.edit', $proj['id']) }}"
                    >
                        Editar
                    </x-filament::button>
                </div>
            @endforeach
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-filament::button wire:click="$set('abrirModalDuplicados', false)">
                    Cadastrar mesmo assim
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament::page>
