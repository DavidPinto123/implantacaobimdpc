@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\TextColumn $column */
    $state = $column->resolveState($record);
    $classes = ['gs-table-excel-page__text'];
    if ($column->compact) {
        $classes[] = 'gs-table-excel-page__text--compact';
    }
    if ($column->monospace) {
        $classes[] = 'gs-table-excel-page__text--mono';
    }
    if ($column->muted) {
        $classes[] = 'gs-table-excel-page__text--muted';
    }
@endphp

<span class="{{ implode(' ', $classes) }}">{{ $state ?? '—' }}</span>
