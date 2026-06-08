<?php

use App\Enums\AsStatus;
use App\Enums\TipoUnidade;
use App\Filament\Components\Forms\MoneyInput;
use App\Filament\Resources\ImportacaoNotaFiscals\Pages\CreateImportacaoNotaFiscal;
use App\Filament\Resources\ImportacaoNotaFiscals\Schemas\ImportacaoNotaFiscalForm;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\Banco;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use App\Models\Projeto;
use App\Support\Cnpj;
use Database\Factories\ControleNotaFiscalNotaFactory;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('inicializa campos mascarados vazios sem null no formulário', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->assertSet('data.cnpj_fornecedor', '')
        ->assertSet('data.cnpj_faturamento', '')
        ->assertSet('data.valor_acumulado_medido_nf', 0);
});

it('preserva a digitação rápida do número da nota fiscal sem normalização reativa', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->set('data.numero_nf', '123456789')
        ->assertSet('data.numero_nf', '123456789')
        ->set('data.tipo_medicao', 'mao_obra')
        ->assertSet('data.numero_nf', '123456789');
});

it('configura a máscara monetária sem bloquear teclas numéricas no keydown', function () {
    $attributes = MoneyInput::make('valor')->getExtraInputAttributes();

    expect($attributes)
        ->not->toHaveKey('onkeydown')
        ->not->toHaveKey('onpaste');
});

it('configura a máscara monetária para remover zero à esquerda no input', function () {
    $attributes = MoneyInput::make('valor')->getExtraInputAttributes();

    expect($attributes)
        ->toHaveKey('x-on:input')
        ->and($attributes['x-on:input'])->toContain('replace(/^0+(?=\\d)/');
});

it('configura a máscara monetária em formato pt-BR com centavos por digitação', function () {
    $mask = (string) MoneyInput::make('valor')->getMask();

    expect($mask)
        ->toContain('return `${integerMask},99`')
        ->toContain("replace(/\\B(?=(9{3})+(?!9))/g, '.')")
        ->not->toContain('$money($input');
});

it('configura número da nota fiscal para cortar zero à esquerda no input', function () {
    $reflection = new ReflectionClass(ImportacaoNotaFiscalForm::class);
    $method = $reflection->getMethod('getNumeroNotaFiscalInputAttributes');
    $method->setAccessible(true);

    $attributes = $method->invoke(null);

    expect($attributes)
        ->toHaveKey('x-on:input')
        ->and($attributes['x-on:input'])->toContain('replace(/^0+/');
});

it('ordena opções de banco pelo código', function () {
    Banco::query()->create([
        'codigo' => '999',
        'ispb' => '60701190',
        'nome_reduzido' => 'ALFA TESTE',
        'participa_compe' => true,
        'ativo' => true,
    ]);
    Banco::query()->create([
        'codigo' => '001',
        'ispb' => '00000000',
        'nome_reduzido' => 'ZETA TESTE',
        'participa_compe' => true,
        'ativo' => true,
    ]);

    $reflection = new ReflectionClass(ImportacaoNotaFiscalForm::class);
    $method = $reflection->getMethod('getBancoOptions');
    $method->setAccessible(true);

    expect(array_values($method->invoke(null)))->toBe([
        '001 - ZETA TESTE',
        '999 - ALFA TESTE',
    ]);
});

it('exibe os labels do emissor da nota no formulário', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->assertSee('Razão Social do Emissor da Nota')
        ->assertSee('CNPJ do Emissor da Nota')
        ->assertDontSee('Data de envio da nota');
});

it('configura upload da nota fiscal em largura total', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->assertFormFieldExists('arquivo_path', function (FileUpload $field): bool {
            return $field->getColumnSpan('default') === 'full';
        });
});

