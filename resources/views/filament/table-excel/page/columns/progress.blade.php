@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\ProgressColumn $column */
    $pct = $column->resolvePercentage($record);
    $color = $column->resolveColor($record);
@endphp

<div class="gs-table-excel-page__progress" data-color="{{ $color }}">
    <div class="gs-table-excel-page__progress-track">
        <div class="gs-table-excel-page__progress-fill" style="width: {{ $pct }}%"></div>
    </div>
    <span class="gs-table-excel-page__progress-pct">{{ $pct }}%</span>
</div>
