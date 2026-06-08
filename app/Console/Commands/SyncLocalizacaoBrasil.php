<?php

namespace App\Console\Commands;

use App\Services\DadosAbertos\LocalizacaoBrasilSyncService;
use Illuminate\Console\Command;

class SyncLocalizacaoBrasil extends Command
{
    protected $signature = 'importar:localizacao';

    protected $description = 'Importa países, estados e cidades brasileiras a partir do dataset local do IBGE.';

    public function handle(LocalizacaoBrasilSyncService $syncService): int
    {
        $result = $syncService->sync();

        $this->info('Países sincronizados: '.$result['paises']);
        $this->info('Estados sincronizados: '.$result['estados']);
        $this->info('Cidades sincronizadas: '.$result['cidades']);

        return self::SUCCESS;
    }
}
