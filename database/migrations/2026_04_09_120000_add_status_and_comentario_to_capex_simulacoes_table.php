<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capex_simulacoes', function (Blueprint $table) {
            $table->tinyInteger('status')
                ->after('custo_por_m2');

            $table->text('comentario')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('capex_simulacoes', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'comentario',
            ]);
        });
    }
};
