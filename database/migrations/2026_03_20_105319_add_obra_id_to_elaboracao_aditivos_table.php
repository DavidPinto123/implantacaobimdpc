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
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elaboracao_aditivos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('obra_id');
        });
    }
};
