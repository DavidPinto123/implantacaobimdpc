<?php

namespace App\Console\Commands;

use App\Services\BancoCentral\BancoCentralBancosSyncService;
use Illuminate\Console\Command;

class SyncBancosBancoCentral extends Command
{
    protected $signature = 'importar:bancos';

    protected $description = 'Sincroniza a lista de bancos com os dados abertos do Banco Central.';

    public function handle(BancoCentralBancosSyncService $syncService): int
    {
        $result = $syncService->sync();

        $this->info('Bancos sincronizados: '.$result['sincronizados']);
        $this->info('Bancos inativados: '.$result['inativados']);

        return self::SUCCESS;
    }
}
