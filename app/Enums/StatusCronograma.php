<?php

namespace App\Enums;

enum StatusCronograma: string
{
    // Status compartilhado (padrão)
    case NAO_INICIADO = 'nao_iniciado';
    case EM_ANDAMENTO = 'em_andamento';
    case CONCLUIDO = 'concluido';
    case ATRASADO = 'atrasado';
    case BLOQUEADO = 'bloqueado';
    case VERIFICAR = 'verificar';
    case NA = 'na';
    case SOLICITADO = 'solicitado';
    case PENDENCIA_ENGENHARIA = 'pendencia_engenharia';
    case PARALISADO = 'paralisado';
    case AGENDADO = 'agendado';
    case PENDENCIA = 'pendencia';
    case PENDENCIA_COM = 'pendencia_com';
    case PENDENCIA_LEG = 'pendencia_leg';
    case PENDENCIA_ARQ = 'pendencia_arq';
    case PENDENCIA_DIR = 'pendencia_dir';

    // Status Imóvel (Início de Projeto)
    case PRONTO = 'pronto';
    case OBRA_PP = 'obra_pp';
    case OBRA_SF = 'obra_sf';
    case TERRENO = 'terreno';
    case ESTUDO = 'estudo';
    case INDEFINIDO = 'indefinido';

    // Status Contrato
    case ASSINADO = 'assinado';
    case EM_ASSINATURA = 'em_assinatura';
    case MINUTA = 'minuta';
    case NEGOCIACAO = 'negociacao';

    // Status Aprovação (Ordem de Investimento)
    case EM_APROVACAO = 'em_aprovacao';
    case APROVADO = 'aprovado';
    case REVISAO = 'revisao';

    // Status Consulta Prévia
    case FINALIZADO = 'finalizado';

    // Status Posse
    case NAO_REALIZADO = 'nao_realizado';
    case REALIZADO = 'realizado';

    public function label(): string
    {
        return match ($this) {
            self::NAO_INICIADO => 'Não Iniciado',
            self::EM_ANDAMENTO => 'Em Andamento',
            self::CONCLUIDO => 'Concluído',
            self::ATRASADO => 'Atrasado',
            self::BLOQUEADO => 'Bloqueado',
            self::VERIFICAR => 'Verificar',
            self::NA => 'N/A',
            self::SOLICITADO => 'Solicitado',
            self::PENDENCIA_ENGENHARIA => 'Pendência Engª',
            self::PARALISADO => 'Paralisado',
            self::AGENDADO => 'Agendado',
            self::PENDENCIA => 'Pendência',
            self::PENDENCIA_COM => 'Pendência Com',
            self::PENDENCIA_LEG => 'Pendência Leg',
            self::PENDENCIA_ARQ => 'Pendência Arq',
            self::PENDENCIA_DIR => 'Pendência Dir',
            self::PRONTO => 'Pronto',
            self::OBRA_PP => 'Obra PP',
            self::OBRA_SF => 'Obra SF',
            self::TERRENO => 'Terreno',
            self::ESTUDO => 'Estudo',
            self::INDEFINIDO => '?',
            self::ASSINADO => 'Assinado',
            self::EM_ASSINATURA => 'Em Assinatura',
            self::MINUTA => 'Minuta',
            self::NEGOCIACAO => 'Negociação',
            self::EM_APROVACAO => 'Em Aprovação',
            self::APROVADO => 'Aprovado',
            self::REVISAO => 'Revisão',
            self::FINALIZADO => 'Finalizado',
            self::NAO_REALIZADO => 'Não Realizado',
            self::REALIZADO => 'Realizado',
        };
    }

    /**
     * Porcentagem de progresso associada ao status do contrato.
     * Conforme reunião 09/05: Negociação 0% → Minuta 25% → Em Assinatura 50% → Assinado 100%.
     * A barra de progresso da fase ASSINATURA_CONTRATO é alimentada automaticamente
     * pelo retorno deste método quando o status corresponde a uma das 4 fases do contrato.
     */
    public function percentualConclusao(): ?int
    {
        return match ($this) {
            self::NEGOCIACAO => 0,
            self::MINUTA => 25,
            self::EM_ASSINATURA => 50,
            self::ASSINADO => 100,
            default => null,
        };
    }

    /**
     * Lista de status considerados "deprecated" — não aparecem em selects
     * novos, mas continuam funcionando para registros históricos.
     *
     * @var array<int, self>
     */
    public const DEPRECATED = [
        self::PARALISADO,
    ];

    /**
     * Retorna apenas os status válidos para seleção (exclui os deprecated).
     *
     * @return array<int, self>
     */
    public static function disponiveis(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case): bool => ! in_array($case, self::DEPRECATED, true),
        ));
    }

    public function color(): string
    {
        return match ($this) {
            self::NAO_INICIADO => '#6b7280',
            self::EM_ANDAMENTO => '#4a9eff',
            self::CONCLUIDO => '#2dd67c',
            self::ATRASADO => '#ff4d6a',
            self::BLOQUEADO => '#f5ba00',
            self::VERIFICAR => '#f59e0b',
            self::NA => '#9ca3af',
            self::SOLICITADO => '#8b5cf6',
            self::PENDENCIA_ENGENHARIA => '#f5ba00',
            self::PARALISADO => '#f97316',
            self::AGENDADO => '#06b6d4',
            self::PENDENCIA => '#f5ba00',
            self::PENDENCIA_COM => '#f5ba00',
            self::PENDENCIA_LEG => '#f5ba00',
            self::PENDENCIA_ARQ => '#f5ba00',
            self::PENDENCIA_DIR => '#f5ba00',
            self::PRONTO => '#2dd67c',
            self::OBRA_PP => '#f97316',
            self::OBRA_SF => '#4a9eff',
            self::TERRENO => '#84cc16',
            self::ESTUDO => '#a78bfa',
            self::INDEFINIDO => '#9ca3af',
            self::ASSINADO => '#2dd67c',
            self::EM_ASSINATURA => '#4a9eff',
            self::MINUTA => '#f59e0b',
            self::NEGOCIACAO => '#8b5cf6',
            self::EM_APROVACAO => '#f59e0b',
            self::APROVADO => '#2dd67c',
            self::REVISAO => '#ef4444',
            self::FINALIZADO => '#2dd67c',
            self::NAO_REALIZADO => '#6b7280',
            self::REALIZADO => '#2dd67c',
        };
    }
}
