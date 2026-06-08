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
        Schema::create('relatorio_fotograficos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('projeto_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('gestor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->longText('objetivo')->nullable();

            // checklist de entregas contratuais
            $table->json('entregas_contratuais')->nullable();

            // fotos do relatório
            $table->json('fotos')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatorio_fotograficos');
    }
};