it('ao selecionar mão de obra altera apenas o fornecedor e preserva os CNPJs', function () {
    $usuario = Auth::user();

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'empresa' => 'Fornecedor Original',
            'cnpj_fornecedor' => '12.345.678/0001-95',
            'cnpj_faturamento' => '98.765.432/0001-10',
        ])
        ->set('data.tipo_medicao', 'mao_obra')
        ->assertSet('data.empresa', $usuario?->name)
        ->assertSet('data.cnpj_fornecedor', Cnpj::format('12.345.678/0001-95'))
        ->assertSet('data.cnpj_faturamento', Cnpj::format('98.765.432/0001-10'));
});

it('ao selecionar mão de obra preserva o CNPJ do fornecedor quando o usuário tem construtora vinculada', function () {
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Teste',
        'cnpj' => '11.222.333/0001-81',
        'tipo' => 'CONSTRUTORA',
    ]);

    $usuario = Auth::user();
    $usuario->update(['construtoras_id' => $construtora->id]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'cnpj_fornecedor' => '44.555.666/0001-77',
            'cnpj_faturamento' => '98.765.432/0001-10',
        ])
        ->set('data.tipo_medicao', 'mao_obra')
        ->assertSet('data.cnpj_fornecedor', Cnpj::format('44.555.666/0001-77'))
        ->assertSet('data.cnpj_faturamento', Cnpj::format('98.765.432/0001-10'));
});

it('preserva CNPJs em branco como string vazia após updates reativos do formulário', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'cnpj_fornecedor' => '',
            'cnpj_faturamento' => '',
        ])
        ->set('data.numero_nf', '01')
        ->set('data.tipo_medicao', 'mao_obra')
        ->assertSet('data.cnpj_fornecedor', '')
        ->assertSet('data.cnpj_faturamento', '');
});

it('preserva CNPJs intocados como string vazia após updates reativos do formulário', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->set('data.numero_nf', '01')
        ->set('data.tipo_medicao', 'mao_obra')
        ->assertSet('data.cnpj_fornecedor', '')
        ->assertSet('data.cnpj_faturamento', '');
});

it('preserva campo monetário limpo pelo usuário após updates reativos do formulário', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->set('data.valor_acumulado_medido_nf', '')
        ->set('data.numero_nf', '01')
        ->assertSet('data.valor_acumulado_medido_nf', '');
});

it('normaliza número da nota fiscal ao criar sem zero à esquerda', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal();

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'numero_nf' => '000123',
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ControleNotaFiscalNota::query()->sole()->numero_nf)->toBe('123');
});

it('bloqueia envio de nova nota fiscal para controle encerrado', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal();
    $cenario['controle']->update([
        'status' => ControleNotaFiscal::STATUS_ENCERRADO,
    ]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario))
        ->call('create')
        ->assertHasFormErrors();

    expect(ControleNotaFiscalNota::query()->count())->toBe(0);
});

it('bloqueia boleto com vencimento inferior a trinta dias usando validação nativa', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal();

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'instrucoes_pagamento' => 'boleto_bancario',
            'boleto_path' => UploadedFile::fake()->create('boleto.pdf', 10, 'application/pdf'),
            'data_vencimento_boleto' => now()->addDays(29)->toDateString(),
        ]))
        ->call('create')
        ->assertHasFormErrors([
            'data_vencimento_boleto' => 'O campo data de vencimento do boleto deve ser uma data posterior ou igual a '.now()->addDays(30)->format('d/m/Y').'.',
        ]);

    expect(ControleNotaFiscalNota::query()->count())->toBe(0);
});

it('limita os seletores de emissão e boleto às datas permitidas', function () {
    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->assertFormFieldExists('emissao', fn ($field): bool => $field->getMaxDate() === today()->toDateString())
        ->assertFormFieldExists('data_vencimento_boleto', fn ($field): bool => $field->getMinDate() === today()->toDateString());
});

it('bloqueia emissão de nota fiscal futura', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal();

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'emissao' => now()->addDay()->toDateString(),
        ]))
        ->call('create')
        ->assertHasFormErrors([
            'emissao' => 'before_or_equal',
        ]);

    expect(ControleNotaFiscalNota::query()->count())->toBe(0);
});

