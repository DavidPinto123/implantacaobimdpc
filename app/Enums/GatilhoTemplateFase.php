<?php

namespace App\Enums;

enum GatilhoTemplateFase: string
{
    case INICIO_ANTERIOR = 'inicio_anterior';
    case FIM_ANTERIOR = 'fim_anterior';
    case FIM_ANTERIOR_MESMO_DIA = 'fim_anterior_mesmo_dia';
    case FIM_JUNTO = 'fim_junto';
    case FIM_ANTES_INICIO = 'fim_antes_inicio';

    public function label(): string
    {
        return match ($this) {
            self::INICIO_ANTERIOR => 'Início da dependência',
            self::FIM_ANTERIOR => 'Dia seguinte ao fim (natural)',
            self::FIM_ANTERIOR_MESMO_DIA => 'Mesmo dia do fim (sobreposição)',
            self::FIM_JUNTO => 'Termina junto com a dependência',
            self::FIM_ANTES_INICIO => 'Termina antes do início da dependência',
        };
    }

    /**
     * Rótulo curto para uso em células compactas (tabela, pills de dependência).
     */
    public function labelCurto(): string
    {
        return match ($this) {
            self::INICIO_ANTERIOR => 'início',
            self::FIM_ANTERIOR => 'fim+1',
            self::FIM_ANTERIOR_MESMO_DIA => 'fim',
            self::FIM_JUNTO => 'fim=junto',
            self::FIM_ANTES_INICIO => 'fim<inicio',
        };
    }
}
