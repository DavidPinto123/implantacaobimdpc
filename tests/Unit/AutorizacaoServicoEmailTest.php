<?php

use App\Enums\AsStatus;
use App\Mail\AutorizacaoServicoMail;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Services\AutorizacaoServicoFluxoService;
use App\Services\AutorizacaoServicoPdfService;
use Database\Factories\AsEscopoFactory;
use Database\Factories\ControleNotaFiscalFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Database\Factories\ObrasFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    config(['filesystems.media_disk' => 'r2']);
});

it('gera e armazena o pdf da autorizacao de servico com nome previsivel', function () {
    Storage::fake('r2');

    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'numero_as' => 'SBRPAIMRB02-SF-EXP-01.1',
        'numero_complemento' => 'C1',
    ]);

    $path = app(AutorizacaoServicoPdfService::class)->generateAndStorePdf($autorizacaoServico);

    expect($path)->toBe('autorizacao-servico/'.$autorizacaoServico->id.'/pdf/SBRPAIMRB02-SF-EXP-01.1.pdf')
        ->and($autorizacaoServico->refresh()->anexo_autorizacao_servico)->toBe($path);

    Storage::disk('r2')->assertExists($path);
});

it('renderiza o numero da as com mais espaco no pdf', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'numero_as' => 'SBRPAIMRB02-SF-EXP-01.1',
    ]);

    $html = view('pdf.autorizacao-servico', app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico))->render();

    expect($html)
        ->toContain('<td style="width: 50%;"><div class="box box-nowrap">SBRPAIMRB02-SF-EXP-01.1</div></td>')
        ->toContain('<td style="width: 12px;"></td>');
});

it('renderiza a faixa de logos no header do pdf', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();

    $html = view('pdf.autorizacao-servico', app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico))->render();

    expect($html)
        ->not->toContain('images/logo-smart.png')
        ->toContain('images/logos-pdf-as.png');
});

it('usa o parcelamento salvo na autorizacao de servico para montar o pdf', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'valor' => 12000,
        'parcelamento_autorizacao_servico' => [
            [
                'parcela' => 'Parcela 01',
                'percentual' => 40.0,
                'valor' => 5000.0,
                'observacao' => 'Na mobilização',
            ],
            [
                'parcela' => 'Parcela 02',
                'percentual' => 60.0,
                'valor' => 7500.0,
                'observacao' => 'Na entrega',
            ],
        ],
    ]);

    $viewData = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico);

    expect($viewData['parcelamento'])->toHaveCount(2)
        ->and($viewData['parcelamento'][0]['valor'])->toBe(5000.0)
        ->and($viewData['parcelamento'][1]['observacao'])->toBe('Na entrega');
});

it('aplica desconto salvo nos totais do pdf da autorizacao de servico', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'valor' => 12500,
        'valor_estimado' => 13000,
        'desconto_autorizacao_servico' => 500,
        'parcelamento_autorizacao_servico' => [
            [
                'parcela' => 'Parcela 01',
                'percentual' => 100.0,
                'valor' => 12500.0,
                'observacao' => 'Parcela unica',
            ],
        ],
    ]);

    $viewData = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico);

    expect($viewData['subtotal'])->toBe(13000.0)
        ->and($viewData['desconto'])->toBe(500.0)
        ->and($viewData['total'])->toBe(12500.0)
        ->and($viewData['parcelamento'][0]['valor'])->toBe(12500.0);
});

