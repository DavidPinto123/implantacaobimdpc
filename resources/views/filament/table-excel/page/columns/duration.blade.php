@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\DurationColumn $column */
    $state = $column->resolveState($record);
@endphp

<span class="gs-table-excel-page__duration">{{ $column->formatValue($state) }}</span>
