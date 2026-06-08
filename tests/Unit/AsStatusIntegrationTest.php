<?php

use App\Enums\AsStatus;
use App\Models\Asa;
use App\Models\AutorizacaoServico;

it('usa valores válidos do enum nos pontos de criação de AS e ASA', function () {
    $asa = new Asa;
    $asa->status = AsStatus::RASCUNHO;

    $autorizacaoServico = new AutorizacaoServico;
    $autorizacaoServico->status = AsStatus::ENVIADA;

    expect($asa->status)->toBe(AsStatus::RASCUNHO)
        ->and($autorizacaoServico->status)->toBe(AsStatus::ENVIADA);
});

it('não mantém constantes removidas ou status legados nos fluxos demo de AS e ASA', function () {
    $root = dirname(__DIR__, 2);
    $createAsaPage = file_get_contents($root.'/app/Filament/Resources/Asas/Pages/CreateAsa.php');
    $localDemoSeeder = file_get_contents($root.'/database/seeders/LocalDemoSeeder.php');

    expect($createAsaPage)->not->toContain("'status' => 'Rascunho'")
        ->and($localDemoSeeder)->not->toContain('AutorizacaoServico::STATUS_')
        ->and($localDemoSeeder)->not->toContain("'asa_status' => 'Solicitado'")
        ->and($localDemoSeeder)->not->toContain("'asa_status' => 'Em aprovação do orçamento'")
        ->and($localDemoSeeder)->not->toContain("'asa_status' => 'Aprovado'");
});
