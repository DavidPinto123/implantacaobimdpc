<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('colunas_personalizadas')) {
            return;
        }

        Schema::table('colunas_personalizadas', function (Blueprint $table) {
            if (! Schema::hasColumn('colunas_personalizadas', 'opcoes')) {
                $table->json('opcoes')->nullable()->after('tipo');
            }
        });

        if (Schema::hasColumn('colunas_personalizadas', 'valor')) {
            DB::statement('ALTER TABLE colunas_personalizadas MODIFY valor VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('colunas_personalizadas')) {
            return;
        }

        Schema::table('colunas_personalizadas', function (Blueprint $table) {
            if (Schema::hasColumn('colunas_personalizadas', 'opcoes')) {
                $table->dropColumn('opcoes');
            }
        });
    }
};
