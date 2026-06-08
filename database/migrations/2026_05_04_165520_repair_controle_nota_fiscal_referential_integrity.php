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
        if (! $this->hasControleNotaFiscalTables()) {
            return;
        }

        $this->deleteNotasWithoutValidOwner();
        $this->deleteOrphanedItems();
        $this->deleteOrphanedAuxiliares();

        $this->ensureCascadeForeignKey(
            tableName: 'controle_nota_fiscal_items',
            column: 'controle_nota_fiscal_id',
            foreignTable: 'controle_nota_fiscals',
            constraintName: 'controle_nota_fiscal_items_controle_nota_fiscal_id_foreign',
        );

        $this->ensureCascadeForeignKey(
            tableName: 'controle_nota_fiscal_auxiliares',
            column: 'controle_nota_fiscal_id',
            foreignTable: 'controle_nota_fiscals',
            constraintName: 'cnf_aux_controle_fk',
        );

        $this->ensureCascadeForeignKey(
            tableName: 'controle_nota_fiscal_notas',
            column: 'controle_nota_fiscal_item_id',
            foreignTable: 'controle_nota_fiscal_items',
            constraintName: 'controle_nota_fiscal_notas_controle_nota_fiscal_item_id_foreign',
        );

        $this->ensureCascadeForeignKey(
            tableName: 'controle_nota_fiscal_notas',
            column: 'controle_nota_fiscal_auxiliar_id',
            foreignTable: 'controle_nota_fiscal_auxiliares',
            constraintName: 'cnf_notas_auxiliar_fk',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function hasControleNotaFiscalTables(): bool
    {
        return Schema::hasTable('controle_nota_fiscals')
            && Schema::hasTable('controle_nota_fiscal_items')
            && Schema::hasTable('controle_nota_fiscal_auxiliares')
            && Schema::hasTable('controle_nota_fiscal_notas');
    }

    private function deleteNotasWithoutValidOwner(): void
    {
        DB::table('controle_nota_fiscal_notas')
            ->where(function ($query): void {
                $query
                    ->whereNotNull('controle_nota_fiscal_item_id')
                    ->whereNotExists(function ($subQuery): void {
                        $subQuery
                            ->selectRaw('1')
                            ->from('controle_nota_fiscal_items')
                            ->whereColumn('controle_nota_fiscal_items.id', 'controle_nota_fiscal_notas.controle_nota_fiscal_item_id');
                    });
            })
            ->orWhere(function ($query): void {
                $query
                    ->whereNotNull('controle_nota_fiscal_auxiliar_id')
                    ->whereNotExists(function ($subQuery): void {
                        $subQuery
                            ->selectRaw('1')
                            ->from('controle_nota_fiscal_auxiliares')
                            ->whereColumn('controle_nota_fiscal_auxiliares.id', 'controle_nota_fiscal_notas.controle_nota_fiscal_auxiliar_id');
                    });
            })
            ->orWhere(function ($query): void {
                $query
                    ->whereNull('controle_nota_fiscal_item_id')
                    ->whereNull('controle_nota_fiscal_auxiliar_id');
            })
            ->orWhereIn(
                'controle_nota_fiscal_item_id',
                DB::table('controle_nota_fiscal_items')
                    ->select('controle_nota_fiscal_items.id')
                    ->whereNotExists(function ($subQuery): void {
                        $subQuery
                            ->selectRaw('1')
                            ->from('controle_nota_fiscals')
                            ->whereColumn('controle_nota_fiscals.id', 'controle_nota_fiscal_items.controle_nota_fiscal_id');
                    }),
            )
            ->orWhereIn(
                'controle_nota_fiscal_auxiliar_id',
                DB::table('controle_nota_fiscal_auxiliares')
                    ->select('controle_nota_fiscal_auxiliares.id')
                    ->whereNotExists(function ($subQuery): void {
                        $subQuery
                            ->selectRaw('1')
                            ->from('controle_nota_fiscals')
                            ->whereColumn('controle_nota_fiscals.id', 'controle_nota_fiscal_auxiliares.controle_nota_fiscal_id');
                    }),
            )
            ->delete();
    }

    private function deleteOrphanedItems(): void
    {
        DB::table('controle_nota_fiscal_items')
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('controle_nota_fiscals')
                    ->whereColumn('controle_nota_fiscals.id', 'controle_nota_fiscal_items.controle_nota_fiscal_id');
            })
            ->delete();
    }

    private function deleteOrphanedAuxiliares(): void
    {
        DB::table('controle_nota_fiscal_auxiliares')
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('controle_nota_fiscals')
                    ->whereColumn('controle_nota_fiscals.id', 'controle_nota_fiscal_auxiliares.controle_nota_fiscal_id');
            })
            ->delete();
    }

    private function ensureCascadeForeignKey(string $tableName, string $column, string $foreignTable, string $constraintName): void
    {
        if (! Schema::hasColumn($tableName, $column)) {
            return;
        }

        $this->dropForeignKeysForColumn($tableName, $column);

        Schema::table($tableName, function (Blueprint $table) use ($column, $foreignTable, $constraintName): void {
            $table->foreign($column, $constraintName)
                ->references('id')
                ->on($foreignTable)
                ->cascadeOnDelete();
        });
    }

    private function dropForeignKeysForColumn(string $tableName, string $column): void
    {
        $foreignKeys = Schema::getConnection()
            ->getSchemaBuilder()
            ->getForeignKeys($tableName);

        foreach ($foreignKeys as $foreignKey) {
            if (($foreignKey['columns'] ?? []) !== [$column]) {
                continue;
            }

            $foreignKeyName = $foreignKey['name'] ?? null;

            if ($foreignKeyName === null) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName): void {
                $table->dropForeign($foreignKeyName);
            });
        }
    }
};
