<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->boolean('data_prevista_manual')->default(false)->after('data_prevista_fim');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->dropColumn('data_prevista_manual');
        });
    }
};
