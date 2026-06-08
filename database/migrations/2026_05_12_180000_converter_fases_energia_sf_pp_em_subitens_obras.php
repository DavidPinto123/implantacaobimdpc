<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reunião 11/05: Energia Smart Fit e Energia Proprietário deixam de ser
 * fases independentes e passam a ser subitens da fase OBRAS.
 *
 * Para cada CronogramaFase com fase ∈ {energia_sf, energia_pp}, esta migration:
 *  - Localiza a CronogramaFase OBRAS do mesmo projeto;
 *  - Cria um CronogramaFaseItem na OBRAS preservando datas, observações e ordem;
 *  - Remove dependências de outras fases/itens que apontavam para a fase Energia;
 *  - Deleta a CronogramaFase Energia.
 *
 * Down não é restaurável (datas/itens já consolidados). Mantemos exception explícita.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // Limpa dependências de outras fases que apontavam para energia_sf/pp.
            if (Schema::hasTable('cronograma_fase_dependencias')) {
                DB::table('cronograma_fase_dependencias')
                    ->whereIn('depende_de_fase', ['energia_sf', 'energia_pp'])
                    ->delete();
            }

            $fasesEnergia = DB::table('cronograma_fases')
                ->whereIn('fase', ['energia_sf', 'energia_pp'])
                ->get();

            foreach ($fasesEnergia as $fase) {
                $obrasFase = DB::table('cronograma_fases')
                    ->where('projeto_id', $fase->projeto_id)
                    ->where('fase', 'obras')
                    ->first();

                if (! $obrasFase) {
                    // Sem fase OBRAS no projeto, apenas remove a fase Energia órfã.
                    $this->deletarFaseEnergia($fase->id);

                    continue;
                }

                $titulo = $fase->fase === 'energia_sf' ? 'Energia Smart Fit' : 'Energia Proprietário';
                $ordem = $fase->fase === 'energia_sf' ? 0 : 1;

                $jaExiste = DB::table('cronograma_fase_itens')
                    ->where('cronograma_fase_id', $obrasFase->id)
                    ->where('titulo', $titulo)
                    ->exists();

                if (! $jaExiste) {
                    DB::table('cronograma_fase_itens')->insert([
                        'cronograma_fase_id' => $obrasFase->id,
                        'titulo' => $titulo,
                        'recebido' => in_array($fase->status, ['concluido', 'finalizado', 'realizado'], true),
                        'data_prevista_inicio' => $fase->data_prevista_inicio,
                        'data_prevista_fim' => $fase->data_prevista_fim,
                        'data_realizada_inicio' => $fase->data_realizada_inicio,
                        'data_realizada_fim' => $fase->data_realizada_fim,
                        'observacoes' => $fase->observacoes,
                        'ordem' => $ordem,
                        'origem' => 'template',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->deletarFaseEnergia($fase->id);
            }
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Esta migration é one-way (consolidou fases Energia SF/PP em subitens da Obra). '
            .'Para reverter, restaure o banco a partir de um backup pré-migration.'
        );
    }

    private function deletarFaseEnergia(int $faseId): void
    {
        // Dependências cuja origem é a fase Energia (cascadeOnDelete cobriria, mas explicitamos).
        if (Schema::hasTable('cronograma_fase_dependencias')) {
            DB::table('cronograma_fase_dependencias')
                ->where('cronograma_fase_id', $faseId)
                ->delete();
        }

        // Itens dessa fase (deveriam ser zero, mas garantimos).
        DB::table('cronograma_fase_itens')
            ->where('cronograma_fase_id', $faseId)
            ->delete();

        DB::table('cronograma_fases')->where('id', $faseId)->delete();
    }
};
