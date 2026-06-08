<?php

namespace App\Filament\Tables\TableExcel\Concerns;

use App\Filament\Tables\TableExcel\Support\TableExcelPreferences;

trait HasTableExcel
{
    /**
     * Livewire handler invoked by Alpine when the user toggles frozen columns
     * via the ManageFrozenColumnsAction modal or equivalent.
     *
     * @param  array<string>  $columns
     */
    public function tableExcelSetFrozenColumns(string $tableKey, array $columns): void
    {
        $normalized = array_values(array_filter(
            $columns,
            fn ($name): bool => is_string($name) && $name !== '',
        ));

        TableExcelPreferences::put($tableKey, 'frozen_columns', $normalized);
    }

    /**
     * Livewire handler invoked by the Alpine resize dragger when the user
     * finishes dragging a column handle.
     */
    public function tableExcelSetColumnWidth(string $tableKey, string $column, int $width): void
    {
        if ($column === '') {
            return;
        }

        $bounded = max(40, min(1200, $width));

        $current = (array) TableExcelPreferences::get($tableKey, 'column_widths', []);
        $current[$column] = $bounded;

        TableExcelPreferences::put($tableKey, 'column_widths', $current);
    }

    public function tableExcelResetColumnWidth(string $tableKey, string $column): void
    {
        $current = (array) TableExcelPreferences::get($tableKey, 'column_widths', []);
        unset($current[$column]);

        TableExcelPreferences::put($tableKey, 'column_widths', $current);
    }
}
