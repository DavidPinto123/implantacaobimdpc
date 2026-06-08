<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithR2Migration;
use App\Models\AutorizacaoServico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateAutorizacaoServicoMediaToR2 extends Command
{
    use InteractsWithR2Migration;

    protected $signature = 'autorizacao-servico:migrate-media-to-r2
                            {--id= : Migra apenas uma autorizacao de servico}
                            {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra anexos de autorizacao de servico do disco public para o disco r2';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $id = $this->option('id');
        $stats = $this->initializeMigrationStats();

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRACAO DE ANEXOS DE AUTORIZACAO DE SERVICO');

        $query = AutorizacaoServico::query()
            ->whereNotNull('anexo_autorizacao_servico')
            ->orderBy('id');

        if (filled($id)) {
            $query->whereKey($id);
        }

        $query->chunkById(50, function ($records) use ($dryRun, &$stats) {
            foreach ($records as $record) {
                $stats['records']++;

                try {
                    $oldPath = $this->extractPath($record->anexo_autorizacao_servico);

                    if (! $oldPath) {
                        $stats['skipped']++;

                        continue;
                    }

                    if (Str::startsWith($oldPath, 'autorizacao-servicos/') && Storage::disk('r2')->exists($oldPath)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $targetPath = 'autorizacao-servicos/'.$record->id.'/anexos/'.$this->sanitizeFileName($oldPath);
                    $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                    if ($result['status'] === 'missing_source') {
                        $this->warn("Arquivo nao encontrado no public | AS {$record->numero_as} | path {$oldPath}");
                        $stats['warnings']++;

                        continue;
                    }

                    if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                        $this->error("Falha ao migrar anexo | AS {$record->numero_as} | path {$oldPath}");
                        $stats['errors']++;

                        continue;
                    }

                    if ($result['copied']) {
                        $stats['files_copied']++;
                        $this->line("Copiado: {$result['source']} -> {$result['target']}");
                    } elseif ($result['status'] === 'already_exists') {
                        $stats['skipped']++;
                    }

                    if (! $dryRun) {
                        $record->anexo_autorizacao_servico = $targetPath;
                        $record->saveQuietly();
                        $this->info("Registro atualizado: AS {$record->numero_as}");
                    }

                    $stats['fields_updated']++;
                } catch (\Throwable $e) {
                    $this->error("Erro | AS {$record->numero_as} | {$e->getMessage()}");
                    $stats['errors']++;
                }
            }
        });

        $this->printMigrationSummary($stats);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
