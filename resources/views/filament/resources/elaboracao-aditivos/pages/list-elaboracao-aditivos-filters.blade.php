<div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl fi-header-heading font-bold tracking-tight text-gray-950 dark:text-white">
                ELABORAÇÃO DE ADITIVOS DE OBRA
            </h1>
        </div>
        <x-filament::actions
            :actions="$this->getCachedHeaderActions()"
        />
    </div>
<div class="rounded-2xl bg-white dark:bg-gray-900 p-4 shadow-sm ring-1 ring-white/10">
    <div class="lg:col-span-2 [&_label]:text-slate-950 [&_.fi-input-wrp]:bg-slate-100 [&_.fi-input-wrp]:ring-0 [&_.fi-input-wrp]:text-slate-950 dark:[&_label]:text-slate-200 dark:[&_.fi-input-wrp]:bg-slate-900 dark:[&_.fi-input-wrp]:text-white">
        {{ $this->form }}
    </div>

    <div class="mt-4 flex justify-end">
        <button
            type="button"
            wire:click="limparFiltros"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-950/5 px-4 py-2 text-sm font-medium text-slate-950 transition hover:bg-slate-950/10 dark:bg-white/10 dark:text-white dark:hover:bg-white/15"
        >
            Limpar filtros
        </button>
    </div>
</div>