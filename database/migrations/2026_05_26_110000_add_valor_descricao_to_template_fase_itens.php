<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->decimal('valor', 15, 2)->nullable()->after('titulo')->comment('Valor do subserviço no template');
            $table->text('descricao')->nullable()->after('valor')->comment('Descrição detalhada do subserviço');
        });

        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->decimal('valor', 15, 2)->nullable()->after('titulo')->comment('Valor do subserviço no projeto (pode ser ajustado)');
            $table->text('descricao')->nullable()->after('valor')->comment('Descrição detalhada do subserviço');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->dropColumn(['valor', 'descricao']);
        });

        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->dropColumn(['valor', 'descricao']);
        });
    }
};
