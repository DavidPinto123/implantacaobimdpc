<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithR2Migration;
use App\Models\Projeto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateProjetoPontoMediaToR2 extends Command
{
    use InteractsWithR2Migration;

    protected $signature = 'projeto-ponto:migrate-media-to-r2
                            {--id= : Migra apenas um ponto/projeto específico}
                            {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra anexos legados de cadastro de ponto para o disco r2 em arquivos-pt/{id}/midia';

    protected array $arrayFields = [
        'anexo_evtl',
        'anexo_matricula_iptu',
        'anexo_habite_se',
        'anexo_avcb',
        'anexo_projeto',
        'anexo_convencao_condominio',
        'anexo_regime_interno',
        'anexo_normas_gerais',
        'anexo_outros_documentos',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $id = $this->option('id');
        $stats = $this->initializeMigrationStats();

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRACAO DE MIDIAS DE CADASTRO DE PONTO');

        $query = Projeto::query()->orderBy('id');

        if (filled($id)) {
            $query->whereKey($id);
        }

        $query->chunkById(50, function ($records) use ($dryRun, &$stats) {
            foreach ($records as $record) {
                $stats['records']++;
                $recordChanged = false;

                foreach ($this->arrayFields as $field) {
                    $originalValue = $record->{$field};
                    $files = $this->normalizeFiles($originalValue);

                    if ($files === []) {
                        continue;
                    }

                    $newFiles = [];
                    $fieldChanged = false;
                    $targetPrefix = 'arquivos-pt/'.$record->id.'/midia/';

                    foreach ($files as $file) {
                        try {
                            $oldPath = $this->extractPath($file);

                            if (! $oldPath) {
                                $newFiles[] = $file;
                                $stats['skipped']++;

                                continue;
                            }

                            if (str_starts_with($oldPath, $targetPrefix) && Storage::disk('r2')->exists($oldPath)) {
                                $newFiles[] = $oldPath;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPath = $targetPrefix.$this->sanitizeFileName($oldPath, $field);
                            $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                            if ($result['status'] === 'missing_source') {
                                $this->warn("Arquivo nao encontrado | Projeto {$record->id} | campo {$field} | path {$oldPath}");
                                $newFiles[] = $file;
                                $stats['warnings']++;

                                continue;
                            }

                            if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                                $this->error("Falha ao migrar arquivo | Projeto {$record->id} | campo {$field} | path {$oldPath}");
                                $newFiles[] = $file;
                                $stats['errors']++;

                                continue;
                            }

                            if ($result['copied']) {
                                $stats['files_copied']++;
                                $this->line("Copiado: {$result['source']} -> {$result['target']}");
                            } elseif ($result['status'] === 'already_exists') {
                                $stats['skipped']++;
                            }

                            $newFiles[] = $targetPath;
                            $fieldChanged = true;
                        } catch (\Throwable $e) {
                            $this->error("Erro | Projeto {$record->id} | campo {$field} | {$e->getMessage()}");
                            $newFiles[] = $file;
                            $stats['errors']++;
                        }
                    }

                    if ($fieldChanged) {
                        $record->{$field} = $this->restoreOriginalFormat($originalValue, $newFiles);
                        $recordChanged = true;
                        $stats['fields_updated']++;
                    }
                }

                if ($recordChanged && ! $dryRun) {
                    $record->saveQuietly();
                    $this->info("Registro atualizado: Projeto {$record->id}");
                }
            }
        });

        $this->printMigrationSummary($stats);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
