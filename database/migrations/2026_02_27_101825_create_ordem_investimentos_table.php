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
        Schema::create('ordem_investimentos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('projeto_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('valor_total', 15, 2);
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('custo_m2', 15, 2)->nullable();

            $table->string('pdf_path')->nullable();

            $table->foreignId('user_id')->nullable()->constrained();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordem_investimentos');
    }
};
