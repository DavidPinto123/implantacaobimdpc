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
        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->decimal('percentual_faturamento_mao_obra_default', 5, 2)->default(60)->after('item_recebimento');
            $table->decimal('percentual_faturamento_material_default', 5, 2)->default(40)->after('percentual_faturamento_mao_obra_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('as_escopos', function (Blueprint $table): void {
            $table->dropColumn([
                'percentual_faturamento_mao_obra_default',
                'percentual_faturamento_material_default',
            ]);
        });
    }
};
