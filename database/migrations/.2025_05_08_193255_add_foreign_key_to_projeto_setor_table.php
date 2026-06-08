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
        Schema::table('projeto_setor', function (Blueprint $table) {
            $table->foreign('setor_id')->references('id')->on('setores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projeto_setor', function (Blueprint $table) {
            $table->dropForeign(['setor_id']);
        });
    }
};
