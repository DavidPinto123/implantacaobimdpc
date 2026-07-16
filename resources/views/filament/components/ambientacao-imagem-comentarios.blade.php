<div class="space-y-3">
    @forelse ($imagem->comentarios->sortByDesc('created_at') as $comentario)
        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium">{{ $comentario->autor?->name ?? 'Usuário removido' }}</span>
                <span>{{ $comentario->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <p class="text-sm text-gray-800 dark:text-gray-200">{{ $comentario->comentario }}</p>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum comentário ainda.</p>
    @endforelse
</div>
