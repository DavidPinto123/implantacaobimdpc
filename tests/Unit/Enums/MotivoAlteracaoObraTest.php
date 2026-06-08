<?php

use App\Enums\MotivoAlteracaoObra;

it('tem os 9 motivos padronizados definidos na reunião 08/05', function () {
    expect(MotivoAlteracaoObra::cases())->toHaveCount(9);

    $values = array_map(fn ($c) => $c->value, MotivoAlteracaoObra::cases());

    expect($values)->toContain('ATRASO_SHELL_DOCUMENTACAO_PP')
        ->and($values)->toContain('ATRASO_ENGENHARIA')
        ->and($values)->toContain('ENTRADA_DE_ENERGIA')
        ->and($values)->toContain('ANTECIPACAO')
        ->and($values)->toContain('LEGALIZACAO')
        ->and($values)->toContain('NOVA_UNIDADES')
        ->and($values)->toContain('CANCELADAS')
        ->and($values)->toContain('ESTRATEGIA_PMO')
        ->and($values)->toContain('SUPPLY');
});

it('expõe label legível para cada motivo', function () {
    expect(MotivoAlteracaoObra::ATRASO_SHELL_DOCUMENTACAO_PP->label())
        ->toBe('Atraso Shell ou Documentação PP')
        ->and(MotivoAlteracaoObra::ATRASO_ENGENHARIA->label())->toBe('Atraso Engenharia')
        ->and(MotivoAlteracaoObra::ESTRATEGIA_PMO->label())->toBe('Estratégia PMO');
});

it('paraSelect() retorna [value => label] no formato Filament', function () {
    $select = MotivoAlteracaoObra::paraSelect();

    expect($select)->toBeArray()->toHaveCount(9)
        ->and($select['ATRASO_ENGENHARIA'])->toBe('Atraso Engenharia')
        ->and($select['ESTRATEGIA_PMO'])->toBe('Estratégia PMO');
});

it('color() retorna cor Filament válida para cada motivo', function () {
    foreach (MotivoAlteracaoObra::cases() as $motivo) {
        expect($motivo->color())->toBeIn(['danger', 'warning', 'success', 'info', 'gray', 'indigo', 'orange']);
    }
});
