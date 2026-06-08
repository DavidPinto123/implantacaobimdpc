<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_escopo_marca', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('as_escopo_id')->constrained('as_escopos')->cascadeOnDelete();
            $table->foreignId('marca_id')->constrained('marcas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['as_escopo_id', 'marca_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_escopo_marca');
    }
};
