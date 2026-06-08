<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obra_entregas_contratuais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnDelete();
            $table->string('entrega');
            $table->text('descricao_entrega')->nullable();
            $table->text('descricao_existente')->nullable();
            $table->string('status')->default('nao_entregue');
            $table->date('data_entrega')->nullable();
            $table->decimal('custo_estimado', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['obra_id', 'sort_order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obra_entregas_contratuais');
    }
};
