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
        Schema::create('nota_fiscals', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('fornecedor');
            $table->string('cnpj', 20)->nullable();
            $table->decimal('valor', 15, 2);
            $table->date('data_emissao')->nullable();
            $table->date('data_recebimento')->nullable();
            $table->date('data_envio')->nullable();
            $table->enum('status', ['pendente', 'paga', 'cancelada'])->default('pendente');
            $table->string('arquivo')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('obra_id')->constrained('gestao_obras')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_fiscals');
    }
};
