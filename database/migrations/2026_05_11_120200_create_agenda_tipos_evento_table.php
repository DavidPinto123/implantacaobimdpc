<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_tipos_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setor_id')->constrained('setores')->cascadeOnDelete();
            $table->string('slug', 80);
            $table->string('nome', 120);
            $table->string('cor', 30)->default('#64748b');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['setor_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_tipos_evento');
    }
};
