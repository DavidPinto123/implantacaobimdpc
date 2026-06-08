<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->dropOldForeignKeys();

        if (Schema::hasTable('asa_items') && ! Schema::hasTable('autorizacao_servico_adicional_items')) {
            Schema::rename('asa_items', 'autorizacao_servico_adicional_items');
        }

        if (Schema::hasTable('asas') && ! Schema::hasTable('autorizacao_servico_adicionais')) {
            Schema::rename('asas', 'autorizacao_servico_adicionais');
        }

        $this->renameColumnIfExists(
            table: 'autorizacao_servico_adicional_items',
            from: 'asa_id',
            to: 'autorizacao_servico_adicional_id',
        );

        $this->renameColumnIfExists(
            table: 'controle_nota_fiscals',
            from: 'asa_id',
            to: 'autorizacao_servico_adicional_id',
        );

        $this->renameColumnIfExists(
            table: 'controle_nota_fiscal_notas',
            from: 'asa_id',
            to: 'autorizacao_servico_adicional_id',
        );

        $this->addNewForeignKeys();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropNewForeignKeys();

        $this->renameColumnIfExists(
            table: 'controle_nota_fiscal_notas',
            from: 'autorizacao_servico_adicional_id',
            to: 'asa_id',
        );

        $this->renameColumnIfExists(
            table: 'controle_nota_fiscals',
            from: 'autorizacao_servico_adicional_id',
            to: 'asa_id',
        );

        $this->renameColumnIfExists(
            table: 'autorizacao_servico_adicional_items',
            from: 'autorizacao_servico_adicional_id',
            to: 'asa_id',
        );

        if (Schema::hasTable('autorizacao_servico_adicionais') && ! Schema::hasTable('asas')) {
            Schema::rename('autorizacao_servico_adicionais', 'asas');
        }

        if (Schema::hasTable('autorizacao_servico_adicional_items') && ! Schema::hasTable('asa_items')) {
            Schema::rename('autorizacao_servico_adicional_items', 'asa_items');
        }

        $this->addOldForeignKeys();
    }

    private function dropOldForeignKeys(): void
    {
        $this->dropForeignForColumn('controle_nota_fiscals', 'asa_id');
        $this->dropForeignForColumn('controle_nota_fiscal_notas', 'asa_id');
        $this->dropForeignForColumn('asa_items', 'asa_id');
    }

    private function addNewForeignKeys(): void
    {
        $this->addForeignIfColumnExists(
            table: 'controle_nota_fiscals',
            column: 'autorizacao_servico_adicional_id',
            foreignTable: 'autorizacao_servico_adicionais',
            onDelete: 'set null',
            name: 'cnf_asa_adicional_fk',
        );

        $this->addForeignIfColumnExists(
            table: 'controle_nota_fiscal_notas',
            column: 'autorizacao_servico_adicional_id',
            foreignTable: 'autorizacao_servico_adicionais',
            onDelete: 'set null',
            name: 'cnfn_asa_adicional_fk',
        );

        $this->addForeignIfColumnExists(
            table: 'autorizacao_servico_adicional_items',
            column: 'autorizacao_servico_adicional_id',
            foreignTable: 'autorizacao_servico_adicionais',
            onDelete: 'cascade',
            name: 'asai_asa_adicional_fk',
        );
    }

    private function dropNewForeignKeys(): void
    {
        $this->dropForeignForColumn('controle_nota_fiscals', 'autorizacao_servico_adicional_id');
        $this->dropForeignForColumn('controle_nota_fiscal_notas', 'autorizacao_servico_adicional_id');
        $this->dropForeignForColumn('autorizacao_servico_adicional_items', 'autorizacao_servico_adicional_id');
    }

    private function addOldForeignKeys(): void
    {
        $this->addForeignIfColumnExists(
            table: 'controle_nota_fiscals',
            column: 'asa_id',
            foreignTable: 'asas',
            onDelete: 'set null',
        );

        $this->addForeignIfColumnExists(
            table: 'controle_nota_fiscal_notas',
            column: 'asa_id',
            foreignTable: 'asas',
            onDelete: 'set null',
        );

        $this->addForeignIfColumnExists(
            table: 'asa_items',
            column: 'asa_id',
            foreignTable: 'asas',
            onDelete: 'cascade',
        );
    }

    private function renameColumnIfExists(string $table, string $from, string $to): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($from, $to): void {
            $table->renameColumn($from, $to);
        });
    }

    private function dropForeignForColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $foreignKeys = Schema::getConnection()
            ->getSchemaBuilder()
            ->getForeignKeys($table);

        foreach ($foreignKeys as $foreignKey) {
            if (($foreignKey['columns'] ?? []) !== [$column]) {
                continue;
            }

            $foreignKeyName = $foreignKey['name'] ?? null;

            if ($foreignKeyName === null) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($foreignKeyName): void {
                $table->dropForeign($foreignKeyName);
            });
        }
    }

    private function addForeignIfColumnExists(string $table, string $column, string $foreignTable, string $onDelete, ?string $name = null): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || ! Schema::hasTable($foreignTable)) {
            return;
        }

        if ($this->hasForeignForColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column, $foreignTable, $onDelete, $name): void {
            $definition = $table->foreign($column, $name)
                ->references('id')
                ->on($foreignTable)
                ->cascadeOnUpdate();

            if ($onDelete === 'cascade') {
                $definition->cascadeOnDelete();

                return;
            }

            $definition->nullOnDelete();
        });
    }

    private function hasForeignForColumn(string $table, string $column): bool
    {
        $foreignKeys = Schema::getConnection()
            ->getSchemaBuilder()
            ->getForeignKeys($table);

        foreach ($foreignKeys as $foreignKey) {
            if (($foreignKey['columns'] ?? []) === [$column]) {
                return true;
            }
        }

        return false;
    }
};
