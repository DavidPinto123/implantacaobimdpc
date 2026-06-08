<?php

namespace App\Enums;

enum TipoContratacaoAutorizacaoServico: string
{
    case MAO_OBRA = 'mao_obra';
    case MATERIAL = 'material';

    public function label(): string
    {
        return match ($this) {
            self::MAO_OBRA => 'Mão de obra',
            self::MATERIAL => 'Material',
        };
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
