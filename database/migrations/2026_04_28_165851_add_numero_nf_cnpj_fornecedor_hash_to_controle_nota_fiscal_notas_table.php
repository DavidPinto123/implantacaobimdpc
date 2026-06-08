<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            $table->string('numero_nf_cnpj_fornecedor_hash', 64)->nullable()->after('cnpj_fornecedor');
        });

        DB::table('controle_nota_fiscal_notas')
            ->select(['id', 'numero_nf', 'cnpj_fornecedor'])
            ->orderBy('id')
            ->chunkById(100, function ($notas): void {
                foreach ($notas as $nota) {
                    $numeroNotaFiscal = preg_replace('/\D/', '', (string) $nota->numero_nf) ?? '';
                    $numeroNotaFiscal = ltrim($numeroNotaFiscal, '0');

                    $cnpjFornecedor = strtoupper(trim((string) $nota->cnpj_fornecedor));
                    $cnpjFornecedor = preg_replace('/[^A-Z0-9]/', '', $cnpjFornecedor) ?? '';

                    $duplicateHash = $numeroNotaFiscal !== '' && $cnpjFornecedor !== ''
                        ? hash('sha256', $numeroNotaFiscal.'|'.$cnpjFornecedor)
                        : null;

                    DB::table('controle_nota_fiscal_notas')
                        ->where('id', $nota->id)
                        ->update([
                            'numero_nf_cnpj_fornecedor_hash' => $duplicateHash,
                        ]);
                }
            });

        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            $table->unique(
                'numero_nf_cnpj_fornecedor_hash',
                'controle_nf_notas_numero_cnpj_hash_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscal_notas', function (Blueprint $table): void {
            $table->dropUnique('controle_nf_notas_numero_cnpj_hash_unique');
            $table->dropColumn('numero_nf_cnpj_fornecedor_hash');
        });
    }
};
