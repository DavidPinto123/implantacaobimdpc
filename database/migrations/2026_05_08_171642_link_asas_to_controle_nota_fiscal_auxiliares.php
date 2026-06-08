<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asas', function (Blueprint $table): void {
            if (! Schema::hasColumn('asas', 'controle_nota_fiscal_auxiliar_id')) {
                $table->foreignId('controle_nota_fiscal_auxiliar_id')
                    ->nullable()
                    ->after('elaboracao_aditivo_id')
                    ->constrained('controle_nota_fiscal_auxiliares')
                    ->nullOnDelete();
            }
        });

        Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
            $table->index('controle_nota_fiscal_id', 'cnf_aux_controle_idx');
        });

        Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
            $table->dropUnique('controle_nf_auxiliares_unique_group');

            if (! Schema::hasColumn('controle_nota_fiscal_auxiliares', 'numero_complemento')) {
                $table->string('numero_complemento', 20)
                    ->default('')
                    ->after('numero_as');
            }

            $table->unique(
                ['controle_nota_fiscal_id', 'numero_as', 'numero_complemento'],
                'cnf_aux_controle_numero_complemento_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_auxiliares', function (Blueprint $table): void {
            $table->dropUnique('cnf_aux_controle_numero_complemento_unique');

            if (Schema::hasColumn('controle_nota_fiscal_auxiliares', 'numero_complemento')) {
                $table->dropColumn('numero_complemento');
            }

            $table->unique(['controle_nota_fiscal_id', 'grupo'], 'controle_nf_auxiliares_unique_group');
            $table->dropIndex('cnf_aux_controle_idx');
        });

        Schema::table('asas', function (Blueprint $table): void {
            if (Schema::hasColumn('asas', 'controle_nota_fiscal_auxiliar_id')) {
                $table->dropForeignKeyIfExists('asas_controle_nota_fiscal_auxiliar_id_foreign');
                $table->dropColumn('controle_nota_fiscal_auxiliar_id');
            }
        });
    }
};
