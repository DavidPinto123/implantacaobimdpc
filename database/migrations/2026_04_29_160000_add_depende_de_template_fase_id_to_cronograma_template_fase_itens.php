<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('depende_de_template_fase_id')->nullable()->after('depende_de_item_id');
            $table->foreign('depende_de_template_fase_id', 'ctfi_dep_template_fase_fk')
                ->references('id')
                ->on('cronograma_template_fases')
                ->nullOnDelete();
            $table->index('depende_de_template_fase_id', 'ctfi_dep_template_fase_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->dropForeign('ctfi_dep_template_fase_fk');
            $table->dropIndex('ctfi_dep_template_fase_idx');
            $table->dropColumn('depende_de_template_fase_id');
        });
    }
};
