@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\ActionsColumn $column */
    $recordId = data_get($record, 'id');
@endphp

<div class="gs-table-excel-page__actions">
    @foreach ($column->actions as $action)
        @php
            $url = $action->resolveUrl($record);
            $colorClass = $action->color ? 'gs-te-action--'.$action->color : '';
        @endphp

        @if ($url)
            <a
                href="{{ $url }}"
                class="gs-te-action {{ $colorClass }}"
                title="{{ $action->label }}"
                aria-label="{{ $action->label }}"
            >
                <x-filament::icon :icon="$action->icon" class="gs-te-action__icon" />
            </a>
        @elseif ($action->hasMountsAction())
            <button
                type="button"
                class="gs-te-action {{ $colorClass }}"
                title="{{ $action->label }}"
                aria-label="{{ $action->label }}"
                x-on:click.stop="$wire.mountAction(@js($action->mountsActionName), { record: {{ (int) $recordId }} })"
            >
                <x-filament::icon :icon="$action->icon" class="gs-te-action__icon" />
            </button>
        @elseif ($action->hasHandler())
            <button
                type="button"
                class="gs-te-action {{ $colorClass }}"
                title="{{ $action->label }}"
                aria-label="{{ $action->label }}"
                @if ($action->confirmMessage)
                    wire:click.stop="executarAcaoLinha('{{ $column->key }}', '{{ $action->key }}', {{ (int) $recordId }})"
                    wire:confirm="{{ $action->confirmMessage }}"
                @else
                    wire:click.stop="executarAcaoLinha('{{ $column->key }}', '{{ $action->key }}', {{ (int) $recordId }})"
                @endif
            >
                <x-filament::icon :icon="$action->icon" class="gs-te-action__icon" />
            </button>
        @endif
    @endforeach
</div>
