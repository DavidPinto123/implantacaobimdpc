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
 *
 * Base: ajustes do cliente na reunião de 30/04, refinados em 08/05 no template
 * "Expansão A partir da posse Reuniao 30/04 (Antonio)" do ambiente de homologação.
 *
 * Acréscimos locais (11/05): fases CNPJ_LEGALIZACAO, LIBERACAO_POSSE e
 * ENTREGAS_PROPRIETARIO — encaixadas nos "buracos" de ordem (3 e 18) que o
 * cliente deixou explícitos no template de homologação. Energia Smart Fit
 * e Energia Proprietário entram como subitens da fase OBRAS.
 *
 * Diferenças relevantes em relação ao SmartFitSeeder anterior (reunião 30/04):
 * - ordem_investimento: 5d (cliente) — antes 7d
 * - obras: observação reflete o cliente; não depende de LIBERACAO_POSSE
 * - suframa: observação reflete o cliente; não depende de INAUGURACAO
 */
class CronogramaTemplateExpansaoReuniao1105Seeder extends Seeder
{
    public function run(): void
    {
        $template = CronogramaTemplate::updateOrCreate(
            ['nome' => 'Expansão A partir da posse Reuniao 11/05'],
            [
                'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
                'ancora_campo' => 'projeto.data_posse',
                'ativo' => true,
                'observacoes' => 'Retroplanejamento a partir da Posse. Recebimento de Projetos (Fase 1 e Fase 2), Marketing, Liberação de Posse e Entregas do Proprietário são fases elásticas. SUFRAMA é uma fase pai oculta (ativar por projeto) com subitens CNPJ/PIN/Compras.',
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

        $this->seedSubitensRecebimentoArquitetura($criadas);
        $this->seedSubitensRecebimentoComplementares($criadas);
        $this->seedSubitensLiberacaoPosse($criadas);
        $this->seedSubitensEntregasProprietario($criadas);
        $this->seedSubitensMktAtivacaoPreVendas($criadas);
        $this->seedSubitensObras($criadas);
        $this->seedSubitensSuframa($criadas);
    }

    private function seedSubitensRecebimentoArquitetura(array $faseSeed): void
    {
        $fase = $faseSeed[FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA->value] ?? null;
        if (! $fase) {
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
                'cronograma_template_fase_id' => $fase->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
    }

    private function seedSubitensRecebimentoComplementares(array $faseSeed): void
    {
        $fase = $faseSeed[FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES->value] ?? null;
        if (! $fase) {
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
                'cronograma_template_fase_id' => $fase->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
    }

    private function seedSubitensLiberacaoPosse(array $faseSeed): void
    {
        $fase = $faseSeed[FaseCronograma::LIBERACAO_POSSE->value] ?? null;
        if (! $fase) {
            return;
        }

        foreach (['Engenharia', 'Legalização'] as $idx => $titulo) {
            CronogramaTemplateFaseItem::create([
                'cronograma_template_fase_id' => $fase->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
    }

    private function seedSubitensEntregasProprietario(array $faseSeed): void
    {
        $fase = $faseSeed[FaseCronograma::ENTREGAS_PROPRIETARIO->value] ?? null;
        if (! $fase) {
            return;
        }

        $titulos = [
            'Entrega de projeto contratual (PP → SF)',
            'Retorno SF: Layout',
            'Retorno SF: Planta técnica',
            'Prazo entrega Shell',
        ];

        foreach ($titulos as $idx => $titulo) {
            CronogramaTemplateFaseItem::create([
                'cronograma_template_fase_id' => $fase->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
    }

    private function seedSubitensMktAtivacaoPreVendas(array $faseSeed): void
    {
        $fase = $faseSeed[FaseCronograma::MKT_ATIVACAO_PRE_VENDAS->value] ?? null;
        if (! $fase) {
            return;
        }

        foreach (['Pré-vendas físico', 'Pré-vendas online'] as $idx => $titulo) {
            CronogramaTemplateFaseItem::create([
                'cronograma_template_fase_id' => $fase->id,
                'titulo' => $titulo,
                'ordem' => $idx,
            ]);
        }
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
     * SUFRAMA — fase pai (oculta) com 3 subitens (CNPJ, PIN, Compras).
     * CNPJ: 30d após assinatura.
     * PIN : termina 45d antes do início da Implantação (FIM_ANTES_INICIO).
     * Compras: encadeada ao fim do PIN.
     */
    private function seedSubitensSuframa(array $faseSeed): void
    {
        $suframa = $faseSeed[FaseCronograma::SUFRAMA->value] ?? null;
        if (! $suframa) {
            return;
        }

        // Reunião 12/05: CNPJ Suframa saiu da lista (passou a viver no bloco
        // CNPJ_LEGALIZACAO). Sobraram PIN e Compras, sem o termo "Suframa".
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
                'gap_dias' => -45,
            ]);
        }

        CronogramaTemplateFaseItemDependencia::create([
            'cronograma_template_fase_item_id' => $compras->id,
            'depende_de_item_id' => $pin->id,
            'gatilho' => GatilhoTemplateFase::FIM_ANTERIOR->value,
            'gap_dias' => 0,
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
            ['fase' => FaseCronograma::INICIO_PROJETO, 'duracao' => 0, 'deps' => []],

            ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],

            // Acréscimo local (11/05): deadline de 60d após assinatura.
            ['fase' => FaseCronograma::CNPJ_LEGALIZACAO, 'duracao' => 60, 'obs' => 'Deadline 60 dias após assinatura (a confirmar 45 ou 60).', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::CODIGO_ORACLE, 'duracao' => 1, 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'duracao' => 15, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::VISITA_TECNICA, 'duracao' => 20, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::CONSULTA_PREVIA, 'duracao' => 10, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 1],
            ]],

            // Fase 1 — elástica (Início do Projeto → 1d antes do Briefing).
            ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA, 'duracao' => 0, 'elastica' => true, 'obs' => 'Fase 1: duração emergente entre Início do Projeto e 1d antes do Briefing.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $fimAntesInicio, 'gap' => -1],
            ]],

            ['fase' => FaseCronograma::BRIEFING, 'duracao' => 1, 'obs' => 'Depende de Levantamento Cadastral E Fase 1 (Arquitetura). Se um dos dois for marcado como "não se aplica", o algoritmo usa apenas o outro (max dos candidatos restantes).', 'deps' => [
                ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'gatilho' => $fim, 'gap' => 0],
                ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA, 'gatilho' => $fim, 'gap' => 0],
                ['fase' => FaseCronograma::VISITA_TECNICA, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::LAYOUT, 'duracao' => 7, 'deps' => [
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // Cliente: 5d (era 7d na versão de 30/04).
            ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'duracao' => 5, 'deps' => [
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // Fase 2 — elástica (Início do Projeto → 1d antes do Start do Executivo).
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

            // Acréscimo local (11/05): elástica da assinatura até 1d antes da Posse.
            ['fase' => FaseCronograma::LIBERACAO_POSSE, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $fimAntesInicio, 'gap' => -1],
            ]],

            ['fase' => FaseCronograma::POSSE, 'duracao' => 0, 'ancora' => true, 'deps' => [
                ['fase' => FaseCronograma::ORCAMENTOS, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // Acréscimo local (11/05): elástica paralela à Posse, subitens datados.
            ['fase' => FaseCronograma::ENTREGAS_PROPRIETARIO, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse. Subitens datados.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $fimAntesInicio, 'gap' => -1],
            ]],

            // Cliente: obras Posse +60d e Prazo Legal +1d. SEM dep de LIBERACAO_POSSE.
            ['fase' => FaseCronograma::OBRAS, 'duracao' => 85, 'obs' => 'Posse +60d (gordura) E Prazo Legal +1d (compliance). Tirar a gordura conforme o cronograma se concretiza.', 'deps' => [
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $fim, 'gap' => 60],
                ['fase' => FaseCronograma::PRAZO_LEGAL, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::MKT_ATIVACAO_PRE_VENDAS, 'duracao' => 0, 'elastica' => true, 'obs' => 'Duração elástica: SS com Início do Projeto, FF com Inauguração.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::INAUGURACAO, 'gatilho' => $fimJunto, 'gap' => 0],
            ]],

            // Cliente: suframa sem dep para inauguracao.
            ['fase' => FaseCronograma::SUFRAMA, 'duracao' => 0, 'visivel' => false, 'obs' => 'Bloco opcional. Ativar quando a unidade for amparada pelo regime SUFRAMA. Subitens: CNPJ, PIN, Compras.', 'deps' => []],

            ['fase' => FaseCronograma::IMPLANTACAO, 'duracao' => 15, 'deps' => [
                ['fase' => FaseCronograma::OBRAS, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::INAUGURACAO, 'duracao' => 0, 'obs' => 'Marco no último dia da Implantação.', 'deps' => [
                ['fase' => FaseCronograma::IMPLANTACAO, 'gatilho' => $fimMesmoDia, 'gap' => 0],
            ]],
        ];
    }
}
