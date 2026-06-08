<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relatorio_fotograficos', function (Blueprint $table) {
            if (! Schema::hasColumn('relatorio_fotograficos', 'status_termo_de_posse')) {
                $table
                    ->string('status_termo_de_posse', 255)
                    ->nullable()
                    ->after('data_posse');
            }
        });
    }

    public function down(): void
    {
        Schema::table('relatorio_fotograficos', function (Blueprint $table) {
            if (Schema::hasColumn('relatorio_fotograficos', 'status_termo_de_posse')) {
                $table->dropColumn('status_termo_de_posse');
            }
        });
    }
};

