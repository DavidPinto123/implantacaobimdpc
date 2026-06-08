<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscal_items', 'percentual_total')) {
                $table->decimal('percentual_total', 5, 2)->default(100)->after('empresa');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_direto')) {
                $table->decimal('percentual_faturamento_direto', 5, 2)->default(60)->after('percentual_total');
            }

            if (! Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_indireto')) {
                $table->decimal('percentual_faturamento_indireto', 5, 2)->default(40)->after('percentual_faturamento_direto');
            }
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $columnsToDrop = [];

            if (Schema::hasColumn('controle_nota_fiscal_items', 'percentual_total')) {
                $columnsToDrop[] = 'percentual_total';
            }

            if (Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_direto')) {
                $columnsToDrop[] = 'percentual_faturamento_direto';
            }

            if (Schema::hasColumn('controle_nota_fiscal_items', 'percentual_faturamento_indireto')) {
                $columnsToDrop[] = 'percentual_faturamento_indireto';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
