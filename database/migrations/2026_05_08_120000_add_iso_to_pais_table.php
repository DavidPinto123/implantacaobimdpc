<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pais', function (Blueprint $table) {
            $table->string('iso', 2)->nullable()->unique()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('pais', function (Blueprint $table) {
            $table->dropUnique(['iso']);
            $table->dropColumn('iso');
        });
    }
};
