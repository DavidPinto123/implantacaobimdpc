<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_ois', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('grupo_ois')
                ->nullOnDelete();
            $table->string('nome');
            $table->unsignedTinyInteger('nivel')->default(1);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('nivel');
            $table->index(['parent_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_ois');
    }
};
