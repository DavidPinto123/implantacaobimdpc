<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_categoria_id')->constrained('orcamento_categorias')->cascadeOnDelete();
            $table->string('codigo', 100)->nullable();
            $table->text('descricao');
            $table->string('unidade', 20);
            $table->decimal('quantidade', 12, 3)->default(0);
            $table->decimal('valor_mat', 12, 2)->default(0);
            $table->decimal('valor_mo', 12, 2)->default(0);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_itens');
    }
};
