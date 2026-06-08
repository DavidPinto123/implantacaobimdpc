<div class="mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl fi-header-heading font-bold tracking-tight text-black dark:text-white">
                CC - CONTROLE DE CUSTOS ADICIONAIS
            </h1>
        </div>
        <x-filament::actions
            :actions="$this->getCachedHeaderActions()"
        />
    </div>

    <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-white/10">
        <form wire:submit.prevent novalidate>
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] items-end">
                <div class="lg:col-span-2 [&_label]:text-gray-950 [&_.fi-input-wrp]:bg-gray-50 [&_.fi-input-wrp]:ring-gray-200 [&_.fi-input-wrp]:text-gray-950 dark:[&_label]:text-gray-200 dark:[&_.fi-input-wrp]:bg-gray-800 dark:[&_.fi-input-wrp]:ring-gray-700 dark:[&_.fi-input-wrp]:text-gray-100">
                    {{ $this->form }}
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="limparFiltros"
                        class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-4 py-2 text-sm font-medium text-gray-950 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
                    >
                        Limpar filtros
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
