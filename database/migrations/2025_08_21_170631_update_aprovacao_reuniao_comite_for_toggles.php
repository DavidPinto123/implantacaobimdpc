<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aprovacao_reuniao_comite', function (Blueprint $table) {
            // Quem aprovou (se ainda não existir)
            if (! Schema::hasColumn('aprovacao_reuniao_comite', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('projeto_id')->index();
                // Se quiser FK:
                // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            }

            // Status (se não existir)
            if (! Schema::hasColumn('aprovacao_reuniao_comite', 'aprovacao')) {
                $table->enum('aprovacao', ['aprovado', 'aprovado_com_ressalva', 'reprovado'])
                    ->after('role');
            }

            // Textos / anexos (se não existirem)
            if (! Schema::hasColumn('aprovacao_reuniao_comite', 'comentarios_gerais')) {
                $table->text('comentarios_gerais')->nullable();
            }
            if (! Schema::hasColumn('aprovacao_reuniao_comite', 'observacoes_ressalva')) {
                $table->text('observacoes_ressalva')->nullable();
            }
            if (! Schema::hasColumn('aprovacao_reuniao_comite', 'anexos_ressalva')) {
                $table->json('anexos_ressalva')->nullable();
            }

            // Checklists como boolean (mudança de tipo requer doctrine/dbal)
            if (Schema::hasColumn('aprovacao_reuniao_comite', 'pmo_cronograma')) {
                $table->boolean('pmo_cronograma')->default(false)->nullable(false)->change();
            } else {
                $table->boolean('pmo_cronograma')->default(false)->nullable(false);
            }

            if (Schema::hasColumn('aprovacao_reuniao_comite', 'pmo_termo_abertura')) {
                $table->boolean('pmo_termo_abertura')->default(false)->nullable(false)->change();
            } else {
                $table->boolean('pmo_termo_abertura')->default(false)->nullable(false);
            }

            if (Schema::hasColumn('aprovacao_reuniao_comite', 'comercial_proposta')) {
                $table->boolean('comercial_proposta')->default(false)->nullable(false)->change();
            } else {
                $table->boolean('comercial_proposta')->default(false)->nullable(false);
            }

            if (Schema::hasColumn('aprovacao_reuniao_comite', 'comercial_contrato')) {
                $table->boolean('comercial_contrato')->default(false)->nullable(false)->change();
            } else {
                $table->boolean('comercial_contrato')->default(false)->nullable(false);
            }

            if (Schema::hasColumn('aprovacao_reuniao_comite', 'planejamento_plano')) {
                $table->boolean('planejamento_plano')->default(false)->nullable(false)->change();
            } else {
                $table->boolean('planejamento_plano')->default(false)->nullable(false);
            }

            if (Schema::hasColumn('aprovacao_reuniao_comite', 'planejamento_estudo')) {
                $table->boolean('planejamento_estudo')->default(false)->nullable(false)->change();
            } else {
                $table->boolean('planejamento_estudo')->default(false)->nullable(false);
            }
        });
    }

    public function down(): void
    {
        // Intencionalmente vazio para não reverter tipos/estruturas em produção.
        // Se precisar reverter, crie uma migration específica de rollback.
    }
};
