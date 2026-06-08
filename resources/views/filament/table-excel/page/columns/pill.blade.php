@php
    /** @var \App\Filament\Tables\TableExcel\Page\Columns\PillColumn $column */
    $state = $column->resolveState($record);
    $color = $column->getColorForState($state);
    $label = $column->getLabelForState($state);
    $recordId = data_get($record, 'id');
    $editable = $column->isEditable();
@endphp

@if ($editable)
    <div
        class="gs-pill-dropdown"
        x-data="{
            open: false,
            currentValue: @js((string) ($state ?? '')),
            currentLabel: @js($label),
            currentColor: @js($color),
            pos: { top: 0, left: 0, width: 0 },
            reposition() {
                const btn = this.$refs.trigger.getBoundingClientRect();
                this.pos = {
                    top: btn.bottom + window.scrollY + 4,
                    left: btn.left + window.scrollX,
                    width: btn.width,
                };
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.reposition());
                }
            },
        }"
        x-on:keydown.escape="open = false"
        x-on:click.away="open = false"
    >
        <button
            type="button"
            class="gs-pill"
            :class="'gs-pill--' + currentColor"
            x-ref="trigger"
            x-on:click.stop="toggle()"
            aria-haspopup="listbox"
            :aria-expanded="open.toString()"
        >
            <span class="gs-pill__label" x-text="currentLabel"></span>
            @if ($column->chevron)
                <svg class="gs-pill__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5.25 7.5L10 12.25L14.75 7.5"/>
                </svg>
            @endif
        </button>

        <template x-teleport="body">
            <div
                class="gs-pill-menu"
                x-show="open"
                x-transition.opacity.duration.120ms
                x-cloak
                :style="`top: ${pos.top}px; left: ${pos.left}px; min-width: ${pos.width}px;`"
            >
                @foreach ($column->options as $optionValue => $optionLabel)
                    @php $optionColor = $column->getColorForState($optionValue); @endphp
                    <button
                        type="button"
                        :class="currentValue === @js((string) $optionValue) ? 'gs-pill-option gs-pill-option--selected' : 'gs-pill-option'"
                        x-on:click.stop="
                            currentValue = @js((string) $optionValue);
                            currentLabel = @js($optionLabel);
                            currentColor = @js($optionColor);
                            open = false;
                            $wire.mudarValorColuna({{ (int) $recordId }}, @js($column->key), @js($optionValue));
                        "
                    >
                        <span class="gs-pill-option__dot" data-color="{{ $optionColor }}"></span>
                        <span class="gs-pill-option__label">{{ $optionLabel }}</span>
                    </button>
                @endforeach
            </div>
        </template>
    </div>
@else
    <span class="gs-pill gs-pill--{{ $color }}">
        <span class="gs-pill__label">{{ $label }}</span>
    </span>
@endif
