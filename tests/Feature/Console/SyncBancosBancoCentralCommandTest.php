<?php

use App\Models\Banco;
use App\Services\BancoCentral\BancoCentralBancosSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;

uses(DatabaseTransactions::class);

it('executa sincronização de bancos do banco central', function () {
    Http::fake([
        BancoCentralBancosSyncService::CSV_URL => Http::response(<<<'CSV'
ISPB,Short_Name,Code_Number,Participation_in_Compe,Main_access,Full_name,Start_Date
00000000,Banco Teste,001,Yes,Internet,Banco Teste S.A.,01/01/2024
CSV),
    ]);

    $this->artisan('importar:bancos')
        ->expectsOutput('Bancos sincronizados: 1')
        ->expectsOutput('Bancos inativados: 0')
        ->assertSuccessful();

    expect(Banco::query()->where('codigo', '001')->exists())->toBeTrue();
});
