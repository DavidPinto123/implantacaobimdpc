<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_aprovacoes_finalizacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendencia_id')->constrained('po_pendencias')->cascadeOnDelete();
            $table->foreignId('solicitado_por')->constrained('users')->cascadeOnDelete();
            $table->foreignId('aprovado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('PENDENTE'); // PENDENTE | APROVADA | REJEITADA
            $table->text('motivo_rejeicao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_aprovacoes_finalizacao');
    }
};
