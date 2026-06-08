@if (! empty($messages))
    <div class="mb-4 rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-800 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-200">
        <p class="font-medium">Para salvar o projeto, corrija os campos abaixo:</p>

        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
