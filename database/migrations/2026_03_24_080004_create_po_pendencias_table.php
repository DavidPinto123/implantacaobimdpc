<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_pendencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // PO-YYYY-XXXX
            $table->foreignId('obras_id')->constrained('obras')->cascadeOnDelete();
            $table->foreignId('construtora_id')->nullable()->constrained('construtoras')->nullOnDelete();
            $table->foreignId('lider_obra_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gestor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('disciplina_config_id')->nullable()->constrained('po_disciplinas_config')->nullOnDelete();
            $table->string('ticket')->nullable();
            $table->text('descricao');
            $table->text('observacoes')->nullable();
            $table->string('urgencia'); // P1, P2, P3
            $table->string('status')->default('REGISTRADA');
            $table->date('data_inicio')->nullable();
            $table->date('data_termino')->nullable();
            $table->dateTime('data_conclusao')->nullable();
            $table->boolean('impacto_operacao')->default(false);
            $table->string('local_especifico')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_pendencias');
    }
};
