<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona suporte a templates pareados (POSSE x OBRAS) — reunião 12/05.
 *
 * Cada template pode opcionalmente apontar para outro template do par via
 * `template_pareado_id`. O campo `modo_ancora` indica qual variante o
 * template representa (posse | obras).
 *
 * Para templates pré-existentes:
 *  - Se `ancora_campo` contém 'posse' → modo_ancora = 'posse'
 *  - Caso contrário → modo_ancora = 'obras'
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_templates', function (Blueprint $table) {
            $table->string('modo_ancora', 10)->default('posse')->after('ancora_campo');
            $table->foreignId('template_pareado_id')
                ->nullable()
                ->after('modo_ancora')
                ->constrained('cronograma_templates')
                ->nullOnDelete();
        });

        DB::statement(
            "UPDATE cronograma_templates SET modo_ancora = 'obras' "
            ."WHERE ancora_campo NOT LIKE '%posse%' AND ancora_campo IS NOT NULL"
        );
    }

    public function down(): void
    {
        Schema::table('cronograma_templates', function (Blueprint $table) {
            $table->dropForeign(['template_pareado_id']);
            $table->dropColumn(['template_pareado_id', 'modo_ancora']);
        });
    }
};
