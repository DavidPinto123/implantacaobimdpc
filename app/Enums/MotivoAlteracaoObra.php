<?php

namespace App\Enums;

enum MotivoAlteracaoObra: string
{
    case ATRASO_SHELL_DOCUMENTACAO_PP = 'ATRASO_SHELL_DOCUMENTACAO_PP';
    case ATRASO_ENGENHARIA = 'ATRASO_ENGENHARIA';
    case ENTRADA_DE_ENERGIA = 'ENTRADA_DE_ENERGIA';
    case ANTECIPACAO = 'ANTECIPACAO';
    case LEGALIZACAO = 'LEGALIZACAO';
    case NOVA_UNIDADES = 'NOVA_UNIDADES';
    case CANCELADAS = 'CANCELADAS';
    case ESTRATEGIA_PMO = 'ESTRATEGIA_PMO';
    case SUPPLY = 'SUPPLY';

    public function label(): string
    {
        return match ($this) {
            self::ATRASO_SHELL_DOCUMENTACAO_PP => 'Atraso Shell ou Documentação PP',
            self::ATRASO_ENGENHARIA => 'Atraso Engenharia',
            self::ENTRADA_DE_ENERGIA => 'Entrada de Energia',
            self::ANTECIPACAO => 'Antecipação',
            self::LEGALIZACAO => 'Legalização',
            self::NOVA_UNIDADES => 'Nova Unidades',
            self::CANCELADAS => 'Canceladas',
            self::ESTRATEGIA_PMO => 'Estratégia PMO',
            self::SUPPLY => 'Supply',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ATRASO_SHELL_DOCUMENTACAO_PP => 'danger',
            self::ATRASO_ENGENHARIA => 'danger',
            self::ENTRADA_DE_ENERGIA => 'warning',
            self::ANTECIPACAO => 'success',
            self::LEGALIZACAO => 'info',
            self::NOVA_UNIDADES => 'success',
            self::CANCELADAS => 'gray',
            self::ESTRATEGIA_PMO => 'indigo',
            self::SUPPLY => 'orange',
        };
    }

    public static function paraSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
