<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use App\Filament\Resources\ControleNotaFiscals\Pages\ListControleNotaFiscals;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('mantem o controle de medicao somente leitura sem rota de criacao', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
        'Create:ControleNotaFiscal',
        'Update:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);
    $controle->itens()->firstOrFail()->autorizacaoServico()->update([
        'status' => AsStatus::ENVIADA,
    ]);

    expect(ControleNotaFiscalResource::getPages())
        ->not->toHaveKey('create');

    Livewire::test(ListControleNotaFiscals::class)
        ->assertActionDoesNotExist('create');

    $this->get('/admin/controle-notas-fiscais/create')
        ->assertNotFound();

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->assertSee('Visualização consolidada')
        ->assertSee('Complemento')
        ->assertSee('Escopo Complementar')
        ->assertDontSee('Adicionar escopo')
        ->assertDontSee('Liberar para fornecedor')
        ->assertDontSee('Adicionar linha')
        ->assertDontSee('Reprovar');
});

it('exibe complemento e escopo complementar no mesmo padrao do controle de as', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle, 'item' => $item] = createControleNotaFiscalComNota($user);
    $item->autorizacaoServico()->update([
        'status' => AsStatus::ENVIADA,
    ]);
    $item->update([
        'numero_as' => '20.1/C1',
        'numero_complemento' => 'C1',
        'escopo_complementar' => 'Adição de evaporadora',
    ]);

    Livewire::test(ListControleNotaFiscals::class)
        ->set('controlesExpandidos', [$controle->id])
        ->assertSee('Complemento')
        ->assertSee('20.1')
        ->assertSee('C1')
        ->assertSee('Escopo Complementar')
        ->assertSee('Adição de evaporadora')
        ->assertDontSee('20.1/C1');

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->assertSee('Complemento')
        ->assertSee('20.1')
        ->assertSee('C1')
        ->assertDontSee('20.1/C1')
        ->assertSee('Escopo Complementar')
        ->assertSee('Adição de evaporadora');
});

it('lista todos os controles de nota fiscal para usuario com acesso ao resource', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controleVisivel] = createControleNotaFiscalComNota($user);

    $obraAtiva = Obras::factory()->create([
        'unidade' => 'Unidade Ativa NF',
    ]);

    $controleAtivo = ControleNotaFiscal::create([
        'obra_id' => $obraAtiva->id,
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'unidade' => $obraAtiva->unidade,
        'sigla' => 'ATIVO-NF',
        'endereco' => 'Rua Ativa',
    ]);
    $escopoAtivo = AsEscopo::create([
        'grupo' => 'Grupo Ativo',
        'numero_as' => 'AS-ATIVO-NF',
        'escopo' => 'Escopo ativo NF',
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obraAtiva->id,
        'as_escopo_id' => $escopoAtivo->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-ATIVO-NF',
        'valor' => 500,
        'valor_estimado' => 500,
    ]);
    $controleAtivo->itens()->create([
        'autorizacao_servico_id' => $autorizacao->id,
        'as_escopo_id' => $escopoAtivo->id,
        'grupo' => $escopoAtivo->grupo,
        'numero_as' => $escopoAtivo->numero_as,
        'escopo' => $escopoAtivo->escopo,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 500,
        'valor_acumulado_medido' => 0,
        'saldo' => 500,
    ]);

    Livewire::test(ListControleNotaFiscals::class)
        ->assertSee($controleVisivel->sigla)
        ->assertSee($obraAtiva->unidade);
});

it('exibe apenas escopos com as enviada no controle de nota fiscal', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);
    $controle->itens()->firstOrFail()->autorizacaoServico()->update([
        'status' => AsStatus::ENVIADA,
    ]);

    $escopoCriado = AsEscopo::create([
        'grupo' => 'Grupo Criada',
        'numero_as' => 'AS-CRIADA-NF',
        'escopo' => 'Escopo com AS criada nao deve aparecer',
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $autorizacaoCriada = AutorizacaoServico::create([
        'obra_id' => $controle->obra_id,
        'as_escopo_id' => $escopoCriado->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-CRIADA-NF',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);

    $controle->itens()->create([
        'autorizacao_servico_id' => $autorizacaoCriada->id,
        'as_escopo_id' => $escopoCriado->id,
        'grupo' => 'Grupo Criada',
        'numero_as' => 'AS-CRIADA-NF',
        'escopo' => 'Escopo com AS criada nao deve aparecer',
        'valor_global_a' => 100,
        'total_medicao_a_menos_b' => 100,
        'valor_acumulado_medido' => 0,
        'saldo' => 100,
    ]);

    $controle->itens()->create([
        'grupo' => 'Grupo Rascunho',
        'numero_as' => 'AS-RASCUNHO-NF',
        'escopo' => 'Escopo rascunho nao deve aparecer',
        'valor_global_a' => 0,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 0,
    ]);

    Livewire::test(ListControleNotaFiscals::class)
        ->set('controlesExpandidos', [$controle->id])
        ->assertSee('Escopo de teste')
        ->assertDontSee('Escopo com AS criada nao deve aparecer')
        ->assertDontSee('Escopo rascunho nao deve aparecer');

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->assertSee('Escopo de teste')
        ->assertDontSee('Escopo com AS criada nao deve aparecer')
        ->assertDontSee('Escopo rascunho nao deve aparecer');
});

it('encerra controle de nota fiscal com permissao shield', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);
    $user->givePermissionTo(Permission::findOrCreate(ControleNotaFiscal::PERMISSION_CLOSE, 'web'));

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);
    $controle->itens()->firstOrFail()->autorizacaoServico()->update([
        'status' => AsStatus::ENVIADA,
    ]);

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->callAction('encerrarControleNotaFiscal')
        ->assertNotified();

    expect($controle->refresh()->status)->toBe(ControleNotaFiscal::STATUS_ENCERRADO);
});

