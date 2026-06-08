<?php

use App\Models\ControleNotaFiscal;
use App\Models\Obras;
use App\Models\Projeto;
use Database\Seeders\ControleNotaFiscalSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('sincroniza unidade sigla e endereco do controle a partir da obra existente', function () {
    $projeto = Projeto::factory()->create([
        'sigla' => 'DEM-SIGLA',
    ]);

    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
        'unidade' => 'Unidade Seeder',
        'endereco' => 'Rua Seeder, 123',
    ]);

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => null,
        'sigla' => null,
        'endereco' => null,
    ]);

    $this->seed(ControleNotaFiscalSeeder::class);

    expect($controle->fresh())
        ->unidade->toBe('Unidade Seeder')
        ->sigla->toBe('DEM-SIGLA')
        ->endereco->toBe('Rua Seeder, 123');
});
