@php
    /** @var \App\Filament\Tables\TableExcel\Page\Filters\Filter $filter */
@endphp

@switch($filter->getType())
    @case('select')
        @php $options = $filter->resolveOptions(); @endphp
        <select
            class="gs-table-excel__qf-select"
            aria-label="{{ $filter->label }}"
            @if ($filter->multiple) multiple @endif
            x-model="draftFilters['{{ $filter->key }}']"
        >
            @if (! $filter->multiple)
                <option value="">{{ $filter->placeholder ?? $filter->label }}</option>
            @endif
            @foreach ($options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    @break

    @case('period')
        <select
            class="gs-table-excel__qf-select"
            aria-label="{{ $filter->label }}"
            x-model="draftFilters['{{ $filter->key }}']"
        >
            @foreach ($filter->options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    @break

    @case('date_range')
        <div class="gs-table-excel__qf-daterange" aria-label="{{ $filter->label }}">
            <input
                type="date"
                class="gs-table-excel__qf-input gs-table-excel__qf-daterange-input"
                placeholder="De"
                aria-label="{{ $filter->label }} — de"
                x-model="draftFilters['{{ $filter->key }}'].from"
            >
            <span class="gs-table-excel__qf-daterange-sep">→</span>
            <input
                type="date"
                class="gs-table-excel__qf-input gs-table-excel__qf-daterange-input"
                placeholder="Até"
                aria-label="{{ $filter->label }} — até"
                x-model="draftFilters['{{ $filter->key }}'].until"
            >
        </div>
    @break
@endswitch
