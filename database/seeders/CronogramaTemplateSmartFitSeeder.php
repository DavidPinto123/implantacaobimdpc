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
use App\Models\CronogramaTemplateFaseItemDependencia;
use Illuminate\Database\Seeder;

/**
 * Template oficial Smart Fit — retroplanejamento ancorado em POSSE.
 * Modelado conforme reunião com a cliente em 30/04/2026.
 *
 * Particularidades:
 * - Recebimento de Projetos (Arquitetura e Complementares) são fases ELÁSTICAS:
 *   começam com Início do Projeto e terminam 1d antes de Briefing / Start Executivo
 *   via gatilho FIM_ANTES_INICIO. Funcionam como datas-limite informativas; atraso
 *   delas não trava o consumidor (controle manual de status pelo gestor).
 * - Marketing / Ativação Pré-vendas é elástica (SS com Início do Projeto, FF com Inauguração).
 * - SUFRAMA é uma única fase pai (visivel=false) com 3 subitens: CNPJ, PIN, Compras.
 *   PIN tem dep FIM_ANTES_INICIO com Implantação (-45d); Compras depende do PIN.
 */
class CronogramaTemplateSmartFitSeeder extends Seeder
{
    public function run(): void
    {
        $template = CronogramaTemplate::updateOrCreate(
            ['nome' => 'Expansão A partir da posse Reuniao 30/04'],
            [
                'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
                'ancora_campo' => 'projeto.data_posse',
                'ativo' => true,
                'observacoes' => 'Retroplanejamento a partir da Posse. Recebimento de Projetos (Fase 1 e Fase 2) e Marketing são fases elásticas. SUFRAMA é uma fase pai oculta (ativar por projeto) com subitens CNPJ/PIN/Compras.',
            ]
        );

        foreach ($template->fases()->get() as $f) {
            $f->dependencias()->delete();
            foreach ($f->itens ?? [] as $item) {
                $item->dependencias()->delete();
                $item->delete();
            }
            $f->delete();
        }

        $definicoes = $this->definicoes();
        $criadas = [];

        foreach ($definicoes as $def) {
            $fase = CronogramaTemplateFase::create([
                'cronograma_template_id' => $template->id,
                'fase' => $def['fase']->value,
                'ordem' => $def['fase']->ordem(),
                'duracao_dias' => $def['duracao'],
                'tipo_dias' => TipoDiasTemplate::CORRIDOS->value,
                'visivel' => $def['visivel'] ?? true,
                'is_ancora' => $def['ancora'] ?? false,
                'regra_elastica' => $def['elastica'] ?? false,
                'observacoes' => $def['obs'] ?? null,
            ]);
            $criadas[$def['fase']->value] = $fase;
        }

        foreach ($definicoes as $def) {
            $fase = $criadas[$def['fase']->value];
            foreach ($def['deps'] as $dep) {
                CronogramaTemplateFaseDependencia::create([
                    'cronograma_template_fase_id' => $fase->id,
                    'depende_de_fase' => $dep['fase']->value,
                    'gatilho' => $dep['gatilho']->value,
                    'gap_dias' => $dep['gap'],
                ]);
            }
        }

        $this->seedSubitensSuframa($criadas);
        $this->seedSubitensRecebimentoArquitetura($criadas);
        $this->seedSubitensRecebimentoComplementares($criadas);
        $this->seedSubitensObras($criadas);
    }