it('monta no pdf os dados de gestor fornecedor e faturamento a partir dos cadastros', function () {
    $gestorProjeto = UserFactory::new()->active()->create([
        'name' => 'Gestor Projeto PDF',
        'email' => 'gestor.projeto@example.com',
        'phone' => '(11) 4000-0001',
    ]);
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();
    $autorizacaoServico->obra->update([
        'engenharia' => 'Gestor Legado Obra',
        'unidade' => 'Unidade PDF',
    ]);
    $autorizacaoServico->obra->projeto?->update([
        'nome' => 'SMARTFIT UNIDADE PDF LTDA',
        'resp_eng' => $gestorProjeto->id,
        'pmo_nome' => 'PMO Cadastro Projeto',
        'cnpj' => '12.345.678/0001-90',
        'inscricao_estadual' => '123456789',
        'endereco' => 'Rua de Cobranca, 123',
        'cep' => '12345-678',
        'telefone' => '(11) 4000-0002',
    ]);
    $autorizacaoServico->construtora?->update([
        'nome' => 'Fornecedor Cadastro PDF',
        'cnpj' => '98.765.432/0001-10',
        'inscricao_estadual' => '987654321',
        'endereco' => 'Rua Fornecedor, 456',
        'cep' => '87654-321',
        'responsavel' => 'Responsavel Fornecedor',
        'telefone' => '(11) 4000-0003',
        'email' => 'fornecedor.pdf@example.com',
    ]);

    $html = view('pdf.autorizacao-servico', app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh()))->render();

    expect($html)
        ->toContain('Unidade PDF')
        ->toContain('Gestor Projeto PDF')
        ->toContain('(11) 4000-0001')
        ->toContain('gestor.projeto@example.com')
        ->toContain('PMO Cadastro Projeto')
        ->not->toContain('Gestor Legado Obra')
        ->toContain('Fornecedor Cadastro PDF')
        ->toContain('98.765.432/0001-10')
        ->toContain('987654321')
        ->toContain('Rua Fornecedor, 456')
        ->toContain('87654-321')
        ->toContain('Responsavel Fornecedor')
        ->toContain('(11) 4000-0003')
        ->toContain('fornecedor.pdf@example.com')
        ->toContain('SMARTFIT UNIDADE PDF LTDA')
        ->toContain('12.345.678/0001-90')
        ->toContain('123456789')
        ->toContain('Rua de Cobranca, 123')
        ->toContain('12345-678')
        ->toContain('(11) 4000-0002');
});

it('monta os percentuais de faturamento da as pelo controle de nota fiscal sem tipo de contratacao', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'tipo_contratacao' => 'material',
    ]);

    $item = $autorizacaoServico->itens()->firstOrFail();
    $item->update([
        'percentual_faturamento_mao_obra' => 55,
        'percentual_faturamento_material' => 45,
    ]);

    $viewData = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh());

    expect($viewData)->not->toHaveKeys([
        'tipoContratacao',
        'contratacaoMaoObraSelecionada',
        'contratacaoMaterialSelecionada',
    ])
        ->and($viewData['percentualFaturamentoMaoObra'])->toBe(55.0)
        ->and($viewData['percentualFaturamentoMaterial'])->toBe(45.0);
});

it('marca shell ou recheio no pdf conforme o grupo da linha do escopo', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();
    $item = $autorizacaoServico->itens()->firstOrFail();

    $item->update(['grupo' => 'Shell']);
    $viewDataShell = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh());
    $htmlShell = view('pdf.autorizacao-servico', $viewDataShell)->render();

    expect($viewDataShell['escopoShellSelecionado'])->toBeTrue()
        ->and($viewDataShell['escopoRecheioSelecionado'])->toBeFalse()
        ->and($htmlShell)->toContain('data-escopo-shell="1"')
        ->toContain('data-escopo-recheio="0"')
        ->toContain('background-color: #111');

    $item->update(['grupo' => 'Civil']);
    $viewDataRecheio = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh());
    $htmlRecheio = view('pdf.autorizacao-servico', $viewDataRecheio)->render();

    expect($viewDataRecheio['escopoShellSelecionado'])->toBeFalse()
        ->and($viewDataRecheio['escopoRecheioSelecionado'])->toBeTrue()
        ->and($htmlRecheio)->toContain('data-escopo-shell="0"')
        ->toContain('data-escopo-recheio="1"');

    $escopoShell = AsEscopoFactory::new()->create([
        'grupo' => 'Shell',
        'numero_as' => '033',
        'escopo' => 'Escopo Shell',
    ]);

    $autorizacaoServico->update(['as_escopo_id' => $escopoShell->id]);
    $itemShell = ControleNotaFiscalItemFactory::new()
        ->for($item->controleNotaFiscal, 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => $escopoShell->id,
            'grupo' => 'Shell',
            'numero_as' => $escopoShell->numero_as,
            'escopo' => $escopoShell->escopo,
        ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $itemShell->id])->save();

    $viewDataShellPorLinha = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh());

    expect($viewDataShellPorLinha['itemPrincipal']->as_escopo_id)->toBe($escopoShell->id)
        ->and($viewDataShellPorLinha['escopoShellSelecionado'])->toBeTrue()
        ->and($viewDataShellPorLinha['escopoRecheioSelecionado'])->toBeFalse();
});

