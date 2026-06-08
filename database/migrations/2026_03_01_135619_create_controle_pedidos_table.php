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
        Schema::create('controle_pedidos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('projeto_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('cnpj')->nullable();
            $table->string('status')->nullable();
            $table->date('contratacao')->nullable();
            $table->string('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->text('observacoes')->nullable();

            $table->json('pedidos')->nullable(); // 👈 aqui ficam os 52 toggles

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_pedidos');
    }
};
