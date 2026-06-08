<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/_execute_migrate', function () {
    try {
        if (!Schema::hasColumn('obras', 'tipos_unidade')) {
            Schema::table('obras', function ($table) {
                $table->json('tipos_unidade')->nullable()->after('projeto_id');
            });
            echo "✓ Coluna tipos_unidade criada<br>";

            DB::table('obras')
                ->whereIn('projeto_id',
                    DB::table('projetos')
                        ->where('sigla', 'like', '%\_RET')
                        ->pluck('id')
                )
                ->update(['tipos_unidade' => json_encode(['RETROFIT'], JSON_UNESCAPED_UNICODE)]);
            echo "✓ Obras retrofit atualizadas<br>";

            DB::table('migrations')->insertOrIgnore([
                'migration' => '2026_04_30_000001_add_tipos_unidade_to_obras_table',
                'batch' => DB::table('migrations')->max('batch') + 1,
            ]);
            echo "✓ Migration registrada<br>";
            echo "<strong style='color:green'>SUCESSO! Agora acesse /admin/controle-pedidos-retrofit</strong>";
        } else {
            echo "<strong>Coluna já existe!</strong>";
        }
    } catch (\Exception $e) {
        echo "❌ Erro: " . htmlspecialchars($e->getMessage());
    }
});

Route::get('/_fix_atualizacoes_obra_titulo', function () {
    try {
        DB::statement('ALTER TABLE atualizacoes_obra MODIFY titulo LONGTEXT');

        DB::table('migrations')->insertOrIgnore([
            'migration' => '2026_05_04_164750_alter_atualizacoes_obra_titulo_column',
            'batch' => DB::table('migrations')->max('batch') + 1,
        ]);

        echo "<strong style='color:green'>✓ SUCESSO! Campo titulo alterado para LONGTEXT</strong>";
    } catch (\Exception $e) {
        echo "❌ Erro: " . htmlspecialchars($e->getMessage());
    }
});
