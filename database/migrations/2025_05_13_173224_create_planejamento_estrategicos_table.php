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
        Schema::create('planejamento_estrategicos', function (Blueprint $table) {
            $table->id();

            $table->string('sigla')->nullable();
            $table->string('nova_sigla')->nullable();
            $table->string('crono_revisado')->nullable();
            $table->string('unidade')->nullable();
            $table->string('marca')->nullable();
            $table->string('escopo')->nullable();
            $table->string('pipe_land')->nullable();
            $table->string('status')->nullable();
            $table->string('comercial')->nullable();
            $table->string('arquitetura')->nullable();
            $table->string('engenharia')->nullable();

            $table->date('status_comite')->nullable();
            $table->date('inicio_do_projeto_do_comercial')->nullable();
            $table->string('status_contrato_do_comercial')->nullable();
            $table->date('data_assinatura_contrato_do_comercial')->nullable();

            $table->date('planej_inicio_cadastral')->nullable();
            $table->date('planej_fim_cadastral')->nullable();
            $table->integer('planejado_15d_cadastral')->nullable();
            $table->date('realizado_inicio_cadastral')->nullable();
            $table->date('realizado_fim_cadastral')->nullable();
            $table->string('prazo_cadastral')->nullable();
            $table->string('status_cadastral')->nullable();

            $table->date('planej_inicio_visita_tecnica')->nullable();
            $table->date('planej_fim_visita_tecnica')->nullable();
            $table->integer('planejado_5d_visita_tecnica')->nullable();
            $table->date('realizado_inicio_visita_tecnica')->nullable();
            $table->date('realizado_fim_visita_tecnica')->nullable();
            $table->integer('prazo_visita_tecnica')->nullable();
            $table->string('status_visita_tecnica')->nullable();

            $table->date('planejado_briefing_layout')->nullable();
            $table->date('planej_layout_inicio')->nullable();
            $table->date('planej_layout_fim')->nullable();
            $table->integer('planejado_7d_briefing_layout')->nullable();
            $table->date('realizado_briefing_layout')->nullable();
            $table->date('realizado_layout_inicio')->nullable();
            $table->date('realizado_layout_fim')->nullable();
            $table->integer('prazo_briefing_layout')->nullable();
            $table->string('status_briefing_layout')->nullable();

            $table->date('planej_inicio_oi')->nullable();
            $table->date('planej_fim_oi')->nullable();
            $table->integer('planejado_5d_oi')->nullable();
            $table->date('realizado_inicio_oi')->nullable();
            $table->date('realizado_fim_oi')->nullable();
            $table->integer('prazo_oi')->nullable();
            $table->string('status_oi')->nullable();
            $table->date('data_aprovacao_oi')->nullable();
            $table->string('status_aprovacao_oi')->nullable();

            $table->date('planej_reuniao_start_pe')->nullable();
            $table->date('realizado_reuniao_start_pe')->nullable();
            $table->date('planej_inicio_pe')->nullable();
            $table->date('planej_fim_pe')->nullable();
            $table->integer('planejado_30d_pe')->nullable();
            $table->date('realizado_inicio_pe')->nullable();
            $table->date('realizado_fim_pe')->nullable();
            $table->integer('prazo_pe')->nullable();
            $table->string('status_pe')->nullable();

            $table->date('reuniao_kickoff_orc')->nullable();
            $table->date('planej_inicio_orc')->nullable();
            $table->date('planej_fim_orc')->nullable();
            $table->integer('planejado_20d_orc')->nullable();
            $table->date('realizado_inicio_orc')->nullable();
            $table->date('realizado_fim_orc')->nullable();
            $table->integer('prazo_orc')->nullable();
            $table->string('status_orc')->nullable();

            $table->string('status_cp_evtl')->nullable();
            $table->string('documentacao_posse')->nullable();
            $table->date('planej_inicio_legal')->nullable();
            $table->date('planej_fim_legal')->nullable();
            $table->integer('prazo_legal')->nullable();
            $table->date('realizado_inicio_legal')->nullable();
            $table->date('realizado_fim_legal')->nullable();
            $table->integer('prazo_legalizacao')->nullable();
            $table->string('status_legalizacao')->nullable();

            $table->date('data_de_posse')->nullable();
            $table->smallInteger('mes_posse')->nullable();
            $table->string('engenharia_posse')->nullable();
            $table->string('legalizacao_posse')->nullable();
            $table->string('status_posse')->nullable();
            $table->text('comentarios_posse')->nullable();

            $table->date('inicio_execucao_obras')->nullable();
            $table->date('fim_execucao_obras')->nullable();
            $table->integer('prazo_planejado_execucao_obras')->nullable();
            $table->integer('prazo_realizado_execucao_obras')->nullable();

            $table->date('inicio_implantacao')->nullable();
            $table->date('fim_implantacao')->nullable();
            $table->integer('prazo_planejado_implantacao')->nullable();
            $table->integer('prazo_realizado_implantacao')->nullable();
            $table->smallInteger('mes_implantacao')->nullable();
            $table->smallInteger('ano_implantacao')->nullable();

            $table->string('tipo_de_imovel')->nullable();
            $table->string('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf')->nullable();
            $table->string('empreendimento')->nullable();
            $table->string('locacao')->nullable();
            $table->decimal('aluguel', 10, 2)->nullable(); // verificar se é numérico
            $table->text('obs_aluguel')->nullable();
            $table->string('carencia_contrato')->nullable();
            $table->string('multa_contrato')->nullable();
            $table->decimal('m2_contrato', 10, 2)->nullable();
            $table->decimal('m2_layout_util', 10, 2)->nullable();
            $table->string('pavimento')->nullable();
            $table->string('estacionamento_qtd')->nullable();

            $table->decimal('capex_aprovado', 10, 2)->nullable();
            $table->decimal('coc_aprovado', 5, 2)->nullable(); // porcentagem de 0 a 100
            $table->integer('estimativa_alunos')->nullable();
            $table->string('tier')->nullable();
            $table->string('renda')->nullable();

            $table->string('set_equipamentos')->nullable();
            $table->date('pre_vendas_mkt')->nullable();
            $table->string('pre_vendas_mkt_realizado')->nullable(); // tem números e texto

            $table->string('reuniao_ita_diretoria')->nullable();
            $table->text('obs_reuniao_ita_diretoria')->nullable();
            $table->string('contato_corretor_pp')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planejamento_estrategicos');
    }
};
