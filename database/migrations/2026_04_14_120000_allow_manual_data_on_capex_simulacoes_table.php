<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capex_simulacoes', function (Blueprint $table) {
            $table->string('nome')->nullable()->after('projeto_id');
            $table->string('sigla')->nullable()->after('nome');
            $table->string('endereco')->nullable()->after('sigla');
            $table->string('uf', 2)->nullable()->after('endereco');

            $table->dropForeign(['projeto_id']);
        });

        DB::statement('ALTER TABLE capex_simulacoes MODIFY projeto_id BIGINT UNSIGNED NULL');

        Schema::table('capex_simulacoes', function (Blueprint $table) {
            $table->foreign('projeto_id')
                ->references('id')
                ->on('projetos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('capex_simulacoes')->whereNull('projeto_id')->delete();

        Schema::table('capex_simulacoes', function (Blueprint $table) {
            $table->dropForeign(['projeto_id']);
        });

        DB::statement('ALTER TABLE capex_simulacoes MODIFY projeto_id BIGINT UNSIGNED NOT NULL');

        Schema::table('capex_simulacoes', function (Blueprint $table) {
            $table->foreign('projeto_id')
                ->references('id')
                ->on('projetos')
                ->cascadeOnDelete();

            $table->dropColumn([
                'nome',
                'sigla',
                'endereco',
                'uf',
            ]);
        });
    }
};
