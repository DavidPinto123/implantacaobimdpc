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
        Schema::create('nota_fiscal_tipo_faturamento', function (Blueprint $table) {
            $table->foreignId('nota_fiscal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tipo_faturamento_id')->constrained()->cascadeOnDelete();
            $table->primary(['nota_fiscal_id', 'tipo_faturamento_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_fiscal_tipo_faturamento');
    }
};
