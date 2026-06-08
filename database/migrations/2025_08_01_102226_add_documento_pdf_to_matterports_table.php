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
            $table->string('documentoPDF')->nullable(); // documentoPDF é o nome do campo onde será salvo o arquivo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matterports', function (Blueprint $table) {
            //
        });
    }
};
