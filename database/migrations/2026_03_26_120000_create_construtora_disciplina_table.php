<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construtora_disciplina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('construtora_id')->constrained('construtoras')->cascadeOnDelete();
            $table->foreignId('disciplina_config_id')->constrained('po_disciplinas_config')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['construtora_id', 'disciplina_config_id'], 'construtora_disciplina_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('construtora_disciplina');
    }
};
