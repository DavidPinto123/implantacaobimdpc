<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_configuracoes_sla', function (Blueprint $table) {
            $table->id();
            $table->string('urgencia'); // P1, P2, P3
            $table->unsignedSmallInteger('prazo_horas');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_configuracoes_sla');
    }
};
