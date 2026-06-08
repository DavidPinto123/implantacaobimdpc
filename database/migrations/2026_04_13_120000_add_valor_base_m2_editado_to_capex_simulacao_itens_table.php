<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capex_simulacao_itens', function (Blueprint $table) {
            $table->boolean('valor_base_m2_editado')
                ->default(false)
                ->after('valor_base_m2');
        });
    }

    public function down(): void
    {
        Schema::table('capex_simulacao_itens', function (Blueprint $table) {
            $table->dropColumn('valor_base_m2_editado');
        });
    }
};
