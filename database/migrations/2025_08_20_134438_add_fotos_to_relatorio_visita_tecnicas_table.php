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
            $table->json('foto_entrada_de_energia')->nullable();
            $table->json('foto_energia_provisoria')->nullable();
            $table->json('foto_unica_medicao')->nullable();
            $table->json('foto_spda')->nullable();
            $table->json('foto_telegonia_dg')->nullable();
            $table->json('foto_necessario_estrutura_auxiliar')->nullable();
            $table->json('foto_estrutura_fachada')->nullable();
            $table->json('foto_cobertura_isolamento')->nullable();
            $table->json('foto_permitidas_furacoes_laje')->nullable();
            $table->json('foto_sobrecarga_minima_laje')->nullable();
            $table->json('foto_sobrecarga_minima_laje_teto')->nullable();
            $table->json('foto_local_tomada_ar_externo_exaustao')->nullable();
            $table->json('foto_alvenaria_periferia_existente')->nullable();
            $table->json('foto_reboco_interno_externo_existente')->nullable();
            $table->json('foto_estanqueidade')->nullable();
            $table->json('foto_area_tecnica_externa_existente')->nullable();
            $table->json('foto_sugestao_area_tecnica_interna')->nullable();
            $table->json('foto_prever_acustica_condensadores')->nullable();
            $table->json('foto_prever_protecao_condensadores')->nullable();
            $table->json('foto_reservatorio_agua_existente')->nullable();
            $table->json('foto_reservatorio_incendio_existente')->nullable();
            $table->json('foto_ponto_esgoto_existente_shell')->nullable();
            $table->json('foto_rede_gas_disponivel')->nullable();
            $table->json('foto_medidor_agua_instalado_ligado')->nullable();
            $table->json('foto_sistema_incendio_existente')->nullable();
            $table->json('foto_pd_acima_livre')->nullable();
            $table->json('foto_necessario_elevador_plataforma')->nullable();
            $table->json('foto_piso_acabamento_polido')->nullable();
            $table->json('foto_necessario_pelicula_fachada')->nullable();
            $table->json('foto_prever_marquise')->nullable();
            $table->json('foto_prever_porta_enrolar')->nullable();
            $table->json('foto_caixilhos_vidros_existentes')->nullable();
            $table->json('foto_prever_impermeabilizacao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn([
                'foto_entrada_de_energia',
                'foto_energia_provisoria',
                'foto_unica_medicao',
                'foto_spda',
                'foto_telegonia_dg',
                'foto_necessario_estrutura_auxiliar',
                'foto_estrutura_fachada',
                'foto_cobertura_isolamento',
                'foto_permitidas_furacoes_laje',
                'foto_sobrecarga_minima_laje',
                'foto_sobrecarga_minima_laje_teto',
                'foto_local_tomada_ar_externo_exaustao',
                'foto_alvenaria_periferia_existente',
                'foto_reboco_interno_externo_existente',
                'foto_estanqueidade',
                'foto_area_tecnica_externa_existente',
                'foto_sugestao_area_tecnica_interna',
                'foto_prever_acustica_condensadores',
                'foto_prever_protecao_condensadores',
                'foto_reservatorio_agua_existente',
                'foto_reservatorio_incendio_existente',
                'foto_ponto_esgoto_existente_shell',
                'foto_rede_gas_disponivel',
                'foto_medidor_agua_instalado_ligado',
                'foto_sistema_incendio_existente',
                'foto_pd_acima_livre',
                'foto_necessario_elevador_plataforma',
                'foto_piso_acabamento_polido',
                'foto_necessario_pelicula_fachada',
                'foto_prever_marquise',
                'foto_prever_porta_enrolar',
                'foto_caixilhos_vidros_existentes',
                'foto_prever_impermeabilizacao',
            ]);
        });
    }
};
