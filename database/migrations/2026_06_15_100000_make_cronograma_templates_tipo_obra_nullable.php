<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_templates', function (Blueprint $table) {
            $table->string('tipo_obra', 30)->nullable()->change();
            $table->string('ancora_campo')->nullable()->change();

            if (Schema::hasColumn('cronograma_templates', 'direcao')) {
                $table->string('direcao', 20)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_templates', function (Blueprint $table) {
            $table->string('tipo_obra', 30)->nullable(false)->change();
            $table->string('ancora_campo')->nullable(false)->change();

            if (Schema::hasColumn('cronograma_templates', 'direcao')) {
                $table->string('direcao', 20)->nullable(false)->change();
            }
        });
    }
};
