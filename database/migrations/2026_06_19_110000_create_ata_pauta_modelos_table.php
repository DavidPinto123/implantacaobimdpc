<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ata_pauta_modelos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255)->unique();
            $table->unsignedInteger('uso')->default(0);
            $table->timestamps();
        });

        $agora = now();
        DB::table('ata_pauta_modelos')->insert([
            ['titulo' => 'Reunião de definição de Escopo',      'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Compatibilização de Projetos',         'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Apresentação de avanço',               'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Acordos financeiros',                  'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Mudanças do Layout',                   'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Planejamento e revisão de datas',      'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Kick-off do projeto',                  'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Alinhamento de equipe',                'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Aprovação de projeto',                 'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
            ['titulo' => 'Visita técnica ao canteiro',           'uso' => 0, 'created_at' => $agora, 'updated_at' => $agora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ata_pauta_modelos');
    }
};
