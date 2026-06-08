<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autorizacao_servicos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('obra_id')
                ->constrained('obras')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('as_escopo_id')
                ->constrained('as_escopos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('construtora_id')
                ->nullable()
                ->constrained('construtoras')
                ->nullOnDelete();

            $table->longText('numero_as');
            $table->string('numero_as_hash', 64)->nullable();

            $table->decimal('valor', 15, 2)->default(0);

            $table->longText('anexo_autorizacao_servico')->nullable();

            $table->text('observacoes')->nullable();

            $table->timestamps();

            $table->unique(['obra_id', 'numero_as_hash'], 'aut_serv_obra_numero_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autorizacao_servicos');
    }
};
