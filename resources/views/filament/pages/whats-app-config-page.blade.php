<x-filament-panels::page>
    <form wire:submit="salvar">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" color="primary">
                Salvar configuração
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
