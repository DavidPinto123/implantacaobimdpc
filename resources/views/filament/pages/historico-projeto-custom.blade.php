<x-filament-panels::page>
@forelse($historico as $fase => $registros)
    <div x-data="{ open: true }" class="mb-6 border rounded-xl overflow-hidden shadow-sm dark:border-gray-700">
        <button @click="open = !open" class="w-full flex justify-between items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 text-left dark:bg-gray-800 dark:hover:bg-gray-700">
            <h2 class="text-lg font-semibold text-primary dark:text-white">{{ $fase }}</h2>
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform rotate-180 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 011.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M14.77 12.79a.75.75 0 01-1.06-.02L10 8.707l-3.71 4.06a.75.75 0 01-1.08-1.04l4.25-4.65a.75.75 0 011.08 0l4.25 4.65a.75.75 0 01-.02 1.06z" clip-rule="evenodd" />
            </svg>
        </button>

        <div x-show="open" x-cloak x-transition class="space-y-4 px-4 py-4 bg-white dark:bg-gray-900">
            @foreach($registros as $registro)
                <div class="bg-white shadow-sm rounded-xl p-6 border-l-4 border-primary dark:bg-gray-800">
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-2 dark:text-gray-300">
                        <span>{{ $registro->created_at->format('d/m/Y') }}</span>
                        <span>{{ $registro->created_at->format('H:i') }}</span>
                    </div>
                    <div class="text-gray-800 dark:text-white">
                        {!! $registro->descricao_formatada !!}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <p class="text-gray-600 dark:text-gray-300">Nenhum histórico encontrado.</p>
@endforelse
</x-filament-panels::page>
