<?php

use App\Enums\ModoAncoraCronograma;
use App\Enums\TipoObraCronograma;
use App\Models\CronogramaTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

function fazerTemplate(string $nome, string $modo, ?int $pareadoId = null): CronogramaTemplate
{
    return CronogramaTemplate::create([
        'nome' => $nome,
        'tipo_obra' => TipoObraCronograma::EXPANSAO->value,
        'ancora_campo' => $modo === 'posse' ? 'projeto.data_posse' : 'projeto.data_inicio_obra',
        'modo_ancora' => $modo,
        'template_pareado_id' => $pareadoId,
        'ativo' => true,
    ]);
}

it('temPar retorna true só quando template_pareado_id está setado', function () {
    $solo = fazerTemplate('Solo', 'posse');
    expect($solo->temPar())->toBeFalse();

    $par = fazerTemplate('Par', 'obras');
    $solo->update(['template_pareado_id' => $par->id]);
    expect($solo->fresh()->temPar())->toBeTrue();
});

it('pareado() retorna o template do outro lado', function () {
    $posse = fazerTemplate('Variante Posse', 'posse');
    $obras = fazerTemplate('Variante Obras', 'obras', $posse->id);
    $posse->update(['template_pareado_id' => $obras->id]);

    expect($posse->fresh()->pareado->id)->toBe($obras->id);
    expect($obras->fresh()->pareado->id)->toBe($posse->id);
});

it('variantePara devolve o próprio template quando o modo bate', function () {
    $posse = fazerTemplate('Variante Posse', 'posse');

    $resultado = $posse->variantePara(ModoAncoraCronograma::POSSE);
    expect($resultado?->id)->toBe($posse->id);
});

it('variantePara devolve o par quando o modo é o oposto', function () {
    $posse = fazerTemplate('Variante Posse', 'posse');
    $obras = fazerTemplate('Variante Obras', 'obras', $posse->id);
    $posse->update(['template_pareado_id' => $obras->id]);

    $resultado = $posse->fresh()->variantePara(ModoAncoraCronograma::OBRAS);
    expect($resultado?->id)->toBe($obras->id);
});

it('variantePara devolve null quando o par não existe e o modo solicitado é o oposto', function () {
    $posse = fazerTemplate('Solo Posse', 'posse');

    $resultado = $posse->variantePara(ModoAncoraCronograma::OBRAS);
    expect($resultado)->toBeNull();
});

it('variantePara aceita string e enum equivalentes', function () {
    $posse = fazerTemplate('Variante Posse', 'posse');
    $obras = fazerTemplate('Variante Obras', 'obras', $posse->id);
    $posse->update(['template_pareado_id' => $obras->id]);

    expect($posse->fresh()->variantePara('obras')?->id)->toBe($obras->id);
    expect($posse->fresh()->variantePara(ModoAncoraCronograma::OBRAS)?->id)->toBe($obras->id);
});

it('modo_ancora é cast para enum ModoAncoraCronograma', function () {
    $tpl = fazerTemplate('Cast test', 'obras');
    expect($tpl->fresh()->modo_ancora)->toBe(ModoAncoraCronograma::OBRAS);
});
