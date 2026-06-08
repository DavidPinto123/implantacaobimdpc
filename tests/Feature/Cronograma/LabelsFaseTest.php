<?php

use App\Enums\FaseCronograma;

it('CNPJ_LEGALIZACAO tem label simplificado para "CNPJ"', function () {
    expect(FaseCronograma::CNPJ_LEGALIZACAO->label())->toBe('CNPJ');
});

it('ASSINATURA_CONTRATO tem label "Status do Contrato"', function () {
    expect(FaseCronograma::ASSINATURA_CONTRATO->label())->toBe('Status do Contrato');
});

it('mantém demais labels esperados', function () {
    expect(FaseCronograma::POSSE->label())->toBe('Posse')
        ->and(FaseCronograma::OBRAS->label())->toBe('Obras')
        ->and(FaseCronograma::LIBERACAO_POSSE->label())->toBe('Liberação de Posse');
});
