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

    protected $description = 'Deleta Tasks geradas pelo planejamento cujo subitem foi removido ou responsável foi trocado';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $total  = 0;

        // ── Caso 1: tasks COM FK apontando para item inexistente ──────────────
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

        // ── Caso 2: tasks de responsável removido do item ─────────────────────
        $semResp = Task::whereNotNull('cronograma_fase_item_id')
            ->where('eh_revisor', false)
            ->whereHas('cronogramaFaseItem')
            ->with('cronogramaFaseItem.responsaveis')
            ->get()
            ->filter(fn (Task $t) => ! $t->cronogramaFaseItem->responsaveis->contains('id', $t->assigned_to));

        if ($semResp->isNotEmpty()) {
            $this->line("Tasks de responsável removido: {$semResp->count()}");
            if ($dryRun) {
                $semResp->each(fn (Task $t) =>
                    $this->line("  [RESP] ID {$t->id} | {$t->title} | assigned_to={$t->assigned_to}")
                );
            } else {
                Task::whereIn('id', $semResp->pluck('id'))->delete();
            }
            $total += $semResp->count();
        }

        // ── Caso 3: tasks de revisor trocado no item ──────────────────────────
        $semRev = Task::whereNotNull('cronograma_fase_item_id')
            ->where('eh_revisor', true)
            ->whereHas('cronogramaFaseItem')
            ->with('cronogramaFaseItem')
            ->get()
            ->filter(fn (Task $t) => $t->cronogramaFaseItem->revisor_id !== $t->assigned_to);

        if ($semRev->isNotEmpty()) {
            $this->line("Tasks de revisor trocado: {$semRev->count()}");
            if ($dryRun) {
                $semRev->each(fn (Task $t) =>
                    $this->line("  [REV] ID {$t->id} | {$t->title} | assigned_to={$t->assigned_to}")
                );
            } else {
                Task::whereIn('id', $semRev->pluck('id'))->delete();
            }
            $total += $semRev->count();
        }

        // ── Caso 4: tasks legado sem FK (sync antigo) cujo item foi removido ──
        if ($this->option('com-legado')) {
            $semFk = Task::whereNull('cronograma_fase_item_id')
                ->whereNotNull('projeto_id')
                ->where('description', 'like', 'Projeto: % | Fase: %')
                ->get();

            $legados = $semFk->filter(function (Task $task) {
                return ! CronogramaFaseItem::whereHas('fase', fn ($q) => $q->where('projeto_id', $task->projeto_id))
                    ->where('titulo', $task->title)
                    ->exists();
            });

            if ($legados->isNotEmpty()) {
                $this->line("Órfãs legado (sem FK, item removido): {$legados->count()}");
                if ($dryRun) {
                    $legados->each(fn (Task $t) =>
                        $this->line("  [LEG] ID {$t->id} | {$t->title} | projeto_id={$t->projeto_id}")
                    );
                } else {
                    Task::whereIn('id', $legados->pluck('id'))->delete();
                }
                $total += $legados->count();
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
