<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estados', function (Blueprint $table) {
            $table->string('iso_3166_2', 10)->nullable()->after('uf');
            $table->index(['pais_id', 'iso_3166_2'], 'estados_pais_iso_idx');
        });
    }

    public function down(): void
    {
        Schema::table('estados', function (Blueprint $table) {
            $table->dropIndex('estados_pais_iso_idx');
            $table->dropColumn('iso_3166_2');
        });
    }
};
