<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_documentos', function (Blueprint $table): void {
            $table->foreignId('construtora_id')
                ->nullable()
                ->after('obra_id')
                ->constrained('construtoras')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('obra_documentos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('construtora_id');
        });
    }
};
