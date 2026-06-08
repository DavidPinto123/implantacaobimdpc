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
        Schema::create('capex_disciplinas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');

            $table->enum('tipo_calculo', [
                'area',        // calcula por m²
                'fixo',        // valor fixo
                'percentual',  // percentual sobre total
            ]);

            $table->decimal('valor_base', 15, 2);

            $table->boolean('usa_fator_correcao')
                ->default(true);

            $table->boolean('ativo')
                ->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capex_disciplinas');
    }
};
