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
        Schema::create('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('projeto_id')->constrained()->onDelete('cascade');
            $table->string('numero_relatorio_vt')->nullable();
            $table->date('iniciado_em')->nullable();
            $table->date('concluido_em')->nullable();
            $table->timestamp('sicronizado_em')->nullable();
            $table->string('autor')->nullable();
            $table->string('unidade_relatorio')->nullable();

            // Área 1 - Informações Técnicas
            $table->string('unidade')->nullable();
            $table->string('marca')->nullable();
            $table->string('endereco')->nullable();
            $table->string('responsavel_tecnico')->nullable();
            $table->string('prazo_de_obras')->nullable();
            $table->text('link_drive_fotos_e_videos')->nullable();

            // Área 2 - Elétrica/Telefonia/Internet
            $table->boolean('entrada_de_energia')->nullable();
            $table->text('descricao_energia')->nullable();
            $table->boolean('energia_provisoria')->nullable();
            $table->text('descricao_energia_provisoria')->nullable();
            $table->boolean('unica_medicao')->nullable();
            $table->text('descricao_medicao')->nullable();
            $table->boolean('spda')->nullable();
            $table->text('descricao_spda')->nullable();
            $table->boolean('telegonia_dg')->nullable();
            $table->text('descricao_telefonia')->nullable();

            // Área 3 - Estrutura/Cobertura/Acústica
            $table->boolean('cobertura_isolamento')->nullable();
            $table->text('descricao_cobertura_isolamento')->nullable();
            $table->string('tipo_estrutura')->nullable();
            $table->boolean('necessario_estrutura_auxiliar')->nullable();
            $table->text('descricao_estrutura_auxiliar')->nullable();
            $table->boolean('estrutura_fachada')->nullable();
            $table->text('descricao_estrutura_fachada')->nullable();
            $table->boolean('permitidas_furacoes_laje')->nullable();
            $table->text('descricao_furacoes_laje')->nullable();
            $table->boolean('sobrecarga_minima_laje')->nullable();
            $table->text('descricao_sobrecarga_minima_laje')->nullable();
            $table->boolean('sobrecarga_minima_laje_teto')->nullable();
            $table->text('descricao_sobrecarga_minima_laje_teto')->nullable();
            $table->boolean('local_tomada_ar_externo_exaustao')->nullable();
            $table->text('descricao_local_tomada_ar_externo_exaustao')->nullable();
            $table->boolean('alvenaria_periferia_existente')->nullable();
            $table->text('descricao_alvenaria_periferia_existente')->nullable();
            $table->boolean('reboco_interno_externo_existente')->nullable();
            $table->text('descricao_reboco_interno_externo_existente')->nullable();
            $table->boolean('estanqueidade')->nullable();
            $table->text('descricao_estanqueidade')->nullable();

            // Área 4
            $table->boolean('area_tecnica_externa_existente')->nullable();
            $table->text('descricao_area_tecnica_externa_existente')->nullable();
            $table->boolean('sugestao_area_tecnica_interna')->nullable();
            $table->text('descricao_sugestao_area_tecnica_interna')->nullable();
            $table->boolean('prever_acustica_condensadores')->nullable();
            $table->text('descricao_prever_acustica_condensadores')->nullable();
            $table->boolean('prever_protecao_condensadores')->nullable();
            $table->text('descricao_prever_protecao_condensadores')->nullable();

            // Área 5
            $table->boolean('reservatorio_agua_existente')->nullable();
            $table->text('descricao_reservatorio_agua_existente')->nullable();
            $table->boolean('reservatorio_incendio_existente')->nullable();
            $table->text('descricao_reservatorio_incendio_existente')->nullable();
            $table->boolean('ponto_esgoto_existente_shell')->nullable();
            $table->text('descricao_ponto_esgoto_existente_shell')->nullable();
            $table->boolean('rede_gas_disponivel')->nullable();
            $table->text('descricao_rede_gas_disponivel')->nullable();
            $table->boolean('medidor_agua_instalado_ligado')->nullable();
            $table->text('descricao_medidor_agua_instalado_ligado')->nullable();
            $table->string('sistema_incendio_existente')->nullable();
            $table->text('descricao_sistema_incendio_existente')->nullable();

            // Área 6
            $table->boolean('pd_acima_livre')->nullable();
            $table->text('descricao_pd_acima_livre')->nullable();
            $table->boolean('necessario_elevador_plataforma')->nullable();
            $table->text('descricao_necessario_elevador_plataforma')->nullable();
            $table->boolean('piso_acabamento_polido')->nullable();
            $table->text('descricao_piso_acabamento_polido')->nullable();
            $table->boolean('necessario_pelicula_fachada')->nullable();
            $table->text('descricao_necessario_pelicula_fachada')->nullable();
            $table->boolean('prever_marquise')->nullable();
            $table->text('descricao_prever_marquise')->nullable();
            $table->boolean('prever_porta_enrolar')->nullable();
            $table->text('descricao_prever_porta_enrolar')->nullable();
            $table->boolean('caixilhos_vidros_existentes')->nullable();
            $table->text('descricao_caixilhos_vidros_existentes')->nullable();
            $table->boolean('prever_impermeabilizacao')->nullable();
            $table->text('descricao_prever_impermeabilizacao')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatorio_visita_tecnicas');
    }
};
