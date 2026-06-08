<?php

namespace Database\Seeders;

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\TipoDiasTemplate;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseDependencia;
use App\Models\CronogramaTemplateFaseItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Template de Implantação BIM — DPC Consultoria (Proposta R7 Mai/2026).
 *
 * 10 serviços principais com 86 subitens totais, valores da proposta
 * e dependências entre fases conforme Gantt "CAPITAL CRONOGRAMA IMPLANTAÇÃO".
 *
 * Dependências principais registradas:
 *   BIM Mandate, Template e Showroom → aguardam conclusão dos Projetos Modelo
 *   Mentoria / Projetos Piloto        → aguarda Template, Showroom, BIM Mandate e Qualificação
 */
class CronogramaTemplateBIMSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('cronograma_template_fase_item_dependencias')->truncate();
        DB::table('cronograma_template_fase_itens')->truncate();
        DB::table('cronograma_template_fase_dependencias')->truncate();
        DB::table('cronograma_template_fases')->truncate();
        DB::table('cronograma_templates')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $template = CronogramaTemplate::create([
            'nome'        => 'Implantação BIM — DPC Consultoria',
            'tipo_obra'   => TipoObraCronograma::IMPLANTACAO_BIM->value,
            'ancora_campo' => 'manual',
            'ativo'       => true,
            'observacoes' => 'Template padrão baseado na Proposta DPC R7 de Mai/2026 (13 meses). Os valores podem ser ajustados por contrato.',
        ]);

        // ── Criar fases e indexar por enum value ──────────────────────────────
        $fases = [];
        foreach ($this->servicos() as $i => $s) {
            $fase = CronogramaTemplateFase::create([
                'cronograma_template_id' => $template->id,
                'fase'                   => $s['fase']->value,
                'titulo_personalizado'   => $s['titulo'],
                'ordem'                  => ($i + 1) * 10,
                'duracao_dias'           => 0,
                'valor'                  => $s['valor'],
                'descricao'              => $s['descricao'],
                'tipo_dias'              => TipoDiasTemplate::CORRIDOS->value,
                'visivel'                => true,
                // Projeto Modelo 1 é a âncora; as demais partem das dependências
                'is_ancora'              => $s['fase'] === FaseCronograma::BIM_PROJETO_MODELO_1,
            ]);
            $fases[$s['fase']->value] = $fase;

            foreach ($s['subitens'] as $j => $item) {
                CronogramaTemplateFaseItem::create([
                    'cronograma_template_fase_id' => $fase->id,
                    'titulo'                      => $item['titulo'],
                    'valor'                       => $item['valor'],
                    'descricao'                   => $item['descricao'] ?? null,
                    'ordem'                       => ($j + 1) * 10,
                ]);
            }
        }

        // ── Dependências entre fases ──────────────────────────────────────────
        $fim = GatilhoTemplateFase::FIM_ANTERIOR->value;

        // BIM Mandate, Template e Showroom dependem dos dois Projetos Modelo
        foreach ([FaseCronograma::BIM_MANDATE, FaseCronograma::BIM_TEMPLATE, FaseCronograma::BIM_SHOWROOM] as $dep) {
            foreach ([FaseCronograma::BIM_PROJETO_MODELO_1, FaseCronograma::BIM_PROJETO_MODELO_2] as $pre) {
                CronogramaTemplateFaseDependencia::create([
                    'cronograma_template_fase_id' => $fases[$dep->value]->id,
                    'depende_de_fase'             => $pre->value,
                    'gatilho'                     => $fim,
                    'gap_dias'                    => 0,
                ]);
            }
        }

        // Mentoria / Projetos Piloto depende de Template, Showroom, BIM Mandate e Qualificação
        foreach ([
            FaseCronograma::BIM_TEMPLATE,
            FaseCronograma::BIM_SHOWROOM,
            FaseCronograma::BIM_MANDATE,
            FaseCronograma::BIM_QUALIFICACAO,
        ] as $pre) {
            CronogramaTemplateFaseDependencia::create([
                'cronograma_template_fase_id' => $fases[FaseCronograma::BIM_MENTORIA->value]->id,
                'depende_de_fase'             => $pre->value,
                'gatilho'                     => $fim,
                'gap_dias'                    => 0,
            ]);
        }
    }

    // ── Definições completas ──────────────────────────────────────────────────

    private function servicos(): array
    {
        return [
            // ─────────────────────────────────────────────────────────────────
            // 1. PROJETO MODELO 1 — MORAR MAIS
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_PROJETO_MODELO_1,
                'titulo'  => 'Projeto Modelo 1 – Morar Mais',
                'valor'   => 166560.00,
                'descricao' => 'Desenvolvimento do Projeto Modelo BIM para a tipologia Morar Mais (Ideal + Lagos) com base nos projetos já desenvolvidos em DWG/RVT. Reproduz fielmente plantas, cortes, elevações, tabelas, legendas e detalhamentos, servindo de referência gráfica e de informação para BIM Mandate, Templates, Showroom, Qualificação e sistemas de Auditoria e Modelagem Paramétrica.',
                'subitens' => [
                    ['titulo' => 'Arquitetura – Executivo',                        'valor' => 22200.00, 'descricao' => 'Modelagem BIM executiva de Arquitetura: plantas, cortes, elevações, detalhes construtivos e folhas de impressão conforme padrão Capital.'],
                    ['titulo' => 'Arquitetura – Interiores e Luminotécnica',        'valor' => 12600.00, 'descricao' => 'Ambientação, renderização e luminotécnica de espaços internos e externos usando ferramentas Revit e Autodesk.'],
                    ['titulo' => 'Paisagismo',                                      'valor' =>  9800.00, 'descricao' => 'Modelagem BIM de elementos de paisagismo: vegetação, pavimentação externa, mobiliário urbano e detalhamentos de áreas comuns.'],
                    ['titulo' => 'Estrutura – Executivo',                           'valor' => 12000.00, 'descricao' => 'Modelagem BIM estrutural: superestrutura, ancoragem, radier, estrutura metálica, cobertura e fundação, com interoperabilidade TQS.'],
                    ['titulo' => 'Infraestrutura – Terreno e infra de coleta',      'valor' => 13600.00, 'descricao' => 'Modelagem BIM de infraestrutura externa: terraplenagem, pavimentação, contenções e rede coletora de esgoto/pluvial.'],
                    ['titulo' => 'Hidrossanitário – Executivo',                     'valor' => 18600.00, 'descricao' => 'Modelagem BIM de instalações hidrossanitárias executivas: água fria, esgoto, drenagem e ETE.'],
                    ['titulo' => 'Elétrica – Executivo',                            'valor' => 18600.00, 'descricao' => 'Modelagem BIM de instalações elétricas: rede de alimentação e distribuição, BT, MT, internet, TV e comunicação.'],
                    ['titulo' => 'Ar condicionado – Executivo',                     'valor' =>  6890.00, 'descricao' => 'Modelagem BIM de instalações mecânicas: refrigeração, exaustão e insuflação de ambientes.'],
                    ['titulo' => 'PPCI – Executivo (Incêndio)',                     'valor' =>  6890.00, 'descricao' => 'Modelagem BIM do PPCI: sistemas de detecção e combate a incêndio, análise segundo normas ABNT e uso de plugins especializados.'],
                    ['titulo' => 'SPDA – Executivo',                                'valor' =>  6890.00, 'descricao' => 'Modelagem BIM do Sistema de Proteção contra Descargas Atmosféricas (para-raios) executivo.'],
                    ['titulo' => 'Gás – Executivo',                                 'valor' =>  6890.00, 'descricao' => 'Modelagem BIM da rede de distribuição de gás combustível executiva.'],
                    ['titulo' => 'Compatibilização',                                'valor' =>  6000.00, 'descricao' => 'Coordenação e compatibilização BIM de todas as disciplinas: clash detection via Navisworks, relatórios de interferências e revisão colaborativa.'],
                    ['titulo' => 'Planejamento de Obras e Simulação 4D',            'valor' => 16600.00, 'descricao' => 'Planejamento 4D: integração Revit + MS Project + Navisworks para simulação e previsibilidade de eventos de obra.'],
                    ['titulo' => 'Orçamento de obras em conceito BIM',              'valor' =>  9000.00, 'descricao' => 'Extração de quantitativos do modelo BIM e elaboração de orçamento de obras integrado por plugin Revit–Sistema de orçamento.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 2. PROJETO MODELO 2 — CAPITAL
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_PROJETO_MODELO_2,
                'titulo'  => 'Projeto Modelo 2 – Capital',
                'valor'   => 215590.00,
                'descricao' => 'Desenvolvimento do Projeto Modelo BIM para a tipologia Capital (Horizonte Ponta Negra) com base nos projetos já desenvolvidos em DWG/RVT. Reproduz fielmente plantas, cortes, elevações, tabelas, legendas e detalhamentos, servindo de referência gráfica e de informação para todas as demais etapas da implantação BIM.',
                'subitens' => [
                    ['titulo' => 'Arquitetura – Executivo',                        'valor' => 26600.00, 'descricao' => 'Modelagem BIM executiva de Arquitetura: plantas, cortes, elevações, detalhes construtivos e folhas de impressão conforme padrão Capital.'],
                    ['titulo' => 'Arquitetura – Interiores e Luminotécnica',        'valor' => 12600.00, 'descricao' => 'Ambientação, renderização e luminotécnica de espaços internos e externos usando ferramentas Revit e Autodesk.'],
                    ['titulo' => 'Paisagismo',                                      'valor' =>  9800.00, 'descricao' => 'Modelagem BIM de elementos de paisagismo: vegetação, pavimentação externa, mobiliário urbano e detalhamentos de áreas comuns.'],
                    ['titulo' => 'Estrutura – Executivo',                           'valor' => 16780.00, 'descricao' => 'Modelagem BIM estrutural: superestrutura, ancoragem, radier, estrutura metálica, cobertura e fundação, com interoperabilidade TQS.'],
                    ['titulo' => 'Infraestrutura – Terreno e infra de coleta',      'valor' => 19600.00, 'descricao' => 'Modelagem BIM de infraestrutura externa: terraplenagem, pavimentação, contenções e rede coletora de esgoto/pluvial.'],
                    ['titulo' => 'Hidrossanitário – Executivo',                     'valor' => 21400.00, 'descricao' => 'Modelagem BIM de instalações hidrossanitárias executivas: água fria, esgoto, drenagem e ETE.'],
                    ['titulo' => 'Elétrica – Executivo',                            'valor' => 23230.00, 'descricao' => 'Modelagem BIM de instalações elétricas: rede de alimentação e distribuição, BT, MT, internet, TV e comunicação.'],
                    ['titulo' => 'Ar condicionado – Executivo',                     'valor' => 16700.00, 'descricao' => 'Modelagem BIM de instalações mecânicas: refrigeração, exaustão e insuflação de ambientes.'],
                    ['titulo' => 'PPCI – Executivo (Incêndio)',                     'valor' => 16700.00, 'descricao' => 'Modelagem BIM do PPCI: sistemas de detecção e combate a incêndio, análise segundo normas ABNT e uso de plugins especializados.'],
                    ['titulo' => 'SPDA – Executivo',                                'valor' =>  6890.00, 'descricao' => 'Modelagem BIM do Sistema de Proteção contra Descargas Atmosféricas (para-raios) executivo.'],
                    ['titulo' => 'Gás – Executivo',                                 'valor' =>  6890.00, 'descricao' => 'Modelagem BIM da rede de distribuição de gás combustível executiva.'],
                    ['titulo' => 'Compatibilização',                                'valor' =>  9800.00, 'descricao' => 'Coordenação e compatibilização BIM de todas as disciplinas: clash detection via Navisworks, relatórios de interferências e revisão colaborativa.'],
                    ['titulo' => 'Planejamento de Obras e Simulação 4D',            'valor' => 16600.00, 'descricao' => 'Planejamento 4D: integração Revit + MS Project + Navisworks para simulação e previsibilidade de eventos de obra.'],
                    ['titulo' => 'Orçamento de obras em conceito BIM',              'valor' => 12000.00, 'descricao' => 'Extração de quantitativos do modelo BIM e elaboração de orçamento de obras integrado por plugin Revit–Sistema de orçamento.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 3. BIM MANDATE — TODAS AS DISCIPLINAS
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_MANDATE,
                'titulo'  => 'BIM Mandate – Todas as Disciplinas',
                'valor'   => 63400.00,
                'descricao' => 'Construção das Normas de Modelagem BIM (BIM Mandate) para padronização de informações e procedimentos em projetos BIM do Grupo Capital e Morar Mais. Define entregáveis, padrões de qualidade, compatibilização entre disciplinas e extração de quantitativos. Base para contratos com projetistas externos e treinamento de novos colaboradores. Desenvolvido com referência nos dois Projetos Modelos.',
                'subitens' => [
                    ['titulo' => 'BIM Mandate de Arquitetura',      'valor' => 16000.00, 'descricao' => 'Normas de modelagem BIM para Arquitetura Executiva e Interiores: padrão de famílias, tipos, detalhamento, folhas de impressão e extração de quantitativos.'],
                    ['titulo' => 'BIM Mandate de Estrutura',         'valor' =>  6600.00, 'descricao' => 'Normas de modelagem BIM para Estrutura: superestrutura, ancoragem, radier, estrutura metálica, cobertura e fundação, com regras de interoperabilidade TQS.'],
                    ['titulo' => 'BIM Mandate de Hidrossanitário',   'valor' =>  6800.00, 'descricao' => 'Normas de modelagem BIM para Hidrossanitário: padrão de elementos, conexões, nomenclaturas e extração de quantitativos de água fria, esgoto, drenagem e ETE.'],
                    ['titulo' => 'BIM Mandate de Elétrica',          'valor' =>  6800.00, 'descricao' => 'Normas de modelagem BIM para Elétrica: padrão de circuitos, canaletas, bandejas, pontos, redes e sistemas de segurança e comunicação.'],
                    ['titulo' => 'BIM Mandate de Ar condicionado',   'valor' =>  6800.00, 'descricao' => 'Normas de modelagem BIM para HVAC: padrão de dutos, exaustores, grelhas e tirantes para refrigeração e exaustão de ambientes.'],
                    ['titulo' => 'BIM Mandate de PPCI',              'valor' =>  6800.00, 'descricao' => 'Normas de modelagem BIM para PPCI: padrão de elementos de combate a incêndio, detecção, sinalização e conformidade com normas ABNT.'],
                    ['titulo' => 'BIM Mandate de SPDA',              'valor' =>  6800.00, 'descricao' => 'Normas de modelagem BIM para SPDA: padrão de captores, condutores e aterramentos conforme NBR 5419.'],
                    ['titulo' => 'BIM Mandate de Gás',               'valor' =>  6800.00, 'descricao' => 'Normas de modelagem BIM para Gás: padrão de ramais, medidores, registros e elementos de segurança da rede de gás combustível.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 4. TEMPLATE BIM — TODAS AS DISCIPLINAS
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_TEMPLATE,
                'titulo'  => 'Template BIM – Todas as Disciplinas',
                'valor'   => 54890.00,
                'descricao' => 'Criação e parametrização dos Templates BIM por disciplina: detalhamentos técnicos, parâmetros de coordenação, orçamentação e planejamento, famílias essenciais (fechamentos, revestimentos, tags e folhas de impressão). Integrado aos plugins de Auditoria e Modelagem Paramétrica. Construído após finalização e aprovação dos Projetos Modelos.',
                'subitens' => [
                    ['titulo' => 'Template de Arquitetura',      'valor' => 12890.00, 'descricao' => 'Template Revit de Arquitetura: configurações de vistas, folhas, tags, anotações, estilos de objeto e famílias carregadas conforme BIM Mandate.'],
                    ['titulo' => 'Template de Estrutura',        'valor' =>  6000.00, 'descricao' => 'Template Revit de Estrutura: configurações de elementos estruturais, parâmetros de carga e interoperabilidade com TQS.'],
                    ['titulo' => 'Template de Hidrossanitário',  'valor' =>  6000.00, 'descricao' => 'Template Revit de Hidrossanitário: configurações de sistemas, nomenclaturas e parâmetros para extração automática de quantitativos.'],
                    ['titulo' => 'Template de Elétrica',         'valor' =>  6000.00, 'descricao' => 'Template Revit de Elétrica: configurações de painéis, circuitos, cargas e tabelas de distribuição conforme padrão Capital.'],
                    ['titulo' => 'Template de Ar condicionado',  'valor' =>  6000.00, 'descricao' => 'Template Revit de HVAC: configurações de sistemas mecânicos, tipos de dutos e conectores para refrigeração e exaustão.'],
                    ['titulo' => 'Template de PPCI',             'valor' =>  6000.00, 'descricao' => 'Template Revit de PPCI: configurações de sistemas de incêndio, normas aplicadas e tabelas de verificação automática.'],
                    ['titulo' => 'Template de SPDA',             'valor' =>  6000.00, 'descricao' => 'Template Revit de SPDA: configurações de elementos de proteção atmosférica e tabelas de conformidade NBR 5419.'],
                    ['titulo' => 'Template de Gás',              'valor' =>  6000.00, 'descricao' => 'Template Revit de Gás: configurações de sistemas de gás combustível, parâmetros de pressão e tabelas de quantitativos.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 5. SHOWROOM E BIBLIOTECAS — TODAS AS DISCIPLINAS
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_SHOWROOM,
                'titulo'  => 'Showroom BIM – Todas as Disciplinas',
                'valor'   => 64690.00,
                'descricao' => 'Criação dos Showrooms de famílias: arquivos únicos por disciplina onde todos os blocos e famílias são mantidos e constantemente atualizados. Possuem dados essenciais para projetos, orçamento e planejamento. Integrados aos plugins de Auditoria e Modelagem Paramétrica. Construídos após aprovação dos Projetos Modelos.',
                'subitens' => [
                    ['titulo' => 'Showroom de Arquitetura',      'valor' => 22690.00, 'descricao' => 'Biblioteca BIM de Arquitetura: famílias paramétricas de janelas, portas, pisos, revestimentos, forros, mobiliários e equipamentos com parâmetros de orçamento e planejamento.'],
                    ['titulo' => 'Showroom de Estrutura',        'valor' =>  6000.00, 'descricao' => 'Biblioteca BIM de Estrutura: famílias de pilares, vigas, lajes, escadas, fundações e pré-moldados com parâmetros técnicos e quantitativos.'],
                    ['titulo' => 'Showroom de Hidrossanitário',  'valor' =>  6000.00, 'descricao' => 'Biblioteca BIM de Hidrossanitário: famílias de conexões, registros, ralos, louças e equipamentos de ETE com parâmetros de especificação.'],
                    ['titulo' => 'Showroom de Elétrica',         'valor' =>  6000.00, 'descricao' => 'Biblioteca BIM de Elétrica: famílias de quadros, dispositivos, luminárias, tomadas, interruptores e equipamentos de comunicação.'],
                    ['titulo' => 'Showroom de Ar condicionado',  'valor' =>  6000.00, 'descricao' => 'Biblioteca BIM de HVAC: famílias de equipamentos de refrigeração, dutos, grelhas, difusores e exaustores com dados de eficiência.'],
                    ['titulo' => 'Showroom de PPCI',             'valor' =>  6000.00, 'descricao' => 'Biblioteca BIM de PPCI: famílias de sprinklers, detectores, extintores, hidrantes e sinalização de emergência.'],
                    ['titulo' => 'Showroom de SPDA',             'valor' =>  6000.00, 'descricao' => 'Biblioteca BIM de SPDA: famílias de captores, condutores, caixas de inspeção e hastes de aterramento.'],
                    ['titulo' => 'Showroom de Gás',              'valor' =>  6000.00, 'descricao' => 'Biblioteca BIM de Gás: famílias de medidores, reguladores, válvulas e conexões da rede de gás combustível.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 6. MANUAIS DE PADRONIZAÇÃO E QUALIDADE
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_MANUAIS,
                'titulo'  => 'Manuais de Padronização e Qualidade',
                'valor'   => 92470.00,
                'descricao' => 'Elaboração dos Manuais de Padronização de Qualidade de Projetos: recursos fundamentais para construção de diretrizes de qualidade, métodos de avaliação e aprovação de projetos, orçamentos e obras. Diretamente vinculados aos processos e ferramentas BIM. Base para treinamento de novas equipes e colaboradores.',
                'subitens' => [
                    ['titulo' => 'Manual da Marca',                   'valor' => 18000.00, 'descricao' => 'Define e determina o uso das marcas e identidade visual da empresa em empreendimentos Capital e Morar Mais. Possibilita a construção de listas de revisão e métodos eletrônicos de modelagem e auditoria de conformidade visual.'],
                    ['titulo' => 'Manual de Entregáveis por Fase',    'valor' =>  8690.00, 'descricao' => 'Define o conteúdo de cada entregável BIM por fase de projeto nos contratos com projetistas externos, com detalhes que possibilitam revisão eletrônica. Inclui definição de conteúdo de pranchas e vistas.'],
                    ['titulo' => 'Manual de Orçamentação de Obras',   'valor' => 12220.00, 'descricao' => 'Registra os métodos de construção de orçamentos de obras em conceito BIM: métodos de quantificação, índices de estimativa para pré-orçamentos e regras de utilização de composições de preço, insumos e mão de obra.'],
                    ['titulo' => 'Manual de Planejamento de Obras',   'valor' => 12220.00, 'descricao' => 'Registra os métodos de planejamento de obras BIM considerando todas as atividades e eventos, índices de tempo e fatores logísticos, bem como o registro de avanços em cronogramas para estatísticas e melhorias nas obras seguintes.'],
                    ['titulo' => 'Manual de Construtividade',         'valor' => 16900.00, 'descricao' => 'Registra as técnicas internas de aplicação e construção dos elementos mais estratégicos da execução de obras, gerando conjunto de orientações para novos coordenadores e apoiando treinamentos de novas equipes.'],
                    ['titulo' => 'Manual de Vistoria e Levantamento', 'valor' => 12220.00, 'descricao' => 'Diretamente vinculado às FVS\'s, registra os métodos de vistoria de qualidade dos empreendimentos, bem como os procedimentos de levantamento e medição do avanço das obras.'],
                    ['titulo' => 'Manual de Asbuilt',                 'valor' => 12220.00, 'descricao' => 'Vinculado a todas as informações e documentos relacionados à entrega das obras por parte de coordenadores e empreiteiros. Registra entrega de documentos, projetos, controles e materiais relacionados a equipamentos e soluções construtivas, considerando garantias e prazos de manutenção.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 7. QUALIFICAÇÃO PROFISSIONAL INTERNA
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_QUALIFICACAO,
                'titulo'  => 'Qualificação Profissional Interna',
                'valor'   => 396580.00,
                'descricao' => 'Programa estruturado de capacitação interna em BIM: 19 treinamentos presenciais avançados com foco nos novos processos BIM do Grupo Capital. Garante a transferência de informações e métodos e cria progressivamente a independência da equipe em relação à Consultoria. Todos os cursos utilizam projetos reais da empresa como exercício.',
                'subitens' => [
                    ['titulo' => '6.1 – Introdução ao Conceito BIM',                             'valor' =>  6000.00, 'descricao' => 'Palestra conceitual para toda a equipe técnica: fundamentos BIM, diferenças CAD x BIM, vantagens e desvantagens, processo de implantação previsto. Carga horária: 4 horas.'],
                    ['titulo' => '6.2 – Revit Básico Multidisciplinar',                          'valor' => 18900.00, 'descricao' => 'Estudo dos principais comandos do Revit aplicados ao conceito BIM: modelagem, extração de quantitativos, recursos de coordenação e planejamento. Base obrigatória para todos os demais cursos. Carga horária: 20 horas.'],
                    ['titulo' => '6.3 – Revit Avançado – Arquitetura – Projeto Executivo',       'valor' => 21690.00, 'descricao' => 'Projeto executivo de arquitetura em nível avançado: fases, detalhamento de esquadrias, exportações, tabelas, legendas e técnicas avançadas de modelagem. Aprofundamento no uso de plugins. Carga horária: 20 horas.'],
                    ['titulo' => '6.4 – Revit Avançado – Arquitetura – Projeto de Interiores',   'valor' => 21690.00, 'descricao' => 'Ambientação e renderização de espaços internos e externos: perspectivas, revestimentos, iluminação, vegetação, mobiliários e vídeos realistas com IA. Carga horária: 20 horas.'],
                    ['titulo' => '6.5 – Revit Avançado – Estrutura em Concreto Armado',         'valor' => 21690.00, 'descricao' => 'Elementos estruturais: lajes, vigas, pilares, escadas, estruturas em concreto e pré-moldadas. Interoperabilidade com TQS. Carga horária: 20 horas.'],
                    ['titulo' => '6.6 – Revit Avançado – Hidrossanitário',                       'valor' => 21690.00, 'descricao' => 'Técnicas avançadas de modelagem de instalações hidráulicas: água, esgoto, incêndio, gases e pluvial. Papel da compatibilização nos sistemas prediais. Carga horária: 20 horas.'],
                    ['titulo' => '6.7 – Revit Avançado – Elétrica',                              'valor' => 21690.00, 'descricao' => 'Modelagem paramétrica de instalações elétricas: distribuição, canaletas, circuitos, bandejas de cabos, segurança e lógica. Foco em compatibilização e orçamentação. Carga horária: 20 horas.'],
                    ['titulo' => '6.8 – Revit Avançado – Ar condicionado',                       'valor' => 21690.00, 'descricao' => 'Modelagem de instalações mecânicas: dutos, exaustores, grelhas, tirantes e refrigeração/exaustão. Relação com planilhas orçamentárias e planejamento. Carga horária: 20 horas.'],
                    ['titulo' => '6.9 – Revit Avançado – PPCI',                                  'valor' => 21690.00, 'descricao' => 'Projeto de PPCI em nível avançado: produção de projetos, análise normativa ABNT, plugins exclusivos para PPCI e integração com orçamento e planejamento. Carga horária: 20 horas.'],
                    ['titulo' => '6.10 – Civil 3D – Empreendimentos Imobiliários',               'valor' => 26600.00, 'descricao' => 'Aproveitamento do solo e detalhamentos técnicos com Civil 3D: cálculos de terraplanagem, construção de taludes e adaptação de vias de empreendimentos imobiliários. Limitado a 15 profissionais. Carga horária: 30 horas.'],
                    ['titulo' => '6.11 – Coordenação e Compatibilização BIM',                    'valor' => 21690.00, 'descricao' => 'Capacitação de coordenadores e gerentes: clash detection no Navisworks, BIM Collaborate Pro, coordenação em nuvem e integração otimizada entre disciplinas. Carga horária: 12 horas.'],
                    ['titulo' => '6.12 – Orçamento de Obras em Conceito BIM',                    'valor' => 23600.00, 'descricao' => 'Quantificação automática e semiautomática de modelos BIM e relação direta com composições de preço via plugin Revit–Sistema de orçamento. Carga horária: 20 horas.'],
                    ['titulo' => '6.13 – MS Project – Planejamento de Obras Residenciais',       'valor' => 23600.00, 'descricao' => 'Planejamento de obras com MS Project: infraestrutura, rede de esgoto/pluvial/elétrica e construção de torres Capital. Limitado a 15 profissionais (licenças próprias). Carga horária: 20 horas.'],
                    ['titulo' => '6.14 – Planejamento de Obras + Navisworks',                    'valor' => 18000.00, 'descricao' => 'Planejamento 4D integrando Revit + Navisworks: cronograma visual, detecção de gargalos, simulações de obra e otimização de recursos. Carga horária: 12 horas.'],
                    ['titulo' => '6.15 – Revit Avançado – Edição e Criação de Famílias BIM',     'valor' => 19600.00, 'descricao' => 'Elaboração de famílias paramétricas, parâmetros e customizações para independência em templates, famílias e BIM Mandates. Carga horária: 20 horas.'],
                    ['titulo' => '6.16 – Introdução à Programação para Arquitetos e Engenheiros', 'valor' => 21690.00, 'descricao' => 'Lógica de programação, algoritmos, variáveis, condicionais e laços aplicados ao mercado AEC. Base para desenvolvimento de plugins Revit com C#. Carga horária: 20 horas.'],
                    ['titulo' => '6.17 – Programação – C# Básico',                               'valor' => 21690.00, 'descricao' => 'Fundamentos de C#: orientação a objetos, classes, métodos, propriedades e listas. Base para desenvolvimento de aplicações e plugins Revit. Carga horária: 20 horas.'],
                    ['titulo' => '6.18 – Programação – C# Avançado',                             'valor' => 21690.00, 'descricao' => 'C# avançado: herança, interfaces, LINQ, manipulação de arquivos, exceções e integração inicial com API Revit para desenvolvimento de plugins. Carga horária: 20 horas.'],
                    ['titulo' => '6.19 – Programação – API\'s Revit, Plugins e IA',              'valor' => 21690.00, 'descricao' => 'Desenvolvimento de plugins Revit via API oficial: manipulação de elementos, parâmetros, vistas, famílias e automações. Uso de IA (Anthropic Claude) como suporte no desenvolvimento. Carga horária: 20 horas.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 8. MENTORIA E SUPORTE TÉCNICO — PROJETOS PILOTO
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_MENTORIA,
                'titulo'  => 'Mentoria e Suporte Técnico – Projetos Piloto',
                'valor'   => 184500.00,
                'descricao' => 'Acompanhamento técnico de 5 projetos reais em conceito BIM após conclusão de Projetos Modelos, BIM Mandate, Templates, Showrooms e Treinamentos. Orienta coordenadores e projetistas, ajusta conteúdos anteriores, acompanha reuniões de compatibilização e valida entregáveis parciais e finais. Divisor de águas entre teoria e produção real.',
                'subitens' => [
                    ['titulo' => 'Projeto Piloto 1', 'valor' => 36900.00, 'descricao' => 'Acompanhamento completo do 1.º projeto piloto real: apresentação dos processos BIM aos projetistas, suporte a BIM Mandate/Templates/Showrooms/Plugins, análise de entregas parciais e finais, relatórios de compatibilização e suporte à coordenação interna.'],
                    ['titulo' => 'Projeto Piloto 2', 'valor' => 36900.00, 'descricao' => 'Acompanhamento completo do 2.º projeto piloto real: aplicação consolidada dos padrões BIM com ajustes identificados no Piloto 1, suporte técnico remoto e presencial, relatórios de avanço e mentoria à equipe de coordenação.'],
                    ['titulo' => 'Projeto Piloto 3', 'valor' => 36900.00, 'descricao' => 'Acompanhamento completo do 3.º projeto piloto real: verificação da maturidade BIM da equipe, ajustes finos em templates e famílias, suporte a orçamento e planejamento BIM e relatório de evolução.'],
                    ['titulo' => 'Projeto Piloto 4', 'valor' => 36900.00, 'descricao' => 'Acompanhamento completo do 4.º projeto piloto real: foco na independência progressiva da equipe, validação de processos automatizados e suporte especializado nas disciplinas de maior complexidade.'],
                    ['titulo' => 'Projeto Piloto 5', 'valor' => 36900.00, 'descricao' => 'Acompanhamento completo do 5.º projeto piloto real: consolidação final da implantação BIM, avaliação do nível de maturidade alcançado e relatório de encerramento da implantação.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 9. SISTEMA DE AUDITORIA DE MODELOS BIM
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_AUDITORIA,
                'titulo'  => 'Sistema de Auditoria de Modelos BIM',
                'valor'   => 252850.00,
                'descricao' => 'Implantação de sistema de auditoria automatizada dos modelos BIM por meio de plugins customizados para o Revit. Otimiza a análise de conformidade técnica, garantindo qualidade do modelo e reduzindo revisões manuais. Customizado com base nos dois Projetos Modelos.',
                'subitens' => [
                    ['titulo' => 'Plugin de Auditoria de Normas Técnicas',   'valor' => 189360.00, 'descricao' => 'Sistema que analisa projetos BIM buscando cumprimento fiel das normas técnicas ABNT, além de normas internas da empresa (orçamento e planejamento). Objetivo: garantir qualidade do modelo e eliminar revisões manuais que demandam tempo e podem gerar prejuízos em obra.'],
                    ['titulo' => 'Plugin de Conteúdo Mínimo de Ambientes',   'valor' =>  36890.00, 'descricao' => 'Plugin que rastreia todos os espaços e ambientes do projeto para garantir que nenhum equipamento, mobiliário ou placa de identificação obrigatórios estejam ausentes. Valida as etapas de projeto executivo, orçamento e planejamento.'],
                    ['titulo' => 'Plugin de Conteúdo Mínimo de Pranchas',    'valor' =>  26600.00, 'descricao' => 'Plugin que garante que as pranchas de impressão estejam 100% vinculadas aos padrões do Grupo Capital: formatação, selo, conteúdo mínimo de vistas, tabelas e anotações para qualidade e melhores resultados em obra.'],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // 10. SISTEMA DE MODELAGEM PARAMÉTRICA
            // ─────────────────────────────────────────────────────────────────
            [
                'fase'    => FaseCronograma::BIM_MODELAGEM_PARAMETRICA,
                'titulo'  => 'Sistema de Modelagem Paramétrica',
                'valor'   => 215000.00,
                'descricao' => 'Implantação de sistema de modelagem paramétrica por meio de plugins customizados para o Revit. Objetivo: construção acelerada dos projetos com maior qualidade. Os sistemas automatizam tarefas que garantem a conformidade do modelo. Customizados com base nos dois Projetos Modelos.',
                'subitens' => [
                    ['titulo' => 'Plugin de Revestimento de Ambientes', 'valor' => 189000.00, 'descricao' => 'Modelagem paramétrica automática de pisos, rodapés, paredes e forros com base no nome do ambiente. Considera todas as camadas, espessuras e alturas. Vantagens: velocidade (centenas de revestimentos em minutos) e precisão (configurações feitas antecipadamente pela DPC, eliminando dependência do modelador).'],
                    ['titulo' => 'Plugin de Kits',                      'valor' =>  26000.00, 'descricao' => 'Lança automaticamente dentro dos ambientes todos os mobiliários e equipamentos necessários seguindo a nomenclatura padrão Capital. Posiciona elementos no centro do ambiente com alturas pré-estabelecidas. Resultado validado: 350 mobiliários em 18 ambientes em 2 minutos vs. ~3 dias manual.'],
                ],
            ],
        ];
    }
}
