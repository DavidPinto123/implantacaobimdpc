<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_fase_item_dependencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cronograma_fase_item_id');
            $table->foreign('cronograma_fase_item_id', 'cfid_item_fk')
                ->references('id')
                ->on('cronograma_fase_itens')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('depende_de_fase_id')->nullable();
            $table->foreign('depende_de_fase_id', 'cfid_dep_fase_fk')
                ->references('id')
                ->on('cronograma_fases')
                ->nullOnDelete();
            $table->unsignedBigInteger('depende_de_item_id')->nullable();
            $table->foreign('depende_de_item_id', 'cfid_dep_item_fk')
                ->references('id')
                ->on('cronograma_fase_itens')
                ->nullOnDelete();
            $table->string('gatilho', 30)->default('fim_anterior');
            $table->smallInteger('gap_dias')->default(1);
            $table->timestamps();

            $table->index('cronograma_fase_item_id', 'cfid_item_idx');
            $table->index('depende_de_fase_id', 'cfid_dep_fase_idx');
            $table->index('depende_de_item_id', 'cfid_dep_item_idx');
        });

        $agora = now();

        DB::table('cronograma_fase_itens')
            ->whereNotNull('depende_de_fase_id')
            ->orderBy('id')
            ->select(['id', 'depende_de_fase_id'])
            ->chunkById(500, function ($itens) use ($agora): void {
                foreach ($itens as $item) {
                    DB::table('cronograma_fase_item_dependencias')->insert([
                        'cronograma_fase_item_id' => $item->id,
                        'depende_de_fase_id' => $item->depende_de_fase_id,
                        'gatilho' => 'fim_anterior',
                        'gap_dias' => 1,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ]);
                }
            });

        DB::table('cronograma_fase_itens')
            ->whereNotNull('depende_de_item_id')
            ->orderBy('id')
            ->select(['id', 'depende_de_item_id'])
            ->chunkById(500, function ($itens) use ($agora): void {
                foreach ($itens as $item) {
                    DB::table('cronograma_fase_item_dependencias')->insert([
                        'cronograma_fase_item_id' => $item->id,
                        'depende_de_item_id' => $item->depende_de_item_id,
                        'gatilho' => 'fim_anterior',
                        'gap_dias' => 1,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_fase_item_dependencias');
    }
};
