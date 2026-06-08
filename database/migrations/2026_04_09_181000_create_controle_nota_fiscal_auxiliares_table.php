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
        if (Schema::hasTable('controle_nota_fiscal_auxiliares')) {
            return;
        }

        Schema::create('controle_nota_fiscal_auxiliares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('controle_nota_fiscal_id');
            $table->string('grupo')->index();
            $table->string('numero_as', 20)->nullable()->index();
            $table->string('escopo')->nullable();
            $table->string('empresa')->nullable();
            $table->decimal('percentual_total', 5, 2)->default(100);
            $table->decimal('percentual_faturamento_direto', 5, 2)->default(60);
            $table->decimal('percentual_faturamento_indireto', 5, 2)->default(40);
            $table->decimal('valor_global_a', 12, 2)->default(0);
            $table->decimal('percentual_retencao', 5, 2)->default(0);
            $table->decimal('valor_retencao_b', 12, 2)->default(0);
            $table->decimal('total_medicao_a_menos_b', 12, 2)->default(0);
            $table->decimal('valor_acumulado_medido', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['controle_nota_fiscal_id', 'grupo'], 'controle_nf_auxiliares_unique_group');
            $table->foreign('controle_nota_fiscal_id', 'cnf_aux_controle_fk')
                ->references('id')
                ->on('controle_nota_fiscals')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_nota_fiscal_auxiliares');
    }
};
