<?php

use App\Enums\AsStatus;
use App\Enums\TipoUnidade;
use App\Filament\Resources\Asas\AsaResource;
use App\Filament\Resources\Asas\Pages\EditAsa;
use App\Filament\Resources\Asas\Pages\ListAsas;
use App\Filament\Resources\ElaboracaoAditivos\Pages\ViewElaboracaoAditivoCustom;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ElaboracaoAditivo;
use Filament\Actions\Action;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();

    Artisan::call('migrate', [
        '--path' => database_path('migrations/2026_04_28_102024_allow_null_projeto_id_on_asas_for_draft.php'),
        '--realpath' => true,
        '--force' => true,
    ]);
});

it('executa CRUD basico de ASA com fallback de modelo', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Create:Asa',
        'Update:Asa',
        'Delete:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'numero_asa' => 'ASA-DCT009-'.str()->upper(str()->random(4)),
        'descricao' => 'ASA DCT-009 inicial',
        'objeto' => 'ASA DCT-009 inicial',
    ]);

    $this->assertDatabaseHas('autorizacao_servico_adicionais', ['id' => $asa->id, 'descricao' => 'ASA DCT-009 inicial']);

    $this->get(AsaResource::getUrl('index'))->assertOk();
    Livewire::test(ListAsas::class)->assertCanSeeTableRecords([$asa]);

    $asa->update([
        'desconto' => 250,
        'valor_total' => 750,
        'status' => AsStatus::APROVADO,
    ]);

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'desconto' => 250,
        'valor_total' => 750,
    ]);

    $asa->delete();
    $this->assertDatabaseMissing('autorizacao_servico_adicionais', ['id' => $asa->id]);
});

/*
it('cria rascunho automatico ao abrir o create e redireciona para edit', function () {
    // Fluxo desativado: a criação manual de ASA não fica mais exposta na resource.
});

it('cria o rascunho inicial com defaults consistentes ao abrir o create', function () {
    // Fluxo desativado: a criação manual de ASA não fica mais exposta na resource.
});
*/

it('salva alteracoes incrementais no edit sem depender do submit final', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Create:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'descricao' => 'Descrição inicial',
        'objeto' => 'Objeto provisório',
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->set('data.descricao', 'Descrição salva automaticamente');

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'descricao' => 'Descrição salva automaticamente',
    ]);
});

it('bloqueia edicao manual do numero da asa e do fornecedor', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'numero_asa' => 'ASA-BLOQUEADA-001',
        'solicitante' => 'Fornecedor Bloqueado',
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->assertFormFieldExists('numero_asa', fn ($field): bool => $field->isDisabled())
        ->assertFormFieldExists('solicitante', fn ($field): bool => $field->isDisabled());
});

it('salva alteracao de prazo no edit sem depender do submit final', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Create:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'altera_prazo' => 'Não',
        'dias_prazo' => null,
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->set('data.altera_prazo', 'Sim')
        ->set('data.dias_prazo', 15)
        ->call('autoSaveCurrentState');

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'altera_prazo' => 'Sim',
        'dias_prazo' => 15,
    ]);
});

it('salva imediatamente quando altera prazo para não', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Create:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'altera_prazo' => 'Sim',
        'dias_prazo' => 10,
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->set('data.altera_prazo', 'Não');

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'altera_prazo' => 'Não',
    ]);
});

