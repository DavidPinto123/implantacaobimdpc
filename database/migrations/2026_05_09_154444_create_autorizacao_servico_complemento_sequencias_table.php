<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autorizacao_servico_complemento_sequencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnDelete();
            $table->foreignId('as_escopo_id')
                ->constrained('as_escopos')
                ->restrictOnDelete();
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->timestamps();

            $table->unique(['obra_id', 'as_escopo_id'], 'as_complemento_seq_obra_escopo_unique');
        });

        $agora = now();

        $linhasControle = DB::table('controle_nota_fiscal_items as items')
            ->join('controle_nota_fiscals as controles', 'controles.id', '=', 'items.controle_nota_fiscal_id')
            ->whereNotNull('controles.obra_id')
            ->whereNotNull('items.as_escopo_id')
            ->select('controles.obra_id', 'items.as_escopo_id', 'items.numero_complemento')
            ->get();

        $autorizacoes = DB::table('autorizacao_servicos')
            ->whereNotNull('obra_id')
            ->whereNotNull('as_escopo_id')
            ->select('obra_id', 'as_escopo_id', 'numero_complemento')
            ->get();

        collect([...$linhasControle, ...$autorizacoes])
            ->groupBy(fn (object $registro): string => $registro->obra_id.'|'.$registro->as_escopo_id)
            ->each(function ($registros, string $chave) use ($agora): void {
                [$obraId, $asEscopoId] = array_map('intval', explode('|', $chave));
                $ultimoNumero = $registros
                    ->map(function (object $registro): ?int {
                        $complemento = strtoupper(trim((string) $registro->numero_complemento));

                        return preg_match('/^C(\d+)$/', $complemento, $matches) ? (int) $matches[1] : null;
                    })
                    ->filter()
                    ->max() ?? 0;

                DB::table('autorizacao_servico_complemento_sequencias')->updateOrInsert(
                    [
                        'obra_id' => $obraId,
                        'as_escopo_id' => $asEscopoId,
                    ],
                    [
                        'ultimo_numero' => $ultimoNumero,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ],
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('autorizacao_servico_complemento_sequencias');
    }
};
