<?php

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Pais;
use App\Services\DadosAbertos\LocalizacaoBrasilSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('sincroniza estados e cidades brasileiras a partir do formato do IBGE', function (): void {
    $municipios = [
        [
            'nome' => 'São Paulo',
            'microrregiao' => [
                'mesorregiao' => [
                    'UF' => [
                        'sigla' => 'SP',
                        'nome' => 'São Paulo',
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Campinas',
            'microrregiao' => [
                'mesorregiao' => [
                    'UF' => [
                        'sigla' => 'SP',
                        'nome' => 'São Paulo',
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Rio de Janeiro',
            'microrregiao' => [
                'mesorregiao' => [
                    'UF' => [
                        'sigla' => 'RJ',
                        'nome' => 'Rio de Janeiro',
                    ],
                ],
            ],
        ],
        [
            'nome' => 'Boa Esperança do Norte',
            'microrregiao' => null,
            'regiao-imediata' => [
                'regiao-intermediaria' => [
                    'UF' => [
                        'sigla' => 'MT',
                        'nome' => 'Mato Grosso',
                    ],
                ],
            ],
        ],
    ];

    $result = app(LocalizacaoBrasilSyncService::class)->syncFromMunicipios($municipios);

    expect($result)->toMatchArray([
        'paises' => 1,
        'estados' => 3,
        'cidades' => 4,
    ]);

    $brasil = Pais::query()->where('nome', 'Brasil')->sole();
    $saoPaulo = Estado::query()->where('pais_id', $brasil->id)->where('nome', 'São Paulo')->sole();

    expect($saoPaulo->uf)->toBe('SP')
        ->and(Cidade::query()->where('estado_id', $saoPaulo->id)->where('nome', 'Campinas')->exists())->toBeTrue()
        ->and(Cidade::query()->where('nome', 'Rio de Janeiro')->exists())->toBeTrue()
        ->and(Cidade::query()->where('nome', 'Boa Esperança do Norte')->exists())->toBeTrue();
});

it('atualiza uf de estado existente criado sem sigla', function (): void {
    $brasil = Pais::query()->create(['nome' => 'Brasil']);
    Estado::query()->create([
        'pais_id' => $brasil->id,
        'nome' => 'São Paulo',
        'uf' => null,
    ]);

    app(LocalizacaoBrasilSyncService::class)->syncFromMunicipios([
        [
            'nome' => 'São Paulo',
            'microrregiao' => [
                'mesorregiao' => [
                    'UF' => [
                        'sigla' => 'SP',
                        'nome' => 'São Paulo',
                    ],
                ],
            ],
        ],
    ]);

    expect(Estado::query()->where('nome', 'São Paulo')->sole()->uf)->toBe('SP');
});