it('exige dados bancários ao selecionar transferência', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal();

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'instrucoes_pagamento' => 'transferencia',
        ]))
        ->call('create')
        ->assertHasFormErrors([
            'banco_codigo' => 'required',
            'agencia' => 'required',
            'conta_corrente' => 'required',
        ]);
});

it('grava dados bancários quando o pagamento é transferência', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal();
    Banco::query()->create([
        'codigo' => '001',
        'ispb' => '00000000',
        'nome_reduzido' => 'Banco Teste',
        'nome_extenso' => 'Banco Teste S.A.',
        'participa_compe' => true,
        'ativo' => true,
    ]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'instrucoes_pagamento' => 'transferencia',
            'banco_codigo' => '001',
            'agencia' => '1234',
            'conta_corrente' => '98765-4',
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ControleNotaFiscalNota::query()->sole())
        ->instrucoes_pagamento->toBe('transferencia')
        ->banco_codigo->toBe('001')
        ->banco->toBe('001 - Banco Teste')
        ->agencia->toBe('1234')
        ->conta_corrente->toBe('98765-4');
});

it('bloqueia CNPJ do emissor igual ao CNPJ do destinatário', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal([
        'cnpj_projeto' => '12.345.678/0001-95',
    ]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'cnpj_fornecedor' => '12.345.678/0001-95',
            'cnpj_faturamento' => '12.345.678/0001-95',
        ]))
        ->call('create')
        ->assertHasFormErrors(['cnpj_faturamento']);

    expect(ControleNotaFiscalNota::query()->count())->toBe(0);
});

it('bloqueia importação quando o valor da nota deixa o saldo negativo', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal([
        'valor_global' => 1000,
        'percentual_material' => 40,
    ]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
            'valor_acumulado_medido_nf' => 500,
        ]))
        ->call('create')
        ->assertActionMounted('saldoInsuficiente')
        ->assertMountedActionModalSee('Entendi')
        ->assertMountedActionModalSeeHtml('margin-inline: auto !important; width: max-content !important;')
        ->assertMountedActionModalDontSee(['Enviar', 'Cancelar', 'Fechar'])
        ->assertMountedActionModalSee('Saldo insuficiente')
        ->assertMountedActionModalSee('O valor da nota (R$ 500,00) excede o saldo disponível de material/transporte (R$ 400,00).')
        ->assertHasFormErrors(['valor_acumulado_medido_nf']);

    expect(ControleNotaFiscalNota::query()->count())->toBe(0);
});

it('bloqueia a importação de nota fiscal duplicada pelo número e CNPJ do fornecedor', function () {
    ControleNotaFiscalNotaFactory::new()->create([
        'numero_nf' => '12345',
        'cnpj_fornecedor' => '12.345.678/0001-95',
    ]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'numero_nf' => '12345',
            'cnpj_fornecedor' => '12345678000195',
        ])
        ->call('create');

    expect(ControleNotaFiscalNota::query()->count())->toBe(1);
});

it('exibe aviso para solicitar cadastro do cnpj ao gestor quando a unidade não possui cnpj cadastrado', function () {
    $projeto = Projeto::factory()->create([
        'status_cnpj' => null,
        'cnpj' => null,
        'cnpj_provisorio' => null,
    ]);

    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
    ]);

    $mensagemEsperada = 'Esta unidade não possui CNPJ cadastrado. Solicite ao gestor o cadastro do CNPJ para prosseguir com a importação da nota fiscal.';

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'obra_id_lookup' => $obra->id,
            'cnpj_faturamento' => '55.555.555/0001-55',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'cnpj_faturamento' => $mensagemEsperada,
        ]);
});

