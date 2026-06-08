<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('controle_nota_fiscal_items')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `controle_nota_fiscal_items` DROP INDEX `controle_nota_fiscal_items_numero_as_index`');
        } catch (\Throwable $exception) {
            // índice pode não existir em alguns ambientes
        }

        DB::statement('ALTER TABLE `controle_nota_fiscal_items` MODIFY `numero_as` LONGTEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('controle_nota_fiscal_items')) {
            return;
        }

        DB::statement('ALTER TABLE `controle_nota_fiscal_items` MODIFY `numero_as` VARCHAR(20) NULL');

        try {
            DB::statement('ALTER TABLE `controle_nota_fiscal_items` ADD INDEX `controle_nota_fiscal_items_numero_as_index` (`numero_as`)');
        } catch (\Throwable $exception) {
            // índice pode já existir
        }
    }
};
