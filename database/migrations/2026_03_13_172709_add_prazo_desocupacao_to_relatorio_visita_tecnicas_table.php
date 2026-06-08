<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->json('contrato_bts')->nullable();
            $table->date('prazo_bts')->nullable();
            $table->date('prazo_desocupacao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn('contrato_bts');
            $table->dropColumn('prazo_bts');
            $table->dropColumn('prazo_desocupacao');
        });
    }
};