it('exibe o CNPJ ativo da unidade na mensagem de divergência do destinatário/remetente', function (string $statusCnpj, ?string $cnpj, ?string $cnpjProvisorio, string $cnpjEsperado) {
    $projeto = Projeto::factory()->create([
        'status_cnpj' => $statusCnpj,
        'cnpj' => $cnpj,
        'cnpj_provisorio' => $cnpjProvisorio,
    ]);

    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
    ]);

    $mensagemEsperada = "O CNPJ do destinatário/remetente informado não corresponde a essa unidade, só serão aceitas as notas conforme o CNPJ definitivo da unidade {$cnpjEsperado}.";

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'obra_id_lookup' => $obra->id,
            'cnpj_faturamento' => '55.555.555/0001-55',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'cnpj_faturamento' => $mensagemEsperada,
        ]);
})->with([
    'cnpj provisório ativo' => ['provisorio', null, '12.345.678/0001-90', '12.345.678/0001-90'],
    'cnpj definitivo ativo' => ['definitivo', '98.765.432/0001-10', null, '98.765.432/0001-10'],
]);

it('aceita CNPJ alfanumérico no destinatário/remetente quando o cadastro da unidade existe', function () {
    $projeto = Projeto::factory()->create([
        'status_cnpj' => 'definitivo',
        'cnpj' => '12.ABC.345/01DE-35',
        'cnpj_provisorio' => null,
    ]);

    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
    ]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'obra_id_lookup' => $obra->id,
            'cnpj_faturamento' => '12.ABC.345/01DE-35',
        ])
        ->call('create')
        ->assertHasNoFormErrors(['cnpj_faturamento']);
});

it('exige o arquivo da nota fiscal na criação', function () {
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Arquivo',
        'cnpj' => '11.222.333/0001-81',
        'tipo' => 'CONSTRUTORA',
    ]);

    $projeto = Projeto::factory()->create([
        'status_cnpj' => 'definitivo',
        'cnpj' => '12.345.678/0001-90',
        'cnpj_provisorio' => null,
    ]);

    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
    ]);

    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => 'ARQ',
    ]);

    $asEscopo = AsEscopo::create([
        'grupo' => 'Grupo Arquivo',
        'numero_as' => 'AS-ARQ',
        'escopo' => 'Escopo Arquivo',
        'is_active' => true,
    ]);

    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $asEscopo->id,
        'grupo' => 'Grupo Arquivo',
        'numero_as' => 'AS-ARQ',
        'escopo' => 'Escopo Arquivo',
        'empresa' => $construtora->nome,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-ARQ',
        'numero_complemento' => null,
        'valor' => 1000,
        'observacoes' => null,
    ]);

    $usuario = Auth::user();
    $usuario->update(['construtoras_id' => $construtora->id]);
    $usuario->assignRole(Role::findOrCreate('Fornecedor', 'web'));
    $usuario->givePermissionTo([
        Permission::findOrCreate('ViewAny:ControleNotaFiscalNota', 'web'),
        Permission::findOrCreate('Create:ControleNotaFiscalNota', 'web'),
    ]);

    Livewire::test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'obra_id_lookup' => $obra->id,
            'asa_id_lookup' => $autorizacao->id,
            'tipo_medicao' => 'material',
            'empresa' => 'Fornecedor Arquivo',
            'cnpj_fornecedor' => '44.555.666/0001-77',
            'numero_nf' => '12345',
            'cnpj_faturamento' => '12.345.678/0001-90',
            'valor_acumulado_medido_nf' => 150,
            'emissao' => now()->toDateString(),
            'instrucoes_pagamento' => 'pix',
            'observacoes' => 'Teste sem arquivo.',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'arquivo_path' => 'required',
        ]);
});