it('nao expoe acoes legadas de escopo as ou nota fiscal na pagina de visualizacao', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
        'Update:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle] = createControleNotaFiscalComNota($user);

    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()]);

    expect(method_exists(EditControleNotaFiscal::class, 'attachEscoposSelecionados'))->toBeFalse()
        ->and(method_exists(EditControleNotaFiscal::class, 'releaseRow'))->toBeFalse()
        ->and(method_exists(EditControleNotaFiscal::class, 'addNotaRow'))->toBeFalse()
        ->and(method_exists(EditControleNotaFiscal::class, 'reprovarNota'))->toBeFalse();

    expect(AutorizacaoServico::query()
        ->where('obra_id', $controle->obra_id)
        ->where('as_escopo_id', $escopo->id)
        ->exists())->toBeFalse();
});

it('le notas derivadas de as e asa e calcula saldos realizados', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);

    $this->actingAs($user);

    ['controle' => $controle, 'item' => $item] = createControleNotaFiscalComNota($user);

    $autorizacao = $item->autorizacaoServico;
    $autorizacao->update([
        'controle_nota_fiscal_item_id' => $item->id,
        'status' => AsStatus::ENVIADA,
    ]);
    $item->update([
        'autorizacao_servico_id' => null,
        'empresa' => 'Fornecedor derivado',
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'saldo' => 1000,
    ]);

    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-READ',
        'escopo' => 'Escopo ASA leitura derivada',
        'empresa' => 'Fornecedor derivado',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 500,
        'valor_acumulado_medido' => 0,
        'saldo' => 500,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $asa = Asa::create([
        'numero_asa' => 'ASA-READ-001',
        'projeto_id' => $controle->obra->projeto_id,
        'sigla' => $controle->obra->sigla,
        'endereco' => $controle->obra->endereco,
        'contrato' => 'Projeto',
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => 'aprovado',
        'codigo_as_emitida' => 'ASA-READ-CODIGO',
        'data_solicitacao' => now()->toDateString(),
        'data_aprovacao' => now()->toDateString(),
        'objeto' => 'ASA leitura',
        'descricao' => 'Escopo ASA leitura derivada',
        'valor_bruto' => 500,
        'desconto' => 0,
        'valor_total' => 500,
        'solicitante' => 'Fornecedor derivado',
    ]);

    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacao->id,
        'autorizacao_servico_adicional_id' => null,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => 'Fornecedor derivado',
        'cnpj_fornecedor' => '88888888000188',
        'numero_nf' => 'NF-READ-AS-APROVADA',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'valor_acumulado_medido_nf' => 300,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacao->id,
        'autorizacao_servico_adicional_id' => null,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => 'Fornecedor derivado',
        'cnpj_fornecedor' => '88888888000188',
        'numero_nf' => 'NF-READ-AS-PENDENTE',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::PENDENTE->value,
        'valor_acumulado_medido_nf' => 200,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => null,
        'autorizacao_servico_adicional_id' => $asa->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => 'Fornecedor derivado',
        'cnpj_fornecedor' => '88888888000188',
        'numero_nf' => 'NF-READ-ASA-APROVADA',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'valor_acumulado_medido_nf' => 100,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => null,
        'autorizacao_servico_adicional_id' => $asa->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => 'Fornecedor derivado',
        'cnpj_fornecedor' => '88888888000188',
        'numero_nf' => 'NF-READ-ASA-PENDENTE',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::PENDENTE->value,
        'valor_acumulado_medido_nf' => 100,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);

    Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->assertSee('NF-READ-AS-APROVADA')
        ->assertSee('NF-READ-AS-PENDENTE')
        ->assertSee('NF-READ-ASA-APROVADA')
        ->assertSee('NF-READ-ASA-PENDENTE')
        ->assertSee('R$ 1.500,00')
        ->assertSee('R$ 1.100,00');
});
