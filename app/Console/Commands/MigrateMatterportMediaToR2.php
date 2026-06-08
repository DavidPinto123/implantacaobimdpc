<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithR2Migration;
use App\Models\Matterport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateMatterportMediaToR2 extends Command
{
    use InteractsWithR2Migration;

    protected $signature = 'matterport:migrate-media-to-r2
                            {--id= : Migra apenas um matterport especifico}
                            {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra imagem e PDF de matterports do disco public para o disco r2';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $id = $this->option('id');
        $stats = $this->initializeMigrationStats();

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRACAO DE MIDIAS DE MATTERPORT');

        $query = Matterport::query()->orderBy('id');

        if (filled($id)) {
            $query->whereKey($id);
        }

        $query->chunkById(50, function ($records) use ($dryRun, &$stats) {
            foreach ($records as $record) {
                $stats['records']++;
                $recordChanged = false;

                foreach (['imagem' => 'imagem', 'documentoPDF' => 'documentos'] as $field => $directory) {
                    try {
                        $currentValue = $record->{$field};

                        if (blank($currentValue)) {
                            continue;
                        }

                        $oldPath = $this->extractPath($currentValue);

                        if (! $oldPath) {
                            $stats['skipped']++;

                            continue;
                        }

                        if (Str::startsWith($oldPath, 'matterports/') && Storage::disk('r2')->exists($oldPath)) {
                            $stats['skipped']++;

                            continue;
                        }

                        $targetPath = 'matterports/'.$record->id.'/'.$directory.'/'.$this->sanitizeFileName($oldPath, $field === 'imagem' ? 'imagem' : 'documento');
                        $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                        if ($result['status'] === 'missing_source') {
                            $this->warn("Arquivo nao encontrado no public | Matterport {$record->id} | campo {$field} | path {$oldPath}");
                            $stats['warnings']++;

                            continue;
                        }

                        if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                            $this->error("Falha ao migrar arquivo | Matterport {$record->id} | campo {$field} | path {$oldPath}");
                            $stats['errors']++;

                            continue;
                        }

                        if ($result['copied']) {
                            $stats['files_copied']++;
                            $this->line("Copiado: {$result['source']} -> {$result['target']}");
                        } elseif ($result['status'] === 'already_exists') {
                            $stats['skipped']++;
                        }

                        $record->{$field} = $targetPath;
                        $recordChanged = true;
                        $stats['fields_updated']++;
                    } catch (\Throwable $e) {
                        $this->error("Erro | Matterport {$record->id} | campo {$field} | {$e->getMessage()}");
                        $stats['errors']++;
                    }
                }

                if ($recordChanged && ! $dryRun) {
                    $record->saveQuietly();
                    $this->info("Registro atualizado: Matterport {$record->id}");
                }
            }
        });

        $this->printMigrationSummary($stats);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
