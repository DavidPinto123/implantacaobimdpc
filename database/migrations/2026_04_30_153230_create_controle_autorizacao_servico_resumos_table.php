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
        Schema::create('controle_autorizacao_servico_resumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('oi_shell', 15, 2)->nullable()->default(0);
            $table->decimal('oi_recheio', 15, 2)->nullable()->default(0);
            $table->decimal('valor_inicial_shell', 15, 2)->nullable()->default(0);
            $table->decimal('valor_inicial_recheio', 15, 2)->nullable()->default(0);
            $table->decimal('valor_final_shell', 15, 2)->nullable()->default(0);
            $table->decimal('valor_final_recheio', 15, 2)->nullable()->default(0);
            $table->decimal('valor_final_adicional_hp', 15, 2)->nullable()->default(0);
            $table->decimal('valor_final_adicional_smart', 15, 2)->nullable()->default(0);
            $table->timestamps();

            $table->unique('obra_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_autorizacao_servico_resumos');
    }
};