it('salva confirmacao e justificativa pelo modal quando origem da asa e shell', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'contrato' => 'Projetos',
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->assertActionExists(
            'registrarNegociacaoShell',
            fn (Action $action): bool => str_contains((string) ($action->getExtraAttributes()['class'] ?? ''), 'hidden'),
        )
        ->set('data.contrato', 'Shell')
        ->assertActionMounted('registrarNegociacaoShell')
        ->assertActionDataSet([
            'shell_cabe_como_negociacao' => null,
        ])
        ->setActionData([
            'shell_cabe_como_negociacao' => true,
            'shell_justificativa_negociacao' => 'Negociação aprovada para origem Shell.',
        ])
        ->callMountedAction()
        ->assertActionExists(
            'registrarNegociacaoShell',
            fn (Action $action): bool => str_contains((string) ($action->getExtraAttributes()['class'] ?? ''), 'hidden'),
        )
        ->assertFormFieldExists(
            'shell_cabe_como_negociacao',
            fn ($field): bool => $field instanceof ToggleButtons
                && $field->getState() === 1,
        )
        ->assertSet('data.shell_cabe_como_negociacao', 1);

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'contrato' => 'Shell',
        'shell_cabe_como_negociacao' => true,
        'shell_justificativa_negociacao' => 'Negociação aprovada para origem Shell.',
    ]);
});

it('exige justificativa para shell e retorna origem anterior quando modal nao e preenchido', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'contrato' => 'Projetos',
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->set('data.contrato', 'Shell')
        ->assertActionMounted('registrarNegociacaoShell')
        ->setActionData([
            'shell_cabe_como_negociacao' => null,
            'shell_justificativa_negociacao' => '',
        ])
        ->callMountedAction()
        ->assertHasActionErrors([
            'shell_cabe_como_negociacao' => 'required',
            'shell_justificativa_negociacao' => 'required',
        ]);

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'contrato' => 'Projetos',
        'shell_cabe_como_negociacao' => false,
        'shell_justificativa_negociacao' => null,
    ]);
});

it('bloqueia fechamento manual do modal shell antes da confirmacao', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'contrato' => 'Projetos',
    ]);

    $component = Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->set('data.contrato', 'Shell')
        ->assertActionMounted('registrarNegociacaoShell');

    $action = $component->instance()->getMountedAction();

    expect($action)->not->toBeNull()
        ->and($action->hasModalCloseButton())->toBeFalse()
        ->and($action->isModalClosedByClickingAway())->toBeFalse()
        ->and($action->isModalClosedByEscaping())->toBeFalse()
        ->and($action->getModalCancelAction())->toBeNull();
});

it('exibe e permite editar dados de negociacao quando asa e shell', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'contrato' => 'Shell',
        'shell_cabe_como_negociacao' => true,
        'shell_justificativa_negociacao' => 'Justificativa inicial.',
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->assertFormFieldExists(
            'shell_cabe_como_negociacao',
            fn ($field): bool => $field instanceof ToggleButtons
                && $field->getLabel() === 'Cabe negociação com proprietário',
        )
        ->assertFormFieldVisible('shell_cabe_como_negociacao')
        ->assertFormFieldVisible('shell_justificativa_negociacao')
        ->assertFormSet([
            'shell_cabe_como_negociacao' => true,
            'shell_justificativa_negociacao' => 'Justificativa inicial.',
        ])
        ->set('data.shell_cabe_como_negociacao', false)
        ->set('data.shell_justificativa_negociacao', 'Justificativa revisada.');

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'contrato' => 'Shell',
        'shell_cabe_como_negociacao' => false,
        'shell_justificativa_negociacao' => 'Justificativa revisada.',
    ]);
});

it('limpa e oculta dados de negociacao quando asa deixa de ser shell', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'contrato' => 'Shell',
        'shell_cabe_como_negociacao' => true,
        'shell_justificativa_negociacao' => 'Justificativa Shell.',
    ]);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->assertFormFieldVisible('shell_cabe_como_negociacao')
        ->assertFormFieldVisible('shell_justificativa_negociacao')
        ->set('data.contrato', 'Projetos')
        ->assertFormFieldIsHidden('shell_cabe_como_negociacao')
        ->assertFormFieldIsHidden('shell_justificativa_negociacao')
        ->assertSet('data.shell_cabe_como_negociacao', false)
        ->assertSet('data.shell_justificativa_negociacao', null);

    $this->assertDatabaseHas('autorizacao_servico_adicionais', [
        'id' => $asa->id,
        'contrato' => 'Projetos',
        'shell_cabe_como_negociacao' => false,
        'shell_justificativa_negociacao' => null,
    ]);
});

