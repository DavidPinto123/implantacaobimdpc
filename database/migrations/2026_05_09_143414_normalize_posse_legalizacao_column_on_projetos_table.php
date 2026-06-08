<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('projetos', 'posse_legalizacao')) {
            return;
        }

        Schema::table('projetos', function (Blueprint $table) {
            $table->string('posse_legalizacao')->nullable()->after('posse_engenharia');
        });

        if (! Schema::hasColumn('projetos', 'posse_legalização')) {
            return;
        }

        DB::table('projetos')
            ->whereNotNull('posse_legalização')
            ->update([
                'posse_legalizacao' => DB::raw('`posse_legalização`'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('projetos', 'posse_legalizacao')) {
            return;
        }

        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn('posse_legalizacao');
        });
    }
};
