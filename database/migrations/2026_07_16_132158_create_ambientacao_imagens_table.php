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
        Schema::create('ambientacao_imagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambientacao_id')->constrained('ambientacoes')->cascadeOnDelete();
            $table->string('arquivo');
            $table->string('legenda')->nullable();
            $table->string('origem')->default('upload');
            $table->float('yaw')->nullable();
            $table->float('pitch')->nullable();
            $table->float('fov')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambientacao_imagens');
    }
};
