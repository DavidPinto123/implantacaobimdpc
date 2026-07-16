@if ($url)
    <img
        src="{{ $url }}"
        alt="Imagem estática do ambiente"
        class="h-[260px] w-full rounded-lg border border-gray-200 object-cover dark:border-gray-700"
    >
@else
    <div class="flex h-[260px] items-center justify-center rounded-lg border border-dashed border-gray-300 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
        Nenhuma imagem estática enviada ainda.
    </div>
@endif
