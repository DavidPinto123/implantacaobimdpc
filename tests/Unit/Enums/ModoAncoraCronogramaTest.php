<?php

use App\Enums\ModoAncoraCronograma;

it('tem dois modos: POSSE e OBRAS', function () {
    expect(ModoAncoraCronograma::cases())
        ->toHaveCount(2)
        ->and(ModoAncoraCronograma::POSSE->value)->toBe('posse')
        ->and(ModoAncoraCronograma::OBRAS->value)->toBe('obras');
});

it('expõe label, descrição e cor para cada modo', function () {
    expect(ModoAncoraCronograma::POSSE->label())->toBe('Ancorado em Posse')
        ->and(ModoAncoraCronograma::POSSE->color())->toBe('warning')
        ->and(ModoAncoraCronograma::POSSE->descricao())
            ->toContain('Mudanças no cronograma movem a data de posse');

    expect(ModoAncoraCronograma::OBRAS->label())->toBe('Ancorado em Obras')
        ->and(ModoAncoraCronograma::OBRAS->color())->toBe('success')
        ->and(ModoAncoraCronograma::OBRAS->descricao())
            ->toContain('não recalculam o cronograma');
});
