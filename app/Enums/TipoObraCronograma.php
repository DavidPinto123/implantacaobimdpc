<?php

namespace App\Enums;

enum TipoObraCronograma: string
{
    case EXPANSAO = 'expansao';
    case AMPLIACAO_RETROFIT = 'ampliacao_retrofit';
    case IMPLANTACAO_BIM = 'implantacao_bim';

    public function label(): string
    {
        return match ($this) {
            self::EXPANSAO => 'Expansão',
            self::AMPLIACAO_RETROFIT => 'Ampliação / Retrofit',
            self::IMPLANTACAO_BIM => 'Implantação BIM',
        };
    }
}
