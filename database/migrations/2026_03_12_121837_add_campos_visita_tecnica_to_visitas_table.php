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
            // Cobertura
            $table->decimal('cobertura_vao_1_5_metragem', 8, 2)->nullable();
            $table->decimal('cobertura_area_isolamento', 8, 2)->nullable();

            // Reservatórios
            $table->decimal('reservatorio_agua_litragem', 8, 2)->nullable();
            $table->decimal('reservatorio_incendio_litragem', 8, 2)->nullable();

            // Esgoto
            $table->decimal('ponto_esgoto_mais_proximo', 8, 2)->nullable();

            // Medidor água
            $table->string('numero_instalacao_agua')->nullable();

            // Piso
            $table->decimal('piso_area_intervencao', 8, 2)->nullable();

            // Película fachada
            $table->decimal('pelicula_fachada_area', 8, 2)->nullable();

            // Porta de enrolar
            $table->decimal('porta_enrolar_area_necessaria', 8, 2)->nullable();

            // Caixilhos e vidros
            $table->decimal('caixilhos_vidros_area', 8, 2)->nullable();

            // Impermeabilização
            $table->decimal('impermeabilizacao_area_necessaria', 8, 2)->nullable();

            // Estrutura laje
            $table->string('comprovacao_sobrecarga_laje')->nullable();

            $table->string('comprovacao_sobrecarga_laje_teto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn([
                'cobertura_vao_1_5_metragem',
                'cobertura_area_isolamento',
                'reservatorio_agua_litragem',
                'reservatorio_incendio_litragem',
                'ponto_esgoto_mais_proximo',
                'numero_instalacao_agua',
                'piso_area_intervencao',
                'pelicula_fachada_area',
                'porta_enrolar_area_necessaria',
                'caixilhos_vidros_area',
                'impermeabilizacao_area_necessaria',
                'comprovacao_sobrecarga_laje',
                'comprovacao_sobrecarga_laje_teto',
            ]);
        });
    }
};
