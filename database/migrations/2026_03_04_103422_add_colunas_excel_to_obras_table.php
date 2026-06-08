<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {

            $table->string('arquitetura')->nullable();
            $table->date('entrada_ponto')->nullable();
            $table->string('status_contrato')->nullable();
            $table->date('data_assinatura_contrato')->nullable();

            $table->integer('entrada_ponto_ate_inauguracao')->nullable();
            $table->integer('assinatura_ate_inauguracao')->nullable();

            $table->date('data_envio_relatorio_fotografico')->nullable();
            $table->date('data_atualizacao_comentario')->nullable();

            $table->date('inicio_real')->nullable();

            $table->longText('observacao_implantacao')->nullable();
            $table->date('inauguracao')->nullable();

            $table->integer('dias_obra_inicio_pmo')->nullable();
            $table->decimal('percentual_obra_executado', 5, 2)->nullable();
            $table->decimal('desvio', 5, 2)->nullable();

            $table->text('itens_criticos')->nullable();
            $table->longText('descricao_itens_criticos')->nullable();

            $table->string('camera_unidade')->nullable();

            $table->date('previsao_ligacao_energia')->nullable();
            $table->string('gerador_contratual')->nullable();

            $table->date('data_check_list')->nullable();

            $table->string('elevador')->nullable();

            $table->string('gestor_pos_obra')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {

            $table->dropColumn([
                'arquitetura',
                'entrada_ponto',
                'status_contrato',
                'data_assinatura_contrato',
                'entrada_ponto_ate_inauguracao',
                'assinatura_ate_inauguracao',
                'data_envio_relatorio_fotografico',
                'data_atualizacao_comentario',
                'inicio_real',
                'observacao_implantacao',
                'inauguracao',

                'dias_obra_inicio_pmo',
                'percentual_obra_executado',
                'desvio',

                'itens_criticos',
                'descricao_itens_criticos',
                'camera_unidade',

                'previsao_ligacao_energia',
                'gerador_contratual',
                'data_check_list',
                'elevador',
                'gestor_pos_obra',
            ]);

        });
    }
};
