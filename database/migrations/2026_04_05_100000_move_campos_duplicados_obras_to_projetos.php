<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $campos = [
        'sigla',
        'nova_sigla',
        'marca',
        'tipo_imovel',
        'empreendimento',
        'locacao',
        'contato_corretor',
        'inauguracao',
        'status_contrato',
    ];

    public function up(): void
    {
        DB::table('obras')
            ->whereNotNull('projeto_id')
            ->orderBy('id')
            ->chunk(100, function ($obras) {
                foreach ($obras as $obra) {
                    $projeto = DB::table('projetos')->find($obra->projeto_id);
                    if (! $projeto) {
                        continue;
                    }

                    $updates = [];
                    foreach ($this->campos as $col) {
                        if (empty($projeto->$col) && ! empty($obra->$col)) {
                            $updates[$col] = $obra->$col;
                        }
                    }

                    if (! empty($updates)) {
                        DB::table('projetos')->where('id', $projeto->id)->update($updates);
                    }
                }
            });

        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn($this->campos);
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->string('sigla')->nullable();
            $table->string('nova_sigla')->nullable();
            $table->string('marca')->nullable();
            $table->string('tipo_imovel')->nullable();
            $table->string('empreendimento')->nullable();
            $table->string('locacao')->nullable();
            $table->string('contato_corretor')->nullable();
            $table->date('inauguracao')->nullable();
            $table->string('status_contrato')->nullable();
        });
    }
};
