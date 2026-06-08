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
        Schema::create('bancos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 3)->nullable()->index();
            $table->string('ispb', 8)->unique();
            $table->string('nome_reduzido');
            $table->string('nome_extenso')->nullable();
            $table->boolean('participa_compe')->default(false)->index();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
