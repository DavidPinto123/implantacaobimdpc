<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->string('status_fluxo')
                ->default('elaboracao')
                ->after('anexos');

            $table->text('justificativa_reprovacao_gestor')
                ->nullable()
                ->after('status_fluxo');

            $table->text('justificativa_reprovacao_orcamento')
                ->nullable()
                ->after('justificativa_reprovacao_gestor');

            $table->foreignId('aprovado_gestor_por_id')
                ->nullable()
                ->after('justificativa_reprovacao_orcamento')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('aprovado_gestor_em')
                ->nullable()
                ->after('aprovado_gestor_por_id');

            $table->foreignId('aprovado_orcamento_por_id')
                ->nullable()
                ->after('aprovado_gestor_em')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('aprovado_orcamento_em')
                ->nullable()
                ->after('aprovado_orcamento_por_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprovado_orcamento_por_id');
            $table->dropConstrainedForeignId('aprovado_gestor_por_id');

            $table->dropColumn([
                'status_fluxo',
                'justificativa_reprovacao_gestor',
                'justificativa_reprovacao_orcamento',
                'aprovado_gestor_em',
                'aprovado_orcamento_em',
            ]);
        });
    }
};