it('exibe indicadores de adicional e complementar nas opções de AS/ASA', function () {
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Label',
        'cnpj' => '55.555.555/0001-55',
        'tipo' => 'CONSTRUTORA',
    ]);

    $obra = Obras::factory()->create();

    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => 'LBL',
    ]);

    $escopoPrincipal = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'Escopo Principal',
        'is_active' => true,
    ]);

    $escopoComplementar = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1-C1',
        'escopo' => 'Escopo Complementar',
        'is_active' => true,
    ]);

    $escopoCompartilhado = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '04.1',
        'escopo' => 'Escopo compartilhado',
        'is_active' => true,
    ]);

    $itemPrincipal = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoPrincipal->id,
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'Escopo Principal',
        'empresa' => $construtora->nome,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $itemComplementar = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoComplementar->id,
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'numero_complemento' => 'C1',
        'escopo_complementar' => 'Complementar',
        'escopo' => 'Escopo Complementar',
        'empresa' => $construtora->nome,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 500,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $itemCompartilhadoPrincipal = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoCompartilhado->id,
        'grupo' => 'Civil',
        'numero_as' => '04.1',
        'escopo' => 'Escopo compartilhado principal',
        'empresa' => $construtora->nome,
        'valor_global_a' => 800,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 800,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $itemCompartilhadoComplementar = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoCompartilhado->id,
        'grupo' => 'Civil',
        'numero_as' => '04.1',
        'numero_complemento' => 'C3',
        'escopo_complementar' => 'Complementar compartilhado',
        'escopo' => 'Escopo compartilhado complementar',
        'empresa' => $construtora->nome,
        'valor_global_a' => 300,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 300,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'AD-01',
        'escopo' => 'Linha adicional demo',
        'empresa' => $construtora->nome,
        'valor_global_a' => 250,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 250,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $auxiliarComplemento = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Shell',
        'numero_as' => 'AD-02',
        'escopo' => 'Linha adicional complementar demo',
        'empresa' => $construtora->nome,
        'valor_global_a' => 325,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 325,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $escopoAdicional = AsEscopo::create([
        'grupo' => 'Projeto',
        'numero_as' => 'AD-01-AUX',
        'escopo' => 'Placeholder adicional',
        'is_active' => true,
    ]);

    $escopoAdicionalComplemento = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => 'AD-02-AUX',
        'escopo' => 'Placeholder adicional complementar',
        'is_active' => true,
    ]);

    $autorizacaoPrincipal = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $itemPrincipal->id,
        'as_escopo_id' => $escopoPrincipal->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-03.1-A',
        'numero_complemento' => null,
        'valor' => 1000,
        'observacoes' => null,
    ]);

    $autorizacaoComplementar = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $itemComplementar->id,
        'as_escopo_id' => $escopoComplementar->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-03.1-C1',
        'numero_complemento' => 'C1',
        'valor' => 500,
        'observacoes' => null,
    ]);

    $autorizacaoCompartilhadaPrincipal = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $itemCompartilhadoPrincipal->id,
        'as_escopo_id' => $escopoCompartilhado->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-04.1-A',
        'numero_complemento' => null,
        'valor' => 800,
        'observacoes' => null,
    ]);

    $autorizacaoCompartilhadaComplementar = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $itemCompartilhadoComplementar->id,
        'as_escopo_id' => $escopoCompartilhado->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-04.1-C3',
        'numero_complemento' => 'C3',
        'valor' => 300,
        'observacoes' => null,
    ]);

    $asaAdicional = Asa::create([
        'numero_asa' => 'ASA-AD-01',
        'projeto_id' => $obra->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => 'aprovado',
        'codigo_as_emitida' => 'CODIGO-AS-EMITIDA-01',
        'controle_nota_fiscal_destino' => 'adicional',
        'objeto' => $auxiliar->grupo,
        'valor_total' => 250,
        'solicitante' => $construtora->nome,
    ]);

    $asaAdicionalComplemento = Asa::create([
        'numero_asa' => 'ASA-AD-02',
        'projeto_id' => $obra->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliarComplemento->id,
        'status' => 'aprovado',
        'codigo_as_emitida' => $auxiliarComplemento->numero_as,
        'controle_nota_fiscal_destino' => 'adicional',
        'objeto' => $auxiliarComplemento->grupo,
        'numero_complemento' => 'C2',
        'valor_total' => 325,
        'solicitante' => $construtora->nome,
    ]);

    $usuario = Auth::user();
    $usuario->update(['construtoras_id' => $construtora->id]);
    $usuario->assignRole(Role::findOrCreate('Fornecedor', 'web'));

    $reflection = new ReflectionClass(ImportacaoNotaFiscalForm::class);
    $method = $reflection->getMethod('getAsaOptionsForObra');
    $method->setAccessible(true);

    $options = $method->invoke(null, $obra->id);

    expect((string) $options[$autorizacaoPrincipal->id])->not->toContain('Complemento:')
        ->and((string) $options[$autorizacaoComplementar->id])->toContain('Complemento: C1')
        ->and((string) $options[$autorizacaoCompartilhadaPrincipal->id])->not->toContain('Complemento:')
        ->and((string) $options[$autorizacaoCompartilhadaComplementar->id])->toContain('Complemento: C3')
        ->and((string) $options['asa:'.$asaAdicional->id])->toContain('Adicional')
        ->and((string) $options['asa:'.$asaAdicional->id])->toContain('ASA-AD-01')
        ->and((string) $options['asa:'.$asaAdicional->id])->not->toContain($asaAdicional->codigo_as_emitida)
        ->and((string) $options['asa:'.$asaAdicionalComplemento->id])->toContain('Adicional');
});

