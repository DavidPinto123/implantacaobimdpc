<?php

namespace App\Enums;

enum FaseCronograma: string
{
    case INICIO_PROJETO = 'inicio_projeto';
    case ASSINATURA_CONTRATO = 'assinatura_contrato';
    case CNPJ_SUFRAMA = 'cnpj_suframa';
    case CNPJ_LEGALIZACAO = 'cnpj_legalizacao';
    case CODIGO_ORACLE = 'codigo_oracle';
    case LEVANTAMENTO_CADASTRAL = 'levantamento_cadastral';
    case VISITA_TECNICA = 'visita_tecnica';
    case CONSULTA_PREVIA = 'consulta_previa';
    case RECEBIMENTO_PROJETOS_ARQUITETURA = 'recebimento_projetos_arquitetura';
    case BRIEFING = 'briefing';
    case LAYOUT = 'layout';
    case ORDEM_INVESTIMENTO = 'ordem_investimento';
    case RECEBIMENTO_PROJETOS_COMPLEMENTARES = 'recebimento_projetos_complementares';
    case START_PROJETOS_EXECUTIVOS = 'start_projetos_executivos';
    case EXECUTIVO = 'executivo';
    case KICKOFF = 'kickoff';
    case ORCAMENTOS = 'orcamentos';
    case PRAZO_LEGAL = 'prazo_legal';
    case LIBERACAO_POSSE = 'liberacao_posse';
    case PIN_SUFRAMA = 'pin_suframa';
    case POSSE = 'posse';
    case ENTREGAS_PROPRIETARIO = 'entregas_proprietario';
    case MKT_ATIVACAO_PRE_VENDAS = 'mkt_ativacao_pre_vendas';
    case OBRAS = 'obras';
    case COMPRAS_SUFRAMA = 'compras_suframa';
    case SUFRAMA = 'suframa';
    case IMPLANTACAO = 'implantacao';
    case INAUGURACAO = 'inauguracao';

    case PROJETOS = 'projetos';

    // Fase ad-hoc criada manualmente em um projeto específico; o rótulo
    // visível vem de `cronograma_fases.titulo_personalizado`.
    case PERSONALIZADA = 'personalizada';

    // Serviços de Implantação BIM (DPC Consultoria)
    case BIM_PROJETO_MODELO_1 = 'bim_projeto_modelo_1';
    case BIM_PROJETO_MODELO_2 = 'bim_projeto_modelo_2';
    case BIM_MANDATE = 'bim_mandate';
    case BIM_TEMPLATE = 'bim_template';
    case BIM_SHOWROOM = 'bim_showroom';
    case BIM_MANUAIS = 'bim_manuais';
    case BIM_QUALIFICACAO = 'bim_qualificacao';
    case BIM_MENTORIA = 'bim_mentoria';
    case BIM_AUDITORIA = 'bim_auditoria';
    case BIM_MODELAGEM_PARAMETRICA = 'bim_modelagem_parametrica';

