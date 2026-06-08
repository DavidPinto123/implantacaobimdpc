<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capex_simulacoes', function (Blueprint $table) {
            $table->id();

            // FK para projetos
            $table->foreignId('projeto_id')
                ->constrained('projetos')
                ->cascadeOnDelete();

            // Dados principais
            $table->decimal('area_unidade', 15, 2)->default(0);
            $table->decimal('fator_correcao', 10, 4)->default(1);

            // Faixa identificada
            $table->foreignId('as_faixa_area_id')
                ->nullable()
                ->constrained('as_faixa_areas')
                ->nullOnDelete();

            $table->string('faixa_nome')->nullable();

            // Resultados
            $table->decimal('custo_total_estimado', 15, 2)->default(0);
            $table->decimal('custo_por_m2', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capex_simulacoes');
    }
};
