<?php

namespace App\Filament\Tables\TableExcel\Actions;

use App\Filament\Tables\TableExcel\Page\Filters\DateRangeFilter;
use App\Filament\Tables\TableExcel\Page\Filters\Filter;
use App\Filament\Tables\TableExcel\Page\Filters\PeriodFilter;
use App\Filament\Tables\TableExcel\Page\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Modal de configuração de filtros para o modo Page do Table Excel.
 *
 * Substitui o modal custom Alpine do Blade. Cada Filter do nosso sistema é
 * mapeado para um componente Filament equivalente:
 *
 *   SelectFilter     → Select::make(key)->[multiple]
 *   DateRangeFilter  → Fieldset com dois DatePicker (De / Até)
 *   PeriodFilter     → Select::make(key)
 *
 * O valor retornado pelo form é normalizado de volta para o shape esperado
 * por HasTableExcelPage::aplicarFiltrosModal (DateRange vira ['from','until']).
 */
final class ManageFiltersAction
{
    /**
     * @param  array<int, Filter>  $filters
     */
    public static function make(array $filters, ?string $name = null): Action
    {
        return Action::make($name ?? 'manageFilters')
            ->label('Filtros')
            ->icon('heroicon-o-funnel')
            ->color('gray')
            ->modalHeading('Filtros')
            ->modalDescription(new HtmlString(
                '<button type="button" wire:click="limparFiltros" x-on:click="$dispatch(\'close-modal\', { id: $el.closest(\'[data-fi-modal-id]\').dataset.fiModalId })" class="gs-te-filter-reset-action">Limpar Filtros</button>',
            ))
            ->modalWidth('3xl')
            ->modalSubmitActionLabel('Aplicar')
            ->modalSubmitAction(fn (Action $action): Action => $action->color('gray'))
            ->modalCancelAction(false)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->extraModalWindowAttributes(['class' => 'gs-te-config-modal gs-te-config-modal--filters'])
            ->badge(fn ($livewire): ?int => self::countActiveFilters($livewire, $filters))
            ->fillForm(fn ($livewire): array => self::fillState($livewire, $filters))
            ->schema(fn (Schema $schema): Schema => $schema->components(self::buildFilterGroups($filters)))
            ->action(function (array $data, $livewire) use ($filters): void {
                $normalized = self::normalizeSubmission($data, $filters);

                if (is_object($livewire) && method_exists($livewire, 'aplicarFiltrosModal')) {
                    $livewire->aplicarFiltrosModal($normalized);
                }
            });
    }

    /**
     * @param  array<int, Filter>  $filters
     */
    protected static function countActiveFilters($livewire, array $filters): ?int
    {
        if (! is_object($livewire) || ! property_exists($livewire, 'filtros')) {
            return null;
        }

        $state = (array) $livewire->filtros;
        $count = 0;

        foreach ($filters as $f) {
            $v = $state[$f->key] ?? null;
            if (! $f->isEmptyValue($v)) {
                $count++;
            }
        }

        return $count > 0 ? $count : null;
    }

