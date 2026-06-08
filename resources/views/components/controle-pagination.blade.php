@props([
    'paginator',
    'itemLabel' => 'registro(s)',
    'pageProperty' => 'porPagina',
    'pageOptions' => [25, 50, 100],
])

@once
    @push('styles')
        <style>
            .gs-controle-page-size-select { color-scheme: light; }
            .dark .gs-controle-page-size-select {
                color-scheme: dark;
                background-color: #2a2a2a !important;
                color: #f3f4f6;
            }
            .gs-controle-page-size-select option {
                background-color: #ffffff;
                color: #111827;
            }
            .dark .gs-controle-page-size-select option {
                background-color: #2a2a2a;
                color: #f3f4f6;
            }
        </style>
    @endpush
@endonce

<div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5 border-t border-gray-200 dark:border-white/5 bg-gray-50/60 dark:bg-white/[0.02]">
    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
        <span>
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            de {{ $paginator->total() }} {{ $itemLabel }}
        </span>
        <span class="text-gray-300 dark:text-white/10">|</span>
        <label class="flex items-center gap-1.5">
            Por página:
            <select
                wire:model.live="{{ $pageProperty }}"
                class="gs-controle-page-size-select px-2 py-1 rounded-md border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 text-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            >
                @foreach($pageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="flex items-center gap-1">
        <button
            wire:click="gotoPage(1)"
            @disabled($paginator->onFirstPage())
            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            title="Primeira página"
        >@svg('heroicon-m-chevron-double-left', 'w-3.5 h-3.5')</button>

        <button
            wire:click="previousPage"
            @disabled($paginator->onFirstPage())
            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            title="Página anterior"
        >@svg('heroicon-m-chevron-left', 'w-3.5 h-3.5')</button>

        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $start = max(1, $currentPage - 2);
            $end = min($lastPage, $currentPage + 2);
        @endphp

        @for($page = $start; $page <= $end; $page++)
            <button
                wire:click="gotoPage({{ $page }})"
                @class([
                    'inline-flex items-center justify-center w-7 h-7 rounded-md text-xs font-medium transition-colors',
                    'bg-primary-600 text-white shadow-sm' => $page === $currentPage,
                    'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10' => $page !== $currentPage,
                ])
            >{{ $page }}</button>
        @endfor

        <button
            wire:click="nextPage"
            @disabled(! $paginator->hasMorePages())
            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            title="Próxima página"
        >@svg('heroicon-m-chevron-right', 'w-3.5 h-3.5')</button>

        <button
            wire:click="gotoPage({{ $paginator->lastPage() }})"
            @disabled(! $paginator->hasMorePages())
            class="inline-flex items-center justify-center w-7 h-7 rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            title="Última página"
        >@svg('heroicon-m-chevron-double-right', 'w-3.5 h-3.5')</button>
    </div>
</div>
