<?php

namespace App\Enums;

enum ModoAncoraCronograma: string
{
    case POSSE = 'posse';
    case OBRAS = 'obras';

    public function label(): string
    {
        return match ($this) {
            self::POSSE => 'Ancorado em Posse',
            self::OBRAS => 'Ancorado em Obras',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::POSSE => 'Mudanças no cronograma movem a data de posse. Use durante o planejamento inicial.',
            self::OBRAS => 'Mudanças na data de posse não recalculam o cronograma. Use durante a execução.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::POSSE => 'warning',
            self::OBRAS => 'success',
        };
    }
}
