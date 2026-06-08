<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove linhas soft-deleted que colidem com o novo indice
        // (mantem apenas a mais recente por obras_id + fase).
        DB::statement('
            DELETE f1 FROM cronograma_fases f1
            INNER JOIN cronograma_fases f2
                ON f1.obras_id = f2.obras_id
               AND f1.fase = f2.fase
               AND f1.id < f2.id
            WHERE f1.deleted_at IS NOT NULL
        ');

        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropUnique('cronograma_fases_obras_id_fase_unique');
            $table->unique(['obras_id', 'fase', 'deleted_at'], 'cronograma_fases_obras_id_fase_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropUnique('cronograma_fases_obras_id_fase_unique');
            $table->unique(['obras_id', 'fase'], 'cronograma_fases_obras_id_fase_unique');
        });
    }
};
