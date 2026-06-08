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
        Schema::create('elaboracao_aditivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('construtora_id')
                ->nullable()
                ->constrained('construtoras')
                ->nullOnDelete();

            $table->string('obra');
            $table->foreignId('gestor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('data')->nullable();
            $table->string('ref_servico')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elaboracao_aditivos');
    }
};
