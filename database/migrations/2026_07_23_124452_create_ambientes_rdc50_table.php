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
        Schema::create('ambientes_rdc50', function (Blueprint $table) {
            $table->id();
            $table->string('unidade_funcional');
            $table->string('subgrupo');
            $table->string('tipo');
            $table->string('num_atividade')->nullable();
            $table->string('ambiente');
            $table->string('nome_fiorentini')->nullable();
            $table->string('obrigatoriedade')->nullable();
            $table->string('quantificacao_minima')->nullable();
            $table->string('pe_direito_minimo')->nullable();
            $table->string('area_dimensao_minima')->nullable();
            $table->string('instalacoes')->nullable();
            $table->string('rev_piso')->nullable();
            $table->string('rev_parede')->nullable();
            $table->string('rev_forro')->nullable();
            $table->string('rev_rodape')->nullable();
            $table->string('rev_rodameio')->nullable();
            $table->text('comentarios')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambientes_rdc50');
    }
};
