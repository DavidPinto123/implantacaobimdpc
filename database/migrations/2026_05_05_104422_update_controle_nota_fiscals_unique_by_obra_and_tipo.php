<?php

use App\Enums\TipoUnidade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('controle_nota_fiscals')) {
            return;
        }

        DB::table('controle_nota_fiscals')
            ->where(function ($query): void {
                $query
                    ->whereNull('tipo_unidade')
                    ->orWhere('tipo_unidade', 'AMPLIAÇÃO');
            })
            ->update(['tipo_unidade' => TipoUnidade::EXPANSAO->value]);

        if (Schema::hasColumn('obras', 'tipos_unidade')) {
            DB::table('obras')
                ->where('tipos_unidade', 'like', '%AMPLIAÇÃO%')
                ->update([
                    'tipos_unidade' => DB::raw("REPLACE(tipos_unidade, 'AMPLIAÇÃO', '".TipoUnidade::EXPANSAO->value."')"),
                ]);
        }

        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            $table->dropForeign(['obra_id']);
            $table->dropUnique('controle_nota_fiscals_obra_id_unique');
            $table->unique(['obra_id', 'tipo_unidade'], 'controle_nota_fiscals_obra_tipo_unique');
            $table->foreign('obra_id')
                ->references('id')
                ->on('obras')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('controle_nota_fiscals')) {
            return;
        }

        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            $table->dropForeign(['obra_id']);
            $table->dropUnique('controle_nota_fiscals_obra_tipo_unique');
            $table->unique('obra_id', 'controle_nota_fiscals_obra_id_unique');
            $table->foreign('obra_id')
                ->references('id')
                ->on('obras')
                ->cascadeOnDelete();
        });

        DB::table('controle_nota_fiscals')
            ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
            ->update(['tipo_unidade' => 'AMPLIAÇÃO']);

        if (Schema::hasColumn('obras', 'tipos_unidade')) {
            DB::table('obras')
                ->where('tipos_unidade', 'like', '%'.TipoUnidade::EXPANSAO->value.'%')
                ->update([
                    'tipos_unidade' => DB::raw("REPLACE(tipos_unidade, '".TipoUnidade::EXPANSAO->value."', 'AMPLIAÇÃO')"),
                ]);
        }
    }
};
