<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_conversas_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('telefone')->unique();
            $table->foreignId('pendencia_id')->nullable()->constrained('po_pendencias')->nullOnDelete();
            $table->string('perfil'); // LIDER | CONSTRUTORA | GESTOR
            $table->string('fase');
            $table->json('contexto')->nullable();
            $table->dateTime('ultima_mensagem_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_conversas_whatsapp');
    }
};
