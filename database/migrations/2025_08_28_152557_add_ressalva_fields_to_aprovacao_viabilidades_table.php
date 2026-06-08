<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aprovacao_viabilidades', function (Blueprint $table) {
            $table->longText('observacoes_ressalva')->nullable();
            $table->json('anexos_ressalva')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('aprovacao_viabilidades', function (Blueprint $table) {
            $table->dropColumn([
                'observacoes_ressalva',
                'anexos_ressalva',
            ]);
        });
    }
};
