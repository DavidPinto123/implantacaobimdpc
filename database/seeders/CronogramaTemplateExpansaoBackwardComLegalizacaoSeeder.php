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
 * TEMPLATE 3 — EXPANSÃO — Fim > Início (com Legalização).
 *
 * Modelado a partir das fórmulas da planilha PMO oficial (aba EXPANSÃO, linha 5).
 * Retroplanejamento ancorado em POSSE.
 *
 * Trecho híbrido forward intencional: KICKOFF e ORCAMENTOS seguem após o fim
 * do EXECUTIVO mesmo em template backward (planilha BG5/BH5/BI5).
 *
 * Span total: 175 dias offset, idêntico ao Template 1.
 */
class CronogramaTemplateExpansaoBackwardComLegalizacaoSeeder extends Seeder
{
    public function run(): void
    {
        $template = CronogramaTemplate::updateOrCreate(
            ['nome' => 'Expansão — Fim > Início (com Legalização)'],
            [
                'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
                'ancora_campo' => 'projeto.data_posse',
                'ativo' => true,
                'observacoes' => 'Template regressivo ancorado em POSSE. Reproduz exatamente a cadeia da planilha PMO (linha 5 da aba EXPANSÃO). Span total = 175 dias.',
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
            // Posse é a âncora.
            ['fase' => FaseCronograma::POSSE, 'duracao' => 0, 'ancora' => true, 'deps' => []],

            // Forward a partir da Posse: OBRAS começa MESMO DIA da Posse.
            // Depende também de LIBERACAO_POSSE (sim engenharia + sim legalização).
            ['fase' => FaseCronograma::OBRAS, 'duracao' => 86, 'obs' => 'Começa no mesmo dia da Posse (sobreposição). Aguarda Liberação de Posse concluída.', 'deps' => [
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $fimMesmoDia, 'gap' => 0],
                ['fase' => FaseCronograma::LIBERACAO_POSSE, 'gatilho' => $fim, 'gap' => 0],
            ]],

            ['fase' => FaseCronograma::IMPLANTACAO, 'duracao' => 16, 'deps' => [
                ['fase' => FaseCronograma::OBRAS, 'gatilho' => $fim, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::INAUGURACAO, 'duracao' => 0, 'obs' => 'Ocorre no último dia da Implantação (sobreposição).', 'deps' => [
                ['fase' => FaseCronograma::IMPLANTACAO, 'gatilho' => $fimMesmoDia, 'gap' => 0],
            ]],

            // Backward chain a partir da Posse (lag negativo).
            // PRAZO_LEGAL termina 1 dia antes da Posse, dura 30 dias.
            ['fase' => FaseCronograma::PRAZO_LEGAL, 'duracao' => 31, 'obs' => 'Termina 1 dia antes da Posse.', 'deps' => [
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $ini, 'gap' => -31],
            ]],

            // LIBERAÇÃO DE POSSE: fase elástica que começa na assinatura do contrato e termina
            // 1 dia antes do início da Posse. Subitens "Liberação Engenharia" e "Liberação
            // Legalização" são populados pelo CronogramaTemplateFaseItensSeeder.
            ['fase' => FaseCronograma::LIBERACAO_POSSE, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -1],
            ]],
            // EXECUTIVO inicia 10 dias antes do início do PRAZO_LEGAL (lag negativo).
            ['fase' => FaseCronograma::EXECUTIVO, 'duracao' => 31, 'obs' => 'Inicia 10 dias antes do início do PRAZO_LEGAL.', 'deps' => [
                ['fase' => FaseCronograma::PRAZO_LEGAL, 'gatilho' => $ini, 'gap' => -10],
            ]],

            // Trecho híbrido: KICKOFF e ORCAMENTOS propagam para frente a partir do EXECUTIVO.
            ['fase' => FaseCronograma::KICKOFF, 'duracao' => 0, 'obs' => 'Propaga para frente (forward) mesmo em template backward.', 'deps' => [
                ['fase' => FaseCronograma::EXECUTIVO, 'gatilho' => $fim, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::ORCAMENTOS, 'duracao' => 21, 'obs' => 'Propaga para frente (forward) mesmo em template backward.', 'deps' => [
                ['fase' => FaseCronograma::KICKOFF, 'gatilho' => $fim, 'gap' => 0],
            ]],

            // Backward chain antes do EXECUTIVO.
            ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'duracao' => 0, 'obs' => '1 dia antes do início do EXECUTIVO.', 'deps' => [
                ['fase' => FaseCronograma::EXECUTIVO, 'gatilho' => $ini, 'gap' => -1],
            ]],
            ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'duracao' => 6, 'obs' => 'Termina 1 dia antes do início do START.', 'deps' => [
                ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'gatilho' => $ini, 'gap' => -6],
            ]],
            ['fase' => FaseCronograma::LAYOUT, 'duracao' => 8, 'obs' => 'Termina 1 dia antes do início da OI.', 'deps' => [
                ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'gatilho' => $ini, 'gap' => -8],
            ]],
            ['fase' => FaseCronograma::BRIEFING, 'duracao' => 0, 'obs' => '1 dia antes do início do LAYOUT.', 'deps' => [
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $ini, 'gap' => -1],
            ]],
            ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'duracao' => 16, 'obs' => 'Termina 1 dia antes do BRIEFING.', 'deps' => [
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $ini, 'gap' => -16],
            ]],
            ['fase' => FaseCronograma::VISITA_TECNICA, 'duracao' => 6, 'obs' => 'Termina 1 dia antes do BRIEFING.', 'deps' => [
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $ini, 'gap' => -6],
            ]],

            // INICIO_PROJETO é o dia anterior ao início do CADASTRAL (planilha O5 = R5-1).
            ['fase' => FaseCronograma::INICIO_PROJETO, 'duracao' => 0, 'obs' => '1 dia antes do início do CADASTRAL.', 'deps' => [
                ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'gatilho' => $ini, 'gap' => -1],
            ]],
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

            // ENTREGAS DO PROPRIETÁRIO: fase mãe elástica com subitens datados.
            ['fase' => FaseCronograma::ENTREGAS_PROPRIETARIO, 'duracao' => 0, 'elastica' => true, 'obs' => 'Elástica: assinatura do contrato → 1 dia antes da Posse. Subitens datados.', 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::POSSE, 'gatilho' => GatilhoTemplateFase::FIM_ANTES_INICIO, 'gap' => -1],
            ]],

            ['fase' => FaseCronograma::CONSULTA_PREVIA, 'duracao' => 16, 'obs' => 'Solicitada pelo comercial na entrada do ponto; roda em paralelo ao cadastral.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $fim, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_ARQUITETURA, 'duracao' => 6, 'obs' => 'Checklist de projetos recebidos do proprietário — base para o briefing.', 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $fim, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::RECEBIMENTO_PROJETOS_COMPLEMENTARES, 'duracao' => 6, 'obs' => 'Checklist de projetos complementares — base para o executivo.', 'deps' => [
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $fim, 'gap' => 0],
            ]],
        ];
    }
}
