<?php

namespace App\Console\Commands;

use App\Models\Obras;
use App\Observers\ObrasObserver;
use App\Services\ConstructinService;
use Illuminate\Console\Command;

class RecalcularCamposObras extends Command
{
    protected $signature = 'obras:recalcular-campos';

    protected $description = 'Recalcula campos derivados e sincroniza percentuais do ConstructIN';

    public function handle(): int
    {
        $total = Obras::count();
        $this->info("Recalculando campos de {$total} obras...");

        $atualizadas = 0;
        $service = new ConstructinService;

        Obras::query()->chunkById(100, function ($obras) use (&$atualizadas, $service) {
            foreach ($obras as $obra) {
                if ($obra->constructin_project_id) {
                    try {
                        $progress = $service->getProgressPercentages((int) $obra->constructin_project_id);

                        if ($progress['percentual_obra'] !== null) {
                            $obra->percentual_obra = $progress['percentual_obra'];
                        }

                        if ($progress['percentual_obra_executado'] !== null) {
                            $obra->percentual_obra_executado = $progress['percentual_obra_executado'];
                        }
                    } catch (\Throwable) {
                        // Continua recalculando as demais obras mesmo se o ConstructIN falhar pontualmente.
                    }
                }

                ObrasObserver::calcularCamposDerivados($obra);
                $obra->saveQuietly();
                $atualizadas++;
            }
        });

        $this->info("{$atualizadas} obras atualizadas.");

        return self::SUCCESS;
    }
}
