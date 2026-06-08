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
        Schema::table('asas', function (Blueprint $table) {
            $table->foreignId('elaboracao_aditivo_id')
                ->nullable()
                ->after('projeto_id')
                ->constrained('elaboracao_aditivos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('elaboracao_aditivo_id');
        });
    }
};
