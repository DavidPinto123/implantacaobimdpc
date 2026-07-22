<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->unsignedInteger('revisao')->default(1)->after('arquivo_revit');
            $table->timestamp('revit_sincronizado_em')->nullable()->after('revisao');
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn(['revisao', 'revit_sincronizado_em']);
        });
    }
};
