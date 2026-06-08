<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('construtoras', function (Blueprint $table) {
            $table->string('tipo')->default('CONSTRUTORA')->after('nome'); // CONSTRUTORA | PRESTADORA_SERVICO
            $table->string('telefone_whatsapp')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('construtoras', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'telefone_whatsapp']);
        });
    }
};