    public function label(): string
    {
        return match ($this) {
            self::INICIO_PROJETO => 'Início de Projeto',
            self::ASSINATURA_CONTRATO => 'Status do Contrato',
            self::CNPJ_SUFRAMA => 'CNPJ Suframa',
            self::CNPJ_LEGALIZACAO => 'CNPJ',
            self::CODIGO_ORACLE => 'Código Oracle',
            self::LEVANTAMENTO_CADASTRAL => 'Levantamento Cadastral',
            self::VISITA_TECNICA => 'Visita Técnica',
            self::CONSULTA_PREVIA => 'Consulta Prévia',
            self::RECEBIMENTO_PROJETOS_ARQUITETURA => 'Recebimento de Projetos (Arquitetura)',
            self::BRIEFING => 'Briefing',
            self::LAYOUT => 'Layout',
            self::ORDEM_INVESTIMENTO => 'Ordem de Investimento',
            self::RECEBIMENTO_PROJETOS_COMPLEMENTARES => 'Recebimento de Projetos (Complementares)',
            self::START_PROJETOS_EXECUTIVOS => 'Start Projetos Executivos',
            self::EXECUTIVO => 'Executivo',
            self::KICKOFF => 'KICKOFF',
            self::ORCAMENTOS => 'Orçamentos',
            self::PRAZO_LEGAL => 'Prazo Legal',
            self::LIBERACAO_POSSE => 'Liberação de Posse',
            self::PIN_SUFRAMA => 'PIN Suframa',
            self::POSSE => 'Posse',
            self::ENTREGAS_PROPRIETARIO => 'Entregas do Proprietário',
            self::MKT_ATIVACAO_PRE_VENDAS => 'MKT Ativação Pré-vendas',
            self::OBRAS => 'Obras',
            self::COMPRAS_SUFRAMA => 'Compras (Suframa / supply)',
            self::SUFRAMA => 'SUFRAMA',
            self::IMPLANTACAO => 'Implantação',
            self::INAUGURACAO => 'Inauguração',
            self::PROJETOS => 'Projetos',
            self::PERSONALIZADA => 'Fase Personalizada',
            self::BIM_PROJETO_MODELO_1 => 'Projeto Modelo 1 – Morar Mais',
            self::BIM_PROJETO_MODELO_2 => 'Projeto Modelo 2 – Capital',
            self::BIM_MANDATE => 'BIM Mandate',
            self::BIM_TEMPLATE => 'Template BIM',
            self::BIM_SHOWROOM => 'Showroom BIM',
            self::BIM_MANUAIS => 'Manuais de Padronização e Qualidade',
            self::BIM_QUALIFICACAO => 'Qualificação Profissional Interna',
            self::BIM_MENTORIA => 'Mentoria e Suporte Técnico',
            self::BIM_AUDITORIA => 'Sistema de Auditoria de Modelos BIM',
            self::BIM_MODELAGEM_PARAMETRICA => 'Sistema de Modelagem Paramétrica',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INICIO_PROJETO => 'teal',
            self::ASSINATURA_CONTRATO => 'teal',
            self::CNPJ_SUFRAMA => 'teal',
            self::CNPJ_LEGALIZACAO => 'teal',
            self::CODIGO_ORACLE => 'cyan',
            self::LEVANTAMENTO_CADASTRAL => 'teal',
            self::VISITA_TECNICA => 'teal',
            self::CONSULTA_PREVIA => 'indigo',
            self::RECEBIMENTO_PROJETOS_ARQUITETURA => 'blue',
            self::BRIEFING => 'blue',
            self::LAYOUT => 'blue',
            self::ORDEM_INVESTIMENTO => 'purple',
            self::RECEBIMENTO_PROJETOS_COMPLEMENTARES => 'purple',
            self::START_PROJETOS_EXECUTIVOS => 'purple',
            self::EXECUTIVO => 'blue',
            self::KICKOFF => 'orange',
            self::ORCAMENTOS => 'blue',
            self::PRAZO_LEGAL => 'indigo',
            self::LIBERACAO_POSSE => 'indigo',
            self::PIN_SUFRAMA => 'indigo',
            self::POSSE => 'success',
            self::ENTREGAS_PROPRIETARIO => 'blue',
            self::MKT_ATIVACAO_PRE_VENDAS => 'orange',
            self::OBRAS => 'gray',
            self::COMPRAS_SUFRAMA => 'purple',
            self::SUFRAMA => 'purple',
            self::IMPLANTACAO => 'orange',
            self::INAUGURACAO => 'success',
            self::PROJETOS => 'blue',
            self::PERSONALIZADA => 'gray',
            self::BIM_PROJETO_MODELO_1 => 'blue',
            self::BIM_PROJETO_MODELO_2 => 'blue',
            self::BIM_MANDATE => 'indigo',
            self::BIM_TEMPLATE => 'indigo',
            self::BIM_SHOWROOM => 'teal',
            self::BIM_MANUAIS => 'purple',
            self::BIM_QUALIFICACAO => 'orange',
            self::BIM_MENTORIA => 'cyan',
            self::BIM_AUDITORIA => 'red',
            self::BIM_MODELAGEM_PARAMETRICA => 'green',
        };
    }

    public function ordem(): int
    {
        return match ($this) {
            self::INICIO_PROJETO => 1,
            self::ASSINATURA_CONTRATO => 2,
            self::CNPJ_SUFRAMA => 3,
            self::CNPJ_LEGALIZACAO => 3,
            self::CODIGO_ORACLE => 4,
            self::LEVANTAMENTO_CADASTRAL => 5,
            self::VISITA_TECNICA => 6,
            self::CONSULTA_PREVIA => 7,
            self::RECEBIMENTO_PROJETOS_ARQUITETURA => 8,
            self::BRIEFING => 9,
            self::LAYOUT => 10,
            self::ORDEM_INVESTIMENTO => 11,
            self::RECEBIMENTO_PROJETOS_COMPLEMENTARES => 12,
            self::START_PROJETOS_EXECUTIVOS => 13,
            self::EXECUTIVO => 14,
            self::KICKOFF => 15,
            self::ORCAMENTOS => 16,
            self::PRAZO_LEGAL => 17,
            self::LIBERACAO_POSSE => 18,
            self::PIN_SUFRAMA => 19,
            self::POSSE => 20,
            self::ENTREGAS_PROPRIETARIO => 20,
            self::MKT_ATIVACAO_PRE_VENDAS => 21,
            self::OBRAS => 22,
            self::COMPRAS_SUFRAMA => 23,
            self::SUFRAMA => 23,
            self::IMPLANTACAO => 24,
            self::INAUGURACAO => 25,
            // PERSONALIZADA recebe ordem alta mas dentro do limite do tinyint (0-127).
            // Usamos 99 para ficar bem distante das fases padrão sem estourar o tipo.
            self::PROJETOS => 4,
            self::PERSONALIZADA => 99,
            self::BIM_PROJETO_MODELO_1 => 10,
            self::BIM_PROJETO_MODELO_2 => 20,
            self::BIM_MANDATE => 30,
            self::BIM_TEMPLATE => 40,
            self::BIM_SHOWROOM => 50,
            self::BIM_MANUAIS => 60,
            self::BIM_QUALIFICACAO => 70,
            self::BIM_MENTORIA => 80,
            self::BIM_AUDITORIA => 90,
            self::BIM_MODELAGEM_PARAMETRICA => 100,
        };
    }