    /**
     * Subitens da fase OBRAS — Energia Smart Fit e Energia Proprietário,
     * que passaram a ser tratados como entregas dentro da Obra (reunião 11/05).
     */
    private function seedSubitensObras(array $faseSeed): void
    {
        $obras = $faseSeed[FaseCronograma::OBRAS->value] ?? null;
        if (! $obras) {
            return;
        }

        foreach (['Energia Smart Fit', 'Energia Proprietário'] as $idx => $titulo) {
            CronogramaTemplateFaseItem::create([
                'cronograma_template_fase_id' => $obras->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
    }

    /**
     * Subitens da Fase 1 — Recebimento de Projetos de Arquitetura.
     * Lista vinda da planilha "Análise de Projetos Recebidos do Proprietário"
     * (anexo da reunião de 30/04/2026).
     */
    private function seedSubitensRecebimentoArquitetura(array $faseSeed): void
    {
        $fase1 = $faseSeed[FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA->value] ?? null;
        if (! $fase1) {
            return;
        }

        $titulos = [
            'Plantas',
            'Local da academia indicado?',
            'Cortes',
            'Fachadas',
            'Posição da área técnica externa definida?',
        ];

        foreach ($titulos as $idx => $titulo) {
            CronogramaTemplateFaseItem::create([
                'cronograma_template_fase_id' => $fase1->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
    }

    /**
     * Subitens da Fase 2 — Recebimento de Projetos Complementares.
     * Lista vinda da planilha "Análise de Projetos Recebidos do Proprietário"
     * (anexo da reunião de 30/04/2026).
     */
    private function seedSubitensRecebimentoComplementares(array $faseSeed): void
    {
        $fase2 = $faseSeed[FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES->value] ?? null;
        if (! $fase2) {
            return;
        }

        $titulos = [
            'Entrada de Energia',
            'Estrutura',
            'Estrutura Cobertura / Cobertura',
            'Águas Pluviais',
            'Elétrica',
            'Hidráulica',
            'Incêndio',
        ];

        foreach ($titulos as $idx => $titulo) {
            CronogramaTemplateFaseItem::create([
                'cronograma_template_fase_id' => $fase2->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
    }

    /**
     * Cria os 2 subitens da fase SUFRAMA com suas dependências.
     * Reunião 12/05: CNPJ Suframa saiu da lista (passou a viver no bloco
     * CNPJ_LEGALIZACAO). Sobraram PIN e Compras, sem o termo "Suframa".
     */
    private function seedSubitensSuframa(array $faseSeed): void
    {
        $suframa = $faseSeed[FaseCronograma::SUFRAMA->value] ?? null;
        if (! $suframa) {
            return;
        }

        $implantacao = $faseSeed[FaseCronograma::IMPLANTACAO->value] ?? null;

        $pin = CronogramaTemplateFaseItem::create([
            'cronograma_template_fase_id' => $suframa->id,
            'titulo' => 'PIN',
            'ordem' => 0,
        ]);

        $compras = CronogramaTemplateFaseItem::create([
            'cronograma_template_fase_id' => $suframa->id,
            'titulo' => 'Compras',
            'ordem' => 1,
        ]);

        if ($implantacao) {
            CronogramaTemplateFaseItemDependencia::create([
                'cronograma_template_fase_item_id' => $pin->id,
                'depende_de_template_fase_id' => $implantacao->id,
                'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO->value,
                'gap_dias' => -45, // PIN termina 45d antes do início da Implantação.
            ]);
        }

        CronogramaTemplateFaseItemDependencia::create([
            'cronograma_template_fase_item_id' => $compras->id,
            'depende_de_item_id' => $pin->id,
            'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR->value,
            'gap_dias' => 0, // Compras inicia 1d após o fim do PIN.
        ]);
    }

    private function definicoes(): array
    {
        $ini = GatilhoTemplateFase::INICIO_ANTERIOR;
        $fim = GatilhoTemplateFase::FIM_ANTERIOR;
        $fimMesmoDia = GatilhoTemplateFase::FIM_ANTERIOR_MESMO_DIA;
        $fimJunto = GatilhoTemplateFase::FIM_JUNTO;
        $fimAntesInicio = GatilhoTemplateFase::FIM_ANTES_INICIO;

        return [
            // ===== Pré-projeto =====
            ['fase' => FaseCronograma::INICIO_PROJETO, 'duracao' => 0, 'deps' => []],

            ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::CODIGO_ORACLE, 'duracao' => 1, 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // CNPJ Legalização: deadline 60 dias após assinatura de contrato
            // (Pendência de produto: confirmar com Carol se é 45 ou 60 dias).
            ['fase' => FaseCronograma::CNPJ_LEGALIZACAO, 'duracao' => 60, 'obs' => 'Deadline 60 dias após assinatura (a confirmar 45 ou 60).', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::VISITA_TECNICA, 'duracao' => 20, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'duracao' => 15, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::CONSULTA_PREVIA, 'duracao' => 10, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 1],
            ]],

            // ===== Fase 1 — Recebimento de Projetos de Arquitetura (elástica) =====
            ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA, 'duracao' => 0, 'elastica' => true, 'obs' => 'Fase 1: duração emergente entre Início do Projeto e 1d antes do Briefing.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $fimAntesInicio, 'gap' => -1],
            ]],

            ['fase' => FaseCronograma::BRIEFING, 'duracao' => 1, 'obs' => 'Depende de Levantamento Cadastral E Fase 1 (Arquitetura). Se um dos dois for marcado como "não se aplica", o algoritmo usa apenas o outro (max dos candidatos restantes).', 'deps' => [
                ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'gatilho' => $fim, 'gap' => 0],
                ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::LAYOUT, 'duracao' => 7, 'deps' => [
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'duracao' => 7, 'deps' => [
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // ===== Fase 2 — Recebimento de Projetos Complementares (elástica) =====
            ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES, 'duracao' => 0, 'elastica' => true, 'obs' => 'Fase 2: duração emergente entre Início do Projeto e 1d antes do Start do Executivo.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'gatilho' => $fimAntesInicio, 'gap' => -1],
            ]],

            ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::EXECUTIVO, 'duracao' => 30, 'deps' => [
                ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::KICKOFF, 'duracao' => 1, 'deps' => [
                ['fase' => FaseCronograma::EXECUTIVO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::ORCAMENTOS, 'duracao' => 20, 'deps' => [
                ['fase' => FaseCronograma::KICKOFF, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::PRAZO_LEGAL, 'duracao' => 30, 'obs' => 'Duração default 30d — ajustar 60/90/120 por projeto após consulta prévia. Início +10d após Executivo e após Assinatura.', 'deps' => [
                ['fase' => FaseCronograma::EXECUTIVO, 'gatilho' => $ini, 'gap' => 10],
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // LIBERAÇÃO DE POSSE: fase elástica que começa na assinatura do contrato e termina
            // 1 dia antes do início da Posse. Subitens "Liberação Engenharia" e "Liberação
            // Legalização" são populados pelo CronogramaTemplateFaseItensSeeder.
            ['fase' => FaseCronograma::LIBERACAO_POSSE, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -1],
            ]],

            // ===== Posse (âncora) e Obras =====
            ['fase' => FaseCronograma::POSSE, 'duracao' => 0, 'ancora' => true, 'deps' => [
                ['fase' => FaseCronograma::ORCAMENTOS, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // ENTREGAS DO PROPRIETÁRIO: fase mãe elástica com subitens datados
            // (Projeto contratual / Retorno SF Layout / Retorno SF Planta técnica / Shell).
            ['fase' => FaseCronograma::ENTREGAS_PROPRIETARIO, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse. Subitens datados.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -1],
            ]],

            ['fase' => FaseCronograma::OBRAS, 'duracao' => 85, 'obs' => 'Posse +60d (gordura) E Prazo Legal +1d (compliance). Aguarda Liberação de Posse concluída.', 'deps' => [
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $fim, 'gap' => 60],
                ['fase' => FaseCronograma::PRAZO_LEGAL, 'gatilho' => $fim, 'gap' => 0],
                ['fase' => FaseCronograma::LIBERACAO_POSSE, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::MKT_ATIVACAO_PRE_VENDAS, 'duracao' => 0, 'elastica' => true, 'obs' => 'Duração elástica: SS com Início do Projeto, FF com Inauguração.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::INAUGURACAO, 'gatilho' => $fimJunto, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::IMPLANTACAO, 'duracao' => 15, 'deps' => [
                ['fase' => FaseCronograma::OBRAS, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::INAUGURACAO, 'duracao' => 0, 'obs' => 'Marco no último dia da Implantação.', 'deps' => [
                ['fase' => FaseCronograma::IMPLANTACAO, 'gatilho' => $fimMesmoDia, 'gap' => 0],
            ]],

            // ===== SUFRAMA (oculto por padrão; subitens CNPJ/PIN/Compras criados em seedSubitensSuframa) =====
            // Deadline: 60 dias antes da Inauguração. O time pisca a data quando faltam ≤60 dias.
            ['fase' => FaseCronograma::SUFRAMA, 'duracao' => 0, 'visivel' => false, 'obs' => 'Bloco opcional. Deadline 60d antes da Inauguração. Ativar quando a unidade for amparada pelo regime SUFRAMA.', 'deps' => [
                ['fase' => FaseCronograma::INAUGURACAO, 'gatilho' => $ini, 'gap' => -60],
            ]],
        ];
    }
}
