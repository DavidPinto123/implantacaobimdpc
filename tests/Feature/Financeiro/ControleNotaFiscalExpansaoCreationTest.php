<?php

use App\Enums\TipoUnidade;
use App\Filament\Resources\Obras\Pages\CreateObras;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\Obras;
use App\Services\AutorizacaoServicoService;
use App\Services\ControleNotaFiscal\CriaControleNotaFiscalExpansao;
use Database\Factories\AsEscopoFactory;
use Database\Factories\ConstrutoraFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('usa expansao como tipo padrao do controle de nota fiscal', function () {
    $obra = Obras::factory()->create([
        'tipos_unidade' => [],
    ]);

    expect(TipoUnidade::EXPANSAO->value)->toBe('EXPANSÃO')
        ->and(ControleNotaFiscal::resolveTipoUnidade($obra))->toBe(TipoUnidade::EXPANSAO->value)
        ->and(ControleNotaFiscal::resolveTipoUnidade($obra, true))->toBe(TipoUnidade::RETROFIT->value);
});

it('cria controle de nota fiscal expansao automaticamente ao criar obra pelo resource', function () {
    $projeto = createProjetoRecord(auth()->user());

    Livewire::test(CreateObras::class)
        ->fillForm([
            'projeto_id' => $projeto->id,
            'status' => 'Obras',
            'unidade' => 'Unidade Expansao',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $obra = Obras::query()->where('projeto_id', $projeto->id)->firstOrFail();

    $this->assertDatabaseHas('controle_nota_fiscals', [
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
        'status' => ControleNotaFiscal::STATUS_ATIVO,
    ]);
});

it('cria controle de nota fiscal expansao de forma idempotente', function () {
    $obra = Obras::factory()->create();

    app(CriaControleNotaFiscalExpansao::class)->handle($obra);
    app(CriaControleNotaFiscalExpansao::class)->handle($obra);

    expect(ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->count())->toBe(1);

    expect(ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::RETROFIT->value)
        ->exists())->toBeFalse();
});

it('preenche controle expansao com escopos as nao personalizados de forma idempotente', function () {
    $escopoCivil = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '1.01',
        'escopo' => 'Escopo Civil',
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $escopoEletrica = AsEscopoFactory::new()->create([
        'grupo' => 'Eletrica',
        'numero_as' => '2.01',
        'escopo' => 'Escopo Eletrica',
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $escopoPersonalizado = AsEscopoFactory::new()->create([
        'grupo' => 'Personalizado',
        'numero_as' => '9.99',
        'escopo' => 'Escopo Personalizado',
        'is_active' => true,
        'is_personalizado' => true,
    ]);
    $obra = Obras::factory()->create();

    app(CriaControleNotaFiscalExpansao::class)->handle($obra);
    app(CriaControleNotaFiscalExpansao::class)->handle($obra);

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    expect($controle->itens()->count())->toBe(2)
        ->and($controle->itens()->where('as_escopo_id', $escopoCivil->id)->exists())->toBeTrue()
        ->and($controle->itens()->where('as_escopo_id', $escopoEletrica->id)->exists())->toBeTrue()
        ->and($controle->itens()->where('as_escopo_id', $escopoPersonalizado->id)->exists())->toBeFalse()
        ->and(ControleNotaFiscalItem::query()
            ->where('controle_nota_fiscal_id', $controle->id)
            ->where('as_escopo_id', $escopoCivil->id)
            ->count())->toBe(1);

    $itemCivil = $controle->itens()->where('as_escopo_id', $escopoCivil->id)->firstOrFail();

    expect($itemCivil->grupo)->toBe('Civil')
        ->and($itemCivil->numero_as)->toBe('1.01')
        ->and($itemCivil->escopo)->toBe('Escopo Civil')
        ->and((float) $itemCivil->percentual_total)->toBe(100.0)
        ->and((float) $itemCivil->percentual_faturamento_mao_obra)->toBe(60.0)
        ->and((float) $itemCivil->percentual_faturamento_material)->toBe(40.0);
});

it('nao restaura escopo padrao removido manualmente do controle expansao existente', function () {
    $escopoCivil = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '1.02',
        'escopo' => 'Escopo Civil Removivel',
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $obra = Obras::factory()->create();

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $controle->itens()->where('as_escopo_id', $escopoCivil->id)->delete();

    app(CriaControleNotaFiscalExpansao::class)->handle($obra);

    expect($controle->itens()->where('as_escopo_id', $escopoCivil->id)->exists())->toBeFalse();
});

it('preserva o fluxo retrofit criando controle separado do controle expansao', function () {
    $obra = Obras::factory()->create([
        'tipos_unidade' => [TipoUnidade::RETROFIT->value],
    ]);
    $construtora = ConstrutoraFactory::new()->create();
    $escopo = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '1.01',
        'escopo' => 'Escopo Retrofit',
    ]);

    ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $autorizacaoServico = AutorizacaoServico::query()->create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $construtora->id,
        'numero_as' => 'AS-RETROFIT',
        'valor' => 1000,
    ]);

    app(AutorizacaoServicoService::class)->sincronizarItensContratuais($autorizacaoServico);

    $controleExpansao = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $controleRetrofit = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::RETROFIT->value)
        ->firstOrFail();

    expect($controleRetrofit->id)->not->toBe($controleExpansao->id)
        ->and($controleRetrofit->itens()->where('as_escopo_id', $escopo->id)->exists())->toBeTrue()
        ->and($controleExpansao->itens()->where('as_escopo_id', $escopo->id)->exists())->toBeFalse();
});

it('nao cria autorizacao de servico automaticamente quando controle de nota fiscal e criado', function () {
    $obra = Obras::factory()->create();
    $construtora = ConstrutoraFactory::new()->create();
    $escopo = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '1.01',
        'escopo' => 'Escopo sem criacao automatica de AS',
        'is_active' => true,
        'is_personalizado' => false,
    ]);

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    ControleNotaFiscalItem::query()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'grupo' => $escopo->grupo,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => $construtora->nome,
        'valor_global_a' => 1000,
        'saldo' => 1000,
    ]);

    expect(AutorizacaoServico::query()
        ->where('obra_id', $obra->id)
        ->where('as_escopo_id', $escopo->id)
        ->where('construtora_id', $construtora->id)
        ->exists())->toBeFalse();
});
