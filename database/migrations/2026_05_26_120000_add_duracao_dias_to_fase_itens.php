<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->unsignedSmallInteger('duracao_dias')->nullable()->after('ordem')
                ->comment('Duração individual do subitem em dias. Subitens-pai mostram a soma dos filhos.');
        });

        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->unsignedSmallInteger('duracao_dias')->nullable()->after('ordem')
                ->comment('Duração individual do subitem no template em dias.');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->dropColumn('duracao_dias');
        });

        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->dropColumn('duracao_dias');
        });
    }
};
