<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ata_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ata_id')->constrained('atas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('empresa')->nullable();
            $table->string('cargo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ata_participantes');
    }
};
