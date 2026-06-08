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
        Schema::table('gestao_obras', function (Blueprint $table) {
            // remove o campo antigo
            // $table->dropColumn('construtora');

            // adiciona o relacionamento com construtoras
            $table->foreignId('construtora_id')
                ->nullable()
                ->after('nome')
                ->constrained()
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestao_obras', function (Blueprint $table) {
            $table->dropForeign(['construtora_id']);
            // $table->dropColumn('construtora');

            // adiciona de volta o campo string caso precise reverter
            $table->string('construtora')->after('nome');
        });
    }
};
