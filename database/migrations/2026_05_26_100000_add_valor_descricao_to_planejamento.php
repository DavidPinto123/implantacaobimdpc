<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->decimal('valor', 15, 2)->nullable()->after('percentual_conclusao')->comment('Valor contratado do serviço (pode variar por projeto)');
            $table->text('descricao')->nullable()->after('valor')->comment('Descrição detalhada do serviço');
        });

        Schema::table('cronograma_template_fases', function (Blueprint $table) {
            $table->decimal('valor', 15, 2)->nullable()->after('duracao_dias')->comment('Valor padrão do serviço no template');
            $table->text('descricao')->nullable()->after('valor')->comment('Descrição detalhada do serviço');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropColumn(['valor', 'descricao']);
        });

        Schema::table('cronograma_template_fases', function (Blueprint $table) {
            $table->dropColumn(['valor', 'descricao']);
        });
    }
};
