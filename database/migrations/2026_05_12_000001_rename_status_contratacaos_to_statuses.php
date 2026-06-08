<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('status_contratacaos', 'statuses');

        Schema::table('statuses', function (Blueprint $table): void {
            $table->string('contexto')->default('retrofit')->after('id');
            $table->string('slug')->nullable()->after('contexto');
            $table->boolean('is_protected')->default(false)->after('is_active');
            $table->string('tipo_custo')->nullable()->after('is_protected');
        });

        // Preencher slug para registros existentes (todos de retrofit) usando a mesma
        // regra usada hoje em ControlePedidosRetrofit::carregarStatusOptions().
        $existentes = DB::table('statuses')->get();
        foreach ($existentes as $registro) {
            $slug = strtolower(str_replace(' ', '_', (string) $registro->nome));
            DB::table('statuses')->where('id', $registro->id)->update([
                'contexto' => 'retrofit',
                'slug' => $slug,
            ]);
        }

        Schema::table('statuses', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
        });

        // O índice unique antigo era apenas em "nome" globalmente; agora unicidade é por (contexto, *).
        Schema::table('statuses', function (Blueprint $table): void {
            try {
                $table->dropUnique('status_contratacaos_nome_unique');
            } catch (\Throwable $e) {
                try {
                    $table->dropUnique(['nome']);
                } catch (\Throwable $e2) {
                    // ignora — índice pode ter outro nome ou já ter sido removido
                }
            }
        });

        Schema::table('statuses', function (Blueprint $table): void {
            $table->unique(['contexto', 'slug']);
            $table->unique(['contexto', 'nome']);
            $table->index('contexto');
        });

        $agora = now();

        // Status protegidos da Entrega Contratual (coluna Status).
        $protegidosStatus = [
            ['slug' => 'entregue', 'nome' => 'ENTREGUE', 'cor' => '#16a34a', 'ordem' => 1],
            ['slug' => 'entregue_parcial', 'nome' => 'ENTREGUE PARCIAL', 'cor' => '#f59e0b', 'ordem' => 2],
            ['slug' => 'nao_entregue', 'nome' => 'NÃO ENTREGUE', 'cor' => '#ef4444', 'ordem' => 3],
        ];

        foreach ($protegidosStatus as $p) {
            DB::table('statuses')->insert([
                'contexto' => 'entrega_contratual_status',
                'slug' => $p['slug'],
                'nome' => $p['nome'],
                'cor' => $p['cor'],
                'ordem' => $p['ordem'],
                'is_active' => true,
                'is_protected' => true,
                'tipo_custo' => null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }

        // Status protegidos da Entrega Contratual (coluna Previsto em contrato?).
        $protegidosPrevisto = [
            ['slug' => 'previsto_sim', 'nome' => 'SIM', 'cor' => '#16a34a', 'ordem' => 1, 'tipo_custo' => 'contrato'],
            ['slug' => 'previsto_nao', 'nome' => 'NÃO', 'cor' => '#ef4444', 'ordem' => 2, 'tipo_custo' => 'sem_contrato'],
        ];

        foreach ($protegidosPrevisto as $p) {
            DB::table('statuses')->insert([
                'contexto' => 'entrega_contratual_previsto',
                'slug' => $p['slug'],
                'nome' => $p['nome'],
                'cor' => $p['cor'],
                'ordem' => $p['ordem'],
                'is_active' => true,
                'is_protected' => true,
                'tipo_custo' => $p['tipo_custo'],
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('statuses')->whereIn('contexto', ['entrega_contratual_status', 'entrega_contratual_previsto'])->delete();

        Schema::table('statuses', function (Blueprint $table): void {
            try {
                $table->dropUnique(['contexto', 'slug']);
            } catch (\Throwable $e) {
            }
            try {
                $table->dropUnique(['contexto', 'nome']);
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex(['contexto']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('statuses', function (Blueprint $table): void {
            $table->dropColumn(['contexto', 'slug', 'is_protected', 'tipo_custo']);
        });

        Schema::table('statuses', function (Blueprint $table): void {
            $table->unique('nome');
        });

        Schema::rename('statuses', 'status_contratacaos');
    }
};