    /**
     * @param  array<int, Filter>  $filters
     * @return array<string, mixed>
     */
    protected static function fillState($livewire, array $filters): array
    {
        $state = is_object($livewire) && property_exists($livewire, 'filtros')
            ? (array) $livewire->filtros
            : [];

        $data = [];

        foreach ($filters as $f) {
            $v = $state[$f->key] ?? null;

            if ($f instanceof DateRangeFilter) {
                $data["{$f->key}__from"] = is_array($v) ? ($v['from'] ?? null) : null;
                $data["{$f->key}__until"] = is_array($v) ? ($v['until'] ?? null) : null;

                continue;
            }

            $data[$f->key] = $v;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, Filter>  $filters
     * @return array<string, mixed>
     */
    protected static function normalizeSubmission(array $data, array $filters): array
    {
        $normalized = [];

        foreach ($filters as $f) {
            if ($f instanceof DateRangeFilter) {
                $from = $data["{$f->key}__from"] ?? null;
                $until = $data["{$f->key}__until"] ?? null;

                if ($from !== null && $from !== '') {
                    $normalized[$f->key]['from'] = (string) $from;
                }
                if ($until !== null && $until !== '') {
                    $normalized[$f->key]['until'] = (string) $until;
                }

                continue;
            }

            $value = $data[$f->key] ?? null;

            if ($f->isEmptyValue($value)) {
                continue;
            }

            $normalized[$f->key] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<int, Filter>  $filters
     * @return array<int, Fieldset>
     */
    protected static function buildFilterGroups(array $filters): array
    {
        $groups = [];

        foreach ($filters as $filter) {
            $group = $filter->group
                ?? ($filter->secondary ? 'Filtros avançados' : 'Filtros principais');

            $groups[$group] ??= [];
            $groups[$group][] = $filter;
        }

        uksort($groups, fn (string $a, string $b): int => self::compareFilterGroupNames($a, $b));

        $components = [];

        foreach ($groups as $group => $groupFilters) {
            usort($groupFilters, fn (Filter $a, Filter $b): int => self::compareFiltersInGroup($group, $a, $b));

            $components[] = Fieldset::make($group)
                ->columns(2)
                ->schema(self::buildFilterComponents($groupFilters))
                ->extraAttributes(['class' => 'gs-te-filters-group']);
        }

        return $components;
    }

    protected static function compareFilterGroupNames(string $a, string $b): int
    {
        $order = [
            'INFORMAÇÕES DO PROJETO' => 10,
            'DADOS DO IMÓVEL' => 20,
            'GESTOR' => 30,
            'TOTAL DE DIAS DE PROCESSO' => 40,
            'VISITA TÉCNICA' => 50,
            'PROJETO EXECUTIVO' => 60,
            'POSSE' => 70,
            'EXECUÇÃO DE OBRAS' => 80,
            'IMPLANTAÇÃO' => 90,
            'CRONOGRAMA VISI' => 100,
            'CONTRATAÇÕES' => 110,
            'CONTAS DE CONSUMO' => 120,
            'PÓS OBRA' => 130,
            'AUDITORIA' => 140,
            'PONTOS DE ATENÇÃO' => 150,
        ];

        return ($order[$a] ?? 1000) <=> ($order[$b] ?? 1000)
            ?: strnatcasecmp($a, $b);
    }

    protected static function compareFiltersInGroup(string $group, Filter $a, Filter $b): int
    {
        $order = [
            'INFORMAÇÕES DO PROJETO' => ['pipe_land', 'status', 'marca'],
            'DADOS DO IMÓVEL' => ['uf', 'tipo_imovel', 'locacao'],
        ][$group] ?? [];
        $position = static fn (Filter $filter): int => (($index = array_search($filter->key, $order, true)) === false) ? 1000 : $index;

        return $position($a) <=> $position($b)
            ?: 0;
    }

    /**
     * @param  array<int, Filter>  $filters
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected static function buildFilterComponents(array $filters): array
    {
        $components = [];

        foreach ($filters as $filter) {
            $component = match (true) {
                $filter instanceof DateRangeFilter => self::buildDateRangeComponent($filter),
                $filter instanceof SelectFilter => self::buildSelectComponent($filter),
                $filter instanceof PeriodFilter => self::buildPeriodComponent($filter),
                default => null,
            };

            if ($component !== null) {
                $components[] = $component;
            }
        }

        return $components;
    }

    protected static function buildSelectComponent(SelectFilter $filter): Select
    {
        $select = Select::make($filter->key)
            ->label($filter->label)
            ->options($filter->resolveOptions())
            ->searchable()
            ->native(false)
            ->extraFieldWrapperAttributes(['class' => 'gs-te-filter-row']);

        if ($filter->multiple) {
            $select->multiple();
        }

        if ($filter->placeholder) {
            $select->placeholder($filter->placeholder);
        }

        return $select;
    }

    protected static function buildPeriodComponent(PeriodFilter $filter): Select
    {
        return Select::make($filter->key)
            ->label($filter->label)
            ->options($filter->options)
            ->native(false)
            ->extraFieldWrapperAttributes(['class' => 'gs-te-filter-row']);
    }

    protected static function buildDateRangeComponent(DateRangeFilter $filter): Fieldset
    {
        return Fieldset::make($filter->label)
            ->columnSpanFull()
            ->columns(2)
            ->extraAttributes(['class' => 'gs-te-filter-date-range'])
            ->schema([
                DatePicker::make("{$filter->key}__from")
                    ->label('De')
                    ->hiddenLabel()
                    ->native()
                    ->placeholder('dd/mm/aaaa'),
                DatePicker::make("{$filter->key}__until")
                    ->label('Até')
                    ->hiddenLabel()
                    ->native()
                    ->placeholder('dd/mm/aaaa'),
            ]);
    }
}
