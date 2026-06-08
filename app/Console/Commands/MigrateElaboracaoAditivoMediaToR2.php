<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithR2Migration;
use App\Models\ElaboracaoAditivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateElaboracaoAditivoMediaToR2 extends Command
{
    use InteractsWithR2Migration;

    protected $signature = 'elaboracao-aditivo:migrate-media-to-r2
                            {--id= : Migra apenas um aditivo especifico}
                            {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra anexos e comparativos de elaboracao de aditivos do disco public para o disco r2';

    protected array $fields = [
        'anexos' => 'anexos',
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

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRACAO DE MIDIAS DE ELABORACAO DE ADITIVOS');

        $query = ElaboracaoAditivo::query()->orderBy('id');

        if (filled($id)) {
            $query->whereKey($id);
        }

        $query->chunkById(50, function ($records) use ($dryRun, &$stats) {
            foreach ($records as $record) {
                $stats['records']++;
                $recordChanged = false;

                foreach ($this->fields as $field => $directory) {
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

                            if (Str::startsWith($oldPath, 'elaboracao-aditivos/') && Storage::disk('r2')->exists($oldPath)) {
                                $newFiles[] = $oldPath;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPath = 'elaboracao-aditivos/'.$record->id.'/'.$directory.'/'.$this->sanitizeFileName($oldPath);
                            $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                            if ($result['status'] === 'missing_everywhere') {
                                $this->warn("Arquivo nao encontrado no public nem no R2 | Aditivo {$record->id} | campo {$field} | path {$oldPath}");
                                $newFiles[] = $file;
                                $stats['warnings']++;

                                continue;
                            }

                            if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                                $this->error("Falha ao migrar arquivo | Aditivo {$record->id} | campo {$field} | path {$oldPath}");
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
                            $this->error("Erro | Aditivo {$record->id} | campo {$field} | {$e->getMessage()}");
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
                    $this->info("Registro atualizado: Aditivo {$record->id}");
                }
            }
        });

        $this->printMigrationSummary($stats);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
