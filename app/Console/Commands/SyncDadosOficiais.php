<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncDadosOficiais extends Command
{
    protected $signature = 'importar:dados';

    protected $description = 'Importa dados oficiais de referência usados pelo sistema.';

    public function handle(): int
    {
        $this->info('Importando bancos do Banco Central...');
        $bancosStatus = $this->call('importar:bancos');

        if ($bancosStatus !== self::SUCCESS) {
            return $bancosStatus;
        }

        $this->info('Importando localização brasileira do IBGE...');
        $localizacaoStatus = $this->call('importar:localizacao');

        if ($localizacaoStatus !== self::SUCCESS) {
            return $localizacaoStatus;
        }

        return self::SUCCESS;
    }
}
