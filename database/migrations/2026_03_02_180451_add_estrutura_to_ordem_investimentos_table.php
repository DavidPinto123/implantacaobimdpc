<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordem_investimentos', function (Blueprint $table) {
            $table->json('estrutura')->nullable()->after('custo_m2');
        });
    }

    public function down(): void
    {
        Schema::table('ordem_investimentos', function (Blueprint $table) {
            $table->dropColumn('estrutura');
        });
    }
};
