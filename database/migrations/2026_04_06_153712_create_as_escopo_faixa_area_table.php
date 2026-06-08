<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_escopo_faixa_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('as_escopo_id')->constrained('as_escopos')->cascadeOnDelete();
            $table->foreignId('as_faixa_area_id')->constrained('as_faixa_areas')->cascadeOnDelete();
            $table->decimal('valor_m2', 15, 2);
            $table->timestamps();

            $table->unique(['as_escopo_id', 'as_faixa_area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_escopo_faixa_area');
    }
};
