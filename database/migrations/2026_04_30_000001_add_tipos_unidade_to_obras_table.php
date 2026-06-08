<?php

use App\Enums\TipoUnidade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table): void {
            if (! Schema::hasColumn('obras', 'tipos_unidade')) {
                $table->json('tipos_unidade')->nullable()->after('projeto_id');
            }
        });

        DB::table('obras')
            ->select('id', 'projeto_id')
            ->whereNotNull('projeto_id')
            ->orderBy('id')
            ->chunkById(500, function ($obras): void {
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
                        'tipos_unidade' => json_encode([TipoUnidade::RETROFIT->value], JSON_UNESCAPED_UNICODE),
                    ]);
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table): void {
            if (Schema::hasColumn('obras', 'tipos_unidade')) {
                $table->dropColumn('tipos_unidade');
            }
        });
    }
};
