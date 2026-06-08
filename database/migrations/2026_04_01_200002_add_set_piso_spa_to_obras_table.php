<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->string('set_equipamentos')->nullable();
            $table->string('piso')->nullable();
            $table->string('alteracao_spa_addons')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn(['set_equipamentos', 'piso', 'alteracao_spa_addons']);
        });
    }
};
