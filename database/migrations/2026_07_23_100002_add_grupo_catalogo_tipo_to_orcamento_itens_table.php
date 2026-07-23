<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->string('grupo_catalogo', 191)->nullable()->after('descricao');
            $table->string('tipo', 100)->nullable()->after('grupo_catalogo');
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table) {
            $table->dropColumn(['grupo_catalogo', 'tipo']);
        });
    }
};
