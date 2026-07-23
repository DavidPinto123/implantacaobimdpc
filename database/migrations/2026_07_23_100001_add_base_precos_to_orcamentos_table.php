<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->string('base_precos', 20)->nullable()->after('arquivo_revit');
            $table->string('uf', 2)->nullable()->after('base_precos');
            $table->string('desoneracao', 20)->nullable()->after('uf');
            $table->string('mes_referencia', 10)->nullable()->after('desoneracao');
            $table->date('data_emissao')->nullable()->after('mes_referencia');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['base_precos', 'uf', 'desoneracao', 'mes_referencia', 'data_emissao']);
        });
    }
};
