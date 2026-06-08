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
        if (Schema::hasTable('controle_nota_fiscals')) {
            return;
        }

        Schema::create('controle_nota_fiscals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asa_id')
                ->nullable()
                ->constrained('asas')
                ->nullOnDelete();

            $table->foreignId('elaboracao_aditivo_id')
                ->nullable()
                ->constrained('elaboracao_aditivos')
                ->nullOnDelete();

            $table->foreignId('obra_id')
                ->nullable()
                ->constrained('obras')
                ->nullOnDelete();

            $table->foreignId('construtora_id')
                ->nullable()
                ->constrained('construtoras')
                ->nullOnDelete();

            $table->string('status')->default('rascunho')->index();
            $table->date('data_base')->nullable();

            $table->string('unidade')->nullable();
            $table->string('sigla')->nullable();
            $table->string('endereco')->nullable();

            $table->timestamp('construtora_notificada_em')->nullable();
            $table->foreignId('financeiro_aprovado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('financeiro_aprovado_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controle_nota_fiscals');
    }
};
