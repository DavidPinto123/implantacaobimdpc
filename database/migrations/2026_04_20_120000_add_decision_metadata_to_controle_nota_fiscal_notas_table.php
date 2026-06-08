<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('controle_nota_fiscal_notas', 'decidido_por_id')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->foreignId('decidido_por_id')
                    ->nullable()
                    ->after('importado_por_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('controle_nota_fiscal_notas', 'decidido_em')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->timestamp('decidido_em')
                    ->nullable()
                    ->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('controle_nota_fiscal_notas', 'decidido_por_id')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('decidido_por_id');
            });
        }

        if (Schema::hasColumn('controle_nota_fiscal_notas', 'decidido_em')) {
            Schema::table('controle_nota_fiscal_notas', function (Blueprint $table) {
                $table->dropColumn('decidido_em');
            });
        }
    }
};
