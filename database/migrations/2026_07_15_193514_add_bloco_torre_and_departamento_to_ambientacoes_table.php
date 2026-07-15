<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ambientacoes', function (Blueprint $table) {
            $table->string('bloco_torre')->nullable()->after('nova_sigla');
            $table->string('departamento')->nullable()->after('bloco_torre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ambientacoes', function (Blueprint $table) {
            $table->dropColumn(['bloco_torre', 'departamento']);
        });
    }
};
