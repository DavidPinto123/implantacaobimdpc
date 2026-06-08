@php
    /**
     * Partial do corpo da tabela (apenas <tr>s).
     *
     * Extraída do index.blade.php para permitir re-render via AJAX sem
     * tocar no morph do Livewire (tbody fica em wire:ignore no index).
     * Também é o alvo de `fetchRowsHtml()` no HasTableExcelPage.
     *
     * Variáveis esperadas:
     *  - $registros: Collection
     *  - $columns: Column[]
     *  - $config: TableExcelPage
     *  - $recordKey: string
     *  - $bulkEnabled: bool
     *  - $resizable: bool
     *  - $frozenCols: array<int, string>
     *  - $widths: array<string, int>
     *  - $selIds: array<int, string>
     */
@endphp
@forelse ($registros as $record)
    @php
        $url = $config->resolveRowUrl($record);
        $rowId = (string) data_get($record, $recordKey);
        $rowSelected = in_array($rowId, $selIds, true);
    @endphp
    <tr
        wire:key="gs-te-page-row-{{ $rowId }}"
        class="gs-table-excel-page__row {{ $url ? 'gs-table-excel-page__row--clickable' : '' }} {{ $rowSelected ? 'gs-table-excel-page__row--selected' : '' }}"
        data-gs-row-id="{{ $rowId }}"
        @if ($url) data-gs-row-url="{{ $url }}" @endif
    >
        @if ($bulkEnabled)
            <td class="gs-table-excel-page__td gs-table-excel-page__td--align-center">
                <input
                    type="checkbox"
                    aria-label="Selecionar registro"
                    @checked($rowSelected)
                    wire:click.stop="toggleSelecao('{{ $rowId }}')"
                >
            </td>
        @endif
        @foreach ($columns as $column)
            @php
                $tdIsFrozen = in_array($column->key, $frozenCols, true);
                $tdW = $widths[$column->key] ?? null;
                $tdStyle = ($resizable && $tdW) ? "width: {$tdW}px; min-width: {$tdW}px; max-width: {$tdW}px;" : '';
                $tdClasses = [
                    'gs-table-excel-page__td',
                    "gs-table-excel-page__td--align-{$column->align}",
                ];
                if ($tdIsFrozen) $tdClasses[] = 'gs-table-excel__col-sticky gs-table-excel__col-sticky--left';
                if ($column->isEditable()) $tdClasses[] = 'gs-table-excel-page__td--editable';
                if ($column->cellClass) $tdClasses[] = $column->cellClass;
            @endphp
            <td
                class="{{ implode(' ', $tdClasses) }}"
                data-gs-column="{{ $column->key }}"
                @if ($tdIsFrozen) data-gs-frozen="1" @endif
                @if ($tdStyle) style="{{ $tdStyle }}" @endif
            >
                @switch($column->getType())
                    @case('text')
                        @include('filament.table-excel.page.columns.text', ['column' => $column, 'record' => $record])
                    @break
                    @case('pill')
                        @include('filament.table-excel.page.columns.pill', ['column' => $column, 'record' => $record])
                    @break
                    @case('date')
                        @include('filament.table-excel.page.columns.date', ['column' => $column, 'record' => $record])
                    @break
                    @case('duration')
                        @include('filament.table-excel.page.columns.duration', ['column' => $column, 'record' => $record])
                    @break
                    @case('progress')
                        @include('filament.table-excel.page.columns.progress', ['column' => $column, 'record' => $record])
                    @break
                    @case('badge-count')
                        @include('filament.table-excel.page.columns.badge-count', ['column' => $column, 'record' => $record])
                    @break
                    @case('actions')
                        @include('filament.table-excel.page.columns.actions', ['column' => $column, 'record' => $record])
                    @break
                    @case('text-input')
                        @include('filament.table-excel.page.columns.text-input', ['column' => $column, 'record' => $record])
                    @break
                    @default
                        {{ $column->resolveState($record) }}
                @endswitch
            </td>
        @endforeach
    </tr>
@empty
    <tr>
        <td colspan="{{ count($columns) + ($bulkEnabled ? 1 : 0) }}" class="gs-table-excel-page__empty">
            <div class="gs-table-excel-page__empty-heading">
                {{ $config->getEmptyStateHeading() ?? 'Nenhum registro encontrado' }}
            </div>
            @if ($config->getEmptyStateDescription())
                <div class="gs-table-excel-page__empty-desc">
                    {{ $config->getEmptyStateDescription() }}
                </div>
            @endif
        </td>
    </tr>
@endforelse
