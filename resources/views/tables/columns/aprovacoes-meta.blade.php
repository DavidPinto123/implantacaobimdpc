<div class="space-y-1">
    @foreach ($getState() as $item)
        <div class="text-xs text-gray-600 dark:text-gray-300">
            <span class="font-medium">{{ $item['role'] }}</span>
            — {{ $item['date'] ?? '—' }}
            @if(!empty($item['by'])) — {{ $item['by'] }} @endif
        </div>
    @endforeach
</div>