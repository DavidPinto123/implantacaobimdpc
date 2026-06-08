<?php

namespace Database\Seeders;

use App\Enums\FaseCronograma;
use App\Enums\GatilhoTemplateFase;
use App\Enums\TipoDiasTemplate;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaTemplate;
use App\Models\CronogramaTemplateFase;
use App\Models\CronogramaTemplateFaseDependencia;
use Illuminate\Database\Seeder;

/**
 * TEMPLATE 1 — EXPANSÃO — Início > Fim (com Legalização).
 *
 * Modelado a partir das fórmulas da planilha PMO oficial (aba EXPANSÃO, linha 3):
 *   - INICIO_PROJETO é apenas o marker de data (sem duração)
 *   - Cada duração no banco = (duração_planilha + 1) por causa da convenção
 *     inclusive (planilha conta offset, meu sistema conta dias inclusive)
 *   - OBRAS começa no MESMO DIA da Posse (sobreposição explícita)
 *   - INAUGURAÇÃO ocorre no ÚLTIMO DIA da Implantação (sobreposição explícita)
 *
 * Span total: 175 dias offset (= INAUGURACAO.fim - INICIO_PROJETO.inicio).
 */
class CronogramaTemplateExpansaoForwardComLegalizacaoSeeder extends Seeder
{
    public function run(): void
    {
        $template = CronogramaTemplate::updateOrCreate(
            ['nome' => 'Expansão — Início > Fim (com Legalização)'],
            [
                'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
                'ancora_campo' => 'projeto.cad_plan_inicio',
                'ativo' => true,
                'observacoes' => 'Template progressivo ancorado em INICIO_PROJETO. Reproduz exatamente a cadeia da planilha PMO (linha 3 da aba EXPANSÃO). Span total = 175 dias.',
            ]
        );

        foreach ($template->fases()->get() as $f) {
            $f->dependencias()->delete();
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
                'visivel' => true,
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
    }

    private function definicoes(): array
    {
        $ini = GatilhoTemplateFase::INICIO_ANTERIOR;
        $fim = GatilhoTemplateFase::FIM_ANTERIOR;
        $fimMesmoDia = GatilhoTemplateFase::FIM_ANTERIOR_MESMO_DIA;

        return [
            ['fase' => FaseCronograma::INICIO_PROJETO, 'duracao' => 0, 'ancora' => true, 'deps' => []],

            // Markers concorrentes ao Início de Projeto (mesma data por padrão).
            ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::CODIGO_ORACLE, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
            ]],

            // CNPJ Legalização: deadline 60 dias após assinatura de contrato
            // (Pendência de produto: confirmar com Carol se é 45 ou 60 dias).
            ['fase' => FaseCronograma::CNPJ_LEGALIZACAO, 'duracao' => 60, 'obs' => 'Deadline 60 dias após assinatura (a confirmar 45 ou 60).', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // CAD: 15 dias offset (16 inclusive) começando 1 dia depois do Início.
            ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'duracao' => 16, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // VT: 5 dias offset (6 inclusive) concorrente com CAD.
            ['fase' => FaseCronograma::VISITA_TECNICA, 'duracao' => 6, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // CONSULTA_PREVIA: 15 dias offset (16 inclusive) em paralelo, a partir do Início do Projeto.
            ['fase' => FaseCronograma::CONSULTA_PREVIA, 'duracao' => 16, 'obs' => 'Solicitada pelo comercial na entrada do ponto; roda em paralelo ao cadastral.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // Recebimento de projetos — Fase 1 (arquitetura): checklist antes do Briefing.
            ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA, 'duracao' => 6, 'obs' => 'Checklist de projetos recebidos do proprietário — base para o briefing.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::BRIEFING, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // LAYOUT: 7 dias offset (8 inclusive).
            ['fase' => FaseCronograma::LAYOUT, 'duracao' => 8, 'deps' => [
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // OI: 5 dias offset (6 inclusive).
            ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'duracao' => 6, 'deps' => [
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // Recebimento de projetos — Fase 2 (complementares): antes do Start do executivo.
            ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES, 'duracao' => 6, 'obs' => 'Checklist de projetos complementares — base para o executivo.', 'deps' => [
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // START: inicia no dia 2 da OI (1 dia após o início da OI).
            ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'duracao' => 0, 'obs' => 'Inicia no dia 2 da OI (1 dia após o início da OI).', 'deps' => [
                ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'gatilho' => $ini, 'gap' => 1],
            ]],
            // EXECUTIVO: 30 dias offset (31 inclusive).
            ['fase' => FaseCronograma::EXECUTIVO, 'duracao' => 31, 'deps' => [
                ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'gatilho' => $fim, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::KICKOFF, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::EXECUTIVO, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // ORÇAMENTOS: 20 dias offset (21 inclusive).
            ['fase' => FaseCronograma::ORCAMENTOS, 'duracao' => 21, 'deps' => [
                ['fase' => FaseCronograma::KICKOFF, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // PRAZO LEGAL: 30 dias offset (31 inclusive), inicia 10 dias após início do EXECUTIVO.
            ['fase' => FaseCronograma::PRAZO_LEGAL, 'duracao' => 31, 'obs' => 'Inicia 10 dias após o início do EXECUTIVO.', 'deps' => [
                ['fase' => FaseCronograma::EXECUTIVO, 'gatilho' => $ini, 'gap' => 10],
            ]],

            // LIBERAÇÃO DE POSSE: fase elástica que começa na assinatura do contrato e termina
            // 1 dia antes do início da Posse. Subitens "Liberação Engenharia" e "Liberação
            // Legalização" são populados pelo CronogramaTemplateFaseItensSeeder.
            ['fase' => FaseCronograma::LIBERACAO_POSSE, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -1],
            ]],

            ['fase' => FaseCronograma::POSSE, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::PRAZO_LEGAL, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // ENTREGAS DO PROPRIETÁRIO: fase mãe elástica que conta da Assinatura
            // até 1 dia antes da Posse, com 4 subitens datados (Projeto contratual,
            // Retorno SF Layout, Retorno SF Planta técnica, Shell) populados pelo
            // CronogramaTemplateFaseItensSeeder.
            ['fase' => FaseCronograma::ENTREGAS_PROPRIETARIO, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse. Subitens datados.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -1],
            ]],

            // OBRAS: 85 dias offset (86 inclusive), começa MESMO DIA da Posse.
            // Depende também de LIBERACAO_POSSE (sim engenharia + sim legalização)
            // — só inicia quando ambas estão concluídas.
            ['fase' => FaseCronograma::OBRAS, 'duracao' => 86, 'obs' => 'Começa no mesmo dia da Posse (sobreposição). Aguarda Liberação de Posse concluída.', 'deps' => [
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $fimMesmoDia, 'gap' => 0],
                ['fase' => FaseCronograma::LIBERACAO_POSSE, 'gatilho' => $fim, 'gap' => 0],
                ['fase' => FaseCronograma::ORCAMENTOS, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // IMPLANTAÇÃO: 15 dias offset (16 inclusive).
            ['fase' => FaseCronograma::IMPLANTACAO, 'duracao' => 16, 'deps' => [
                ['fase' => FaseCronograma::OBRAS, 'gatilho' => $fim, 'gap' => 0],
            ]],
            // INAUGURAÇÃO: marker no ÚLTIMO DIA da Implantação (sobreposição).
            ['fase' => FaseCronograma::INAUGURACAO, 'duracao' => 0, 'obs' => 'Ocorre no último dia da Implantação (sobreposição).', 'deps' => [
                ['fase' => FaseCronograma::IMPLANTACAO, 'gatilho' => $fimMesmoDia, 'gap' => 0],
            ]],
        ];
    }
}
