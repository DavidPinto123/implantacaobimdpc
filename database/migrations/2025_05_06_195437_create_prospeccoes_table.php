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
        Schema::create('prospeccoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->onDelete('cascade');
            $table->foreignId('etapa_id')->constrained()->cascadeOnDelete();

            // Informações do ponto
            $table->string('nome');
            $table->string('sigla');
            $table->string('status');
            $table->string('tipo_entrada');
            $table->string('nome_contato');
            $table->string('contato');
            $table->string('pin_google')->nullable();

            // Características técnicas
            $table->string('tipo_de_loja');
            $table->integer('n_vagas_livres');
            $table->decimal('area_academia', 10, 2);
            $table->decimal('area_terreno', 10, 2);
            $table->integer('n_pisos');
            $table->decimal('pe_direito', 5, 2);
            $table->string('modelo_entrega_pp');
            $table->decimal('aluguel_cto', 12, 2);
            $table->decimal('luvas', 12, 2);
            $table->decimal('iptu', 12, 2);
            $table->decimal('condominio', 12, 2);
            $table->text('configuracao_academia');
            $table->text('dados_engenharia');
            $table->date('prazo_inicio');
            $table->string('projeto_croqui');

            // Estudo de alunos
            $table->integer('potencial_alunos');
            $table->string('link_estudo_projecao_alunos');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospeccoes');
    }
};
