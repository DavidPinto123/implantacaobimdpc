<?php

use App\Rules\ValidCnpj;
use App\Support\Cnpj;

it('normaliza e formata CNPJ alfanumérico em maiúsculas', function () {
    expect(Cnpj::normalize('12.abc.345/01de-35'))->toBe('12ABC34501DE35')
        ->and(Cnpj::format('12.abc.345/01de-35'))->toBe('12.ABC.345/01DE-35');
});

it('trata null textual e undefined como vazio', function () {
    expect(Cnpj::normalize('null'))->toBe('')
        ->and(Cnpj::normalize('undefined'))->toBe('')
        ->and(Cnpj::format('null'))->toBeNull()
        ->and(Cnpj::format('undefined'))->toBeNull();
});

it('valida CNPJ numérico e alfanumérico válidos', function (string $value) {
    expect(Cnpj::isValid($value))->toBeTrue();

    $failed = false;

    (new ValidCnpj)->validate('cnpj', $value, function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
})->with([
    'numérico' => '12.345.678/0001-95',
    'alfanumérico' => '12.ABC.345/01DE-35',
]);

it('rejeita CNPJ numérico e alfanumérico inválidos', function (string $value) {
    expect(Cnpj::isValid($value))->toBeFalse();

    $messages = [];

    (new ValidCnpj)->validate('cnpj', $value, function (string $message) use (&$messages): void {
        $messages[] = $message;
    });

    expect($messages)->toContain('Informe um CNPJ válido.');
})->with([
    'numérico inválido' => '12.345.678/0001-96',
    'alfanumérico inválido' => '12.ABC.345/01DE-36',
]);
