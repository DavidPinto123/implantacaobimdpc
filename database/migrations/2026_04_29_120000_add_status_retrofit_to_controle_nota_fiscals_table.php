<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            if (! Schema::hasColumn('controle_nota_fiscals', 'status_retrofit')) {
                $table->string('status_retrofit')->default('analizar')->after('status')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('controle_nota_fiscals', function (Blueprint $table): void {
            if (Schema::hasColumn('controle_nota_fiscals', 'status_retrofit')) {
                $table->dropColumn('status_retrofit');
            }
        });
    }
};
