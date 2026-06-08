<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_mensagens_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendencia_id')->nullable()->constrained('po_pendencias')->nullOnDelete();
            $table->string('telefone');
            $table->string('direcao'); // RECEBIDA | ENVIADA
            $table->text('mensagem')->nullable();
            $table->string('tipo')->default('TEXTO'); // TEXTO | IMAGEM | DOCUMENTO | AUDIO
            $table->string('midia_url')->nullable();
            $table->string('status_entrega')->nullable(); // ENVIADA | ENTREGUE | LIDA | FALHA
            $table->string('wamid')->nullable(); // ID da mensagem no Meta
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_mensagens_whatsapp');
    }
};
