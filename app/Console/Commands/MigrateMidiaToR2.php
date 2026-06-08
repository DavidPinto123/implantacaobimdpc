<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithR2Migration;
use App\Models\Midia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateMidiaToR2 extends Command
{
    use InteractsWithR2Migration;

    protected $signature = 'midia:migrate-to-r2
                            {--id= : Migra apenas uma midia especifica}
                            {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra registros da tabela midias para o disco r2 e corrige o campo disk';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $id = $this->option('id');
        $stats = $this->initializeMigrationStats();

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRACAO DE REGISTROS DA TABELA MIDIAS');

        $query = Midia::query()->orderBy('id');

        if (filled($id)) {
            $query->whereKey($id);
        }

        $query->chunkById(50, function ($records) use ($dryRun, &$stats) {
            foreach ($records as $record) {
                $stats['records']++;

                try {
                    if (blank($record->path)) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($record->disk === 'r2' && Storage::disk('r2')->exists($record->path)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $oldPath = $this->extractPath($record->path);

                    if (! $oldPath) {
                        $stats['skipped']++;

                        continue;
                    }

                    $mediavelType = class_basename((string) $record->mediavel_type);
                    $mediavelSlug = Str::slug($mediavelType ?: 'registro');
                    $categoriaSlug = Str::slug((string) ($record->categoria ?: 'midia'));
                    $targetPath = 'midias/'.$mediavelSlug.'/'.$record->mediavel_id.'/'.$categoriaSlug.'/'.$this->sanitizeFileName($oldPath, 'midia');
                    $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                    if ($result['status'] === 'missing_source') {
                        $this->warn("Arquivo nao encontrado no public | Midia {$record->id} | path {$oldPath}");
                        $stats['warnings']++;

                        continue;
                    }

                    if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                        $this->error("Falha ao migrar arquivo | Midia {$record->id} | path {$oldPath}");
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
                        $record->path = $targetPath;
                        $record->disk = 'r2';
                        $record->saveQuietly();
                        $this->info("Registro atualizado: Midia {$record->id}");
                    }

                    $stats['fields_updated']++;
                } catch (\Throwable $e) {
                    $this->error("Erro | Midia {$record->id} | {$e->getMessage()}");
                    $stats['errors']++;
                }
            }
        });

        $this->printMigrationSummary($stats);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
