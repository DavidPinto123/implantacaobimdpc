<?php

namespace App\Console\Commands;

use App\Models\RelatorioVisitaTecnica;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateVisitaTecnicaMediaToR2 extends Command
{
    protected $signature = 'vt:migrate-media-to-r2 {--dry-run : Apenas simula sem gravar}';

    protected $description = 'Migra imagens e vídeos da Visita Técnica do disco public para o disco r2';

    protected array $fields = [
        'foto_entrada_de_energia',
        'foto_energia_provisoria',
        'foto_unica_medicao',
        'foto_spda',
        'foto_telegonia_dg',
        'foto_necessario_estrutura_auxiliar',
        'foto_estrutura_fachada',
        'foto_cobertura_isolamento',
        'foto_permitidas_furacoes_laje',
        'foto_sobrecarga_minima_laje',
        'foto_sobrecarga_minima_laje_teto',
        'foto_local_tomada_ar_externo_exaustao',
        'foto_alvenaria_periferia_existente',
        'foto_reboco_interno_externo_existente',
        'foto_estanqueidade',
        'foto_area_tecnica_externa_existente',
        'foto_prever_acustica_condensadores',
        'foto_prever_protecao_condensadores',
        'foto_reservatorio_agua_existente',
        'foto_reservatorio_incendio_existente',
        'foto_ponto_esgoto_existente_shell',
        'foto_rede_gas_disponivel',
        'foto_medidor_agua_instalado_ligado',
        'foto_sistema_incendio_existente',
        'foto_pd_acima_livre',
        'foto_necessario_elevador_plataforma',
        'foto_piso_acabamento_polido',
        'foto_necessario_pelicula_fachada',
        'foto_prever_marquise',
        'foto_prever_porta_enrolar',
        'foto_caixilhos_vidros_existentes',
        'foto_prever_impermeabilizacao',
        'foto_energia_carga_superior_150',
        'foto_cobertura_vao_1_5',
        'foto_planta_demarcacao_area',
        'fotos_gerais',
        'foto_capa',
        'contrato_bts',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'MODO DRY-RUN ATIVADO' : 'INICIANDO MIGRAÇÃO');

        $totalRegistros = 0;
        $totalArquivosCopiados = 0;
        $totalCamposAtualizados = 0;
        $totalAvisos = 0;
        $totalErros = 0;

        RelatorioVisitaTecnica::query()
            ->orderBy('id')
            ->chunkById(50, function ($records) use (
                $dryRun,
                &$totalRegistros,
                &$totalArquivosCopiados,
                &$totalCamposAtualizados,
                &$totalAvisos,
                &$totalErros
            ) {
                foreach ($records as $record) {
                    $totalRegistros++;
                    $alterouRegistro = false;

                    foreach ($this->fields as $field) {
                        $originalValue = $record->{$field};

                        if (blank($originalValue)) {
                            continue;
                        }

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

                                    continue;
                                }

                                // já está no destino relatorios-vt/... e já existe no R2
                                if (
                                    Str::startsWith($oldPath, 'relatorios-vt/')
                                    && Storage::disk('r2')->exists($oldPath)
                                ) {
                                    $newFiles[] = $oldPath;

                                    continue;
                                }

                                $targetPath = $this->buildTargetPath($record, $field, $oldPath);
                                $result = $this->copyFileToR2PreferringPublic($oldPath, $targetPath, $dryRun);

                                if ($result['status'] === 'missing_source') {
                                    $this->warn("Arquivo não encontrado no public | VT {$record->numero_relatorio_vt} | campo {$field} | path {$oldPath}");
                                    $newFiles[] = $file;
                                    $totalAvisos++;

                                    continue;
                                }

                                if (in_array($result['status'], ['stream_error', 'validation_error'], true)) {
                                    $this->error("Falha ao migrar arquivo | VT {$record->numero_relatorio_vt} | campo {$field} | path {$oldPath}");
                                    $newFiles[] = $file;
                                    $totalErros++;

                                    continue;
                                }

                                if ($result['copied']) {
                                    $totalArquivosCopiados++;
                                    $this->line("Copiado: {$result['source']} -> {$result['target']}");
                                }

                                $newFiles[] = $targetPath;
                                $fieldChanged = true;
                            } catch (\Throwable $e) {
                                $this->error("Erro | VT {$record->numero_relatorio_vt} | campo {$field} | {$e->getMessage()}");
                                $newFiles[] = $file;
                                $totalErros++;
                            }
                        }

                        if ($fieldChanged) {
                            $record->{$field} = $this->restoreOriginalFormat($originalValue, $newFiles);
                            $alterouRegistro = true;
                            $totalCamposAtualizados++;
                        }
                    }

                    if ($alterouRegistro && ! $dryRun) {
                        $record->saveQuietly();
                        $this->info("Registro atualizado: VT {$record->numero_relatorio_vt}");
                    }
                }
            });

        $this->newLine();
        $this->info("Registros lidos: {$totalRegistros}");
        $this->info("Arquivos copiados: {$totalArquivosCopiados}");
        $this->info("Campos atualizados: {$totalCamposAtualizados}");
        $this->info("Avisos: {$totalAvisos}");
        $this->info("Erros: {$totalErros}");

        return $totalErros > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function copyPublicFileToR2(string $oldPath, string $targetPath, bool $dryRun): array
    {
        $sourcePath = $this->resolvePublicPath($oldPath);

        if (! $sourcePath) {
            return [
                'status' => 'missing_source',
                'source' => null,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        if (Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'already_exists',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        $stream = Storage::disk('public')->readStream($sourcePath);

        if ($stream === false) {
            return [
                'status' => 'stream_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        try {
            Storage::disk('r2')->writeStream($targetPath, $stream, [
                'visibility' => 'public',
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'validation_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        return [
            'status' => 'copied',
            'source' => $sourcePath,
            'target' => $targetPath,
            'copied' => true,
        ];
    }

    private function copyR2FileToR2(string $sourcePath, string $targetPath, bool $dryRun): array
    {
        $sourcePath = ltrim(str_replace('\\', '/', $sourcePath), '/');
        $targetPath = ltrim(str_replace('\\', '/', $targetPath), '/');

        if (! Storage::disk('r2')->exists($sourcePath)) {
            return [
                'status' => 'missing_source',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        if (Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'already_exists',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        $stream = Storage::disk('r2')->readStream($sourcePath);

        if ($stream === false) {
            return [
                'status' => 'stream_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        try {
            Storage::disk('r2')->writeStream($targetPath, $stream, [
                'visibility' => 'public',
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! Storage::disk('r2')->exists($targetPath)) {
            return [
                'status' => 'validation_error',
                'source' => $sourcePath,
                'target' => $targetPath,
                'copied' => false,
            ];
        }

        return [
            'status' => 'copied',
            'source' => $sourcePath,
            'target' => $targetPath,
            'copied' => true,
        ];
    }

    private function copyFileToR2PreferringPublic(string $oldPath, string $targetPath, bool $dryRun): array
    {
        if ($this->resolvePublicPath($oldPath)) {
            return $this->copyPublicFileToR2($oldPath, $targetPath, $dryRun);
        }

        if (Storage::disk('r2')->exists($oldPath)) {
            return $this->copyR2FileToR2($oldPath, $targetPath, $dryRun);
        }

        return [
            'status' => 'missing_source',
            'source' => null,
            'target' => $targetPath,
            'copied' => false,
        ];
    }

    private function normalizeFiles(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter($decoded));
            }

            return trim($value) !== '' ? [$value] : [];
        }

        return [];
    }

    private function extractPath(mixed $file): ?string
    {
        if (is_string($file)) {
            return ltrim($file, '/');
        }

        if (is_array($file)) {
            return $file['path'] ?? $file['url'] ?? $file[0] ?? null;
        }

        return null;
    }

    private function resolvePublicPath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        $candidates = [
            $path,
            'storage/'.$path,
        ];

        foreach ($candidates as $candidate) {
            $candidate = ltrim($candidate, '/');

            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }

            if (Str::startsWith($candidate, 'storage/')) {
                $withoutStorage = Str::after($candidate, 'storage/');
                if (Storage::disk('public')->exists($withoutStorage)) {
                    return $withoutStorage;
                }
            }
        }

        return null;
    }

    private function buildTargetPath(RelatorioVisitaTecnica $record, string $field, string $oldPath): string
    {
        $fileName = basename(parse_url($oldPath, PHP_URL_PATH) ?: $oldPath);
        $fileName = urldecode($fileName);

        // separa nome e extensão
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        // normaliza (remove acento, espaços, caracteres especiais)
        $name = Str::slug($name);

        // evita nome vazio
        if (empty($name)) {
            $name = 'arquivo';
        }

        // monta novamente
        $fileName = $name.($extension ? '.'.strtolower($extension) : '');

        $baseDir = 'relatorios-vt/'.$record->numero_relatorio_vt;

        if ($field === 'contrato_bts') {
            return $baseDir.'/midia/'.$fileName;
        }

        return $baseDir.'/midia/'.$fileName;
    }

    private function restoreOriginalFormat(mixed $originalValue, array $files): mixed
    {
        if (is_array($originalValue)) {
            return $files;
        }

        if (is_string($originalValue)) {
            $decoded = json_decode($originalValue, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $files;
            }

            return $files[0] ?? null;
        }

        return $files;
    }
}
