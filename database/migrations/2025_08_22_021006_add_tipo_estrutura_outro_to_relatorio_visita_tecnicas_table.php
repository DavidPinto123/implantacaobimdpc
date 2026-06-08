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
            $table->longText('tipo_estrutura_outro')->nullable()->after('tipo_estrutura');
            $table->longText('estanqueidade_outro')->nullable()->after('estanqueidade');
            $table->string('prazo_de_obras_outro')->nullable()->after('prazo_de_obras');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_visita_tecnicas', function (Blueprint $table) {
            $table->dropColumn('tipo_estrutura_outro');
            $table->dropColumn('estanqueidade_outro');
            $table->dropColumn('prazo_de_obras_outro');
        });
    }
};
