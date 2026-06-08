<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dateTime('agendado_em')->nullable()->after('iniciado_em');
        });
    }

    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn('agendado_em');
        });
    }
};
