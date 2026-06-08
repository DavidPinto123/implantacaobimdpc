<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateAllMediaToR2 extends Command
{
    protected $signature = 'media:migrate-to-r2
                            {--dry-run : Apenas simula sem gravar}
                            {--stop-on-error : Interrompe ao primeiro comando com erro}';

    protected $description = 'Executa a migracao de todas as midias legadas para o disco r2';

    protected array $commands = [
        'autorizacao-servico:migrate-media-to-r2',
        'matterport:migrate-media-to-r2',
        'relatorio-fotografico:migrate-media-to-r2',
        'elaboracao-aditivo:migrate-media-to-r2',
        'asa:migrate-media-to-r2',
        'projeto-ponto:migrate-media-to-r2',
        'vt:migrate-media-to-r2',
        'midia:migrate-to-r2',
        'app:migrar-fotos-perfil-r2',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stopOnError = (bool) $this->option('stop-on-error');

        $this->info($dryRun ? 'MODO DRY-RUN GLOBAL ATIVADO' : 'INICIANDO MIGRACAO GLOBAL DE MIDIAS');

        $results = [];
        $hasFailures = false;

        foreach ($this->commands as $command) {
            $this->newLine();
            $this->line(str_repeat('-', 80));
            $this->info("Executando: {$command}".($dryRun ? ' --dry-run' : ''));
            $this->line(str_repeat('-', 80));

            $exitCode = Artisan::call($command, array_filter([
                '--dry-run' => $dryRun ?: null,
            ]));

            $output = trim(Artisan::output());

            if ($output !== '') {
                $this->line($output);
            }

            $results[] = [
                'command' => $command,
                'exit_code' => $exitCode,
            ];

            if ($exitCode !== self::SUCCESS) {
                $hasFailures = true;
                $this->error("Comando finalizado com erro: {$command} (exit code {$exitCode})");

                if ($stopOnError) {
                    break;
                }
            } else {
                $this->info("Comando finalizado com sucesso: {$command}");
            }
        }

        $this->newLine();
        $this->line(str_repeat('=', 80));
        $this->info('RESUMO DA EXECUCAO GLOBAL');
        $this->line(str_repeat('=', 80));

        foreach ($results as $result) {
            $status = $result['exit_code'] === self::SUCCESS ? 'OK' : 'ERRO';
            $line = sprintf('[%s] %s', $status, $result['command']);

            if ($result['exit_code'] === self::SUCCESS) {
                $this->info($line);
            } else {
                $this->error($line);
            }
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }
}
