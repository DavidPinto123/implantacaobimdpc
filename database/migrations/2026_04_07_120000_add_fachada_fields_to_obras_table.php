<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->date('fachada_data_instalacao')->nullable()->after('ponto_atencao');
            $table->string('fachada_status', 255)->nullable()->after('fachada_data_instalacao');
            $table->text('fachada_observacao')->nullable()->after('fachada_status');
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn([
                'fachada_data_instalacao',
                'fachada_status',
                'fachada_observacao',
            ]);
        });
    }
};
