<?php

namespace App\Enums\PosObra;

enum TipoConstrutora: string
{
    case CONSTRUTORA = 'CONSTRUTORA';
    case INSTALADORA = 'INSTALADORA';
    case PRESTADORA_SERVICO = 'PRESTADORA_SERVICO';
    case FORNECEDOR_MATERIAL = 'FORNECEDOR_MATERIAL';
    case PROJETISTA = 'PROJETISTA';
    case GERENCIADORA = 'GERENCIADORA';

    public function label(): string
    {
        return match ($this) {
            self::CONSTRUTORA => 'Construtora',
            self::INSTALADORA => 'Instaladora',
            self::PRESTADORA_SERVICO => 'Prestadora de Serviço',
            self::FORNECEDOR_MATERIAL => 'Fornecedor de Material',
            self::PROJETISTA => 'Projetista',
            self::GERENCIADORA => 'Gerenciadora',
        };
    }
}
