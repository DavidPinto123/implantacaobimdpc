<?php

namespace App\Console\Commands;

use App\Models\Obras;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class LimparCacheConstructin extends Command
{
    protected $signature = 'constructin:limpar-cache
                            {--fotos : Limpa apenas o cache de fotos}
                            {--rdos  : Limpa apenas o cache de RDOs}
                            {--all   : Limpa fotos, RDOs e lista de projetos (padrão)}';

    protected $description = 'Limpa o cache de dados do Constructin para forçar recarga na próxima requisição';

    public function handle(): int
    {
        $fotos = $this->option('fotos') || (! $this->option('rdos') && ! $this->option('fotos'));
        $rdos  = $this->option('rdos')  || (! $this->option('rdos') && ! $this->option('fotos'));
        $all   = $this->option('all')   || (! $this->option('rdos') && ! $this->option('fotos'));

        $ids = Obras::query()
            ->whereNotNull('constructin_project_id')
            ->pluck('constructin_project_id');

        if ($ids->isEmpty()) {
            $this->warn('Nenhuma obra com constructin_project_id encontrada.');

            return self::SUCCESS;
        }

        $this->info("Obras com Constructin: {$ids->count()}");

        $removidos = 0;

        foreach ($ids as $id) {
            if ($fotos && Cache::forget("cin_images_{$id}")) {
                $removidos++;
            }

            if ($rdos) {
                Cache::forget("cin_rdos_list_{$id}");
                Cache::forget("cin_visi_{$id}");
            }
        }

        if ($all) {
            Cache::forget('cin_projects_list');
        }

        if ($fotos) {
            $this->info("Cache de fotos removido para {$removidos} projeto(s).");
        }

        if ($rdos) {
            $this->info('Cache de RDOs e curva S removido.');
        }

        if ($all) {
            $this->info('Cache da lista de projetos removido.');
        }

        return self::SUCCESS;
    }
}
