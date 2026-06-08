<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('grupo_ois')->where('ordem', 0)->update(['ordem' => 1]);

        Schema::table('grupo_ois', function (Blueprint $table): void {
            $table->unsignedInteger('ordem')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('grupo_ois', function (Blueprint $table): void {
            $table->unsignedInteger('ordem')->default(0)->change();
        });
    }
};
