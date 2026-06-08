<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capex_simulacao_itens', function (Blueprint $table) {
            $table->id();

            // Relação com a simulação
            $table->foreignId('capex_simulacao_id')
                ->constrained('capex_simulacoes')
                ->cascadeOnDelete();

            // Escopo (pode ser null para manual)
            $table->foreignId('as_escopo_id')
                ->nullable()
                ->constrained('as_escopos')
                ->nullOnDelete();

            // Tipo: auto ou manual
            $table->string('tipo'); // auto | manual

            // Controle
            $table->boolean('incluir')->default(true);
            $table->unsignedInteger('ordem')->nullable();

            // Dados do escopo (snapshot)
            $table->string('nome_escopo');

            // Valores
            $table->decimal('valor_base_m2', 15, 2)->default(0);
            $table->decimal('area', 15, 2)->default(0);
            $table->decimal('fator_correcao', 10, 4)->default(1);

            // Resultados
            $table->decimal('custo_estimado', 15, 2)->default(0);
            $table->decimal('percentual', 8, 4)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capex_simulacao_itens');
    }
};
