<?php

namespace App\Enums;

enum TipoDiasTemplate: string
{
    case UTEIS = 'uteis';
    case CORRIDOS = 'corridos';

    public function label(): string
    {
        return match ($this) {
            self::UTEIS => 'Dias úteis',
            self::CORRIDOS => 'Dias corridos',
        };
    }
}
