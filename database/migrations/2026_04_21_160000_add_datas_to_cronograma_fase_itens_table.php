<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->date('data_prevista_inicio')->nullable()->after('recebido');
            $table->date('data_prevista_fim')->nullable()->after('data_prevista_inicio');
            $table->date('data_realizada_inicio')->nullable()->after('data_prevista_fim');
            $table->date('data_realizada_fim')->nullable()->after('data_realizada_inicio');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->dropColumn([
                'data_prevista_inicio',
                'data_prevista_fim',
                'data_realizada_inicio',
                'data_realizada_fim',
            ]);
        });
    }
};
