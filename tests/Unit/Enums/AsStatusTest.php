<?php

use App\Enums\AsStatus;

it('expõe todos os 12 estados', function () {
    expect(AsStatus::cases())->toHaveCount(12);

    expect(collect(AsStatus::cases())->pluck('value')->all())
        ->toEqualCanonicalizing([
            'rascunho',
            'solicitado',
            'em_aprovacao_gestor',
            'em_aprovacao_orcamento',
            'aprovado',
            'reprovado_gestor',
            'reprovado_orcamento',
            'criada',
            'enviada',
            'em_orcamento',
            'orcada',
            'cancelada',
        ]);
});

it('tem label PT-BR para todos os estados', function (AsStatus $status) {
    expect($status->label())->not->toBe('')->toBeString();
})->with(AsStatus::cases());

it('tem color válida para todos os estados', function (AsStatus $status) {
    expect($status->color())
        ->toBeIn(['neutral', 'info', 'warning', 'success', 'danger']);
})->with(AsStatus::cases());

it('permite cancelar apenas em criada e enviada', function () {
    foreach (AsStatus::cases() as $status) {
        $esperado = in_array($status, [AsStatus::CRIADA, AsStatus::ENVIADA], true);
        expect($status->permiteCancelar())->toBe($esperado);
    }
});

it('permite visualizar em criada, enviada e cancelada', function () {
    foreach (AsStatus::cases() as $status) {
        $esperado = in_array($status, [AsStatus::CRIADA, AsStatus::ENVIADA, AsStatus::CANCELADA], true);
        expect($status->permiteVisualizar())->toBe($esperado);
    }
});

it('permite criar AS somente em aprovado', function () {
    foreach (AsStatus::cases() as $status) {
        expect($status->permiteCriarAs())->toBe($status === AsStatus::APROVADO);
    }
});

it('permite enviar AS somente em criada', function () {
    foreach (AsStatus::cases() as $status) {
        expect($status->permiteEnviarAs())->toBe($status === AsStatus::CRIADA);
    }
});

it('options() devolve mapa value => label', function () {
    $options = AsStatus::options();

    expect($options)->toHaveCount(12)
        ->and($options['criada'])->toBe('Criada')
        ->and($options['cancelada'])->toBe('Cancelada')
        ->and($options['em_aprovacao_orcamento'])->toBe('Em aprovação do orçamento');
});

it('labelFrom e colorFrom resolvem strings com fallback seguro', function () {
    expect(AsStatus::labelFrom('criada'))->toBe('Criada')
        ->and(AsStatus::labelFrom('valor_invalido'))->toBe('-')
        ->and(AsStatus::labelFrom(null))->toBe('-')
        ->and(AsStatus::colorFrom('enviada'))->toBe('success')
        ->and(AsStatus::colorFrom('valor_invalido'))->toBe('neutral');
});
