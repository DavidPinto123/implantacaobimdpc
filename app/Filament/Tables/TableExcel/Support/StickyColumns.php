<?php

namespace App\Filament\Tables\TableExcel\Support;

final class StickyColumns
{
    /**
     * @param  array<int>  $widthsPx
     * @return array<int>
     */
    public static function cumulativeOffsets(array $widthsPx): array
    {
        $offsets = [];
        $accumulated = 0;

        foreach ($widthsPx as $width) {
            $offsets[] = $accumulated;
            $accumulated += $width;
        }

        return $offsets;
    }

    /**
     * @return array{class: string, style: string}
     */
    public static function leftAttributes(int $offsetPx): array
    {
        return [
            'class' => 'gs-table-excel__col-sticky gs-table-excel__col-sticky--left',
            'style' => "--gs-sticky-left: {$offsetPx}px;",
        ];
    }

    /**
     * @return array{class: string, style: string}
     */
    public static function rightAttributes(int $offsetPx): array
    {
        return [
            'class' => 'gs-table-excel__col-sticky gs-table-excel__col-sticky--right',
            'style' => "--gs-sticky-right: {$offsetPx}px;",
        ];
    }
}
