<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->date('data_posse_pendente')->nullable()->after('data_posse');
            $table->text('data_posse_pendente_motivo')->nullable()->after('data_posse_pendente');
            $table->string('data_posse_pendente_motivo_codigo')->nullable()->after('data_posse_pendente_motivo');
            $table->foreignId('data_posse_pendente_user_id')->nullable()->after('data_posse_pendente_motivo_codigo')->constrained('users')->nullOnDelete();
            $table->timestamp('data_posse_pendente_solicitada_em')->nullable()->after('data_posse_pendente_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('data_posse_pendente_user_id');
            $table->dropColumn([
                'data_posse_pendente',
                'data_posse_pendente_motivo',
                'data_posse_pendente_motivo_codigo',
                'data_posse_pendente_solicitada_em',
            ]);
        });
    }
};