    /**
     * @return StatusCronograma[]
     */
    public function statusDisponiveis(): array
    {
        return match ($this) {
            // Comercial — Status Imóvel
            self::INICIO_PROJETO => [
                StatusCronograma::NA,
                StatusCronograma::PRONTO,
                StatusCronograma::OBRA_PP,
                StatusCronograma::OBRA_SF,
                StatusCronograma::TERRENO,
                StatusCronograma::ESTUDO,
                StatusCronograma::INDEFINIDO,
            ],

            // Status Contrato
            self::ASSINATURA_CONTRATO => [
                StatusCronograma::ASSINADO,
                StatusCronograma::EM_ASSINATURA,
                StatusCronograma::MINUTA,
                StatusCronograma::NEGOCIACAO,
            ],

            self::SUFRAMA,
            self::CNPJ_SUFRAMA,
            self::CNPJ_LEGALIZACAO,
            self::PIN_SUFRAMA,
            self::COMPRAS_SUFRAMA => [
                StatusCronograma::NAO_INICIADO,
                StatusCronograma::EM_ANDAMENTO,
                StatusCronograma::CONCLUIDO,
                StatusCronograma::VERIFICAR,
                StatusCronograma::NA,
                StatusCronograma::SOLICITADO,
                StatusCronograma::ATRASADO,
                StatusCronograma::BLOQUEADO,
            ],

            // Consulta Prévia — lista específica
            self::CONSULTA_PREVIA => [
                StatusCronograma::NAO_INICIADO,
                StatusCronograma::EM_ANDAMENTO,
                StatusCronograma::FINALIZADO,
                StatusCronograma::VERIFICAR,
                StatusCronograma::NA,
                StatusCronograma::SOLICITADO,
            ],

            // Posse
            self::POSSE => [
                StatusCronograma::NAO_REALIZADO,
                StatusCronograma::REALIZADO,
            ],

            // Fases com lista compartilhada
            self::PROJETOS,
            self::LEVANTAMENTO_CADASTRAL,
            self::VISITA_TECNICA,
            self::RECEBIMENTO_PROJETOS_ARQUITETURA,
            self::BRIEFING,
            self::LAYOUT,
            self::ORDEM_INVESTIMENTO,
            self::RECEBIMENTO_PROJETOS_COMPLEMENTARES,
            self::START_PROJETOS_EXECUTIVOS,
            self::EXECUTIVO,
            self::ORCAMENTOS,
            self::PRAZO_LEGAL,
            self::LIBERACAO_POSSE,
            self::ENTREGAS_PROPRIETARIO => [
                StatusCronograma::NAO_INICIADO,
                StatusCronograma::EM_ANDAMENTO,
                StatusCronograma::CONCLUIDO,
                StatusCronograma::VERIFICAR,
                StatusCronograma::NA,
                StatusCronograma::SOLICITADO,
                StatusCronograma::PENDENCIA_ENGENHARIA,
                StatusCronograma::AGENDADO,
                StatusCronograma::ATRASADO,
                StatusCronograma::PENDENCIA,
                StatusCronograma::PENDENCIA_COM,
                StatusCronograma::PENDENCIA_LEG,
                StatusCronograma::PENDENCIA_ARQ,
                StatusCronograma::PENDENCIA_DIR,
            ],

            // Demais fases — lista padrão resumida
            default => [
                StatusCronograma::NAO_INICIADO,
                StatusCronograma::EM_ANDAMENTO,
                StatusCronograma::CONCLUIDO,
                StatusCronograma::ATRASADO,
                StatusCronograma::BLOQUEADO,
            ],
        };
    }

    public function marco(): bool
    {
        return match ($this) {
            self::INICIO_PROJETO,
            self::ASSINATURA_CONTRATO,
            self::CODIGO_ORACLE,
            self::BRIEFING,
            self::START_PROJETOS_EXECUTIVOS,
            self::KICKOFF,
            self::PIN_SUFRAMA,
            self::POSSE,
            self::INAUGURACAO => true,
            default => false,
        };
    }
}
