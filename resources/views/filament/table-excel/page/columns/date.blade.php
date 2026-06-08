@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\DateColumn $column */
    $state = $column->resolveState($record);
@endphp

<span class="gs-table-excel-page__date">{{ $column->formatValue($state) }}</span>
