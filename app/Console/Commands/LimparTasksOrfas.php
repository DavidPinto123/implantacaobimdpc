<?php

namespace App\Console\Commands;

use App\Models\CronogramaFaseItem;
use App\Models\Task;
use Illuminate\Console\Command;

class LimparTasksOrfas extends Command
{
    protected $signature = 'tasks:limpar-orfas
                            {--dry-run : Apenas lista, não deleta}
                            {--com-legado : Inclui tasks do sync antigo (sem FK) cujo item foi removido}';

    protected $description = 'Deleta Tasks geradas pelo planejamento cujo subitem foi removido';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $total  = 0;

        // ── Caso 1: tasks COM cronograma_fase_item_id apontando para item inexistente ──
        $queryFk = Task::whereNotNull('cronograma_fase_item_id')
            ->whereDoesntHave('cronogramaFaseItem');

        $countFk = $queryFk->count();

        if ($countFk > 0) {
            $this->line("Órfãs com FK inválido: {$countFk}");
            if ($dryRun) {
                $queryFk->get()->each(fn (Task $t) =>
                    $this->line("  [FK] ID {$t->id} | {$t->title} | item_id={$t->cronograma_fase_item_id}")
                );
            } else {
                $queryFk->delete();
            }
            $total += $countFk;
        }

        // ── Caso 2: tasks SEM FK, geradas pelo sync antigo, cujo item foi removido ──
        if ($this->option('com-legado')) {
            // Identifica tasks sem FK que parecem vir do sync:
            // description = "Projeto: X | Fase: Y | Atividade: Z"
            // e não existe nenhum CronogramaFaseItem com o mesmo título no mesmo projeto.
            $semFk = Task::whereNull('cronograma_fase_item_id')
                ->whereNotNull('projeto_id')
                ->where('description', 'like', 'Projeto: % | Fase: %')
                ->get();

            // (variável reservada para uso futuro se necessário)

            $legados = $semFk->filter(function (Task $task) {
                // Seguro: inclui se não existe NENHUM item de cronograma no projeto com o mesmo título
                $itemExiste = CronogramaFaseItem::whereHas('fase', fn ($q) => $q->where('projeto_id', $task->projeto_id))
                    ->where('titulo', $task->title)
                    ->exists();

                return ! $itemExiste;
            });

            $countLeg = $legados->count();

            if ($countLeg > 0) {
                $this->line("Órfãs legado (sem FK, item removido): {$countLeg}");
                if ($dryRun) {
                    $legados->each(fn (Task $t) =>
                        $this->line("  [LEG] ID {$t->id} | {$t->title} | projeto_id={$t->projeto_id}")
                    );
                } else {
                    Task::whereIn('id', $legados->pluck('id'))->delete();
                }
                $total += $countLeg;
            }
        }

        if ($total === 0) {
            $this->info('Nenhuma task órfã encontrada.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Dry-run: {$total} task(s) seriam deletadas. Rode sem --dry-run para executar.");
        } else {
            $this->info("Concluído: {$total} task(s) removida(s).");
        }

        return self::SUCCESS;
    }
}
