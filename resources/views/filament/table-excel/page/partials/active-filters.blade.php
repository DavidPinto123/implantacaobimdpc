@php
    /**
     * Barra "Active filters" entre a toolbar e a tabela.
     *
     * Variáveis esperadas:
     *  - $filters: array<Filter> configurados
     *  - $activeFilters: $this->filtros (state Livewire)
     */

    use App\Filament\Tables\TableExcel\Page\Filters\DateRangeFilter;
    use App\Filament\Tables\TableExcel\Page\Filters\PeriodFilter;
    use App\Filament\Tables\TableExcel\Page\Filters\SelectFilter;

    $formatValue = function ($filter, $value): string {
        if ($filter instanceof DateRangeFilter) {
            $from = is_array($value) ? ($value['from'] ?? null) : null;
            $until = is_array($value) ? ($value['until'] ?? null) : null;
            $fmt = function ($d): string {
                try {
                    return \Carbon\Carbon::parse($d)->format('d/m/Y');
                } catch (\Throwable) {
                    return (string) $d;
                }
            };
            if ($from && $until) return $fmt($from) . ' → ' . $fmt($until);
            if ($from) return 'a partir de ' . $fmt($from);
            if ($until) return 'até ' . $fmt($until);

            return '';
        }

        if ($filter instanceof PeriodFilter) {
            return (string) ($filter->options[$value] ?? $value);
        }

        if ($filter instanceof SelectFilter) {
            $opts = $filter->resolveOptions();
            if (is_array($value)) {
                $labels = array_map(fn ($v) => $opts[$v] ?? $v, $value);
                $count = count($labels);
                if ($count <= 2) return implode(', ', $labels);
                if ($count === 3) return $labels[0] . ', ' . $labels[1] . ' & ' . $labels[2];

                return $labels[0] . ', ' . $labels[1] . ' & outros ' . ($count - 2);
            }

            return (string) ($opts[$value] ?? $value);
        }

        return (string) $value;
    };

    $chips = [];
    foreach ($filters as $filter) {
        $v = $activeFilters[$filter->key] ?? null;
        if ($filter->isEmptyValue($v)) {
            continue;
        }
        $display = $formatValue($filter, $v);
        if (trim($display) === '') {
            continue;
        }
        $chips[] = ['key' => $filter->key, 'label' => $filter->label, 'value' => $display];
    }
@endphp

@if (! empty($chips))
    <div class="gs-table-excel__active-filters">
        <span class="gs-table-excel__active-filters-label">Filtros ativos</span>

        @foreach ($chips as $chip)
            <span class="gs-table-excel__active-filter-chip">
                <span class="gs-table-excel__active-filter-chip-text">
                    <strong>{{ $chip['label'] }}:</strong> {{ $chip['value'] }}
                </span>
                <button
                    type="button"
                    class="gs-table-excel__active-filter-chip-remove"
                    wire:click="removerFiltro('{{ $chip['key'] }}')"
                    title="Remover filtro"
                    aria-label="Remover filtro {{ $chip['label'] }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </span>
        @endforeach

        <button
            type="button"
            class="gs-table-excel__active-filters-clear"
            wire:click="limparFiltros"
            title="Limpar todos"
        >
            Limpar todos
        </button>
    </div>
@endif
