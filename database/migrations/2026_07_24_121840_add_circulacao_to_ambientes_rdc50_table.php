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
        Schema::table('ambientes_rdc50', function (Blueprint $table) {
            $table->string('circulacao')->nullable()->after('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ambientes_rdc50', function (Blueprint $table) {
            $table->dropColumn('circulacao');
        });
    }
};
