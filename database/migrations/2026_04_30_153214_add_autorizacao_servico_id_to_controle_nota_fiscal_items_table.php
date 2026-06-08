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
        $this->normalizarDuplicidadesPorEscopoComplemento();

        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->foreignId('autorizacao_servico_id')
                ->nullable()
                ->after('as_escopo_id')
                ->constrained('autorizacao_servicos')
                ->nullOnDelete();
        });

        DB::table('controle_nota_fiscal_items as items')
            ->join('controle_nota_fiscals as controles', 'controles.id', '=', 'items.controle_nota_fiscal_id')
            ->join('autorizacao_servicos as autorizacoes', function ($join): void {
                $join->on('autorizacoes.obra_id', '=', 'controles.obra_id')
                    ->on('autorizacoes.as_escopo_id', '=', 'items.as_escopo_id')
                    ->whereRaw('COALESCE(autorizacoes.numero_complemento, "") = COALESCE(items.numero_complemento, "")');
            })
            ->whereNull('items.autorizacao_servico_id')
            ->whereNotNull('items.as_escopo_id')
            ->update([
                'items.autorizacao_servico_id' => DB::raw('autorizacoes.id'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controle_nota_fiscal_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('autorizacao_servico_id');
        });
    }

    private function normalizarDuplicidadesPorEscopoComplemento(): void
    {
        $gruposDuplicados = DB::table('autorizacao_servicos')
            ->select('obra_id', 'as_escopo_id', DB::raw('COALESCE(numero_complemento, "") as complemento'))
            ->whereNotNull('as_escopo_id')
            ->groupBy('obra_id', 'as_escopo_id', DB::raw('COALESCE(numero_complemento, "")'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($gruposDuplicados as $grupo) {
            $autorizacoes = DB::table('autorizacao_servicos')
                ->where('obra_id', $grupo->obra_id)
                ->where('as_escopo_id', $grupo->as_escopo_id)
                ->where(function ($query) use ($grupo): void {
                    if ($grupo->complemento === '') {
                        $query
                            ->whereNull('numero_complemento')
                            ->orWhere('numero_complemento', '');

                        return;
                    }

                    $query->where('numero_complemento', $grupo->complemento);
                })
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
            ->whereNotNull('numero_complemento')
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
