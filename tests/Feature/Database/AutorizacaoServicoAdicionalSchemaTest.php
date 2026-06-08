<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

it('uses normalized database names for additional service authorizations', function (): void {
    expect(Schema::hasTable('autorizacao_servico_adicionais'))->toBeTrue()
        ->and(Schema::hasTable('asas'))->toBeFalse()
        ->and(Schema::hasTable('autorizacao_servico_adicional_items'))->toBeTrue()
        ->and(Schema::hasTable('asa_items'))->toBeFalse()
        ->and(Schema::hasColumn('autorizacao_servico_adicional_items', 'autorizacao_servico_adicional_id'))->toBeTrue()
        ->and(Schema::hasColumn('autorizacao_servico_adicional_items', 'asa_id'))->toBeFalse()
        ->and(Schema::hasColumn('controle_nota_fiscals', 'autorizacao_servico_adicional_id'))->toBeTrue()
        ->and(Schema::hasColumn('controle_nota_fiscals', 'asa_id'))->toBeFalse()
        ->and(Schema::hasColumn('controle_nota_fiscal_notas', 'autorizacao_servico_adicional_id'))->toBeTrue()
        ->and(Schema::hasColumn('controle_nota_fiscal_notas', 'asa_id'))->toBeFalse();
});
