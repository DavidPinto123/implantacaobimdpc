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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('pais_id')->nullable()->constrained('pais');
            $table->foreignId('estado_id')->nullable()->constrained('estados');
            $table->foreignId('cidade_id')->nullable()->constrained('cidades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pais_id']);
            $table->dropForeign(['estado_id']);
            $table->dropForeign(['cidade_id']);
            $table->dropColumn(['pais_id', 'estado_id', 'cidade_id']);
        });
    }
};
