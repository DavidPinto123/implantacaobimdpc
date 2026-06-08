<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\InteractsWithR2Migration;
use App\Models\RelatorioFotografico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateRelatorioFotograficoMediaToR2 extends Command
{
    use InteractsWithR2Migration;

    private const LEGACY_PREFIXES = [
        'relatorios-fotograficos/',
        'relatorios-rf/',
        'relatorios-rf/',
    ];

    protected $signature = 'relatorio-fotografico:migrate-media-to-r2
                            {--id= : Migra apenas um relatorio fotografico especifico}
                            {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra fotos e arquivos de entregas contratuais do relatorio fotografico para o disco r2';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $id = $this->option('id');
        $stats = $this->initializeMigrationStats();

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRACAO DE MIDIAS DE RELATORIO FOTOGRAFICO');

        $query = RelatorioFotografico::withTrashed()->orderBy('id');

        if (filled($id)) {
            $query->whereKey($id);
        }

        $query->chunkById(50, function ($records) use ($dryRun, &$stats) {
            foreach ($records as $record) {
                $stats['records']++;
                $recordChanged = false;

                try {
                    $originalFotos = $record->fotos;
                    $fotos = $this->normalizeFiles($originalFotos);

                    if ($fotos !== []) {
                        $newFotos = [];
                        $fieldChanged = false;

                        foreach ($fotos as $foto) {
                            $oldPath = $this->extractPath($foto);

                            if (! $oldPath) {
                                $newFotos[] = $foto;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPrefix = 'relatorios-rf/'.$record->id.'/midia/';

                            if (Str::startsWith($oldPath, $targetPrefix) && Storage::disk('r2')->exists($oldPath)) {
                                $newFotos[] = $oldPath;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPath = $targetPrefix.$this->sanitizeFileName($oldPath, 'foto');
                            $result = $this->resolveRelatorioFotograficoMigration($oldPath, $targetPath, $dryRun);

                            if ($result['status'] === 'missing_source') {
                                $this->warn("Arquivo nao encontrado no public | Relatorio {$record->id} | campo fotos | path {$oldPath}");
                                $newFotos[] = $foto;
                                $stats['warnings']++;

                                continue;
                            }

                            if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                                $this->error("Falha ao migrar foto | Relatorio {$record->id} | path {$oldPath}");
                                $newFotos[] = $foto;
                                $stats['errors']++;

                                continue;
                            }

                            if ($result['copied']) {
                                $stats['files_copied']++;
                                $this->line("Copiado: {$result['source']} -> {$result['target']}");
                            } elseif ($result['status'] === 'already_exists') {
                                $stats['skipped']++;
                            }

                            $newFotos[] = $targetPath;
                            $fieldChanged = true;
                        }

                        if ($fieldChanged) {
                            $record->fotos = $newFotos;
                            $recordChanged = true;
                            $stats['fields_updated']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("Erro | Relatorio {$record->id} | campo fotos | {$e->getMessage()}");
                    $stats['errors']++;
                }

                try {
                    $entregas = is_array($record->entregas_contratuais) ? $record->entregas_contratuais : [];
                    $entregasChanged = false;

                    foreach ($entregas as $index => $entrega) {
                        if (! is_array($entrega) || blank($entrega['arquivo'] ?? null)) {
                            continue;
                        }

                        $files = $this->normalizeFiles($entrega['arquivo']);

                        if ($files === []) {
                            continue;
                        }

                        $newFiles = [];
                        $fieldChanged = false;

                        foreach ($files as $file) {
                            $oldPath = $this->extractPath($file);

                            if (! $oldPath) {
                                $newFiles[] = $file;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPrefix = 'relatorios-rf/'.$record->id.'/entregas-contratuais/';

                            if (Str::startsWith($oldPath, $targetPrefix) && Storage::disk('r2')->exists($oldPath)) {
                                $newFiles[] = $oldPath;
                                $stats['skipped']++;

                                continue;
                            }

                            $targetPath = $targetPrefix.$index.'-'.$this->sanitizeFileName($oldPath, 'entrega');
                            $result = $this->resolveRelatorioFotograficoMigration($oldPath, $targetPath, $dryRun);

                            if ($result['status'] === 'missing_source') {
                                $this->warn("Arquivo nao encontrado no public | Relatorio {$record->id} | entrega {$index} | path {$oldPath}");
                                $newFiles[] = $file;
                                $stats['warnings']++;

                                continue;
                            }

                            if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                                $this->error("Falha ao migrar entrega contratual | Relatorio {$record->id} | entrega {$index} | path {$oldPath}");
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
                        }

                        if ($fieldChanged) {
                            $entregas[$index]['arquivo'] = is_array($entrega['arquivo']) ? $newFiles : ($newFiles[0] ?? null);
                            $entregasChanged = true;
                        }
                    }

                    if ($entregasChanged) {
                        $record->entregas_contratuais = $entregas;
                        $recordChanged = true;
                        $stats['fields_updated']++;
                    }
                } catch (\Throwable $e) {
                    $this->error("Erro | Relatorio {$record->id} | campo entregas_contratuais | {$e->getMessage()}");
                    $stats['errors']++;
                }

                if ($recordChanged && ! $dryRun) {
                    $record->saveQuietly();
                    $this->info("Registro atualizado: Relatorio {$record->id}");
                }
            }
        });

        $this->printMigrationSummary($stats);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveRelatorioFotograficoMigration(string $oldPath, string $targetPath, bool $dryRun): array
    {
        return $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);
    }
}
