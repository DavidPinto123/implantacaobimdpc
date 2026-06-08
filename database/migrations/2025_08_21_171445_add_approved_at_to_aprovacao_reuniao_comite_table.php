<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aprovacao_reuniao_comite', function (Blueprint $table) {
            if (! Schema::hasColumn('aprovacao_reuniao_comite', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('aprovacao')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aprovacao_reuniao_comite', function (Blueprint $table) {
            if (Schema::hasColumn('aprovacao_reuniao_comite', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
