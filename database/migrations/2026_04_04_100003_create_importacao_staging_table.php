<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('importacao_staging')) {
            return;
        }

        Schema::create('importacao_staging', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacao_log_id')->constrained('importacao_logs')->cascadeOnDelete();
            $table->unsignedInteger('linha_planilha');
            $table->string('codigo')->nullable()->index();
            $table->string('acao');
            $table->foreignId('obra_existente_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->json('dados');
            $table->json('conflitos')->nullable();
            $table->json('erro')->nullable();
            $table->timestamps();

            $table->index(['importacao_log_id', 'acao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importacao_staging');
    }
};