it('resolve o item correto da importação quando principal e complemento compartilham o escopo', function () {
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Resolve Complemento',
        'cnpj' => '55.555.555/0001-56',
        'tipo' => 'CONSTRUTORA',
    ]);

    $obra = Obras::factory()->create();

    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => 'RCP',
    ]);

    $asEscopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '05.1',
        'escopo' => 'Escopo compartilhado',
        'is_active' => true,
    ]);

    $itemPrincipal = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $asEscopo->id,
        'grupo' => 'Civil',
        'numero_as' => '05.1',
        'escopo' => 'Escopo principal',
        'empresa' => $construtora->nome,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $itemComplementar = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $asEscopo->id,
        'grupo' => 'Civil',
        'numero_as' => '05.1',
        'numero_complemento' => 'C1',
        'escopo_complementar' => 'Complementar',
        'escopo' => 'Escopo complementar',
        'empresa' => $construtora->nome,
        'valor_global_a' => 300,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 300,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $autorizacaoPrincipal = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $itemPrincipal->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-DIRETA-PRINCIPAL',
        'valor' => 1000,
    ]);

    $autorizacaoComplementar = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $itemComplementar->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-DIRETA-COMPLEMENTAR',
        'numero_complemento' => 'C1',
        'valor' => 300,
    ]);

    $page = new CreateImportacaoNotaFiscal;
    $reflection = new ReflectionClass(CreateImportacaoNotaFiscal::class);
    $method = $reflection->getMethod('resolveItemForAutorizacaoServico');
    $method->setAccessible(true);

    $principal = $method->invoke($page, $autorizacaoPrincipal, $obra->id);
    $complementar = $method->invoke($page, $autorizacaoComplementar, $obra->id);

    expect($principal)->toBeInstanceOf(ControleNotaFiscalItem::class)
        ->and($principal->is($itemPrincipal))->toBeTrue()
        ->and($complementar)->toBeInstanceOf(ControleNotaFiscalItem::class)
        ->and($complementar->is($itemComplementar))->toBeTrue();
});

