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
        Schema::table('construtoras', function (Blueprint $table) {
            $table->string('inscricao_estadual')->nullable()->after('cnpj');
            $table->string('endereco')->nullable()->after('email');
            $table->string('cep')->nullable()->after('endereco');
            $table->string('responsavel')->nullable()->after('cep');
        });

        Schema::table('projetos', function (Blueprint $table) {
            $table->string('inscricao_estadual')->nullable()->after('status_cnpj');
            $table->string('telefone')->nullable()->after('cep');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('construtoras', function (Blueprint $table) {
            $table->dropColumn([
                'inscricao_estadual',
                'endereco',
                'cep',
                'responsavel',
            ]);
        });

        Schema::table('projetos', function (Blueprint $table) {
            $table->dropColumn([
                'inscricao_estadual',
                'telefone',
            ]);
        });
    }
};