it('vincula ASA a controle existente sem semear as linhas padrão de escopo do CMED', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
        'Create:ControleNotaFiscal',
        'Update:ControleNotaFiscal',
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $construtora = createConstrutoraRecord();

    createAsEscopoRecord(['grupo' => 'Grupo 01', 'numero_as' => 'AS-001', 'escopo' => 'Escopo 01']);
    createAsEscopoRecord(['grupo' => 'Grupo 02', 'numero_as' => 'AS-002', 'escopo' => 'Escopo 02']);
    createAsEscopoRecord(['grupo' => 'Grupo 03', 'numero_as' => 'AS-003', 'escopo' => 'Escopo 03']);

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);

    $asa = createAsaRecord($user, [
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'status' => 'aprovado',
        'contrato' => 'Projetos',
        'codigo_as_emitida' => 'AS-EXTRA-001',
        'descricao' => 'Adicional isolado da ASA',
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->assertActionVisible('selecionarControleNotaFiscal')
        ->mountAction('selecionarControleNotaFiscal')
        ->assertActionMounted('selecionarControleNotaFiscal')
        ->setActionData([
            'controle_nota_fiscal_id' => (string) $controle->id,
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $controle->refresh();

    expect($controle)
        ->not->toBeNull()
        ->and($controle?->itens()->count())->toBe(0)
        ->and($controle?->auxiliares()->count())->toBe(1)
        ->and($controle?->auxiliares()->first()?->escopo)->toBe('Adicional isolado da ASA')
        ->and($asa->refresh()->controle_nota_fiscal_auxiliar_id)->toBe($controle?->auxiliares()->first()?->id);
});

it('cria linha auxiliar da asa shell com percentuais calculados do aditivo', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
        'Create:ControleNotaFiscal',
    ]);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $construtora = createConstrutoraRecord();

    createAsEscopoRecord([
        'grupo' => 'Shell',
        'numero_as' => 'AS-SHELL-PERC',
        'escopo' => 'Shell negociação',
        'percentual_faturamento_mao_obra_default' => 70,
        'percentual_faturamento_material_default' => 30,
    ]);

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);

    $aditivo->itens()->create([
        'item' => '1',
        'descricao_servico' => 'Mao de obra e material ASA',
        'quantidade' => 5,
        'unidade' => 'un',
        'valor_material_unitario' => 70,
        'valor_mao_obra_unitario' => 130,
        'total_unitario' => 200,
        'valor_total_geral' => 1000,
    ]);

    $asa = createAsaRecord($user, [
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'status' => 'aprovado',
        'contrato' => 'Shell',
        'codigo_as_emitida' => 'AS-SHELL-001',
        'descricao' => 'Shell negociação',
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $controle = ControleNotaFiscal::create([
        'elaboracao_aditivo_id' => $aditivo->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => $asa->sigla,
        'endereco' => $obra->endereco,
    ]);

    $component = Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])->instance();
    $method = new ReflectionMethod($component, 'syncAsaAuxiliarRow');
    $method->setAccessible(true);
    $method->invoke($component, $controle, $asa);

    $auxiliar = $controle->auxiliares()->sole();

    expect($auxiliar->percentual_faturamento_mao_obra)->toBe('65.00')
        ->and($auxiliar->percentual_faturamento_material)->toBe('35.00');
});

