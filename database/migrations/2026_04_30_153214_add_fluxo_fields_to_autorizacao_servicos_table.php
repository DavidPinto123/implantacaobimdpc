<?php

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
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->string('status')->default('rascunho')->after('construtora_id')->index();
            $table->decimal('valor_estimado', 15, 2)->default(0)->after('valor');
            $table->foreignId('created_by_id')
                ->nullable()
                ->after('observacoes')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('enviado_em')->nullable()->after('created_by_id');
            $table->foreignId('enviado_por_id')
                ->nullable()
                ->after('enviado_em')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('cancelado_em')->nullable()->after('enviado_por_id');
            $table->foreignId('cancelado_por_id')
                ->nullable()
                ->after('cancelado_em')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('motivo_cancelamento')->nullable()->after('cancelado_por_id');
        });

        DB::table('autorizacao_servicos')->update([
            'status' => 'criada',
            'valor_estimado' => DB::raw('valor'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorizacao_servicos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancelado_por_id');
            $table->dropConstrainedForeignId('enviado_por_id');
            $table->dropConstrainedForeignId('created_by_id');

            $table->dropColumn([
                'status',
                'valor_estimado',
                'enviado_em',
                'cancelado_em',
                'motivo_cancelamento',
            ]);
        });
    }
};
