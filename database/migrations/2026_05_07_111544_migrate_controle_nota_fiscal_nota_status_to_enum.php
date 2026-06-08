<?php

use App\Enums\StatusControleNotaFiscalNota;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('controle_nota_fiscal_notas')) {
            return;
        }

        DB::table('controle_nota_fiscal_notas')
            ->whereNull('status')
            ->orWhereNotIn('status', array_column(StatusControleNotaFiscalNota::cases(), 'value'))
            ->update(['status' => StatusControleNotaFiscalNota::PENDENTE->value]);

        DB::statement(sprintf(
            "ALTER TABLE controle_nota_fiscal_notas MODIFY status ENUM('%s') NOT NULL DEFAULT '%s'",
            implode("','", array_column(StatusControleNotaFiscalNota::cases(), 'value')),
            StatusControleNotaFiscalNota::PENDENTE->value,
        ));
    }

    public function down(): void
    {
        if (! Schema::hasTable('controle_nota_fiscal_notas')) {
            return;
        }

        DB::statement("ALTER TABLE controle_nota_fiscal_notas MODIFY status VARCHAR(255) NOT NULL DEFAULT 'pendente'");
    }
};
