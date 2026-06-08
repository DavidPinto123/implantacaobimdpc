<?php

use App\Enums\StatusCronograma;

it('status do contrato retorna porcentagem proporcional 0/25/50/100', function () {
    expect(StatusCronograma::NEGOCIACAO->percentualConclusao())->toBe(0)
        ->and(StatusCronograma::MINUTA->percentualConclusao())->toBe(25)
        ->and(StatusCronograma::EM_ASSINATURA->percentualConclusao())->toBe(50)
        ->and(StatusCronograma::ASSINADO->percentualConclusao())->toBe(100);
});

it('status fora do fluxo de contrato retorna null', function () {
    expect(StatusCronograma::NAO_INICIADO->percentualConclusao())->toBeNull()
        ->and(StatusCronograma::EM_ANDAMENTO->percentualConclusao())->toBeNull()
        ->and(StatusCronograma::CONCLUIDO->percentualConclusao())->toBeNull()
        ->and(StatusCronograma::ATRASADO->percentualConclusao())->toBeNull();
});

it('disponiveis() exclui status deprecated (PARALISADO)', function () {
    $disponiveis = StatusCronograma::disponiveis();

    expect($disponiveis)->not->toContain(StatusCronograma::PARALISADO);
    expect(in_array(StatusCronograma::CONCLUIDO, $disponiveis, true))->toBeTrue();
    expect(in_array(StatusCronograma::EM_ANDAMENTO, $disponiveis, true))->toBeTrue();
});

it('case PARALISADO continua existindo no enum (não foi removido)', function () {
    // Registros históricos com paralisado devem continuar renderizando.
    expect(StatusCronograma::PARALISADO->value)->toBe('paralisado')
        ->and(StatusCronograma::PARALISADO->label())->toBe('Paralisado')
        ->and(StatusCronograma::PARALISADO->color())->toBe('#f97316');
});
