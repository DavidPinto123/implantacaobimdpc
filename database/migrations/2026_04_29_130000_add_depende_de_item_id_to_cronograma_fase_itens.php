<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('depende_de_item_id')->nullable()->after('parent_id');
            $table->foreign('depende_de_item_id', 'cfi_dep_fk')
                ->references('id')
                ->on('cronograma_fase_itens')
                ->nullOnDelete();
            $table->index('depende_de_item_id', 'cfi_dep_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->dropForeign('cfi_dep_fk');
            $table->dropIndex('cfi_dep_idx');
            $table->dropColumn('depende_de_item_id');
        });
    }
};
