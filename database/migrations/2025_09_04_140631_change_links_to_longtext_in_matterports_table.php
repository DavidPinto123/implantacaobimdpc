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
            $table->longText('link_matterport1')->nullable()->change();
            $table->longText('link_matterport2')->nullable()->change();
            $table->longText('link_matterport3')->nullable()->change();
            $table->longText('link_drone')->nullable()->change();
            $table->longText('link_google_maps')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matterports', function (Blueprint $table) {
            $table->string('link_matterport1', 255)->nullable()->change();
            $table->string('link_matterport2', 255)->nullable()->change();
            $table->string('link_matterport3', 255)->nullable()->change();
            $table->string('link_drone', 255)->nullable()->change();
            $table->string('link_google_maps', 255)->nullable()->change();
        });
    }
};
