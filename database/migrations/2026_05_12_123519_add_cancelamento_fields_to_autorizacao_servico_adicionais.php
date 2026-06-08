<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorizacao_servico_adicionais', function (Blueprint $table): void {
            $table->foreignId('as_cancelada_por_id')->nullable()->constrained('users')->nullOnDelete()->after('as_enviada_em');
            $table->timestamp('as_cancelada_em')->nullable()->after('as_cancelada_por_id');
            $table->text('as_motivo_cancelamento')->nullable()->after('as_cancelada_em');
        });
    }

    public function down(): void
    {
        Schema::table('autorizacao_servico_adicionais', function (Blueprint $table): void {
            $table->dropForeign(['as_cancelada_por_id']);
            $table->dropColumn(['as_cancelada_por_id', 'as_cancelada_em', 'as_motivo_cancelamento']);
        });
    }
};
