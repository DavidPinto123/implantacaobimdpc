<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class LimparTasksOrfas extends Command
{
    protected $signature = 'tasks:limpar-orfas {--dry-run : Apenas lista, não deleta}';

    protected $description = 'Deleta Tasks geradas pelo planejamento cujo subitem foi removido';

    public function handle(): int
    {
        $query = Task::whereNotNull('cronograma_fase_item_id')
            ->whereDoesntHave('cronogramaFaseItem');

        $total = $query->count();

        if ($total === 0) {
            $this->info('Nenhuma task órfã encontrada.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("Modo dry-run: {$total} task(s) órfã(s) encontrada(s), nenhuma deletada.");
            $query->with('responsavel')->get()->each(function (Task $task) {
                $this->line("  ID {$task->id} | {$task->title} | item_id={$task->cronograma_fase_item_id}");
            });

            return self::SUCCESS;
        }

        $this->warn("Deletando {$total} task(s) órfã(s)...");
        $query->delete();
        $this->info("Concluído: {$total} task(s) removida(s).");

        return self::SUCCESS;
    }
}
