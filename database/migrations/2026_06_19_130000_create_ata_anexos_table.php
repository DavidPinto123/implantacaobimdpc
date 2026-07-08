<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ata_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ata_id')->constrained('atas')->cascadeOnDelete();
            $table->string('nome_original', 255);
            $table->string('caminho', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('tamanho')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ata_anexos');
    }
};
