@php $height ??= 260; @endphp

@if ($url)
    <img
        src="{{ $url }}"
        alt="Imagem estática do ambiente"
        class="w-full rounded-lg border border-gray-200 object-cover dark:border-gray-700"
        style="height: {{ $height }}px;"
    >
@else
    <div
        class="flex items-center justify-center rounded-lg border border-dashed border-gray-300 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
        style="height: {{ $height }}px;"
    >
        Nenhuma imagem estática enviada ainda.
    </div>
@endif