it('localiza controle de ampliacao da unidade mesmo com sigla desatualizada na asa', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $projeto = createResourceProjeto($user, [
        'sigla' => 'PRJ-ASA-CONTROLE',
        'nome' => 'Projeto ASA Controle',
    ]);
    $obra = createObraRecord($user, [
        'projeto_id' => $projeto->id,
        'unidade' => $projeto->nome,
    ]);
    $construtora = createConstrutoraRecord();
    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);
    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $controle->forceFill([
        'elaboracao_aditivo_id' => $aditivo->id,
        'construtora_id' => $construtora->id,
        'sigla' => $projeto->sigla,
    ])->save();
    $asa = createAsaRecord($user, [
        'projeto_id' => $projeto->id,
        'sigla' => 'SIGLA-DESATUALIZADA',
        'endereco' => $obra->endereco,
        'status' => 'aprovado',
        'contrato' => 'Projetos',
        'codigo_as_emitida' => 'AS-SIGLA-PROJETO',
        'descricao' => 'Adicional com sigla do projeto',
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $component = Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])->instance();
    $method = new ReflectionMethod($component, 'findControleNotaFiscalForAsa');
    $method->setAccessible(true);

    $controleResolvido = $method->invoke($component, $asa);

    expect($controleResolvido)->toBeInstanceOf(ControleNotaFiscal::class)
        ->and($controleResolvido->obra_id)->toBe($obra->id)
        ->and($controleResolvido->sigla)->toBe($projeto->sigla)
        ->and($controleResolvido->sigla)->not->toBe($asa->sigla);
});

it('nao cria controle de nota fiscal automaticamente ao aprovar asa', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $obra->controlesNotaFiscal()->delete();

    $construtora = createConstrutoraRecord();
    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);
    $asa = createAsaRecord($user, [
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'status' => 'aprovado',
        'contrato' => 'Shell',
        'codigo_as_emitida' => 'AS-SEM-CONTROLE',
        'descricao' => 'ASA sem controle',
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $component = Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])->instance();
    $method = new ReflectionMethod($component, 'findControleNotaFiscalForAsa');
    $method->setAccessible(true);

    expect($method->invoke($component, $asa))->toBeNull()
        ->and(ControleNotaFiscal::query()->where('obra_id', $obra->id)->exists())->toBeFalse();
});

it('envia email para engenharia quando asa e criada a partir do aditivo', function () {
    Mail::fake();

    $user = createResourceBaselineUser([
        'ViewAny:ElaboracaoAditivo',
        'View:ElaboracaoAditivo',
    ]);
    $engenharia = createActiveUserWithPermissions([], [
        'name' => 'Engenharia ASA Email',
        'email' => 'engenharia.asa@example.test',
    ]);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $obra->update(['engenharia' => $engenharia->name]);
    $construtora = createConstrutoraRecord();
    $escopo = createAsEscopoRecord([
        'grupo' => 'Projeto',
        'numero_as' => 'AS-EMAIL-01',
        'escopo' => 'Projeto executivo email',
    ]);
    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'gestor_id' => $engenharia->id,
        'as_escopo_id' => $escopo->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'elaboracao',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);
    $aditivo->itens()->create([
        'item' => '1',
        'descricao_servico' => 'Servico adicional',
        'quantidade' => 1,
        'unidade' => 'un',
        'valor_material_unitario' => 100,
        'valor_mao_obra_unitario' => 200,
        'total_unitario' => 300,
        'valor_total_geral' => 300,
    ]);

    Livewire::test(ViewElaboracaoAditivoCustom::class, ['record' => $aditivo->getRouteKey()])
        ->callAction('criarAsa', [
            'justificativa' => 'Necessario aprovar adicional.',
        ])
        ->assertHasNoActionErrors();

    $asa = Asa::query()->where('elaboracao_aditivo_id', $aditivo->id)->firstOrFail();

    Mail::assertSent(EnviarPdfMail::class, function (EnviarPdfMail $mail) use ($asa, $engenharia): bool {
        return $mail->hasTo($engenharia->email)
            && $mail->assunto === 'Nova ASA criada '.$asa->numero_asa
            && str_contains($mail->mensagemEmail, 'criou a ASA');
    });
});

