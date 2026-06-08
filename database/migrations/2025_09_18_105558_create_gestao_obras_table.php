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
        Schema::create('obras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('projeto_id')->constrained('projetos')->onDelete('cascade');

            // ======================
            // ÁREA DE ENGENHARIA
            // ======================
            $table->string('civil')->nullable();
            $table->string('hidraulica')->nullable();
            $table->string('eletrica')->nullable();
            $table->string('incendio')->nullable();
            $table->string('instalacao_ar_condicionado')->nullable();
            $table->string('maquinas_ar_condicionado')->nullable();
            $table->string('homologados_em_atraso')->nullable();
            $table->string('status')->nullable();

            // ======================
            // POSSE
            // ======================
            $table->string('relatorio_fotografico')->nullable();
            $table->string('termo_de_posse')->nullable();
            $table->longText('comentarios')->nullable();

            // ======================
            // CRONOGRAMA DE IMPLANTAÇÃO
            // ======================
            $table->string('cronograma_implantacao')->nullable();
            $table->integer('dias_para_inauguracao')->nullable();
            $table->decimal('percentual_obra', 5, 2)->nullable();
            $table->string('cronograma_visi')->nullable();
            $table->longText('ponto_atencao')->nullable();

            // Contas de consumo
            $table->text('energia')->nullable();
            $table->text('agua')->nullable();
            $table->text('gas')->nullable();
            $table->longText('comentario')->nullable();

            // Observações e pendências
            $table->string('email_solicitacao_cl')->nullable();
            $table->string('envio_qrcod')->nullable();
            $table->string('checklist_manutencao')->nullable();
            $table->date('inicio_prev_pendencias')->nullable();
            $table->date('termino_prev_pendencias')->nullable();
            $table->longText('comentarios_adicionais')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obras');
    }
};