it('renderiza no pdf os percentuais de mao de obra e material definidos no escopo', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'tipo_contratacao' => 'mao_obra',
        'descricao_servico_pdf' => 'Execução civil com escopo complementar validado no modal',
    ]);

    $item = $autorizacaoServico->itens()->firstOrFail();
    $item->update([
        'percentual_faturamento_mao_obra' => 55.56,
        'percentual_faturamento_material' => 44.44,
    ]);

    $html = view('pdf.autorizacao-servico', app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh()))->render();

    expect($html)
        ->not->toContain('contract-check')
        ->not->toContain('>X</span>')
        ->not->toContain('data-mao-obra-selecionada')
        ->not->toContain('data-material-selecionada')
        ->toContain('data-escopo-shell="0"')
        ->toContain('data-escopo-recheio="1"')
        ->toContain('55,56%')
        ->toContain('44,44%')
        ->toContain('EXECUÇÃO CIVIL COM ESCOPO COMPLEMENTAR VALIDADO NO MODAL');
});

it('renderiza no pdf a descricao unica do servico com imagem', function () {
    Storage::fake('r2');
    Storage::disk('r2')->put(
        'autorizacao-servico/tmp/descricao/painel.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='),
    );

    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'itens_descricao_servico_pdf' => [
            [
                'descricao_tipo' => 'arquivo',
                'descricao' => 'Painel elétrico conforme anexo',
                'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/painel.png'],
            ],
        ],
    ]);

    $html = view('pdf.autorizacao-servico', app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico))->render();

    expect($html)
        ->toContain('PAINEL ELÉTRICO CONFORME ANEXO')
        ->toContain('<img src="')
        ->toContain('data:image/png;base64,')
        ->not->toContain('autorizacao-servico/tmp/descricao/painel.png')
        ->not->toContain('ANEXO: PAINEL.PNG')
        ->not->toContain('<th style="width: 70px;">ITEM</th>')
        ->not->toContain('<th style="width: 62px;">UNIDADE</th>')
        ->not->toContain('<th style="width: 76px;">QTDE</th>')
        ->not->toContain('<th style="width: 72px;">VLR. UNIT. (R$)</th>');
});

it('nao usa datas da obra ou do item quando datas da as nao foram preenchidas', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'data_inicio_servico' => null,
        'data_termino_servico' => null,
        'data_entrega_material' => null,
    ]);
    $autorizacaoServico->obra()->update([
        'inicio' => '2026-05-10',
        'fim' => '2026-06-10',
    ]);
    $autorizacaoServico->controleNotaFiscalItem?->update([
        'data_entrega' => '2026-05-25',
    ]);

    $html = view('pdf.autorizacao-servico', app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh()))->render();

    expect($html)
        ->not->toContain('10/05/2026')
        ->not->toContain('10/06/2026')
        ->not->toContain('25/05/2026');
});

it('limita imagens da descricao rica dentro da celula do pdf da as', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'descricao_servico_pdf' => '<p>Imagem colada</p><figure><img src="https://example.com/escopo.png" style="width: 1800px; height: 900px;"></figure>',
    ]);

    $html = view('pdf.autorizacao-servico', app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh()))->render();

    expect($html)
        ->toContain('.desc-cell img')
        ->toContain('max-width: 100% !important')
        ->toContain('height: auto !important')
        ->toContain('object-fit: contain')
        ->toContain('src="https://example.com/escopo.png"');
});

