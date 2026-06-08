<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('controle_nota_fiscal_items')) {
            Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
                if (Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_direto')) {
                    $table->renameColumn('percentual_faturamento_direto', 'percentual_faturamento_mao_obra');
                }

                if (Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_indireto')) {
                    $table->renameColumn('percentual_faturamento_indireto', 'percentual_faturamento_material');
                }
            });
        }

        if (Schema::hasTable('controle_nota_fiscal_auxiliares')) {
            Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
                if (Schema::hasColumn('controle_nota_fiscal_auxiliares', 'percentual_faturamento_direto')) {
                    $table->renameColumn('percentual_faturamento_direto', 'percentual_faturamento_mao_obra');
                }

                if (Schema::hasColumn('controle_nota_fiscal_auxiliares', 'percentual_faturamento_indireto')) {
                    $table->renameColumn('percentual_faturamento_indireto', 'percentual_faturamento_material');
                }
            });
        }

        if (Schema::hasTable('controle_nota_fiscal_notas') && Schema::hasColumn('controle_nota_fiscal_notas', 'tipo_medicao')) {
            DB::table('controle_nota_fiscal_notas')
                ->where('tipo_medicao', 'direto')
                ->update(['tipo_medicao' => 'mao_obra']);

            DB::table('controle_nota_fiscal_notas')
                ->where('tipo_medicao', 'indireto')
                ->update(['tipo_medicao' => 'material']);

            DB::statement("ALTER TABLE controle_nota_fiscal_notas MODIFY tipo_medicao VARCHAR(20) NOT NULL DEFAULT 'mao_obra'");
        }

        if (Schema::hasTable('faturamentos') && Schema::hasColumn('faturamentos', 'tipo')) {
            DB::statement("ALTER TABLE faturamentos MODIFY tipo ENUM('direto', 'indireto', 'mao_obra', 'material') NOT NULL");

            DB::table('faturamentos')
                ->where('tipo', 'direto')
                ->update(['tipo' => 'mao_obra']);

            DB::table('faturamentos')
                ->where('tipo', 'indireto')
                ->update(['tipo' => 'material']);

            DB::statement("ALTER TABLE faturamentos MODIFY tipo ENUM('mao_obra', 'material') NOT NULL");
        }

        if (Schema::hasTable('tipo_faturamentos') && Schema::hasColumn('tipo_faturamentos', 'nome')) {
            DB::table('tipo_faturamentos')
                ->where('nome', 'Direto')
                ->update(['nome' => 'Mão de Obra']);

            DB::table('tipo_faturamentos')
                ->where('nome', 'Indireto')
                ->update(['nome' => 'Material']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('controle_nota_fiscal_items')) {
            Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
                if (Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_mao_obra')) {
                    $table->renameColumn('percentual_faturamento_mao_obra', 'percentual_faturamento_direto');
                }

                if (Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_material')) {
                    $table->renameColumn('percentual_faturamento_material', 'percentual_faturamento_indireto');
                }
            });
        }

        if (Schema::hasTable('controle_nota_fiscal_auxiliares')) {
            Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
                if (Schema::hasColumn('controle_nota_fiscal_auxiliares', 'percentual_faturamento_mao_obra')) {
                    $table->renameColumn('percentual_faturamento_mao_obra', 'percentual_faturamento_direto');
                }

                if (Schema::hasColumn('controle_nota_fiscal_auxiliares', 'percentual_faturamento_material')) {
                    $table->renameColumn('percentual_faturamento_material', 'percentual_faturamento_indireto');
                }
            });
        }

        if (Schema::hasTable('controle_nota_fiscal_notas') && Schema::hasColumn('controle_nota_fiscal_notas', 'tipo_medicao')) {
            DB::table('controle_nota_fiscal_notas')
                ->where('tipo_medicao', 'mao_obra')
                ->update(['tipo_medicao' => 'direto']);

            DB::table('controle_nota_fiscal_notas')
                ->where('tipo_medicao', 'material')
                ->update(['tipo_medicao' => 'indireto']);

            DB::statement("ALTER TABLE controle_nota_fiscal_notas MODIFY tipo_medicao VARCHAR(20) NOT NULL DEFAULT 'direto'");
        }

        if (Schema::hasTable('faturamentos') && Schema::hasColumn('faturamentos', 'tipo')) {
            DB::statement("ALTER TABLE faturamentos MODIFY tipo ENUM('direto', 'indireto', 'mao_obra', 'material') NOT NULL");

            DB::table('faturamentos')
                ->where('tipo', 'mao_obra')
                ->update(['tipo' => 'direto']);

            DB::table('faturamentos')
                ->where('tipo', 'material')
                ->update(['tipo' => 'indireto']);

            DB::statement("ALTER TABLE faturamentos MODIFY tipo ENUM('direto', 'indireto') NOT NULL");
        }

        if (Schema::hasTable('tipo_faturamentos') && Schema::hasColumn('tipo_faturamentos', 'nome')) {
            DB::table('tipo_faturamentos')
                ->where('nome', 'Mão de Obra')
                ->update(['nome' => 'Direto']);

            DB::table('tipo_faturamentos')
                ->where('nome', 'Material')
                ->update(['nome' => 'Indireto']);
        }
    }
};
