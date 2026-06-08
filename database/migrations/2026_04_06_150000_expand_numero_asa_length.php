<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asas', function (Blueprint $table) {
            $table->dropUnique('asas_numero_asa_unique');
        });

        Schema::table('asas', function (Blueprint $table) {
            $table->longText('numero_asa')->change();
            $table->string('numero_asa_hash', 64)->nullable()->after('numero_asa');
            $table->unique('numero_asa_hash', 'asas_numero_asa_hash_unique');
        });

        DB::statement('UPDATE asas SET numero_asa_hash = SHA2(numero_asa, 256) WHERE numero_asa IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('asas', function (Blueprint $table) {
            $table->dropUnique('asas_numero_asa_hash_unique');
            $table->dropColumn('numero_asa_hash');
            $table->string('numero_asa', 50)->change();
            $table->unique('numero_asa', 'asas_numero_asa_unique');
        });
    }
};