it('monta o email da as com o texto padrao e anexa o pdf', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();

    $mailable = new AutorizacaoServicoMail(
        autorizacaoServico: $autorizacaoServico,
        pdfBinary: '%PDF-1.4 fake',
        nomeArquivo: 'AS-001.pdf',
        remetente: UserFactory::new()->active()->create([
            'name' => 'Elaboradora AS',
            'email' => 'elaboradora@example.com',
            'phone' => '5511999999999',
        ]),
    );

    $html = $mailable->render();

    expect($html)
        ->toContain('Prezados,')
        ->toContain('Segue anexa AS.')
        ->toContain('Emissão de notas: 1° a 18° dia de cada mês')
        ->toContain('AS NF’S DEVEM SER ENVIADAS OBRIGATORIAMENTE')
        ->toContain('gestor.as@example.com')
        ->toContain('TODO E QUALQUER FATURAMENTO')
        ->toContain('É imprescindível a leitura da autorização de serviço')
        ->toContain('Qualquer dúvida, entrar em contato.')
        ->not->toContain('Prezados, bom dia!')
        ->not->toContain('Obrigada!')
        ->and($mailable->attachments())->toHaveCount(1);
});

it('usa o usuario gestor do projeto da unidade no email da as', function () {
    $gestorObraLegado = UserFactory::new()->active()->create([
        'name' => 'Gestor Legado Obra',
        'email' => 'gestor.legado@example.com',
    ]);
    $gestorProjeto = UserFactory::new()->active()->create([
        'name' => 'Gestor Projeto Unidade',
        'email' => 'gestor.projeto@example.com',
        'phone' => '5511777777777',
    ]);
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();
    $autorizacaoServico->obra->update([
        'engenharia' => $gestorObraLegado->name,
    ]);
    $autorizacaoServico->obra->projeto?->update([
        'resp_eng' => $gestorProjeto->id,
    ]);

    $mailable = new AutorizacaoServicoMail(
        autorizacaoServico: $autorizacaoServico->refresh(),
        pdfBinary: '%PDF-1.4 fake',
        nomeArquivo: 'AS-001.pdf',
    );

    $html = $mailable->render();
    $viewData = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh());

    expect($html)
        ->toContain('gestor.projeto@example.com')
        ->not->toContain('gestor.legado@example.com')
        ->and($viewData['gestor']?->id)->toBe($gestorProjeto->id);
});

it('nao exibe bloco de email do gestor quando o projeto da unidade nao possui gestor', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();
    $autorizacaoServico->obra->update([
        'engenharia' => null,
    ]);
    $autorizacaoServico->obra->projeto?->update([
        'resp_eng' => null,
    ]);

    $mailable = new AutorizacaoServicoMail(
        autorizacaoServico: $autorizacaoServico->refresh(),
        pdfBinary: '%PDF-1.4 fake',
        nomeArquivo: 'AS-001.pdf',
    );

    expect($mailable->render())
        ->not->toContain('AS NF’S DEVEM SER ENVIADAS OBRIGATORIAMENTE')
        ->not->toContain('E-mail do gestor não informado');
});

it('envia a as por email para destinatarios validos do fornecedor com o pdf anexado', function () {
    Storage::fake('r2');
    Mail::fake();

    $usuario = UserFactory::new()->active()->create();
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio(construtoraEmail: 'fornecedor@example.com; invalido, segundo@example.com');
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
    ]);

    $enviada = app(AutorizacaoServicoFluxoService::class)->enviar(
        $autorizacaoServico->refresh(),
        $usuario,
        destinatarios: ['fornecedor@example.com', 'segundo@example.com'],
    );

    expect($enviada->status)->toBe(AsStatus::ENVIADA)
        ->and($enviada->anexo_autorizacao_servico)->not->toBeEmpty()
        ->and($enviada->enviado_por_id)->toBe($usuario->id);

    Storage::disk('r2')->assertExists($enviada->anexo_autorizacao_servico);

    Mail::assertSent(AutorizacaoServicoMail::class, function (AutorizacaoServicoMail $mail): bool {
        return $mail->hasTo('fornecedor@example.com')
            && $mail->hasTo('segundo@example.com')
            && ! $mail->hasTo('invalido')
            && count($mail->attachments()) === 1;
    });
});

