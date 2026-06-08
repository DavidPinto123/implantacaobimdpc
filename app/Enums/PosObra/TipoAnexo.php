<?php

namespace App\Enums\PosObra;

enum TipoAnexo: string
{
    case FOTO_INICIAL = 'FOTO_INICIAL';
    case EVIDENCIA = 'EVIDENCIA';

    public function label(): string
    {
        return match ($this) {
            self::FOTO_INICIAL => 'Foto Inicial',
            self::EVIDENCIA => 'Evidência',
        };
    }
}
