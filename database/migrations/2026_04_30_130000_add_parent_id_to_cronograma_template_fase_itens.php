<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('cronograma_template_fase_id');
            $table->foreign('parent_id')
                ->references('id')
                ->on('cronograma_template_fase_itens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
