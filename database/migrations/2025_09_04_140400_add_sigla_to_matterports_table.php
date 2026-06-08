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
            $table->string('sigla')->nullable()->after('nome');
            $table->string('nova_sigla')->nullable()->after('sigla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matterports', function (Blueprint $table) {
            $table->dropColumn(['sigla', 'nova_sigla']);
        });
    }
};
