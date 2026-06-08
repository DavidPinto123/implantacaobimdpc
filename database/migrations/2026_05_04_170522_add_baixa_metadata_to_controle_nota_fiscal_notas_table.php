<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('controle_nota_fiscal_notas', 'baixado_por_id')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->foreignId('baixado_por_id')
                    ->nullable()
                    ->after('baixado')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('controle_nota_fiscal_notas', 'baixado_em')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->timestamp('baixado_em')
                    ->nullable()
                    ->after('baixado_por_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('controle_nota_fiscal_notas', 'baixado_por_id')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('baixado_por_id');
            });
        }

        if (Schema::hasColumn('controle_nota_fiscal_notas', 'baixado_em')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->dropColumn('baixado_em');
            });
        }
    }
};