it('pré-preenche obra e AS pela query string no atalho da importação', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal();

    autenticarComoConstrutora($cenario['construtora']);

    Livewire::withQueryParams([
        'obra_id_lookup' => (string) $cenario['obra']->id,
        'asa_id_lookup' => (string) $cenario['autorizacao']->id,
    ])->test(CreateImportacaoNotaFiscal::class)
        ->assertSet('data.obra_id_lookup', (string) $cenario['obra']->id)
        ->assertSet('data.asa_id_lookup', (string) $cenario['autorizacao']->id)
        ->assertFormFieldExists('asa_id_lookup', fn ($field): bool => $field->isDisabled())
        ->fillForm(collect(dadosValidosImportacaoNotaFiscal($cenario))
            ->except(['obra_id_lookup', 'asa_id_lookup'])
            ->all())
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ControleNotaFiscalNota::query()->sole())
        ->autorizacao_servico_id->toBe($cenario['autorizacao']->id)
        ->autorizacao_servico_adicional_id->toBeNull();
});

it('preserva a AS selecionada ao importar quando há histórico duplicado para o mesmo item', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $cenario = criarCenarioImportacaoNotaFiscal(['valor_global' => 2000]);
    $autorizacaoSelecionada = AutorizacaoServico::create([
        'obra_id' => $cenario['obra']->id,
        'controle_nota_fiscal_item_id' => $cenario['item']->id,
        'as_escopo_id' => $cenario['asEscopo']->id,
        'construtora_id' => $cenario['construtora']->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-IMP-C1',
        'numero_complemento' => 'C1',
        'valor' => 2000,
        'observacoes' => null,
    ]);

    autenticarComoConstrutora($cenario['construtora']);

    Livewire::withQueryParams([
        'obra_id_lookup' => (string) $cenario['obra']->id,
        'asa_id_lookup' => (string) $autorizacaoSelecionada->id,
    ])->test(CreateImportacaoNotaFiscal::class)
        ->fillForm(dadosValidosImportacaoNotaFiscal($cenario, [
            'asa_id_lookup' => $autorizacaoSelecionada->id,
            'valor_acumulado_medido_nf' => 100,
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ControleNotaFiscalNota::query()->sole())
        ->autorizacao_servico_id->toBe($autorizacaoSelecionada->id)
        ->autorizacao_servico_adicional_id->toBeNull();
});

it('pré-preenche obra e ASA pela query string no atalho da importação do adicional', function () {
    Storage::fake((string) config('filesystems.media_disk', 'r2'));

    $construtora = Construtora::create([
        'nome' => 'Fornecedor Query ASA',
        'cnpj' => '66.666.666/0001-66',
        'tipo' => 'CONSTRUTORA',
    ]);

    $projeto = Projeto::factory()->create([
        'status_cnpj' => 'definitivo',
        'cnpj' => '12.345.678/0001-90',
        'cnpj_provisorio' => null,
    ]);

    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
        'codigo' => 'OBRA-QUERY-ASA',
        'unidade' => 'Obra Query ASA',
    ]);

    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => 'QAS',
    ]);

    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Grupo Query ASA',
        'numero_as' => 'ASA-QRY',
        'escopo' => 'Escopo Query ASA',
        'empresa' => $construtora->nome,
        'valor_global_a' => 1000,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $asEscopo = AsEscopo::create([
        'grupo' => 'Grupo Query ASA',
        'numero_as' => 'AS-QUERY-ASA',
        'escopo' => 'Escopo Query ASA',
        'is_active' => true,
    ]);

    $asa = Asa::create([
        'numero_asa' => 'ASA-QRY-'.str()->upper(str()->random(4)),
        'projeto_id' => $projeto->id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => 'aprovado',
        'data_solicitacao' => now()->toDateString(),
        'objeto' => 'Escopo Query ASA',
        'justificativa' => 'ASA adicional aprovada para importação.',
        'valor_bruto' => 1000,
        'desconto' => 0,
        'valor_total' => 1000,
        'solicitante' => $construtora->nome,
    ]);

    autenticarComoConstrutora($construtora);

    Livewire::withQueryParams([
        'obra_id_lookup' => (string) $obra->id,
        'asa_id_lookup' => 'asa:'.$asa->id,
    ])->test(CreateImportacaoNotaFiscal::class)
        ->assertSet('data.obra_id_lookup', (string) $obra->id)
        ->assertSet('data.asa_id_lookup', 'asa:'.$asa->id)
        ->assertFormFieldDisabled('asa_id_lookup')
        ->set('data.tipo_medicao', ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL)
        ->assertFormFieldDisabled('asa_id_lookup')
        ->fillForm([
            'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
            'empresa' => 'Fornecedor ASA',
            'cnpj_fornecedor' => '12.345.678/0001-95',
            'numero_nf' => '54321',
            'cnpj_faturamento' => $projeto->cnpj,
            'valor_acumulado_medido_nf' => 100,
            'emissao' => now()->toDateString(),
            'instrucoes_pagamento' => 'pix',
            'arquivo_path' => UploadedFile::fake()->create('nota-asa.pdf', 10, 'application/pdf'),
            'observacoes' => 'Teste de importação ASA.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ControleNotaFiscalNota::query()->sole())
        ->autorizacao_servico_adicional_id->toBe($asa->id)
        ->autorizacao_servico_id->toBeNull();
});

