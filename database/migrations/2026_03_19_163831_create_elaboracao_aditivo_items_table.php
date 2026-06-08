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
        Schema::create('elaboracao_aditivo_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('elaboracao_aditivo_id')
                ->constrained('elaboracao_aditivos')
                ->cascadeOnDelete();

            $table->string('item')->nullable();
            $table->text('descricao_servico');
            $table->decimal('quantidade', 15, 2)->default(0);
            $table->string('unidade', 50)->nullable();

            $table->decimal('valor_material_unitario', 15, 2)->default(0);
            $table->decimal('valor_mao_obra_unitario', 15, 2)->default(0);
            $table->decimal('total_unitario', 15, 2)->default(0);
            $table->decimal('valor_total_geral', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elaboracao_aditivo_items');
    }
};
