<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            if (! Schema::hasColumn('cronograma_template_fase_itens', 'revisor_id')) {
                $table->unsignedBigInteger('revisor_id')->nullable()->after('valor');
                $table->foreign('revisor_id', 'ctfi_revisor_fk')
                    ->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('cronograma_template_fase_itens', 'observacoes')) {
                $table->text('observacoes')->nullable()->after('descricao');
            }
        });

        if (! Schema::hasTable('cronograma_template_fase_item_responsaveis')) {
            Schema::create('cronograma_template_fase_item_responsaveis', function (Blueprint $table) {
                $table->unsignedBigInteger('cronograma_template_fase_item_id');
                $table->unsignedBigInteger('user_id');
                $table->primary(
                    ['cronograma_template_fase_item_id', 'user_id'],
                    'ctfi_responsavel_pk'
                );
                $table->foreign('cronograma_template_fase_item_id', 'ctfi_resp_item_fk')
                    ->references('id')->on('cronograma_template_fase_itens')->cascadeOnDelete();
                $table->foreign('user_id', 'ctfi_resp_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_template_fase_item_responsaveis');

        Schema::table('cronograma_template_fase_itens', function (Blueprint $table) {
            $table->dropForeign('ctfi_revisor_fk');
            $table->dropColumn(['revisor_id', 'observacoes']);
        });
    }
};
