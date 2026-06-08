<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->boolean('bloqueada_pos_contrato')
                ->default(false)
                ->after('regra_elastica');

            $table->index('bloqueada_pos_contrato');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropIndex(['bloqueada_pos_contrato']);
            $table->dropColumn('bloqueada_pos_contrato');
        });
    }
};
