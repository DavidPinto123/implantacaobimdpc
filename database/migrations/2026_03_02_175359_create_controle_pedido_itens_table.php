<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controle_pedido_itens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('controle_pedido_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('codigo'); // ex: 1.1
            $table->string('nome');   // descrição do serviço

            $table->boolean('contratado')->default(false);

            $table->decimal('valor', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controle_pedido_itens');
    }
};
