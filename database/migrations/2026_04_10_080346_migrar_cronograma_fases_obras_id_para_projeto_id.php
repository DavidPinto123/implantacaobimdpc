<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->unsignedBigInteger('projeto_id')
                ->nullable()
                ->after('id')
                ->comment('Projeto ao qual o cronograma pertence');
        });

        DB::statement('
            UPDATE cronograma_fases
            SET projeto_id = (
                SELECT obras.projeto_id
                FROM obras
                WHERE obras.id = cronograma_fases.obras_id
            )
        ');

        DB::table('cronograma_fases')->whereNull('projeto_id')->delete();

        DB::statement('ALTER TABLE cronograma_fases MODIFY projeto_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE cronograma_fases MODIFY obras_id BIGINT UNSIGNED NULL');

        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->foreign('projeto_id')->references('id')->on('projetos')->cascadeOnDelete();
            $table->index(['projeto_id', 'ordem'], 'cronograma_fases_projeto_id_ordem_index');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropIndex('cronograma_fases_projeto_id_ordem_index');
            $table->dropForeign(['projeto_id']);
            $table->dropColumn('projeto_id');
        });
    }
};
