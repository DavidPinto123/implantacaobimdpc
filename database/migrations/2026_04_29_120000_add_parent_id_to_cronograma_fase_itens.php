<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('cronograma_fase_id');
            $table->foreign('parent_id', 'cfi_parent_fk')
                ->references('id')
                ->on('cronograma_fase_itens')
                ->cascadeOnDelete();
            $table->index('parent_id', 'cfi_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_itens', function (Blueprint $table) {
            $table->dropForeign('cfi_parent_fk');
            $table->dropIndex('cfi_parent_idx');
            $table->dropColumn('parent_id');
        });
    }
};
