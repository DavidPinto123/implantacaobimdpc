<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Filament\Pages\AprovacaoNotasFiscaisPage;
use App\Filament\Pages\CadastrarPonto;
use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Filament\Resources\Asas\Pages\EditAsa;
use App\Filament\Resources\AutorizacaoServicos\Pages\ControleAutorizacoesServico;
use App\Filament\Resources\ControleNotaFiscals\Pages\EditControleNotaFiscal;
use App\Filament\Resources\ImportacaoNotaFiscals\Pages\CreateImportacaoNotaFiscal;
use App\Filament\Resources\Obras\Pages\CreateObras;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalNota;
use App\Models\ElaboracaoAditivo;
use App\Models\Etapa;
use App\Models\Marca;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\Setor;
use App\Services\AutorizacaoServicoPdfService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    config(['filesystems.media_disk' => 'r2']);

    setupFilamentResourceCoverageForTests($this);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake('r2');
    Mail::fake();
    ensureDefaultRoles();
});

it('cobre o fluxo por feature do ponto ate importacao de notas fiscais de AS e ASA', function (): void {
    /*
     * Fluxo:
     * 1. Comercial cadastra o ponto e cria o projeto.
     * 2. Projeto recebe CNPJ definitivo e gestor, então o gestor cria a obra.
     * 2.1. A atribuição de CNPJ definitivo/provisório é feita por usuário não definido no momento que recebe as permissões adequadas por interface do shield.
     * 3. A obra cria automaticamente o Controle de Nota Fiscal de Expansão.
     * 4. O controle é preenchido com escopos ativos e não personalizados.
     * 4.1. qualquer hipótese que crie um controle de expansão deve preencher as linhas com a regra de escopos ativos e não personalizados, a regra de unicidade é um controle para cada tipo de obra.
     * 5. Orçamentista cria a AS pelo Controle AS informando valor estimado e desconto.
     * 5.1. No Controle AS deve ser a centralização onde o orçamentista configura o AS e autoriza/envia para fornecedor AS/ASA, deve ser testado as formulas para garantir que nao sejam alteradas pro acidente.
     * 5.2. Deve ser possivel editar os valores quando AS é rascunho ou criada (com modal para ajustar alguns valores e regerar pdf)
     * 6. A AS calcula o valor fechado, herda percentuais do escopo, permite sobrescrever percentuais, preenche datas, itens/anexos e gera PDF.
     * 6.1. Existe o botao "Simulação OI", a Simulação é outro fluxo que nao devemos mexer e esta vinculado a um Projeto, ao acionar esse botao deve automaticamente pegar a simulação Aprovada e preencher os valores "Estimados" das linhas de escopo correspondentes a AS. Essa atualização de valor deve refletir na tela sem recarregar a pagina.
     * 7. A AS criada pode ser editada antes do envio e a edição recalcula valores/datas e regenera o PDF.
     * 8. Enviar AS abre modal com fornecedor em Para e gestor em CC, envia e libera a linha para o fornecedor.
     * 9. Fornecedor cria elaboração de aditivo, gestor aprova a ASA e orçamentista aprova/envia a ASA pelo Controle AS.
     * 10. Enviar ASA usa o mesmo padrão do modal de e-mail e libera o adicional para o fornecedor.
     * 11. Gestor vê AS e ASA enviadas no Controle de NF.
     * 12. Fornecedor vê AS e ASA em Meus controles de NF com link de importação.
     * 13. Fornecedor importa notas fiscais para AS e ASA, ambas entram em análise e ficam vinculadas ao destino fiscal correto.
     * 14. Orçamentista aprova nota fiscal na tela de aprovação de notas fiscais.
     * Obs: toda ação que "troca" o agente como aprovar, enviar, importar documento deve notificar via email e no aplicativo com link o usuario da proxima ação
     */
    $comercial = createActiveUserWithPermissions(['View:CadastrarPonto']);
    $comercial->assignRole('Comercial');
    $comercial->setores()->syncWithoutDetaching([Setor::firstOrCreate(['setor' => 'Comercial'])->id]);

    $gestor = createActiveUserWithPermissions([
        'Create:Obras',
        'ViewAny:Obras',
        'View:Obras',
        'Update:Obras',
        'ViewAny:Asa',
        'View:Asa',
        'Update:Asa',
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], [
        'name' => 'Gestor Fluxo Fiscal',
        'email' => 'gestor.fluxo@example.test',
    ]);
    setGestorObras($gestor);

    $orcamentista = createActiveUserWithPermissions([
        'Create:AutorizacaoServico',
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
        'View:AprovacaoNotasFiscaisPage',
        'Update:ControleNotaFiscalNota',
    ], [
        'email' => 'orcamentista.fluxo@example.test',
    ]);

    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Fluxo Fiscal',
        'cnpj' => '12.345.678/0001-95',
        'email' => 'fornecedor.fluxo@example.test',
        'tipo' => 'CONSTRUTORA',
    ]);
    $fornecedorUser = createActiveUserWithPermissions([
        'View:ConstrutoraControlesNotaFiscalPage',
        'ViewAny:ControleNotaFiscalNota',
        'Create:ControleNotaFiscalNota',
    ], [
        'email' => 'fornecedor.fluxo@example.test',
        'construtoras_id' => $fornecedor->id,
        'is_fornecedor' => true,
    ]);
    $fornecedorUser->assignRole(Role::findOrCreate('Fornecedor', 'web'));

    $asEscopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => 'AS-FLUXO',
        'escopo' => 'Escopo fluxo fiscal',
        'percentual_faturamento_mao_obra_default' => 72.5,
        'percentual_faturamento_material_default' => 27.5,
        'is_active' => true,
        'is_personalizado' => false,
    ]);

    ['pais' => $pais, 'estado' => $estado, 'cidade' => $cidade] = createLocationDependencies();
    Etapa::firstOrCreate(['nome' => 'Prospecção']);
    Marca::firstOrCreate(['nome' => 'Smart Fit']);

    $this->actingAs($comercial);

    Livewire::test(CadastrarPonto::class)
        ->fillForm([
            'codigo' => 'PONTO-FLUXO',
            'nome' => 'Ponto Fluxo Fiscal',
            'marca' => 'Smart Fit',
            'data_posse' => now()->addDays(10)->toDateString(),
            'cep' => '01000-000',
            'rua' => 'Rua Fluxo',
            'numero' => '100',
            'bairro' => 'Centro',
            'pais_id' => $pais->id,
            'estado_id' => $estado->id,
            'cidade_id' => $cidade->id,
            'area_academia' => 1200,
            'cad_status' => 'nao',
            'vis_status' => 'nao',
            'legal_status_consulta_prev' => 'nao',
            'evtl_status' => 'nao',
        ])
        ->call('create', true)
        ->assertHasNoFormErrors();

    $projeto = Projeto::query()->where('codigo', 'PONTO-FLUXO')->firstOrFail();
    $projeto->forceFill([
        'status_cnpj' => 'definitivo',
        'cnpj' => '98.765.432/0001-10',
        'resp_eng' => $gestor->id,
    ])->save();

    $this->actingAs($gestor);

    Livewire::test(CreateObras::class)
        ->fillForm([
            'projeto_id' => $projeto->id,
            'status' => 'Obras',
            'unidade' => $projeto->nome,
            'codigo' => 'OBRA-FLUXO',
            'engenharia' => $gestor->name,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $obra = Obras::query()->where('projeto_id', $projeto->id)->firstOrFail();
    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $item = $controle->itens()->where('as_escopo_id', $asEscopo->id)->firstOrFail();

    expect($item->percentual_faturamento_mao_obra)->toBe('72.50')
        ->and($item->percentual_faturamento_material)->toBe('27.50');

    Storage::disk('r2')->put('autorizacao-servico/tmp/descricao/item-fluxo.png', 'item');
    Storage::disk('r2')->put('autorizacao-servico/tmp/anexos/anexo-fluxo.pdf', 'anexo');

    $this->actingAs($orcamentista);

    $controleAsComponent = Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $asEscopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '1.500,00',
            'percentual_faturamento_mao_obra' => '55,56',
            'percentual_faturamento_material' => '44,44',
        ])
        ->assertSet('gerarAsModalItemId', $item->id)
        ->fillForm([
            'data_inicio_servico' => '2026-05-10',
            'data_termino_servico' => '2026-06-10',
            'data_entrega_material' => '2026-05-25',
        ], 'gerarAsDatasForm')
        ->fillForm([
            'desconto_autorizacao_servico' => '250,00',
            'parcelamento' => [[
                'parcela' => 'Parcela 01',
                'percentual' => '100,00',
                'valor' => '1.250,00',
                'observacao' => 'Parcela unica',
            ]],
        ], 'gerarAsValoresParcelamentoForm')
        ->fillForm([
            'descricao_servico_pdf' => 'AS gerada pelo fluxo fiscal automatizado.',
            'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/item-fluxo.png'],
        ], 'gerarAsDescricaoForm')
        ->fillForm([
            'anexos_autorizacao_servico' => ['autorizacao-servico/tmp/anexos/anexo-fluxo.pdf'],
        ], 'gerarAsAnexosForm')
        ->call('confirmarGeracaoAs')
        ->assertNotified()
        ->assertSet('gerarAsModalItemId', null);

    $item->refresh();
    $autorizacaoServico = $item->autorizacaoServico()->firstOrFail();

    expect($item->valor_estimado_as)->toBe('1500.00')
        ->and($item->valor_global_a)->toBe('1250.00')
        ->and($item->total_medicao_a_menos_b)->toBe('1250.00')
        ->and($item->saldo)->toBe('1250.00')
        ->and($item->percentual_faturamento_mao_obra)->toBe('55.56')
        ->and($item->percentual_faturamento_material)->toBe('44.44')
        ->and($autorizacaoServico->valor_estimado)->toBe('1500.00')
        ->and($autorizacaoServico->desconto_autorizacao_servico)->toBe('250.00')
        ->and($autorizacaoServico->valor)->toBe('1250.00')
        ->and($autorizacaoServico->data_inicio_servico?->toDateString())->toBe('2026-05-10')
        ->and($autorizacaoServico->data_termino_servico?->toDateString())->toBe('2026-06-10')
        ->and($autorizacaoServico->data_entrega_material?->toDateString())->toBe('2026-05-25')
        ->and($autorizacaoServico->anexos_autorizacao_servico)->toBe(['autorizacao-servico/tmp/anexos/anexo-fluxo.pdf'])
        ->and($autorizacaoServico->anexo_autorizacao_servico)->not->toBeEmpty();

    expect($autorizacaoServico->itens_descricao_servico_pdf[0]['descricao_tipo'])->toBe('arquivo')
        ->and($autorizacaoServico->itens_descricao_servico_pdf[0]['descricao'])->toBe('AS gerada pelo fluxo fiscal automatizado.')
        ->and($autorizacaoServico->itens_descricao_servico_pdf[0]['descricao_arquivo'])->toBe(['autorizacao-servico/tmp/descricao/item-fluxo.png']);

    Storage::disk('r2')->assertExists($autorizacaoServico->anexo_autorizacao_servico);

    $pdfData = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico);

    expect($pdfData['percentualFaturamentoMaoObra'])->toBe(55.56)
        ->and($pdfData['percentualFaturamentoMaterial'])->toBe(44.44)
        ->and($pdfData['itensDescricaoServicoPdf'][0]['descricao_arquivo'])->toBe(['autorizacao-servico/tmp/descricao/item-fluxo.png']);

    $pdfPath = $autorizacaoServico->anexo_autorizacao_servico;
    Storage::disk('r2')->put($pdfPath, 'pdf anterior');

    $controleAsComponent
        ->call('editarAsComDados', $item->id, [
            'valor_estimado' => '1.600,00',
        ])
        ->assertSet('gerarAsModalEdicao', true)
        ->assertSet('gerarAsModalItemId', $item->id)
        ->fillForm([
            'data_inicio_servico' => '2026-05-12',
            'data_termino_servico' => '2026-06-12',
            'data_entrega_material' => '2026-05-27',
        ], 'gerarAsDatasForm')
        ->fillForm([
            'valor_inicial' => '1.600,00',
            'desconto_autorizacao_servico' => '100,00',
            'parcelamento' => [[
                'parcela' => 'Parcela 01',
                'percentual' => '100,00',
                'valor' => '1.500,00',
                'observacao' => 'Parcela editada',
            ]],
        ], 'gerarAsValoresParcelamentoForm')
        ->fillForm([
            'descricao_servico_pdf' => 'AS editada pelo fluxo fiscal automatizado.',
            'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/item-fluxo.png'],
        ], 'gerarAsDescricaoForm')
        ->call('confirmarGeracaoAs')
        ->assertNotified()
        ->assertSet('gerarAsModalItemId', null);

    $item->refresh();
    $autorizacaoServico->refresh();

    expect($item->valor_estimado_as)->toBe('1600.00')
        ->and($item->valor_global_a)->toBe('1500.00')
        ->and($item->total_medicao_a_menos_b)->toBe('1500.00')
        ->and($item->saldo)->toBe('1500.00')
        ->and($autorizacaoServico->valor_estimado)->toBe('1600.00')
        ->and($autorizacaoServico->desconto_autorizacao_servico)->toBe('100.00')
        ->and($autorizacaoServico->valor)->toBe('1500.00')
        ->and($autorizacaoServico->data_inicio_servico?->toDateString())->toBe('2026-05-12')
        ->and($autorizacaoServico->data_termino_servico?->toDateString())->toBe('2026-06-12')
        ->and($autorizacaoServico->data_entrega_material?->toDateString())->toBe('2026-05-27')
        ->and($autorizacaoServico->descricao_servico_pdf)->toBe('AS editada pelo fluxo fiscal automatizado.')
        ->and($autorizacaoServico->anexo_autorizacao_servico)->toBe($pdfPath);

    Storage::disk('r2')->assertExists($pdfPath);
    expect(Storage::disk('r2')->get($pdfPath))->not->toBe('pdf anterior');

    $controleAsComponent
        ->mountAction('enviarAs', [
            'itemId' => $item->id,
        ])
        ->assertActionDataSet([
            'para' => ['fornecedor.fluxo@example.test'],
            'cc' => ['gestor.fluxo@example.test'],
            'cco' => ['orcamentista.fluxo@example.test'],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified();

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $fornecedorUser->id,
        'obra_id' => $obra->id,
        'construtora_id' => $fornecedor->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'em_aprovacao_gestor',
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);
    $asa = Asa::create([
        'numero_asa' => 'ASA-FLUXO-001',
        'projeto_id' => $projeto->id,
        'sigla' => $projeto->sigla,
        'endereco' => $projeto->endereco,
        'status' => AsStatus::SOLICITADO,
        'contrato' => 'Projeto',
        'codigo_as_emitida' => 'ASA-FLUXO-001',
        'data_solicitacao' => now()->toDateString(),
        'objeto' => 'ASA fluxo',
        'descricao' => 'Adicional fluxo fiscal',
        'valor_bruto' => 800,
        'desconto' => 0,
        'valor_total' => 800,
        'gestor_id' => $gestor->id,
        'solicitante' => $fornecedor->nome,
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $this->actingAs($gestor);

    Livewire::test(EditAsa::class, ['record' => $asa->getRouteKey()])
        ->callAction('aprovar')
        ->assertHasNoActionErrors();

    $asa->refresh();
    $auxiliar = $asa->controleNotaFiscalAuxiliar()->firstOrFail();

    $this->actingAs($orcamentista);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->call('abrirModalGerarAsAsa', $auxiliar->id)
        ->fillForm([
            'desconto_autorizacao_servico' => '0,00',
        ], 'gerarAsValoresParcelamentoForm')
        ->fillForm([
            'descricao_servico_pdf' => 'AS adicional fluxo fiscal',
        ], 'gerarAsDescricaoForm')
        ->call('confirmarGeracaoAs', [[
            'parcela' => 'Parcela 01',
            'percentual' => '100,00',
            'valor' => number_format((float) ($asa->valor_total ?? 0), 2, ',', '.'),
            'observacao' => '',
        ]])
        ->assertNotified()
        ->mountAction('enviarAsa', [
            'auxiliarId' => $auxiliar->id,
        ])
        ->assertActionDataSet([
            'para' => ['fornecedor.fluxo@example.test'],
            'cc' => ['gestor.fluxo@example.test'],
            'cco' => ['orcamentista.fluxo@example.test'],
            'modo_excel_asa' => 'existente',
        ])
        ->setActionData([
            'para' => ['fornecedor.fluxo@example.test'],
            'cc' => ['gestor.fluxo@example.test'],
            'cco' => [],
            'modo_excel_asa' => 'gerar',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified();

    $this->actingAs($gestor);

    $gestorControleRows = Livewire::test(EditControleNotaFiscal::class, ['record' => $controle->getRouteKey()])
        ->get('sheetRows');

    expect(collect($gestorControleRows)->where('source', 'item')->where('id', $item->id)->first())
        ->not->toBeNull()
        ->and(collect($gestorControleRows)->where('source', 'auxiliar')->where('id', $auxiliar->id)->first())
        ->not->toBeNull();

    $this->actingAs($fornecedorUser);

    $fornecedorControleRows = Livewire::test(ConstrutoraControlesNotaFiscalPage::class)
        ->set('selectedObraId', (string) $obra->id)
        ->get('sheetRows');

    $fornecedorItemRow = collect($fornecedorControleRows)->where('source', 'item')->where('id', $item->id)->first();
    $fornecedorAuxiliarRow = collect($fornecedorControleRows)->where('source', 'auxiliar')->where('id', $auxiliar->id)->first();

    expect($fornecedorItemRow)
        ->not->toBeNull()
        ->and($fornecedorItemRow['importacao_url'])->not->toBeNull()
        ->and($fornecedorAuxiliarRow)->not->toBeNull()
        ->and($fornecedorAuxiliarRow['importacao_url'])->not->toBeNull();

    Livewire::withQueryParams([
        'obra_id_lookup' => (string) $obra->id,
        'asa_id_lookup' => (string) $autorizacaoServico->id,
    ])->test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
            'empresa' => $fornecedor->nome,
            'cnpj_fornecedor' => '12.345.678/0001-95',
            'numero_nf' => '900001',
            'cnpj_faturamento' => $projeto->cnpj,
            'valor_acumulado_medido_nf' => 100,
            'emissao' => now()->toDateString(),
            'instrucoes_pagamento' => 'pix',
            'arquivo_path' => UploadedFile::fake()->create('nota-as.pdf', 10, 'application/pdf'),
            'observacoes' => 'Nota fiscal AS fluxo.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::withQueryParams([
        'obra_id_lookup' => (string) $obra->id,
        'asa_id_lookup' => 'asa:'.$asa->id,
    ])->test(CreateImportacaoNotaFiscal::class)
        ->fillForm([
            'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MATERIAL,
            'empresa' => $fornecedor->nome,
            'cnpj_fornecedor' => '12.345.678/0001-95',
            'numero_nf' => '900002',
            'cnpj_faturamento' => $projeto->cnpj,
            'valor_acumulado_medido_nf' => 200,
            'emissao' => now()->toDateString(),
            'instrucoes_pagamento' => 'pix',
            'arquivo_path' => UploadedFile::fake()->create('nota-asa.pdf', 10, 'application/pdf'),
            'observacoes' => 'Nota fiscal ASA fluxo.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $notaAs = ControleNotaFiscalNota::query()->where('numero_nf', '900001')->sole();
    $notaAsa = ControleNotaFiscalNota::query()->where('numero_nf', '900002')->sole();

    $this->actingAs($orcamentista);

    Livewire::test(AprovacaoNotasFiscaisPage::class)
        ->call('selectControle', $controle->id)
        ->call('marcarNotaComoVisualizada', $notaAs->id)
        ->call('aprovar', $notaAs->id)
        ->call('marcarNotaComoVisualizada', $notaAsa->id)
        ->call('aprovar', $notaAsa->id);

    expect($autorizacaoServico->refresh()->status)->toBe(AsStatus::ENVIADA)
        ->and($item->refresh()->liberado_para_fornecedor_at)->not->toBeNull()
        ->and($asa->refresh()->status)->toBe(AsStatus::ENVIADA)
        ->and($auxiliar->refresh()->liberado_para_fornecedor_at)->not->toBeNull()
        ->and(ControleNotaFiscalNota::query()
            ->where(function ($query) use ($autorizacaoServico, $asa): void {
                $query
                    ->where('autorizacao_servico_id', $autorizacaoServico->id)
                    ->orWhere('autorizacao_servico_adicional_id', $asa->id);
            })
            ->count())->toBe(2)
        ->and(ControleNotaFiscalNota::query()->where('numero_nf', '900001')->sole())
        ->autorizacao_servico_id->toBe($autorizacaoServico->id)
        ->autorizacao_servico_adicional_id->toBeNull()
        ->status->toBe(StatusControleNotaFiscalNota::APROVADO->value)
        ->and(ControleNotaFiscalNota::query()->where('numero_nf', '900002')->sole())
        ->autorizacao_servico_adicional_id->toBe($asa->id)
        ->autorizacao_servico_id->toBeNull()
        ->status->toBe(StatusControleNotaFiscalNota::APROVADO->value);

    Mail::assertSent(EnviarPdfMail::class, fn (EnviarPdfMail $mail): bool => $mail->assunto === 'Nova nota fiscal para aprovação 900001');
    Mail::assertSent(EnviarPdfMail::class, fn (EnviarPdfMail $mail): bool => $mail->assunto === 'Nova nota fiscal para aprovação 900002');
    Mail::assertSent(EnviarPdfMail::class, fn (EnviarPdfMail $mail): bool => $mail->assunto === 'Nota fiscal aprovada 900001');
    Mail::assertSent(EnviarPdfMail::class, fn (EnviarPdfMail $mail): bool => $mail->assunto === 'Nota fiscal aprovada 900002');

    $orcamentistaNotifications = DB::table('notifications')
        ->where('notifiable_type', $orcamentista->getMorphClass())
        ->where('notifiable_id', $orcamentista->id)
        ->where('data->title', 'Nova nota fiscal para aprovação')
        ->count();

    $fornecedorNotifications = DB::table('notifications')
        ->where('notifiable_type', $fornecedorUser->getMorphClass())
        ->where('notifiable_id', $fornecedorUser->id)
        ->where('data->title', 'Nota fiscal aprovada')
        ->count();

    expect($orcamentistaNotifications)->toBeGreaterThanOrEqual(2)
        ->and($fornecedorNotifications)->toBeGreaterThanOrEqual(2);
});
