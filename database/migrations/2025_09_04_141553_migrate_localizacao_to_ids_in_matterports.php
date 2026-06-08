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
        Schema::table('matterports', function (Blueprint $table) {
            $table->foreignId('pais_id')->nullable()->after('id');
            $table->foreignId('estado_id')->nullable()->after('pais_id');
            $table->foreignId('cidade_id')->nullable()->after('estado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matterports', function (Blueprint $table) {});
    }
};
