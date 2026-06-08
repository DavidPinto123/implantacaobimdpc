<?php

namespace App\Filament\Tables\TableExcel\Support;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ColumnGroup;
use Illuminate\Contracts\Support\Htmlable;

final class ColumnGroupPreset
{
    /**
     * @param  array<Column>  $columns
     */
    public static function make(string|Htmlable|Closure $label, array $columns = []): ColumnGroup
    {
        return self::decorate(ColumnGroup::make($label, $columns));
    }

    public static function decorate(ColumnGroup $group): ColumnGroup
    {
        return $group->extraHeaderAttributes([
            'class' => 'gs-table-excel__group-header',
        ], merge: true);
    }
}