it('vincula asa ao controle de expansao quando gestor aprova e envia para orcamento', function () {
    Mail::fake();

    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ]);
    setGestorObras($user);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $orcamentista = createActiveUserWithPermissions([], [
        'email' => 'orcamentista.asa@example.test',
    ]);
    $obra->projeto()->update(['user_id' => $orcamentista->id]);

    $construtora = createConstrutoraRecord();
    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'em_aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);
    $asa = createAsaRecord($user, [
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'status' => AsStatus::SOLICITADO,
        'contrato' => 'Projeto',
        'codigo_as_emitida' => 'AS-GESTOR-01',
        'descricao' => 'ASA liberada para orçamento',
        'solicitante' => null,
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->callAction('aprovar')
        ->assertHasNoActionErrors();

    $asa->refresh();
    $aditivo->refresh();

    expect($asa->status)->toBe(AsStatus::EM_APROVACAO_ORCAMENTO)
        ->and($asa->controle_nota_fiscal_auxiliar_id)->not->toBeNull()
        ->and($aditivo->status_fluxo)->toBe('em_aprovacao_orcamento')
        ->and($aditivo->aprovado_gestor_por_id)->toBe($user->id)
        ->and($aditivo->aprovado_orcamento_por_id)->toBeNull();

    $auxiliar = ControleNotaFiscalAuxiliar::query()->findOrFail($asa->controle_nota_fiscal_auxiliar_id);

    expect($auxiliar->controle_nota_fiscal_id)->toBe($controle->id)
        ->and($auxiliar->numero_as)->toBe('AS-GESTOR-01')
        ->and($auxiliar->escopo)->toBe('ASA liberada para orçamento')
        ->and($auxiliar->empresa)->toBe($construtora->nome)
        ->and($auxiliar->liberado_para_fornecedor_at)->toBeNull();

    expect(DB::table('notifications')
        ->where('notifiable_type', $orcamentista->getMorphClass())
        ->where('notifiable_id', $orcamentista->id)
        ->where('data->title', 'ASA aguardando aprovação do orçamento')
        ->exists())->toBeTrue();

    Mail::assertSent(EnviarPdfMail::class, function (EnviarPdfMail $mail) use ($asa, $orcamentista): bool {
        return $mail->hasTo($orcamentista->email)
            && $mail->assunto === 'ASA aguardando aprovação do orçamento '.$asa->numero_asa
            && str_contains($mail->mensagemEmail, 'aguarda aprovação do orçamentista');
    });
});

it('resolve asa para o controle de ampliacao da unidade e ignora retrofit', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $construtora = createConstrutoraRecord();
    $controleExpansao = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $controleRetrofit = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'tipo_unidade' => TipoUnidade::RETROFIT->value,
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'unidade' => $obra->unidade,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
    ]);

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);
    $asa = createAsaRecord($user, [
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'status' => 'aprovado',
        'contrato' => 'Shell',
        'codigo_as_emitida' => 'AS-EXPANSAO',
        'descricao' => 'ASA vinculada ao controle de ampliacao',
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $component = Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])->instance();
    $method = new ReflectionMethod($component, 'findControleNotaFiscalForAsa');
    $method->setAccessible(true);

    $controleResolvido = $method->invoke($component, $asa);

    expect($controleResolvido)->toBeInstanceOf(ControleNotaFiscal::class)
        ->and($controleResolvido->id)->toBe($controleExpansao->id)
        ->and($controleResolvido->id)->not->toBe($controleRetrofit->id);
});

it('resolve o solicitante da aprovação pelo autor do aditivo', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $obra = createObraRecord($user);
    $construtora = createConstrutoraRecord();

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);

    $asa = createAsaRecord($user, [
        'status' => AsStatus::SOLICITADO,
        'solicitante' => null,
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $component = Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])->instance();
    $method = new ReflectionMethod($component, 'resolveSolicitanteFromAditivoAuthor');
    $method->setAccessible(true);

    expect($method->invoke($component, $asa->fresh()))->toBe($user->name);
});

it('usa mensagem especifica quando falta apenas a origem da solicitação da ASA', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
    ]);

    $this->actingAs($user);

    $asa = createAsaRecord($user);

    $component = Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])->instance();
    $method = new ReflectionMethod($component, 'missingApprovalFieldsMessage');
    $method->setAccessible(true);

    expect($method->invoke($component, null, $user->name))
        ->toBe('Informe a origem da solicitação da ASA antes de aprovar.');
});
