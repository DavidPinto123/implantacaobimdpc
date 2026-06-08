<?php

namespace App\Enums;

enum CategoriaAtualizacaoObra: string
{
    case STATUS = 'STATUS';
    case PERCENTUAL = 'PERCENTUAL';
    case POSSE = 'POSSE';
    case IMPLANTACAO = 'IMPLANTACAO';
    case INAUGURACAO = 'INAUGURACAO';
    case CIVIL = 'CIVIL';
    case ELETRICA = 'ELETRICA';
    case HIDRAULICA = 'HIDRAULICA';
    case CLIMATIZACAO = 'CLIMATIZACAO';
    case INCENDIO = 'INCENDIO';
    case CRONOGRAMA = 'CRONOGRAMA';
    case ENERGIA = 'ENERGIA';
    case AGUA = 'AGUA';
    case GAS = 'GAS';
    case COMENTARIO = 'COMENTARIO';
    case CONTRATACAO = 'CONTRATACAO';
    case GERAL = 'GERAL';

    public function label(): string
    {
        return match ($this) {
            self::STATUS => 'Status',
            self::PERCENTUAL => 'Percentual',
            self::POSSE => 'Posse',
            self::IMPLANTACAO => 'Implantação',
            self::INAUGURACAO => 'Inauguração',
            self::CIVIL => 'Civil',
            self::ELETRICA => 'Elétrica',
            self::HIDRAULICA => 'Hidráulica',
            self::CLIMATIZACAO => 'Climatização',
            self::INCENDIO => 'Incêndio',
            self::CRONOGRAMA => 'Cronograma',
            self::ENERGIA => 'Energia',
            self::AGUA => 'Água',
            self::GAS => 'Gás',
            self::COMENTARIO => 'Comentário',
            self::CONTRATACAO => 'Contratação',
            self::GERAL => 'Geral',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::STATUS => 'info',
            self::PERCENTUAL => 'success',
            self::POSSE => 'purple',
            self::IMPLANTACAO => 'warning',
            self::INAUGURACAO => 'success',
            self::CIVIL => 'gray',
            self::ELETRICA => 'warning',
            self::HIDRAULICA => 'blue',
            self::CLIMATIZACAO => 'cyan',
            self::INCENDIO => 'danger',
            self::CRONOGRAMA => 'indigo',
            self::ENERGIA => 'warning',
            self::AGUA => 'blue',
            self::GAS => 'orange',
            self::COMENTARIO => 'gray',
            self::CONTRATACAO => 'success',
            self::GERAL => 'gray',
        };
    }
}
