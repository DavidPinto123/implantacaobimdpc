<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateObrasForceCommand extends Command
{
    protected $signature = 'migrate:obras-force';

    protected $description = 'Force apply the tipos_unidade migration to obras table';

    public function handle(): int
    {
        $this->info('Executando migration: adicionar tipos_unidade à tabela obras...');

        if (Schema::hasColumn('obras', 'tipos_unidade')) {
            $this->info('✓ Coluna tipos_unidade já existe!');
            return 0;
        }

        try {
            $this->info('Adicionando coluna tipos_unidade...');
            Schema::table('obras', function ($table) {
                $table->json('tipos_unidade')->nullable()->after('projeto_id');
            });
            $this->line('✓ Coluna adicionada com sucesso!');

            $this->info('Atualizando registros retrofit...');
            DB::table('obras')
                ->select('id', 'projeto_id')
                ->whereNotNull('projeto_id')
                ->orderBy('id')
                ->chunkById(500, function ($obras) {
                    $projetoIds = $obras->pluck('projeto_id')->filter()->unique()->values();

                    if ($projetoIds->isEmpty()) {
                        return;
                    }

                    $retrofitProjetoIds = DB::table('projetos')
                        ->whereIn('id', $projetoIds)
                        ->where('sigla', 'like', '%\_RET')
                        ->pluck('id');

                    if ($retrofitProjetoIds->isEmpty()) {
                        return;
                    }

                    DB::table('obras')
                        ->whereIn('id', $obras->pluck('id'))
                        ->whereIn('projeto_id', $retrofitProjetoIds)
                        ->update([
                            'tipos_unidade' => json_encode(['RETROFIT'], JSON_UNESCAPED_UNICODE),
                        ]);
                });
            $this->line('✓ Registros atualizados!');

            DB::table('migrations')->insertOrIgnore([
                'migration' => '2026_04_30_000001_add_tipos_unidade_to_obras_table',
                'batch' => DB::table('migrations')->max('batch') + 1,
            ]);

            $this->info('✓ Migration registrada!');
            $this->line('✓ <bg=green;fg=white> Sucesso! </> A migration foi aplicada com sucesso.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Erro ao executar migration: ' . $e->getMessage());
            return 1;
        }
    }
}
