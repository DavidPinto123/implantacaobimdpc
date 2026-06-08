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
        Schema::create('asas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_asa', 50)->unique();

            $table->foreignId('projeto_id')
                ->constrained('projetos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('sigla', 50)->nullable();
            $table->string('endereco')->nullable();
            $table->string('contrato')->nullable();
            $table->string('subgrupo')->nullable();

            $table->string('status')->nullable();

            $table->string('codigo_as_emitida')->nullable();
            $table->date('data_solicitacao')->nullable();
            $table->date('data_aprovacao')->nullable();

            $table->text('objeto');
            $table->longText('justificativa')->nullable();

            $table->string('altera_prazo')->nullable();
            $table->integer('dias_prazo')->nullable();

            $table->decimal('valor_bruto', 15, 2)->default(0);
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);

            $table->json('evidencias')->nullable();
            $table->longText('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asas');
    }
};
