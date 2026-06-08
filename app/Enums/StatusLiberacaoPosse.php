<?php

namespace App\Enums;

enum StatusLiberacaoPosse: string
{
    case SIM = 'sim';
    case NAO = 'nao';
    case RISCO = 'risco';

    public function label(): string
    {
        return match ($this) {
            self::SIM => 'Sim',
            self::NAO => 'Não',
            self::RISCO => 'Risco',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SIM => '#22c55e',
            self::NAO => '#9ca3af',
            self::RISCO => '#ef4444',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::SIM => 'rgba(34,197,94,.12)',
            self::NAO => 'rgba(156,163,175,.12)',
            self::RISCO => 'rgba(239,68,68,.12)',
        };
    }

    /**
     * Considera SIM como concluído para fins de cálculo de % da fase.
     */
    public function concluido(): bool
    {
        return $this === self::SIM;
    }

    public static function paraSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->toArray();
    }
}
