<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->string('evtl_status')->nullable()->after('status');
            $table->date('evtl_recebido_em')->nullable()->after('evtl_status');
            $table->json('anexo_evtl')->nullable()->after('evtl_recebido_em');
        });
    }

    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'evtl_status',
                'evtl_recebido_em',
                'anexo_evtl',
            ]);
        });
    }
};
