<?php

namespace App\Enums\PosObra;

enum StatusPendencia: string
{
    case REGISTRADA = 'REGISTRADA';
    case NOTIFICADA_PRESTADORA = 'NOTIFICADA_PRESTADORA';
    case PENDENTE_COM_PRAZO = 'PENDENTE_COM_PRAZO';
    case EM_EXECUCAO = 'EM_EXECUCAO';
    case AGUARDANDO_APROVACAO = 'AGUARDANDO_APROVACAO';
    case CONCLUIDA = 'CONCLUIDA';
    case AS_ORCAMENTOS = 'AS_ORCAMENTOS';
    case GARANTIA_SOLICITADA = 'GARANTIA_SOLICITADA';
    case PROJ_COMPLEMENTAR = 'PROJ_COMPLEMENTAR';
    case CANCELADA = 'CANCELADA';

    public function label(): string
    {
        return match ($this) {
            self::REGISTRADA => 'Registrada',
            self::NOTIFICADA_PRESTADORA => 'Notificada Prestadora',
            self::PENDENTE_COM_PRAZO => 'Pendente com Prazo',
            self::EM_EXECUCAO => 'Em Execução',
            self::AGUARDANDO_APROVACAO => 'Aguardando Aprovação',
            self::CONCLUIDA => 'Concluída',
            self::AS_ORCAMENTOS => 'As Orçamentos',
            self::GARANTIA_SOLICITADA => 'Garantia Solicitada',
            self::PROJ_COMPLEMENTAR => 'Proj. Complementar',
            self::CANCELADA => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REGISTRADA => 'gray',
            self::NOTIFICADA_PRESTADORA => 'blue',
            self::PENDENTE_COM_PRAZO => 'indigo',
            self::EM_EXECUCAO => 'warning',
            self::AGUARDANDO_APROVACAO => 'purple',
            self::CONCLUIDA => 'success',
            self::AS_ORCAMENTOS => 'cyan',
            self::GARANTIA_SOLICITADA => 'pink',
            self::PROJ_COMPLEMENTAR => 'violet',
            self::CANCELADA => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::CONCLUIDA,
            self::AS_ORCAMENTOS,
            self::GARANTIA_SOLICITADA,
            self::PROJ_COMPLEMENTAR,
            self::CANCELADA,
        ]);
    }

    public static function ativos(): array
    {
        return [
            self::REGISTRADA,
            self::NOTIFICADA_PRESTADORA,
            self::PENDENTE_COM_PRAZO,
            self::EM_EXECUCAO,
            self::AGUARDANDO_APROVACAO,
        ];
    }
}
