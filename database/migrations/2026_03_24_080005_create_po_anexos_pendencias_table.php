<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_anexos_pendencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendencia_id')->constrained('po_pendencias')->cascadeOnDelete();
            $table->string('tipo'); // FOTO_INICIAL | EVIDENCIA
            $table->string('url');
            $table->string('nome_arquivo')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_anexos_pendencias');
    }
};
