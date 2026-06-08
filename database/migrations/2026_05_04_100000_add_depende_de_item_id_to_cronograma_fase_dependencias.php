<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_fase_dependencias', function (Blueprint $table) {
            $table->string('depende_de_fase', 50)->nullable()->change();
            $table->unsignedBigInteger('depende_de_item_id')->nullable()->after('depende_de_fase');
            $table->foreign('depende_de_item_id', 'cfd_dep_item_fk')
                ->references('id')
                ->on('cronograma_fase_itens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_fase_dependencias', function (Blueprint $table) {
            $table->dropForeign('cfd_dep_item_fk');
            $table->dropColumn('depende_de_item_id');
            $table->string('depende_de_fase', 50)->nullable(false)->change();
        });
    }
};
