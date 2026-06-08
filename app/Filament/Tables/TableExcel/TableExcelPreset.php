<?php

namespace App\Filament\Tables\TableExcel;

use App\Filament\Tables\TableExcel\Support\TableExcelPreferences;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

final class TableExcelPreset
{
    public static function apply(Table $table, TableExcelOptions $options): Table
    {
        $classes = self::buildCssClasses($options);

        $attrs = ['class' => $classes];

        if ($options->tableKey !== null) {
            $attrs['data-gs-table-key'] = $options->tableKey;
        }

        $table = $table->extraAttributes($attrs, merge: true);

        if ($options->stickyActionsColumn) {
            $table = $table->recordActionsPosition(RecordActionsPosition::BeforeCells);
        }

        if ($options->filtersModal) {
            $table = $table->filtersLayout(FiltersLayout::Modal);
        }

        if ($options->columnManager) {
            $table = $table
                ->columnManager()
                ->reorderableColumns();
        }

        if ($options->tableKey !== null) {
            $table = $table->persistFiltersInSession();
        }

        return $table;
    }

    /**
     * Aplica preferências de coluna (freeze + widths) persistidas em sessão.
     * Deve ser chamado DEPOIS que ->columns([...]) foi definido no Resource/Page,
     * senão não há colunas para decorar.
     */
    public static function applyColumnPreferences(Table $table, TableExcelOptions $options): Table
    {
        if ($options->tableKey === null) {
            return $table;
        }

        if (! $options->freezable && ! $options->resizable) {
            return $table;
        }

        $frozen = $options->freezable
            ? (array) TableExcelPreferences::get($options->tableKey, 'frozen_columns', [])
            : [];

        $widths = $options->resizable
            ? (array) TableExcelPreferences::get($options->tableKey, 'column_widths', [])
            : [];

        foreach ($table->getColumns() as $name => $column) {
            $headerAttrs = [];
            $cellAttrs = [];

            $isFrozen = $options->freezable && in_array($name, $frozen, true);
            $widthPx = isset($widths[$name]) ? (int) $widths[$name] : null;

            $headerAttrs['data-gs-column'] = $name;
            $cellAttrs['data-gs-column'] = $name;

            if ($isFrozen) {
                $headerAttrs['class'] = 'gs-table-excel__col-sticky gs-table-excel__col-sticky--left';
                $headerAttrs['data-gs-frozen'] = '1';
                $cellAttrs['class'] = 'gs-table-excel__col-sticky gs-table-excel__col-sticky--left';
                $cellAttrs['data-gs-frozen'] = '1';
            }

            if ($widthPx !== null && $widthPx > 0) {
                $style = "width: {$widthPx}px; min-width: {$widthPx}px; max-width: {$widthPx}px;";
                $headerAttrs['style'] = ($headerAttrs['style'] ?? '').$style;
                $cellAttrs['style'] = ($cellAttrs['style'] ?? '').$style;
            }

            if ($options->resizable) {
                $headerAttrs['data-gs-resizable'] = '1';
            }

            if (! empty($headerAttrs)) {
                $column->extraHeaderAttributes($headerAttrs, merge: true);
            }

            if (! empty($cellAttrs)) {
                $column->extraCellAttributes($cellAttrs, merge: true);
            }
        }

        return $table;
    }

    protected static function buildCssClasses(TableExcelOptions $options): string
    {
        $classes = ['gs-table-excel'];

        if ($options->dense) {
            $classes[] = 'gs-table-excel--dense';
        }

        if ($options->excelStyle) {
            $classes[] = 'gs-table-excel--excel';
        }

        if ($options->stickyHeader) {
            $classes[] = 'gs-table-excel--sticky-header';
        }

        if ($options->groupedColumns) {
            $classes[] = 'gs-table-excel--grouped';
        }

        return implode(' ', $classes);
    }
}
