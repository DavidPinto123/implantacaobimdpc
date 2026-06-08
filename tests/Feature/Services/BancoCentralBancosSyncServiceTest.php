<?php

use App\Models\Banco;
use App\Services\BancoCentral\BancoCentralBancosSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;

uses(DatabaseTransactions::class);

it('sincroniza bancos do csv do banco central', function () {
    Http::fake([
        BancoCentralBancosSyncService::CSV_URL => Http::response(<<<'CSV'
ISPB,Short_Name,Code_Number,Participation_in_Compe,Main_access,Full_name,Start_Date
00000000,Banco Teste,1,Yes,Internet,Banco Teste S.A.,01/01/2024
11111111,Fintech Fora Compe,332,No,Internet,Fintech Fora Compe S.A.,02/01/2024
CSV),
    ]);

    $result = app(BancoCentralBancosSyncService::class)->sync();

    expect($result)->toMatchArray([
        'sincronizados' => 2,
        'inativados' => 0,
    ]);

    expect(Banco::query()->where('ispb', '00000000')->first())
        ->codigo->toBe('001')
        ->nome_reduzido->toBe('Banco Teste')
        ->nome_extenso->toBe('Banco Teste S.A.')
        ->participa_compe->toBeTrue()
        ->ativo->toBeTrue();

    expect(Banco::query()->where('ispb', '11111111')->first())
        ->codigo->toBe('332')
        ->participa_compe->toBeFalse()
        ->ativo->toBeTrue();
});

it('sincroniza csv real do banco central com bom e cabeçalhos oficiais', function () {
    Http::fake([
        BancoCentralBancosSyncService::CSV_URL => Http::response(<<<'CSV'
﻿ISPB,Short_Name,Code_Number,Participation_in_Compe,Main_Access,Full_Name,Start_Date
00000000,BCO DO BRASIL S.A.,001,Yes,RSFN,Banco do Brasil S.A.                                                            ,"Apr 22, 2002"
00038121,Selic,n/a,No,RSFN,Banco Central do Brasil - Selic,"Apr 22, 2002"
CSV),
    ]);

    $result = app(BancoCentralBancosSyncService::class)->sync();

    expect($result['sincronizados'])->toBe(2)
        ->and(Banco::query()->where('ispb', '00000000')->first())
        ->codigo->toBe('001')
        ->nome_extenso->toBe('Banco do Brasil S.A.')
        ->and(Banco::query()->where('ispb', '00038121')->first())
        ->codigo->toBeNull();
});

it('inativa bancos ausentes no csv seguinte', function () {
    Banco::query()->create([
        'ispb' => '99999999',
        'codigo' => '999',
        'nome_reduzido' => 'Banco Antigo',
        'nome_extenso' => 'Banco Antigo S.A.',
        'participa_compe' => true,
        'ativo' => true,
    ]);

    Http::fake([
        BancoCentralBancosSyncService::CSV_URL => Http::response(<<<'CSV'
ISPB,Short_Name,Code_Number,Participation_in_Compe,Main_access,Full_name,Start_Date
00000000,Banco Teste,001,Yes,Internet,Banco Teste S.A.,01/01/2024
CSV),
    ]);

    $result = app(BancoCentralBancosSyncService::class)->sync();

    expect($result['inativados'])->toBe(1)
        ->and(Banco::query()->where('ispb', '99999999')->first()->ativo)->toBeFalse();
});