it('envia a as sem anexo especial de planilha', function () {
    Storage::fake('r2');
    Mail::fake();

    $usuario = UserFactory::new()->active()->create();
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
    ]);

    app(AutorizacaoServicoFluxoService::class)->enviar(
        $autorizacaoServico->refresh(),
        $usuario,
        destinatarios: ['fornecedor@example.com'],
    );

    Mail::assertSent(AutorizacaoServicoMail::class, fn (AutorizacaoServicoMail $mail): bool => count($mail->attachments()) === 1);
});

it('envia a as por email anexando os anexos adicionais salvos na as', function () {
    Storage::fake('r2');
    Mail::fake();

    $usuario = UserFactory::new()->active()->create();
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio(construtoraEmail: 'fornecedor@example.com');
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
        'anexos_autorizacao_servico' => ['autorizacao-servico/extra/anexo-01.pdf'],
    ]);

    Storage::disk('r2')->put('autorizacao-servico/extra/anexo-01.pdf', 'anexo extra');

    $enviada = app(AutorizacaoServicoFluxoService::class)->enviar(
        $autorizacaoServico->refresh(),
        $usuario,
        destinatarios: ['fornecedor@example.com'],
    );

    expect($enviada->anexos_autorizacao_servico)->toBe(['autorizacao-servico/extra/anexo-01.pdf']);

    Mail::assertSent(AutorizacaoServicoMail::class);
});

it('monta o mailable da as com anexos adicionais', function () {
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();

    $mailable = new AutorizacaoServicoMail(
        autorizacaoServico: $autorizacaoServico,
        pdfBinary: '%PDF-1.4 fake',
        nomeArquivo: 'AS-001.pdf',
        anexos: [[
            'conteudo' => 'anexo extra',
            'nome' => 'anexo-01.pdf',
            'mime' => 'application/pdf',
        ]],
    );

    expect($mailable->attachments())->toHaveCount(2);
});

it('informa no pdf a quantidade de anexos adicionais enviados no email', function () {
    Storage::fake('r2');

    $autorizacaoServico = criarAutorizacaoServicoParaEnvio([
        'anexos_autorizacao_servico' => [
            'autorizacao-servico/extra/anexo-01.pdf',
            'autorizacao-servico/extra/anexo-02.xlsx',
            'autorizacao-servico/extra/inexistente.pdf',
        ],
    ]);

    Storage::disk('r2')->put('autorizacao-servico/extra/anexo-01.pdf', 'anexo extra 1');
    Storage::disk('r2')->put('autorizacao-servico/extra/anexo-02.xlsx', 'anexo extra 2');

    $viewData = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico->refresh());
    $html = view('pdf.autorizacao-servico', $viewData)->render();

    expect($viewData['quantidadeAnexosEmail'])->toBe(2)
        ->and($html)->toContain('HÁ 2 ARQUIVOS EM ANEXO ENVIADOS NO E-MAIL EM CONJUNTO.');
});

it('impede o envio da as quando o fornecedor nao possui email valido', function () {
    Storage::fake('r2');
    Mail::fake();

    $usuario = UserFactory::new()->active()->create();
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio(construtoraEmail: 'sem-email-valido');
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
    ]);

    app(AutorizacaoServicoFluxoService::class)->enviar($autorizacaoServico->refresh(), $usuario);
})->throws(DomainException::class, 'Informe ao menos um e-mail válido para enviar a AS.');

it('nao usa emails do fornecedor ou usuario como destinatarios automaticos da as', function () {
    Storage::fake('r2');
    Mail::fake();

    $usuario = UserFactory::new()->active()->create([
        'email' => 'usuario.envio@example.com',
    ]);
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio(construtoraEmail: 'fornecedor.automatico@example.com');
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
    ]);

    app(AutorizacaoServicoFluxoService::class)->enviar($autorizacaoServico->refresh(), $usuario);
})->throws(DomainException::class, 'Informe ao menos um e-mail válido para enviar a AS.');