function criarCenarioImportacaoNotaFiscal(array $overrides = []): array
{
    $construtora = Construtora::create([
        'nome' => $overrides['construtora_nome'] ?? 'Fornecedor Importação',
        'cnpj' => '11.222.333/0001-81',
        'tipo' => 'CONSTRUTORA',
    ]);

    $projeto = Projeto::factory()->create([
        'status_cnpj' => 'definitivo',
        'cnpj' => $overrides['cnpj_projeto'] ?? '98.765.432/0001-10',
        'cnpj_provisorio' => null,
    ]);

    $obra = Obras::factory()->create([
        'projeto_id' => $projeto->id,
    ]);

    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => 'IMP',
    ]);

    $asEscopo = AsEscopo::create([
        'grupo' => 'Grupo Importação',
        'numero_as' => 'AS-IMP',
        'escopo' => 'Escopo Importação',
        'is_active' => true,
    ]);

    $valorGlobal = (float) ($overrides['valor_global'] ?? 1000);

    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $asEscopo->id,
        'grupo' => 'Grupo Importação',
        'numero_as' => 'AS-IMP',
        'escopo' => 'Escopo Importação',
        'empresa' => $construtora->nome,
        'valor_global_a' => $valorGlobal,
        'percentual_faturamento_mao_obra' => $overrides['percentual_mao_obra'] ?? 60,
        'percentual_faturamento_material' => $overrides['percentual_material'] ?? 40,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => $valorGlobal,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-IMP',
        'numero_complemento' => null,
        'valor' => $valorGlobal,
        'observacoes' => null,
    ]);

    return compact('construtora', 'projeto', 'obra', 'controle', 'asEscopo', 'item', 'autorizacao');
}

function dadosValidosImportacaoNotaFiscal(array $cenario, array $overrides = []): array
{
    return array_merge([
        'obra_id_lookup' => $cenario['obra']->id,
        'asa_id_lookup' => $cenario['autorizacao']->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
        'empresa' => 'Fornecedor Importação',
        'cnpj_fornecedor' => '12.345.678/0001-95',
        'numero_nf' => '12345',
        'cnpj_faturamento' => $cenario['projeto']->cnpj,
        'valor_acumulado_medido_nf' => 100,
        'emissao' => now()->toDateString(),
        'instrucoes_pagamento' => 'pix',
        'arquivo_path' => UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'),
        'observacoes' => 'Teste de importação.',
    ], $overrides);
}

function autenticarComoConstrutora(Construtora $construtora): void
{
    $usuario = Auth::user();
    $usuario->update(['construtoras_id' => $construtora->id]);
    $usuario->assignRole(Role::findOrCreate('Fornecedor', 'web'));
    $usuario->givePermissionTo([
        Permission::findOrCreate('ViewAny:ControleNotaFiscalNota', 'web'),
        Permission::findOrCreate('Create:ControleNotaFiscalNota', 'web'),
    ]);
}
