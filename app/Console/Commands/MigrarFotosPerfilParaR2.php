<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrarFotosPerfilParaR2 extends Command
{
    protected $signature = 'app:migrar-fotos-perfil-r2 
                            {--user_id= : Migra apenas um usuário específico}
                            {--dry-run : Apenas simula, sem gravar nada}
                            {--apagar-origem : Apaga o arquivo do public após migrar}';

    protected $description = 'Migra fotos de perfil do disk public para o disk r2';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $apagarOrigem = (bool) $this->option('apagar-origem');
        $userId = $this->option('user_id');

        $query = User::query()
            ->whereNotNull('foto_perfil')
            ->where('foto_perfil', '!=', '');

        if ($userId) {
            $query->where('id', $userId);
        }

        $usuarios = $query->get(['id', 'name', 'foto_perfil']);

        if ($usuarios->isEmpty()) {
            $this->info('Nenhum usuário encontrado para migração.');

            return self::SUCCESS;
        }

        $this->info("Usuários encontrados: {$usuarios->count()}");

        $migrados = 0;
        $pulados = 0;
        $erros = 0;

        $bar = $this->output->createProgressBar($usuarios->count());
        $bar->start();

        foreach ($usuarios as $usuario) {
            try {
                $origem = ltrim((string) $usuario->foto_perfil, '/');

                if ($origem === '') {
                    $pulados++;
                    $bar->advance();

                    continue;
                }

                // Já está no formato novo
                if (str_starts_with($origem, 'user/fotos-perfil/')) {
                    $pulados++;
                    $bar->advance();

                    continue;
                }

                if (! Storage::disk('public')->exists($origem)) {
                    $this->newLine();
                    $this->warn("Arquivo não encontrado no public: {$origem} (user_id={$usuario->id})");
                    $pulados++;
                    $bar->advance();

                    continue;
                }

                $nomeArquivo = basename($origem);
                $destino = "user/fotos-perfil/{$usuario->id}/{$nomeArquivo}";

                if ($dryRun) {
                    $this->newLine();
                    $this->line("[DRY-RUN] user_id={$usuario->id}: {$origem} -> {$destino}");
                    $migrados++;
                    $bar->advance();

                    continue;
                }

                $conteudo = Storage::disk('public')->get($origem);

                Storage::disk('r2')->put($destino, $conteudo, [
                    'visibility' => 'public',
                ]);

                if (! Storage::disk('r2')->exists($destino)) {
                    throw new \RuntimeException("Falha ao validar upload no R2: {$destino}");
                }

                $usuario->foto_perfil = $destino;
                $usuario->save();

                if ($apagarOrigem) {
                    Storage::disk('public')->delete($origem);
                }

                $migrados++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Erro no user_id={$usuario->id}: {$e->getMessage()}");
                $erros++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migrados: {$migrados}");
        $this->info("Pulados: {$pulados}");
        $this->info("Erros: {$erros}");

        return $erros > 0 ? self::FAILURE : self::SUCCESS;
    }
}
