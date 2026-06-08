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
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->boolean('energia_carga_superior_150')->nullable();
            $table->text('descricao_energia_carga_superior_150')->nullable();
            $table->json('foto_energia_carga_superior_150')->nullable();

            $table->boolean('cobertura_vao_1_5')->nullable();
            $table->text('descricao_cobertura_vao_1_5')->nullable();
            $table->json('foto_cobertura_vao_1_5')->nullable();

            $table->boolean('planta_demarcacao_area')->nullable();
            $table->string('link_planta_demarcacao_area')->nullable();
            $table->text('descricao_planta_demarcacao_area')->nullable();
            $table->json('foto_planta_demarcacao_area')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn([
                'energia_carga_superior_150',
                'descricao_energia_carga_superior_150',
                'foto_energia_carga_superior_150',
                'cobertura_vao_1_5',
                'descricao_cobertura_vao_1_5',
                'foto_cobertura_vao_1_5',
                'planta_demarcacao_area',
                'link_planta_demarcacao_area',
                'descricao_planta_demarcacao_area',
                'foto_planta_demarcacao_area',
            ]);
        });
    }
};
