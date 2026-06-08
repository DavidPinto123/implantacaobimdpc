<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biblioteca_arquivos', function (Blueprint $table) {
            $table->id();
            $table->string('referenciavel_type');
            $table->unsignedBigInteger('referenciavel_id');
            $table->string('disco')->default('r2');
            $table->string('caminho');
            $table->string('nome_original');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['referenciavel_type', 'referenciavel_id'], 'biblioteca_arquivos_morph_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biblioteca_arquivos');
    }
};
