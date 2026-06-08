<x-filament-panels::page>
    <div>
        <div class="mb-4">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Projetos cadastrados</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Clique em Editar para abrir um modal com os dados fiscais do projeto.</p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
