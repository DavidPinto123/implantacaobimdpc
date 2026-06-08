<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capex_simulacao_itens', function (Blueprint $table): void {
            $table->foreignId('grupo_oi_id')
                ->nullable()
                ->after('as_escopo_id')
                ->constrained('grupo_ois')
                ->nullOnDelete();
        });

        DB::statement('
            UPDATE capex_simulacao_itens csi
            INNER JOIN as_escopos ae ON ae.id = csi.as_escopo_id
            SET csi.grupo_oi_id = ae.grupo_oi_id
            WHERE ae.grupo_oi_id IS NOT NULL
              AND csi.grupo_oi_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('capex_simulacao_itens', function (Blueprint $table): void {
            $table->dropForeign(['grupo_oi_id']);
            $table->dropColumn('grupo_oi_id');
        });
    }
};
