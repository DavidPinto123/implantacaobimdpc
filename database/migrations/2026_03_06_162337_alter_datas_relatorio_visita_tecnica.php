<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dateTime('iniciado_em')->nullable()->change();
            $table->dateTime('concluido_em')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->date('iniciado_em')->nullable()->change();
            $table->date('concluido_em')->nullable()->change();
        });
    }
};
