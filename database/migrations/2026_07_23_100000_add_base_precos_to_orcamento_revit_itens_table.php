<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('revit')->table('orcamento_revit_itens', function (Blueprint $table) {
            $table->string('base_precos', 20)->nullable()->after('sigla');
            $table->string('grupo_catalogo', 191)->nullable()->after('descricao');
            $table->string('tipo', 100)->nullable()->after('grupo_catalogo');
            $table->string('uf', 2)->nullable()->after('valor_mo');
            $table->string('desoneracao', 20)->nullable()->after('uf');
            $table->string('mes_referencia', 10)->nullable()->after('desoneracao');
            $table->date('data_emissao')->nullable()->after('mes_referencia');
        });
    }

    public function down(): void
    {
        Schema::connection('revit')->table('orcamento_revit_itens', function (Blueprint $table) {
            $table->dropColumn([
                'base_precos',
                'grupo_catalogo',
                'tipo',
                'uf',
                'desoneracao',
                'mes_referencia',
                'data_emissao',
            ]);
        });
    }
};
