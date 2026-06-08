<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_atualizacoes_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendencia_id')->constrained('po_pendencias')->cascadeOnDelete();
            $table->string('status_anterior')->nullable();
            $table->string('status_novo');
            $table->text('comentario')->nullable();
            $table->string('atualizado_por'); // nome livre para registrar bot ou usuário
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_atualizacoes_status');
    }
};