it('envia a as somente para copias visiveis quando para esta vazio', function () {
    Storage::fake('r2');
    Mail::fake();

    $usuario = UserFactory::new()->active()->create([
        'email' => 'usuario.visivel@example.com',
    ]);
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio(construtoraEmail: 'fornecedor.nao.usado@example.com');
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
    ]);

    app(AutorizacaoServicoFluxoService::class)->enviar(
        $autorizacaoServico->refresh(),
        $usuario,
        destinatarios: [],
        copias: ['copia.visivel@example.com'],
    );

    Mail::assertSent(AutorizacaoServicoMail::class, function (AutorizacaoServicoMail $mail): bool {
        return ! $mail->hasTo('fornecedor.nao.usado@example.com')
            && ! $mail->hasTo('usuario.visivel@example.com')
            && $mail->hasCc('copia.visivel@example.com');
    });
});

it('envia a as para destinatarios informados mesmo sem gestor valido na obra', function () {
    Storage::fake('r2');
    Mail::fake();

    $usuario = UserFactory::new()->active()->create();
    $autorizacaoServico = criarAutorizacaoServicoParaEnvio();
    $autorizacaoServico->obra->update([
        'engenharia' => null,
    ]);
    $autorizacaoServico->obra->projeto?->update([
        'resp_eng' => null,
    ]);

    app(AutorizacaoServicoFluxoService::class)->enviar(
        $autorizacaoServico->refresh(),
        $usuario,
        destinatarios: ['destinatario.manual@example.com'],
    );

    Mail::assertSent(AutorizacaoServicoMail::class, fn (AutorizacaoServicoMail $mail): bool => $mail->hasTo('destinatario.manual@example.com'));
});

function criarAutorizacaoServicoParaEnvio(array $overrides = [], string $construtoraEmail = 'fornecedor@example.com'): AutorizacaoServico
{
    $gestor = UserFactory::new()->active()->create([
        'name' => 'Gestor AS',
        'email' => 'gestor.as@example.com',
        'phone' => '5511888888888',
    ]);

    $obra = ObrasFactory::new()->create([
        'engenharia' => $gestor->name,
        'unidade' => 'Unidade Teste',
        'endereco' => 'Rua da Unidade, 100',
        'inicio' => now(),
        'fim' => now()->addDays(30),
    ]);
    $obra->projeto?->update([
        'resp_eng' => $gestor->id,
    ]);

    $construtora = Construtora::create([
        'nome' => 'Fornecedor AS',
        'cnpj' => fake()->unique()->numerify('##############'),
        'telefone' => '(11) 3000-0000',
        'email' => $construtoraEmail,
        'tipo' => 'CONSTRUTORA',
    ]);

    $controle = $obra->controlesNotaFiscal()->first()
        ?? ControleNotaFiscalFactory::new()->for($obra, 'obra')->create();
    $asEscopo = AsEscopoFactory::new()->create([
        'grupo' => 'Civil',
        'numero_as' => '001',
        'escopo' => 'Serviços civis',
    ]);

    $autorizacaoServico = AutorizacaoServico::create(array_merge([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS TESTE 001',
        'numero_complemento' => '',
        'valor' => 12345.67,
        'valor_estimado' => 13000,
        'observacoes' => 'Planilha final anexa ao e-mail da AS.',
    ], $overrides));

    $item = ControleNotaFiscalItemFactory::new()
        ->for($controle, 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => $asEscopo->id,
            'numero_as' => $asEscopo->numero_as,
            'numero_complemento' => $autorizacaoServico->numero_complemento,
            'escopo' => $asEscopo->escopo,
            'empresa' => $construtora->nome,
            'quantidade' => 1,
            'valor_estimado_as' => 13000,
            'valor_global_a' => 12345.67,
            'saldo' => 12345.67,
            'observacoes' => 'Parcela única',
            'data_entrega' => now()->addDays(10),
        ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    return $autorizacaoServico->refresh();
}
