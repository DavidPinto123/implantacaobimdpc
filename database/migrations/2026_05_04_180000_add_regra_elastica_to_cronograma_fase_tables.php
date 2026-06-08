<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_template_fases', function (Blueprint $table) {
            $table->boolean('regra_elastica')->default(false)->after('is_ancora');
        });

        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->boolean('regra_elastica')->nullable()->after('regra_customizada');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_template_fases', function (Blueprint $table) {
            $table->dropColumn('regra_elastica');
        });

        Schema::table('cronograma_fases', function (Blueprint $table) {
            $table->dropColumn('regra_elastica');
        });
    }
};
