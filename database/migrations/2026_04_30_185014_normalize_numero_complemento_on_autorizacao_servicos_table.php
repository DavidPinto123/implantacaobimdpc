<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'autorizacao_servicos';

    private const OBRA_INDEX = 'aut_serv_obra_id_fk_index';

    private const UNIQUE_INDEX = 'aut_serv_obra_numero_hash_compl_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table(self::TABLE)
            ->whereNull('numero_complemento')
            ->update(['numero_complemento' => '']);

        $this->normalizarDuplicidadesSemComplemento();

        $this->createObraIndexIfMissing();
        $this->dropUniqueIndexIfExists();

        DB::statement('ALTER TABLE `autorizacao_servicos` MODIFY `numero_complemento` VARCHAR(10) NOT NULL DEFAULT ""');

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unique(['obra_id', 'numero_as_hash', 'numero_complemento'], self::UNIQUE_INDEX);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropUniqueIndexIfExists();

        DB::statement('ALTER TABLE `autorizacao_servicos` MODIFY `numero_complemento` VARCHAR(10) NULL DEFAULT NULL');

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->unique(['obra_id', 'numero_as_hash', 'numero_complemento'], self::UNIQUE_INDEX);
        });

        if (Schema::hasIndex(self::TABLE, self::OBRA_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->dropIndex(self::OBRA_INDEX);
            });
        }
    }

    private function createObraIndexIfMissing(): void
    {
        if (Schema::hasIndex(self::TABLE, ['obra_id'])) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->index('obra_id', self::OBRA_INDEX);
        });
    }

    private function dropUniqueIndexIfExists(): void
    {
        if (! Schema::hasIndex(self::TABLE, self::UNIQUE_INDEX, 'unique')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique(self::UNIQUE_INDEX);
        });
    }

    private function normalizarDuplicidadesSemComplemento(): void
    {
        $gruposDuplicados = DB::table(self::TABLE)
            ->select('obra_id', 'numero_as_hash')
            ->where('numero_complemento', '')
            ->whereNotNull('numero_as_hash')
            ->groupBy('obra_id', 'numero_as_hash')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($gruposDuplicados as $grupo) {
            $autorizacoes = DB::table(self::TABLE)
                ->where('obra_id', $grupo->obra_id)
                ->where('numero_as_hash', $grupo->numero_as_hash)
                ->where('numero_complemento', '')
                ->orderBy('id')
                ->get();

            $autorizacoes->shift();

            foreach ($autorizacoes as $autorizacao) {
                DB::table(self::TABLE)
                    ->where('id', $autorizacao->id)
                    ->update([
                        'numero_complemento' => $this->proximoComplemento(
                            (int) $autorizacao->obra_id,
                            (int) $autorizacao->as_escopo_id,
                        ),
                    ]);
            }
        }
    }

    private function proximoComplemento(int $obraId, int $asEscopoId): string
    {
        $complementosExistentes = DB::table(self::TABLE)
            ->where('obra_id', $obraId)
            ->where('as_escopo_id', $asEscopoId)
            ->where('numero_complemento', '!=', '')
            ->pluck('numero_complemento')
            ->all();

        $proximoNumero = 1;

        while (in_array('C'.$proximoNumero, $complementosExistentes, true)) {
            $proximoNumero++;
        }

        return 'C'.$proximoNumero;
    }
};
