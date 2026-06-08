<?php

use App\Enums\StatusCronograma;

it('mantem labels e cores dos status usados no cronograma', function () {
    expect(StatusCronograma::NAO_INICIADO->label())->toBe('Não Iniciado')
        ->and(StatusCronograma::NAO_INICIADO->color())->toBe('#6b7280')
        ->and(StatusCronograma::EM_ANDAMENTO->label())->toBe('Em Andamento')
        ->and(StatusCronograma::EM_ANDAMENTO->color())->toBe('#4a9eff')
        ->and(StatusCronograma::CONCLUIDO->label())->toBe('Concluído')
        ->and(StatusCronograma::CONCLUIDO->color())->toBe('#2dd67c')
        ->and(StatusCronograma::ATRASADO->label())->toBe('Atrasado')
        ->and(StatusCronograma::ATRASADO->color())->toBe('#ff4d6a');
});

it('mantem labels abreviados de pendencias por area', function () {
    expect(StatusCronograma::PENDENCIA_ENGENHARIA->label())->toBe('Pendência Engª')
        ->and(StatusCronograma::PENDENCIA_COM->label())->toBe('Pendência Com')
        ->and(StatusCronograma::PENDENCIA_LEG->label())->toBe('Pendência Leg')
        ->and(StatusCronograma::PENDENCIA_ARQ->label())->toBe('Pendência Arq')
        ->and(StatusCronograma::PENDENCIA_DIR->label())->toBe('Pendência Dir');
});
