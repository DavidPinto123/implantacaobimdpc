<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_whatsapp_bot_mensagens', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 100)->unique();
            $table->text('texto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_whatsapp_bot_mensagens');
    }
};
