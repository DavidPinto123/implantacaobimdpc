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

class CronogramaTemplateLegalizacaoSeeder extends Seeder
{
    public function run(): void
    {
        $template = CronogramaTemplate::updateOrCreate(
            ['nome' => 'Expansão — Fim > Início (com Legalização) (Demonstração)'],
            [
                'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
                'ancora_campo' => 'projeto.data_posse',
                'ativo' => true,
                'observacoes' => 'Template oficial de demonstração. Ancorado em projeto.data_posse; combina cálculo progressivo (Obras → Inauguração) com regressivo (Início de Projeto → Prazo Legal). Suporta múltiplas dependências por fase.',
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

        return [
            ['fase' => FaseCronograma::INICIO_PROJETO, 'duracao' => 31, 'deps' => []],
            ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::CODIGO_ORACLE, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::LEVANTAMENTO_CADASTRAL, 'duracao' => 15, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::VISITA_TECNICA, 'duracao' => 5, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::CONSULTA_PREVIA, 'duracao' => 10, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::BRIEFING, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::INICIO_PROJETO, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::LAYOUT, 'duracao' => 7, 'deps' => [
                ['fase' => FaseCronograma::BRIEFING, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::ORDEM_INVESTIMENTO, 'duracao' => 7, 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $fim, 'gap' => 1],
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::ASSINATURA_CONTRATO, 'gatilho' => $ini, 'gap' => 0],
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::EXECUTIVO, 'duracao' => 30, 'deps' => [
                ['fase' => FaseCronograma::START_PROJETOS_EXECUTIVOS, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::KICKOFF, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::EXECUTIVO, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::ORCAMENTOS, 'duracao' => 20, 'deps' => [
                ['fase' => FaseCronograma::KICKOFF, 'gatilho' => $ini, 'gap' => 0],
            ]],
            ['fase' => FaseCronograma::PRAZO_LEGAL, 'duracao' => 30, 'deps' => [
                ['fase' => FaseCronograma::CONSULTA_PREVIA, 'gatilho' => $fim, 'gap' => 1],
                ['fase' => FaseCronograma::LAYOUT, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::POSSE, 'duracao' => 0, 'ancora' => true, 'deps' => [
                ['fase' => FaseCronograma::PRAZO_LEGAL, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::MKT_ATIVACAO_PRE_VENDAS, 'duracao' => 30, 'deps' => [
                ['fase' => FaseCronograma::OBRAS, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::OBRAS, 'duracao' => 85, 'deps' => [
                ['fase' => FaseCronograma::POSSE, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::IMPLANTACAO, 'duracao' => 15, 'deps' => [
                ['fase' => FaseCronograma::OBRAS, 'gatilho' => $fim, 'gap' => 1],
            ]],
            ['fase' => FaseCronograma::INAUGURACAO, 'duracao' => 0, 'deps' => [
                ['fase' => FaseCronograma::IMPLANTACAO, 'gatilho' => $fim, 'gap' => 1],
            ]],
        ];
    }
}
