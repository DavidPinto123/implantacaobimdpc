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
        Schema::table('obras', function (Blueprint $table) {
            $table->string('codigo')->nullable();
            $table->string('sigla')->nullable();
            $table->string('nova_sigla')->nullable();
            $table->string('unidade')->nullable();
            $table->string('marca')->nullable();
            $table->string('pipe_land')->nullable();
            $table->string('status_visita')->nullable();
            $table->string('status_proj_exec')->nullable();
            $table->string('engenharia')->nullable();
            $table->string('comercial')->nullable();
            $table->date('status_data_posse')->nullable();
            $table->date('inicio')->nullable();
            $table->date('fim')->nullable();
            $table->integer('prazo_planejado')->nullable();
            $table->integer('prazo_realizado')->nullable();
            $table->date('inicio_imp')->nullable();
            $table->date('fim_imp')->nullable();
            $table->longText('observacao')->nullable();
            $table->integer('imp_prazo_planej')->nullable();
            $table->integer('imp_prazo_realiz')->nullable();
            $table->integer('mes')->nullable();
            $table->integer('ano')->nullable();
            $table->string('tipo_imovel')->nullable();
            $table->text('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf')->nullable();
            $table->string('empreendimento')->nullable();
            $table->string('locacao')->nullable();
            $table->text('contato_corretor')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn([
                'codigo',
                'sigla',
                'nova_sigla',
                'unidade',
                'marca',
                'pipe_land',
                'status_visita',
                'status_proj_exec',
                'engenharia',
                'comercial',
                'status_data_posse',
                'inicio',
                'fim',
                'prazo_planejado',
                'prazo_realizado',
                'inicio_imp',
                'fim_imp',
                'observacao',
                'imp_prazo_planej',
                'imp_prazo_realiz',
                'mes',
                'ano',
                'tipo_imovel',
                'endereco',
                'cidade',
                'uf',
                'empreendimento',
                'locacao',
                'contato_corretor',
            ]);
        });
    }
};
