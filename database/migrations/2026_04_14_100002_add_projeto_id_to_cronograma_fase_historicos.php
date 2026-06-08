<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_historicos', function (Blueprint $table) {
            $table->foreignId('projeto_id')->nullable()->after('id')->constrained('projetos')->cascadeOnDelete();
            $table->unsignedBigInteger('cronograma_fase_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_historicos', function (Blueprint $table) {
            $table->dropForeign(['projeto_id']);
            $table->dropColumn('projeto_id');
        });
    }
};
