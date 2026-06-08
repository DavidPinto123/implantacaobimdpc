<?php

namespace App\Enums;

enum TipoUnidade: string
{
    case EXPANSAO = 'EXPANSÃO';
    case RETROFIT = 'RETROFIT';
    case PADRAO = 'PADRÃO';
    case OUTROS = 'OUTROS';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tipo): array => [$tipo->value => $tipo->label()])
            ->all();
    }
}
