<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->string('foto_perfil')->nullable()->after('unidade');
            $table->string('foto_capa')->nullable()->after('foto_perfil');
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn(['foto_perfil', 'foto_capa']);
        });
    }
};
