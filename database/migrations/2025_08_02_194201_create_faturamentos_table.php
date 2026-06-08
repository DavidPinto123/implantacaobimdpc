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
        Schema::create('faturamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_fiscal_id')->constrained('nota_fiscals')->cascadeOnDelete();
            $table->enum('tipo', ['direto', 'indireto']);

            $table->string('empresa')->nullable();
            $table->string('numero_nf')->nullable();
            $table->string('cnpj_faturamento_smart')->nullable();
            $table->decimal('valor_acumulado_medido_nf', 15, 2)->nullable();

            $table->date('emissao')->nullable();
            $table->date('recebimento')->nullable();
            $table->date('envio')->nullable();

            $table->string('status')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faturamentos');
    }
};
