<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->foreignId('grupo_oi_id')
                ->nullable()
                ->after('grupo')
                ->constrained('grupo_ois')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->dropForeign(['grupo_oi_id']);
            $table->dropColumn('grupo_oi_id');
        });
    }
};
