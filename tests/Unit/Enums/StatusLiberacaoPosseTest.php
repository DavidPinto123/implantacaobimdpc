<?php

use App\Enums\StatusLiberacaoPosse;

it('expõe 3 cases: SIM/NAO/RISCO', function () {
    $cases = StatusLiberacaoPosse::cases();
    expect(count($cases))->toBe(3)
        ->and(StatusLiberacaoPosse::SIM->value)->toBe('sim')
        ->and(StatusLiberacaoPosse::NAO->value)->toBe('nao')
        ->and(StatusLiberacaoPosse::RISCO->value)->toBe('risco');
});

it('label retorna texto em português', function () {
    expect(StatusLiberacaoPosse::SIM->label())->toBe('Sim')
        ->and(StatusLiberacaoPosse::NAO->label())->toBe('Não')
        ->and(StatusLiberacaoPosse::RISCO->label())->toBe('Risco');
});

it('color retorna hex correto para cada case', function () {
    expect(StatusLiberacaoPosse::SIM->color())->toBe('#22c55e')
        ->and(StatusLiberacaoPosse::NAO->color())->toBe('#9ca3af')
        ->and(StatusLiberacaoPosse::RISCO->color())->toBe('#ef4444');
});

it('concluido retorna true apenas para SIM', function () {
    expect(StatusLiberacaoPosse::SIM->concluido())->toBeTrue()
        ->and(StatusLiberacaoPosse::NAO->concluido())->toBeFalse()
        ->and(StatusLiberacaoPosse::RISCO->concluido())->toBeFalse();
});

it('paraSelect retorna array value => label', function () {
    expect(StatusLiberacaoPosse::paraSelect())->toBe([
        'sim' => 'Sim',
        'nao' => 'Não',
        'risco' => 'Risco',
    ]);
});
