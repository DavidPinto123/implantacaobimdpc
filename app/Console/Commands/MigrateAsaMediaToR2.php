<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithR2Migration;
use App\Models\Asa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateAsaMediaToR2 extends Command
{
    use InteractsWithR2Migration;

    protected $signature = 'asa:migrate-media-to-r2
                            {--id= : Migra apenas uma ASA específica}
                            {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra anexos e evidências de ASAs do disco public para o disco r2';

    protected array $arrayFields = [
        'evidencias' => 'evidencias',
        'foto_antes' => 'foto-antes',
        'foto_depois' => 'foto-depois',
        'projeto_orcado' => 'projeto-orcado',
        'projeto_revisado' => 'projeto-revisado',
        'escopo_contratado' => 'escopo-contratado',
        'escopo_real' => 'escopo-real',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $id = $this->option('id');
        $stats = $this->initializeMigrationStats();

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRACAO DE MIDIAS DE ASA');

        $query = Asa::query()->orderBy('id');

        if (filled($id)) {
            $query->whereKey($id);
        }

        $query->chunkById(50, function ($records) use ($dryRun, &$stats) {
            foreach ($records as $record) {
                $stats['records']++;
                $recordChanged = false;

                foreach ($this->arrayFields as $field => $directory) {
                    $originalValue = $record->{$field};
                    $files = $this->normalizeFiles($originalValue);

                    if ($files === []) {
                        continue;
                    }

                    $newFiles = [];
                    $fieldChanged = false;

                    foreach ($files as $file) {
                        try {
                            $oldPath = $this->extractPath($file);

                            if (! $oldPath) {
                                $newFiles[] = $file;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPrefix = 'asa/'.$record->id.'/'.$directory.'/';

                            if (Str::startsWith($oldPath, $targetPrefix) && Storage::disk('r2')->exists($oldPath)) {
                                $newFiles[] = $oldPath;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPath = $targetPrefix.$this->sanitizeFileName($oldPath);
                            $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                            if ($result['status'] === 'missing_everywhere') {
                                $this->warn("Arquivo nao encontrado no public nem no R2 | ASA {$record->numero_asa} | campo {$field} | path {$oldPath}");
                                $newFiles[] = $file;
                                $stats['warnings']++;

                                continue;
                            }

                            if ($result['status'] === 'stream_error' || $result['status'] === 'validation_error') {
                                $this->error("Falha ao migrar arquivo | ASA {$record->numero_asa} | campo {$field} | path {$oldPath}");
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
                            $this->error("Erro | ASA {$record->numero_asa} | campo {$field} | {$e->getMessage()}");
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

                try {
                    $planilha = $record->planilha_apresentada;

                    if (filled($planilha)) {
                        $oldPath = $this->extractPath($planilha);

                        $targetPrefix = 'asa/'.$record->id.'/planilhas/';

                        if ($oldPath && (! Str::startsWith($oldPath, $targetPrefix) || ! Storage::disk('r2')->exists($oldPath))) {
                            $targetPath = $targetPrefix.$this->sanitizeFileName($oldPath, 'planilha');
                            $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                            if ($result['status'] === 'missing_everywhere') {
                                $this->warn("Arquivo nao encontrado no public nem no R2 | ASA {$record->numero_asa} | campo planilha_apresentada | path {$oldPath}");
                                $stats['warnings']++;
                            } elseif (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                                $this->error("Falha ao migrar planilha | ASA {$record->numero_asa} | path {$oldPath}");
                                $stats['errors']++;
                            } else {
                                if ($result['copied']) {
                                    $stats['files_copied']++;
                                    $this->line("Copiado: {$result['source']} -> {$result['target']}");
                                } elseif ($result['status'] === 'already_exists') {
                                    $stats['skipped']++;
                                }

                                $record->planilha_apresentada = $targetPath;
                                $recordChanged = true;
                                $stats['fields_updated']++;
                            }
                        } else {
                            $stats['skipped']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("Erro | ASA {$record->numero_asa} | campo planilha_apresentada | {$e->getMessage()}");
                    $stats['errors']++;
                }

                if ($recordChanged && ! $dryRun) {
                    $record->saveQuietly();
                    $this->info("Registro atualizado: ASA {$record->numero_asa}");
                }
            }
        });

        $this->printMigrationSummary($stats);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
