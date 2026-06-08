@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\TextInputColumn $column */
    $state = $column->resolveState($record);
    $recordId = data_get($record, 'id');
    $editable = $column->isEditable();
    $readOnly = ! $editable;
    $inputType = in_array($column->inputType, ['date', 'number'], true)
        ? $column->inputType
        : 'text';
    $typeClass = $inputType === 'text' ? '' : "gs-table-excel-page__ti--{$inputType}";
@endphp

<input
    type="{{ $inputType }}"
    class="gs-table-excel-page__ti {{ $typeClass }} {{ $readOnly ? 'gs-table-excel-page__ti--readonly' : '' }}"
    value="{{ $state ?? '' }}"
    @if ($column->placeholder) placeholder="{{ $column->placeholder }}" @endif
    @if ($column->maxLength) maxlength="{{ $column->maxLength }}" @endif
    @if ($column->step !== null) step="{{ $column->step }}" @endif
    @if ($readOnly)
        readonly
        tabindex="-1"
    @else
        x-data="{ original: @js((string) ($state ?? '')) }"
        x-on:blur="
            if ($event.target.value !== original) {
                original = $event.target.value;
                $wire.mudarValorColuna({{ (int) $recordId }}, @js($column->key), $event.target.value);
            }
        "
        x-on:keydown.enter.prevent="$event.target.blur()"
    @endif
>
