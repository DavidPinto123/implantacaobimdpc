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
        if (Schema::hasTable('controle_nota_fiscal_items')) {
            return;
        }

        Schema::create('controle_nota_fiscal_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('controle_nota_fiscal_id')
                ->constrained('controle_nota_fiscals')
                ->cascadeOnDelete();

            $table->foreignId('as_escopo_id')
                ->nullable()
                ->constrained('as_escopos')
                ->nullOnDelete();

            $table->string('grupo')->nullable()->index();
            $table->string('numero_as', 20)->nullable()->index();
            $table->string('escopo')->nullable();

            $table->decimal('valor_global_a', 12, 2)->default(0);
            $table->decimal('percentual_retencao', 5, 2)->default(0);
            $table->decimal('valor_retencao_b', 12, 2)->default(0);
            $table->decimal('total_medicao_a_menos_b', 12, 2)->default(0);
            $table->decimal('valor_acumulado_medido', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);

            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_nota_fiscal_items');
    }
};
