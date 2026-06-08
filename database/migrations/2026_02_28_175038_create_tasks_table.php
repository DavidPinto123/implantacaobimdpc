<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->string('title');                  // nome da tarefa
            $table->text('description')->nullable();  // informações da tarefa

            $table->foreignId('task_category_id')
                ->constrained('task_categories')
                ->cascadeOnDelete();

            $table->string('sigla', 20)->nullable();

            // Unidade no seu caso = Marca
            $table->foreignId('marca_id')
                ->constrained('marcas')
                ->cascadeOnDelete();

            // solicitante (quem criou)
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // responsável (para quem foi atribuída)
            $table->foreignId('assigned_to')
                ->constrained('users')
                ->cascadeOnDelete();

            // $table->string('requester')->nullable(); // opcional se você quiser guardar texto além do user (pode remover)
            $table->unsignedInteger('prazo')->nullable(); // prazo em dias
            $table->date('inicio')->nullable();
            $table->date('termino_programado')->nullable();
            $table->date('data_entrega')->nullable();

            $table->string('status')->default('pendente');

            $table->timestamps();

            $table->index(['assigned_to', 'status']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
