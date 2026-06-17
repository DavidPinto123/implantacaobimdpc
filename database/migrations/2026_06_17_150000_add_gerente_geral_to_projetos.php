<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->foreignId('gerente_geral_id')
                ->nullable()
                ->after('resp_pmo')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'gerente_geral_id');
            $table->dropColumn('gerente_geral_id');
        });
    }
};
