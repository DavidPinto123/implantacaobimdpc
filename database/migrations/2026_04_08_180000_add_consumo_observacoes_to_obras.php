<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            if (! Schema::hasColumn('obras', 'energia_observacoes')) {
                $table->longText('energia_observacoes')->nullable()->after('energia');
            }
            if (! Schema::hasColumn('obras', 'agua_observacoes')) {
                $table->longText('agua_observacoes')->nullable()->after('agua');
            }
            if (! Schema::hasColumn('obras', 'gas_observacoes')) {
                $table->longText('gas_observacoes')->nullable()->after('gas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            if (Schema::hasColumn('obras', 'energia_observacoes')) {
                $table->dropColumn('energia_observacoes');
            }
            if (Schema::hasColumn('obras', 'agua_observacoes')) {
                $table->dropColumn('agua_observacoes');
            }
            if (Schema::hasColumn('obras', 'gas_observacoes')) {
                $table->dropColumn('gas_observacoes');
            }
        });
    }
};
