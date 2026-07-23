@php
    $comentarios = $ambientacao->imagens
        ->flatMap(fn ($imagem) => $imagem->comentarios->map(fn ($comentario) => [
            'comentario' => $comentario,
            'imagem' => $imagem,
        ]))
        ->sortByDesc(fn ($item) => $item['comentario']->created_at)
        ->take(5);
@endphp

<div class="max-h-24 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-1.5 dark:border-gray-700">
    @forelse ($comentarios as $item)
        <div class="rounded-md bg-gray-50 p-1.5 text-[0.65rem] leading-snug dark:bg-gray-800">
            <div class="mb-0.5 flex items-center justify-between text-gray-500 dark:text-gray-400">
                <span class="font-medium">{{ $item['comentario']->autor?->name ?? 'Usuário removido' }}</span>
                <span>{{ $item['comentario']->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <p class="text-gray-800 dark:text-gray-200">{{ $item['comentario']->comentario }}</p>
        </div>
    @empty
        <p class="text-[0.65rem] text-gray-500 dark:text-gray-400">Nenhum comentário ainda.</p>
    @endforelse
</div>
