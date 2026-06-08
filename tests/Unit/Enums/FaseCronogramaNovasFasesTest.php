<?php

use App\Enums\FaseCronograma;

it('inclui as 3 fases novas definidas nas reuniões 07-08-09/05', function () {
    $cases = array_map(fn ($f) => $f->value, FaseCronograma::cases());

    expect($cases)->toContain('liberacao_posse')
        ->and($cases)->toContain('cnpj_legalizacao')
        ->and($cases)->toContain('entregas_proprietario');
});

it('Energia SF/PP foram removidas como fases (reunião 11/05 — viraram subitens de OBRAS)', function () {
    $cases = array_map(fn ($f) => $f->value, FaseCronograma::cases());

    expect($cases)->not->toContain('energia_sf')
        ->and($cases)->not->toContain('energia_pp');
});

it('label das fases novas batem com o nome de produto', function () {
    expect(FaseCronograma::LIBERACAO_POSSE->label())->toBe('Liberação de Posse')
        ->and(FaseCronograma::CNPJ_LEGALIZACAO->label())->toBe('CNPJ')
        ->and(FaseCronograma::ENTREGAS_PROPRIETARIO->label())->toBe('Entregas do Proprietário');
});

it('CNPJ_LEGALIZACAO é distinto de CNPJ_SUFRAMA (ambos na ordem 3)', function () {
    expect(FaseCronograma::CNPJ_LEGALIZACAO)->not->toBe(FaseCronograma::CNPJ_SUFRAMA)
        ->and(FaseCronograma::CNPJ_LEGALIZACAO->ordem())->toBe(3)
        ->and(FaseCronograma::CNPJ_SUFRAMA->ordem())->toBe(3);
});

it('LIBERACAO_POSSE fica entre PRAZO_LEGAL e POSSE na ordem', function () {
    expect(FaseCronograma::PRAZO_LEGAL->ordem())->toBe(17)
        ->and(FaseCronograma::LIBERACAO_POSSE->ordem())->toBe(18)
        ->and(FaseCronograma::PIN_SUFRAMA->ordem())->toBe(19)
        ->and(FaseCronograma::POSSE->ordem())->toBe(20);
});

it('ENTREGAS_PROPRIETARIO compartilha ordem com POSSE (20)', function () {
    expect(FaseCronograma::ENTREGAS_PROPRIETARIO->ordem())->toBe(20);
});

it('cores das fases novas estão definidas', function () {
    foreach ([
        FaseCronograma::LIBERACAO_POSSE,
        FaseCronograma::CNPJ_LEGALIZACAO,
        FaseCronograma::ENTREGAS_PROPRIETARIO,
    ] as $fase) {
        expect($fase->color())->toBeString()->not->toBeEmpty();
    }
});

it('statusDisponiveis para fases novas usa a lista compartilhada', function () {
    foreach ([
        FaseCronograma::LIBERACAO_POSSE,
        FaseCronograma::ENTREGAS_PROPRIETARIO,
    ] as $fase) {
        $status = $fase->statusDisponiveis();
        expect($status)->toBeArray()->not->toBeEmpty();
    }
});
