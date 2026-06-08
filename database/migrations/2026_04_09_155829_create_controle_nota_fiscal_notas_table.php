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
        if (Schema::hasTable('controle_nota_fiscal_notas')) {
            return;
        }

        Schema::create('controle_nota_fiscal_notas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('controle_nota_fiscal_item_id')
                ->constrained('controle_nota_fiscal_items')
                ->cascadeOnDelete();

            $table->string('tipo_medicao', 20)->default('direto')->index();
            $table->string('empresa')->nullable();
            $table->string('numero_nf')->nullable()->index();
            $table->string('cnpj_faturamento')->nullable();
            $table->decimal('valor_acumulado_medido_nf', 12, 2)->default(0);

            $table->date('emissao')->nullable();
            $table->date('recebimento')->nullable();
            $table->date('envio')->nullable();

            $table->string('status')->nullable()->index();
            $table->text('observacoes')->nullable();

            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_nota_fiscal_notas');
    }
};
