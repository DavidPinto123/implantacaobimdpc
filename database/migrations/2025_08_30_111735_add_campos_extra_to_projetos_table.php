<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->longText('endereco')->nullable();
            $table->string('crono_revisado')->nullable();
            $table->string('escopo')->nullable();
            $table->foreignId('resp_com')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resp_arq')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resp_eng')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_comite')->nullable();
            $table->string('status_imovel')->nullable();
            $table->string('status_contrato')->nullable();
            $table->date('data_ass_contrato')->nullable();

            $table->date('cad_plan_inicio')->nullable();
            $table->date('cad_plan_fim')->nullable();
            $table->integer('cad_plan_dias')->nullable();
            $table->date('cad_rea_inicio')->nullable();
            $table->date('cad_rea_fim')->nullable();
            $table->integer('cad_prazo')->nullable();
            $table->string('cad_status')->nullable();

            $table->date('vis_plan_inicio')->nullable();
            $table->date('vis_plan_fim')->nullable();
            $table->integer('vis_plan_dias')->nullable();
            $table->date('vis_rea_inicio')->nullable();
            $table->date('vis_rea_fim')->nullable();
            $table->integer('vis_prazo')->nullable();
            $table->string('vis_status')->nullable();

            $table->string('brief_plan')->nullable();
            $table->date('brief_plan_lay_inicio')->nullable();
            $table->date('brief_plan_lay_fim')->nullable();
            $table->integer('brief_plan_dias')->nullable();
            $table->string('brief_real')->nullable();
            $table->date('brief_real_lay_inicio')->nullable();
            $table->date('brief_real_lay_fim')->nullable();
            $table->integer('brief_prazo')->nullable();
            $table->string('brief_status')->nullable();

            $table->date('ordem_planej_ini')->nullable();
            $table->date('ordem_planej_fim')->nullable();
            $table->integer('ordem_planejado')->nullable();
            $table->date('ordem_realizado')->nullable();
            $table->date('ordem_realizado_fim')->nullable();
            $table->integer('ordem_prazo')->nullable();
            $table->string('ordem_status')->nullable();
            $table->date('ordem_data_aprov')->nullable();
            $table->string('ordem_status_aprov')->nullable();

            $table->date('proj_planej_reuniao_start')->nullable();
            $table->date('proj_real_reuniao_start')->nullable();
            $table->date('proj_plan_ini')->nullable();
            $table->date('proj_plan_fim')->nullable();
            $table->integer('proj_plan')->nullable();
            $table->date('proj_real_ini')->nullable();
            $table->date('proj_real_fim')->nullable();
            $table->integer('proj_prazo')->nullable();
            $table->string('proj_status')->nullable();

            $table->date('orca_reuniao_kickoff')->nullable();
            $table->date('orca_planejado_ini')->nullable();
            $table->date('orca_planejado_fim')->nullable();
            $table->integer('orca_planejado')->nullable();
            $table->date('orca_real_ini')->nullable();
            $table->date('orca_real_fim')->nullable();
            $table->integer('orca_prazo')->nullable();
            $table->string('orca_status')->nullable();

            $table->string('legal_status_consulta_prev')->nullable();
            $table->longText('legal_doc_posse')->nullable();
            $table->date('legal_plan_ini')->nullable();
            $table->date('legal_plan_fim')->nullable();
            $table->integer('legal_prazo_legal')->nullable();
            $table->date('legal_realizado_ini')->nullable();
            $table->date('legal_realizado_fim')->nullable();
            $table->integer('legal_prazo')->nullable();
            $table->string('legal_status')->nullable();

            $table->date('data_posse')->nullable();
            $table->date('posse_data_posse')->nullable();
            $table->string('posse_engenharia')->nullable();
            $table->string('posse_legalização')->nullable();
            $table->string('posse_status')->nullable();
            $table->text('posse_comentarios')->nullable();

            $table->integer('exec_prazo_plan')->nullable();
            $table->integer('exec_prazo_real')->nullable();

            $table->date('imp_inicio')->nullable();
            $table->date('imp_fim')->nullable();
            $table->integer('imp_prazo_planejado')->nullable();
            $table->integer('imp_prazo_realizado')->nullable();
            $table->integer('imp_mes')->nullable();
            $table->integer('imp_ano')->nullable();

            $table->text('obs_aluguel')->nullable();
            $table->decimal('metro_contrato', 15, 2)->nullable();
            $table->decimal('metro_layout_util', 15, 2)->nullable();
            $table->string('pavimento')->nullable();

            $table->decimal('capex_aprovado_diretoria_valor', 15, 2)->nullable();
            $table->boolean('capex_aprovado_diretoria')->nullable();
            $table->boolean('coc_aprovado')->nullable();
            $table->string('tier')->nullable();
            $table->decimal('renda', 15, 2)->nullable();

            $table->string('set_equipamentos')->nullable();
            $table->string('vendas_mkt')->nullable();
            $table->string('vendas_mkt_realizado')->nullable();
            $table->string('diretoria')->nullable();
            $table->longText('reuniao_ita')->nullable();
            $table->longText('contato_corretor')->nullable();
            $table->longText('dir_status_contrato')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'endereco', 'crono_revisado', 'escopo', 'resp_com', 'resp_arq', 'resp_eng',
                'status_comite', 'status_imovel', 'status_contrato', 'data_ass_contrato',
                'cad_plan_inicio', 'cad_plan_fim', 'cad_plan_dias', 'cad_rea_inicio', 'cad_rea_fim', 'cad_prazo', 'cad_status',
                'vis_plan_inicio', 'vis_plan_fim', 'vis_plan_dias', 'vis_rea_inicio', 'vis_rea_fim', 'vis_prazo', 'vis_status',
                'brief_plan', 'brief_plan_lay_inicio', 'brief_plan_lay_fim', 'brief_plan_dias', 'brief_real', 'brief_real_lay_inicio', 'brief_real_lay_fim', 'brief_prazo', 'brief_status',
                'ordem_planej_ini', 'ordem_planej_fim', 'ordem_planejado', 'ordem_realizado', 'ordem_realizado_fim', 'ordem_prazo', 'ordem_status', 'ordem_data_aprov', 'ordem_status_aprov',
                'proj_planej_reuniao_start', 'proj_real_reuniao_start', 'proj_plan_ini', 'proj_plan_fim', 'proj_plan', 'proj_real_ini', 'proj_real_fim', 'proj_prazo', 'proj_status',
                'orca_reuniao_kickoff', 'orca_planejado_ini', 'orca_planejado_fim', 'orca_planejado', 'orca_real_ini', 'orca_real_fim', 'orca_prazo', 'orca_status',
                'legal_status_consulta_prev', 'legal_doc_posse', 'legal_plan_ini', 'legal_plan_fim', 'legal_prazo_legal', 'legal_realizado_ini', 'legal_realizado_fim', 'legal_prazo', 'legal_status',
                'data_posse', 'posse_data_posse', 'posse_engenharia', 'posse_legalização', 'posse_status', 'posse_comentarios',
                'exec_prazo_plan', 'exec_prazo_real',
                'imp_inicio', 'imp_fim', 'imp_prazo_planejado', 'imp_prazo_realizado', 'imp_mes', 'imp_ano',
                'obs_aluguel', 'metro_contrato', 'metro_layout_util', 'pavimento',
                'capex_aprovado_diretoria_valor', 'capex_aprovado_diretoria', 'coc_aprovado', 'tier', 'renda',
                'set_equipamentos', 'vendas_mkt', 'vendas_mkt_realizado', 'diretoria', 'reuniao_ita', 'contato_corretor', 'dir_status_contrato',
            ]);
        });
    }
};
