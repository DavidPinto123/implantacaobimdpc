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
        Schema::table('projetos', function (Blueprint $table) {
            $table->string('sigla_antiga', 50)->nullable()->after('sigla');
            $table->string('cnpj', 18)->nullable()->after('nova_sigla');
            $table->string('cnpj_provisorio', 18)->nullable()->after('cnpj');
            $table->string('status_cnpj', 100)->nullable()->after('cnpj_provisorio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn(['sigla_antiga', 'cnpj', 'cnpj_provisorio', 'status_cnpj']);
        });
    }
};
