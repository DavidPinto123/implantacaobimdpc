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
        Schema::table('ordem_investimentos', function (Blueprint $table) {
            $table->string('status_oi', 255)
                ->default('em_aprovacao')
                ->after('estrutura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordem_investimentos', function (Blueprint $table) {
            //
        });
    }
};
