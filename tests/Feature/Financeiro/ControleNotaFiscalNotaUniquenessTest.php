<?php

use App\Models\ControleNotaFiscalNota;
use Database\Factories\ControleNotaFiscalNotaFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\assertDatabaseCount;

uses(LazilyRefreshDatabase::class);

it('detects duplicate invoice by number and supplier cnpj while ignoring the current record', function () {
    $nota = ControleNotaFiscalNotaFactory::new()->create([
        'numero_nf' => '12345',
        'cnpj_fornecedor' => '12.345.678/0001-95',
    ]);

    expect(ControleNotaFiscalNota::duplicateExists('12345', '12345678000195'))->toBeTrue()
        ->and(ControleNotaFiscalNota::duplicateExists('12345', '12.345.678/0001-95', $nota->id))->toBeFalse()
        ->and(ControleNotaFiscalNota::duplicateExists('12345', '98.765.432/0001-10'))->toBeFalse();
});

it('enforces unique invoice number and supplier cnpj combination at the database level', function () {
    ControleNotaFiscalNotaFactory::new()->create([
        'numero_nf' => '998877',
        'cnpj_fornecedor' => '12.345.678/0001-95',
    ]);

    expect(fn () => ControleNotaFiscalNotaFactory::new()->create([
        'numero_nf' => '998877',
        'cnpj_fornecedor' => '12345678000195',
    ]))->toThrow(QueryException::class);

    assertDatabaseCount('controle_nota_fiscal_notas', 1);
});
