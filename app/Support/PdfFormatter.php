<?php

namespace App\Support;

class PdfFormatter
{
    public static function badge($value): string
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                $value = null;
            }
        }

        if ($value === 1 || $value === '1' || $value === true) {
            return '<span class="badge sim">SIM</span>';
        }

        if ($value === 0 || $value === '0' || $value === false) {
            return '<span class="badge nao">NÃO</span>';
        }

        return '<span class="badge na">N/A</span>';
    }
}
