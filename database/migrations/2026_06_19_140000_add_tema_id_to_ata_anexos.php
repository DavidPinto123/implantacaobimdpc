<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ata_anexos', function (Blueprint $table) {
            $table->unsignedBigInteger('tema_id')->nullable()->after('ata_id');
            $table->foreign('tema_id')->references('id')->on('ata_temas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ata_anexos', function (Blueprint $table) {
            $table->dropForeign(['tema_id']);
            $table->dropColumn('tema_id');
        });
    }
};
