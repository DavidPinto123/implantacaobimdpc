<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'aut_serv_obra_escopo_compl_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->normalizarDuplicidadesPorEscopoComplemento();

        Schema::table('autorizacao_servicos', function (Blueprint $table) {
            $table->unique(['obra_id', 'as_escopo_id', 'numero_complemento'], self::INDEX_NAME);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table) {
            $table->dropUnique(self::INDEX_NAME);
        });
    }

    private function normalizarDuplicidadesPorEscopoComplemento(): void
    {
        $gruposDuplicados = DB::table('autorizacao_servicos')
            ->select('obra_id', 'as_escopo_id', 'numero_complemento')
            ->whereNotNull('as_escopo_id')
            ->groupBy('obra_id', 'as_escopo_id', 'numero_complemento')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($gruposDuplicados as $grupo) {
            $autorizacoes = DB::table('autorizacao_servicos')
                ->where('obra_id', $grupo->obra_id)
                ->where('as_escopo_id', $grupo->as_escopo_id)
                ->where('numero_complemento', $grupo->numero_complemento)
                ->orderBy('id')
                ->get();

            $autorizacoes->shift();

            foreach ($autorizacoes as $autorizacao) {
                DB::table('autorizacao_servicos')
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
        $complementosExistentes = DB::table('autorizacao_servicos')
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
