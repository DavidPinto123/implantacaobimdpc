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
        Schema::table('relatorio_fotograficos', function (Blueprint $table) {

            $table->foreignId('autor_id')
                ->after('gestor_id')
                ->constrained('users');

            $table->string('status')
                ->default('rascunho')
                ->after('autor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('relatorio_fotograficos', function (Blueprint $table) {
            $table->dropForeign(['autor_id']);
            $table->dropColumn(['autor_id', 'status']);
        });
    }
};
