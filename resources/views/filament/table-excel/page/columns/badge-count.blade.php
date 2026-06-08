@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\BadgeCountColumn $column */
    $state = (int) $column->resolveState($record);
@endphp

@if ($column->hideWhenZero && $state === 0)
    <span class="gs-table-excel-page__badge-count gs-table-excel-page__badge-count--hidden">—</span>
@else
    <span class="gs-table-excel-page__badge-count" data-color="{{ $column->color }}">
        {{ $column->formatValue($state) }}
    </span>
@endif
