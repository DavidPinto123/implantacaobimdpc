<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('controle_nota_fiscal_nota_baixas')) {
            return;
        }

        Schema::create('controle_nota_fiscal_nota_baixas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('controle_nota_fiscal_nota_id');
            $table->foreign('controle_nota_fiscal_nota_id', 'cnf_nota_baixas_nota_fk')
                ->references('id')
                ->on('controle_nota_fiscal_notas')
                ->cascadeOnDelete();
            $table->foreignId('user_id');
            $table->foreign('user_id', 'cnf_nota_baixas_user_fk')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->timestamp('baixado_em');
            $table->timestamps();

            $table->index(
                ['controle_nota_fiscal_nota_id', 'baixado_em'],
                'cnf_nota_baixas_nota_id_baixado_em_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controle_nota_fiscal_nota_baixas');
    }
};
