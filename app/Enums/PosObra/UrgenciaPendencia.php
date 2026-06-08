<?php

namespace App\Enums\PosObra;

enum UrgenciaPendencia: string
{
    case P1 = 'P1';
    case P2 = 'P2';
    case P3 = 'P3';

    public function label(): string
    {
        return match ($this) {
            self::P1 => 'Leve',
            self::P2 => 'Médio',
            self::P3 => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::P1 => 'warning',  // amarelo
            self::P2 => 'orange',   // laranja
            self::P3 => 'danger',   // vermelho
        };
    }

    public function slaHoras(): int
    {
        return match ($this) {
            self::P1 => 24,
            self::P2 => 12,
            self::P3 => 6,
        };
    }
}
