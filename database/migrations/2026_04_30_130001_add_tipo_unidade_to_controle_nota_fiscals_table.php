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
        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscals', 'tipo_unidade')) {
                $table->string('tipo_unidade', 30)->nullable()->after('obra_id')->index();
            }
        });

        DB::table('controle_nota_fiscals')
            ->select('id', 'obra_id')
            ->whereNotNull('obra_id')
            ->orderBy('id')
            ->chunkById(500, function ($controles): void {
                $obraIds = $controles->pluck('obra_id')->filter()->unique()->values();

                if ($obraIds->isEmpty()) {
                    return;
                }

                $obras = DB::table('obras')
                    ->select('id', 'tipos_unidade')
                    ->whereIn('id', $obraIds)
                    ->get()
                    ->keyBy('id');

                foreach ($controles as $controle) {
                    $obraRaw = $obras->get($controle->obra_id);
                    $tiposRaw = data_get($obraRaw, 'tipos_unidade', []);
                    $tiposDecoded = is_string($tiposRaw)
                        ? (json_decode($tiposRaw, true) ?: [])
                        : $tiposRaw;

                    $tiposUnidade = collect($tiposDecoded)
                        ->map(fn ($tipo) => trim((string) $tipo))
                        ->filter(fn (string $tipo) => $tipo !== '')
                        ->values()
                        ->all();

                    $tipoUnidade = in_array(TipoUnidade::RETROFIT->value, $tiposUnidade, true)
                        ? TipoUnidade::RETROFIT->value
                        : TipoUnidade::EXPANSAO->value;

                    DB::table('controle_nota_fiscals')
                        ->where('id', $controle->id)
                        ->update(['tipo_unidade' => $tipoUnidade]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscals', 'tipo_unidade')) {
                $table->dropIndex(['tipo_unidade']);
                $table->dropColumn('tipo_unidade');
            }
        });
    }
};
