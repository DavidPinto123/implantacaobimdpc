<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalItem;
use App\Models\Obras;
use App\Services\AutorizacaoServicoFluxoService;
use App\Services\AutorizacaoServicoService;
use Database\Factories\AsEscopoFactory;
use Database\Factories\AutorizacaoServicoFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Database\Factories\ControleNotaFiscalNotaFactory;
use Database\Factories\ObrasFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    config(['filesystems.media_disk' => 'r2']);
});

it('cria autorizacao de servico para um item principal e vincula o item', function () {
    $user = UserFactory::new()->active()->create();
    $obra = ObrasFactory::new()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor AS',
        'cnpj' => '12.345.678/0001-90',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = controleNotaFiscalParaObra($obra, [
        'construtora_id' => null,
    ]);
    $asEscopo = AsEscopoFactory::new()->create();
    $item = ControleNotaFiscalItemFactory::new()
        ->for($controle, 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => $asEscopo->id,
            'numero_as' => null,
            'numero_complemento' => null,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 12500,
            'valor_global_a' => 12500,
            'saldo' => 12500,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);

    expect($autorizacaoServico)
        ->toBeInstanceOf(AutorizacaoServico::class)
        ->and($autorizacaoServico->status)->toBe(AsStatus::CRIADA)
        ->and($autorizacaoServico->valor_estimado)->toBe('12500.00')
        ->and($autorizacaoServico->valor)->toBe('12500.00')
        ->and($autorizacaoServico->construtora_id)->toBe($construtora->id)
        ->and($autorizacaoServico->created_by_id)->toBe($user->id);

    $item->refresh();
    $autorizacaoServico->refresh();

    expect($autorizacaoServico->controle_nota_fiscal_item_id)->toBe($item->id)
        ->and($item->numero_complemento)->toBe('')
        ->and($item->numero_as)->toBeNull()
        ->and($item->as_escopo_id)->toBe($asEscopo->id);
});

it('nao cria autorizacao de servico quando o controle fiscal esta encerrado', function () {
    $user = UserFactory::new()->active()->create();
    $obra = ObrasFactory::new()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor AS Encerrada',
        'cnpj' => '12.345.678/0001-91',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = controleNotaFiscalParaObra($obra);
    $asEscopo = AsEscopoFactory::new()->create();
    $item = ControleNotaFiscalItemFactory::new()
        ->for($controle, 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => $asEscopo->id,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 12500,
            'valor_global_a' => 12500,
            'saldo' => 12500,
        ]);
    $controle->update([
        'status' => ControleNotaFiscal::STATUS_ENCERRADO,
    ]);

    expect(fn () => app(AutorizacaoServicoFluxoService::class)->criarParaItem($item, $user))
        ->toThrow(DomainException::class, 'Controle fiscal encerrado para a unidade.');

    expect($item->refresh()->autorizacaoServico()->exists())->toBeFalse()
        ->and(AutorizacaoServico::query()->where('obra_id', $obra->id)->exists())->toBeFalse();
});

it('normaliza descricao da as sem item unidade e quantidade', function () {
    $service = app(AutorizacaoServicoFluxoService::class);

    $resultado = $service->normalizarItensDescricaoServico([
        [
            'item' => '1.1',
            'descricao_tipo' => 'texto',
            'descricao' => 'Instalacao de painel',
            'descricao_arquivo' => [],
            'unidade' => 'UN',
            'quantidade' => '2',
        ],
    ]);

    expect($resultado)->toBe([
        [
            'descricao_tipo' => 'texto',
            'descricao' => 'Instalacao de painel',
            'descricao_arquivo' => [],
        ],
    ]);
});

it('mantem texto e imagem quando ambos forem informados na descricao da as', function () {
    $service = app(AutorizacaoServicoFluxoService::class);

    $resultado = $service->normalizarItensDescricaoServico([
        [
            'descricao_tipo' => 'arquivo',
            'descricao' => 'Texto que deve ir para o PDF',
            'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/painel.png'],
        ],
    ]);

    expect($resultado)->toBe([
        [
            'descricao_tipo' => 'arquivo',
            'descricao' => 'Texto que deve ir para o PDF',
            'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/painel.png'],
        ],
    ]);
});

it('sincroniza item de controle com percentuais padrao do escopo da autorizacao de servico', function () {
    $obra = ObrasFactory::new()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Percentual AS',
        'cnpj' => '33.333.333/0001-33',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = controleNotaFiscalParaObra($obra);
    ControleNotaFiscalItemFactory::new()
        ->for($controle, 'controleNotaFiscal')
        ->create();
    $asEscopo = AsEscopoFactory::new()->create([
        'percentual_faturamento_mao_obra_default' => 74,
        'percentual_faturamento_material_default' => 26,
    ]);
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'valor' => 9000,
        'valor_estimado' => 9000,
    ]);
    $controle->itens()->where('as_escopo_id', $asEscopo->id)->delete();

    app(AutorizacaoServicoService::class)->sincronizarItensContratuais($autorizacaoServico);

    $item = ControleNotaFiscalItem::query()
        ->where('controle_nota_fiscal_id', $controle->id)
        ->whereKey($autorizacaoServico->refresh()->controle_nota_fiscal_item_id)
        ->firstOrFail();

    expect($item->percentual_faturamento_mao_obra)->toBe('74.00')
        ->and($item->percentual_faturamento_material)->toBe('26.00');
});

it('envia autorizacao de servico e marca o item como liberado para fornecedor', function () {
    Mail::fake();
    Storage::fake('r2');

    $user = UserFactory::new()->active()->create();
    $gestor = UserFactory::new()->active()->create([
        'name' => 'Gestor Envio AS',
        'email' => 'gestor.envio@example.com',
    ]);
    $obra = ObrasFactory::new()->create([
        'engenharia' => $gestor->name,
    ]);
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Envio',
        'cnpj' => '77.777.777/0001-77',
        'email' => 'fornecedor.envio@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $item = ControleNotaFiscalItemFactory::new()
        ->for(controleNotaFiscalParaObra($obra), 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => AsEscopoFactory::new()->create()->id,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 8000,
            'valor_global_a' => 8000,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
    ]);

    $enviada = app(AutorizacaoServicoFluxoService::class)
        ->enviar(
            $autorizacaoServico->refresh(),
            $user,
            destinatarios: ['fornecedor.envio@example.com'],
        );

    expect($enviada->refresh())
        ->status->toBe(AsStatus::ENVIADA)
        ->enviado_por_id->toBe($user->id)
        ->enviado_em->not->toBeNull();

    expect($item->refresh()->liberado_para_fornecedor_at)->not->toBeNull();
});

it('envia autorizacao usando empresa da linha quando a as antiga nao possui fornecedor', function () {
    Mail::fake();
    Storage::fake('r2');

    $user = UserFactory::new()->active()->create();
    $obra = ObrasFactory::new()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Linha AS Legada',
        'cnpj' => '77.777.777/0001-80',
        'email' => 'fornecedor.linha.legada@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $asEscopo = AsEscopoFactory::new()->create();
    $autorizacaoServico = AutorizacaoServicoFactory::new()
        ->for($obra, 'obra')
        ->create([
            'as_escopo_id' => $asEscopo->id,
            'construtora_id' => null,
            'status' => AsStatus::CRIADA,
            'numero_as' => 'AS-LEGADA-LINHA',
            'valor' => 8000,
            'valor_estimado' => 8000,
        ]);
    $item = ControleNotaFiscalItemFactory::new()
        ->for(controleNotaFiscalParaObra($obra), 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => $asEscopo->id,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 8000,
            'valor_global_a' => 8000,
        ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    $enviada = app(AutorizacaoServicoFluxoService::class)
        ->enviar(
            $autorizacaoServico->refresh(),
            $user,
            destinatarios: ['fornecedor.linha.legada@example.com'],
        );

    expect($enviada->refresh())
        ->status->toBe(AsStatus::ENVIADA)
        ->construtora_id->toBe($construtora->id)
        ->and($item->refresh()->liberado_para_fornecedor_at)->not->toBeNull();
});

it('envia autorizacao de servico sem duplicar notificacao para fornecedor', function () {
    Mail::fake();
    Storage::fake('r2');

    $user = UserFactory::new()->active()->create();
    $gestor = UserFactory::new()->active()->create([
        'name' => 'Gestor Notificacao AS',
        'email' => 'gestor.notificacao@example.com',
    ]);
    $obra = ObrasFactory::new()->create([
        'engenharia' => $gestor->name,
    ]);
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Notificacao Unica',
        'cnpj' => '77.777.777/0001-78',
        'email' => 'fornecedor.notificacao@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $destinatario = UserFactory::new()->active()->create([
        'email' => 'fornecedor.notificacao@example.com',
        'construtoras_id' => $construtora->id,
    ]);
    $destinatarioNaoSelecionado = UserFactory::new()->active()->create([
        'email' => 'fornecedor.nao.selecionado@example.com',
        'construtoras_id' => $construtora->id,
    ]);
    $item = ControleNotaFiscalItemFactory::new()
        ->for(controleNotaFiscalParaObra($obra), 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => AsEscopoFactory::new()->create()->id,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 8000,
            'valor_global_a' => 8000,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
    ]);

    app(AutorizacaoServicoFluxoService::class)
        ->enviar(
            $autorizacaoServico->refresh(),
            $user,
            destinatarios: ['fornecedor.notificacao@example.com'],
        );

    expect($destinatario->notifications()->count())->toBe(1)
        ->and($destinatario->notifications()->first()?->data['title'])->toBe('Item liberado para fornecedor')
        ->and($destinatarioNaoSelecionado->notifications()->count())->toBe(0);
});

it('nao envia autorizacao de servico quando a as nao possui fornecedor', function () {
    Mail::fake();
    Storage::fake('r2');

    $user = UserFactory::new()->active()->create();
    $gestor = UserFactory::new()->active()->create([
        'name' => 'Gestor Sem Fornecedor AS',
        'email' => 'gestor.sem.fornecedor@example.com',
    ]);
    $obra = ObrasFactory::new()->create([
        'engenharia' => $gestor->name,
    ]);
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Controle Sem Empresa',
        'cnpj' => '77.777.777/0001-79',
        'email' => 'fornecedor.controle@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $item = ControleNotaFiscalItemFactory::new()
        ->for(controleNotaFiscalParaObra($obra, [
            'construtora_id' => $construtora->id,
        ]), 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => AsEscopoFactory::new()->create()->id,
            'empresa' => '',
            'valor_estimado_as' => 8000,
            'valor_global_a' => 8000,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);
    $autorizacaoServico->update([
        'status' => AsStatus::CRIADA,
        'construtora_id' => null,
    ]);

    app(AutorizacaoServicoFluxoService::class)
        ->enviar(
            $autorizacaoServico->refresh(),
            $user,
            destinatarios: ['fornecedor.controle@example.com'],
        );
})->throws(DomainException::class, 'Informe o fornecedor antes de gerar a AS.');

it('cria complemento quando ja existe autorizacao para a obra e escopo', function () {
    $user = UserFactory::new()->active()->create();
    $obra = ObrasFactory::new()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Reuso',
        'cnpj' => '98.765.432/0001-10',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = controleNotaFiscalParaObra($obra, [
        'construtora_id' => null,
    ]);
    $asEscopo = AsEscopoFactory::new()->create();
    $numeroAs = app(AutorizacaoServicoService::class)
        ->gerarNumeroAsEstruturado($obra, $asEscopo, $construtora);
    $autorizacaoExistente = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_as' => $numeroAs,
        'status' => AsStatus::CRIADA,
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $item = ControleNotaFiscalItemFactory::new()
        ->for($controle, 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => $asEscopo->id,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 9100,
            'valor_global_a' => 9300,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);

    expect($autorizacaoServico->id)->not->toBe($autorizacaoExistente->id)
        ->and($autorizacaoServico->numero_complemento)->toBe('C1')
        ->and($autorizacaoServico->construtora_id)->toBe($construtora->id)
        ->and($autorizacaoServico->valor)->toBe('9300.00')
        ->and($autorizacaoServico->valor_estimado)->toBe('9100.00');

    $item->refresh();
    $autorizacaoServico->refresh();

    expect($autorizacaoServico->controle_nota_fiscal_item_id)->toBe($item->id)
        ->and($item->numero_complemento)->toBe('C1');
});

it('sincroniza autorizacao complementar na linha com o mesmo complemento', function () {
    $obra = ObrasFactory::new()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Complementar Sync',
        'cnpj' => '12.222.333/0001-45',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = controleNotaFiscalParaObra($obra, [
        'construtora_id' => null,
    ]);
    $asEscopo = AsEscopoFactory::new()->create();
    $numeroAs = app(AutorizacaoServicoService::class)
        ->gerarNumeroAsEstruturado($obra, $asEscopo, $construtora);
    $principal = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'numero_as' => $numeroAs,
        'numero_complemento' => null,
        'status' => AsStatus::CRIADA,
        'valor' => 100,
        'valor_estimado' => 90,
    ]);
    $complementar = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'numero_as' => $numeroAs,
        'numero_complemento' => 'C1',
        'status' => AsStatus::CRIADA,
        'valor' => 250,
        'valor_estimado' => 200,
    ]);
    $linhaPrincipal = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_complemento' => null,
        'percentual_total' => 100,
        'valor_global_a' => 100,
        'valor_estimado_as' => 90,
    ]);
    $principal->forceFill(['controle_nota_fiscal_item_id' => $linhaPrincipal->id])->save();
    $linhaComplementar = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_complemento' => 'C1',
        'percentual_total' => 100,
    ]);

    app(AutorizacaoServicoService::class)->sincronizarItensContratuais($complementar);

    $linhaPrincipal->refresh();
    $linhaComplementar->refresh();

    expect($principal->refresh()->controle_nota_fiscal_item_id)->toBe($linhaPrincipal->id)
        ->and($linhaPrincipal->valor_global_a)->toBe('100.00')
        ->and($linhaPrincipal->valor_estimado_as)->toBe('90.00');

    expect($complementar->refresh()->controle_nota_fiscal_item_id)->toBe($linhaComplementar->id)
        ->and($linhaComplementar->numero_complemento)->toBe('C1')
        ->and($linhaComplementar->valor_global_a)->toBe('250.00')
        ->and($linhaComplementar->valor_estimado_as)->toBe('200.00');
});

it('nao permite autorizacao duplicada sem complemento para a mesma obra e numero de as', function () {
    $obra = ObrasFactory::new()->create();
    $asEscopo = AsEscopoFactory::new()->create();

    AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_as' => 'AS-DUPLICADA',
        'numero_complemento' => null,
        'status' => AsStatus::CRIADA,
        'valor' => 100,
        'valor_estimado' => 100,
    ]);

    AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_as' => 'AS-DUPLICADA',
        'numero_complemento' => null,
        'status' => AsStatus::CRIADA,
        'valor' => 200,
        'valor_estimado' => 200,
    ]);
})->throws(QueryException::class);

it('permite mesmo numero de as quando o complemento e diferente', function () {
    $obra = ObrasFactory::new()->create();
    $asEscopo = AsEscopoFactory::new()->create();

    AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_as' => 'AS-COM-COMPLEMENTO',
        'numero_complemento' => 'A',
        'status' => AsStatus::CRIADA,
        'valor' => 100,
        'valor_estimado' => 100,
    ]);

    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_as' => 'AS-COM-COMPLEMENTO',
        'numero_complemento' => 'B',
        'status' => AsStatus::CRIADA,
        'valor' => 200,
        'valor_estimado' => 200,
    ]);

    expect($autorizacaoServico)->exists()->toBeTrue();
});

it('nao permite duas autorizacoes para a mesma obra escopo e complemento', function () {
    $obra = ObrasFactory::new()->create();
    $asEscopo = AsEscopoFactory::new()->create();

    AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_as' => 'AS-ESCOPO-C1-A',
        'numero_complemento' => 'C1',
        'status' => AsStatus::CRIADA,
        'valor' => 100,
        'valor_estimado' => 100,
    ]);

    AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'numero_as' => 'AS-ESCOPO-C1-B',
        'numero_complemento' => 'C1',
        'status' => AsStatus::CRIADA,
        'valor' => 200,
        'valor_estimado' => 200,
    ]);
})->throws(QueryException::class);

it('cria nova autorizacao quando existe mesmo numero de as com complemento diferente', function () {
    $user = UserFactory::new()->active()->create();
    $obra = ObrasFactory::new()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Complemento',
        'cnpj' => '12.222.333/0001-44',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = controleNotaFiscalParaObra($obra, [
        'construtora_id' => null,
    ]);
    $asEscopo = AsEscopoFactory::new()->create();
    $numeroAs = app(AutorizacaoServicoService::class)
        ->gerarNumeroAsEstruturado($obra, $asEscopo, $construtora);
    $autorizacaoComComplemento = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'numero_as' => $numeroAs,
        'numero_complemento' => 'A',
        'status' => AsStatus::CRIADA,
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $item = ControleNotaFiscalItemFactory::new()
        ->for($controle, 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => $asEscopo->id,
            'numero_complemento' => null,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 9100,
            'valor_global_a' => 9300,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);

    expect($autorizacaoServico->id)->not->toBe($autorizacaoComComplemento->id)
        ->and($autorizacaoServico->numero_complemento)->toBe('C1')
        ->and(AutorizacaoServico::query()->where('numero_as', $numeroAs)->count())->toBe(2);
});

it('mantem autorizacao de servico criada sem etapa de orcamento', function () {
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'status' => AsStatus::CRIADA,
    ]);

    $emOrcamento = app(AutorizacaoServicoFluxoService::class)
        ->abrirOrcamento($autorizacaoServico);

    expect($emOrcamento->status)->toBe(AsStatus::CRIADA);
});

it('nao envia autorizacao de servico cancelada', function () {
    $user = UserFactory::new()->active()->create();
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'status' => AsStatus::CANCELADA,
    ]);

    app(AutorizacaoServicoFluxoService::class)
        ->enviar($autorizacaoServico, $user);
})->throws(DomainException::class, 'Não é possível enviar uma AS cancelada.');

it('nao cria autorizacao de servico sem todas as informacoes obrigatorias', function () {
    $user = UserFactory::new()->active()->create();
    $obra = ObrasFactory::new()->create();
    $item = ControleNotaFiscalItemFactory::new()
        ->for(controleNotaFiscalParaObra($obra), 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => AsEscopoFactory::new()->create()->id,
            'empresa' => 'Fornecedor Inexistente',
            'valor_estimado_as' => 1000,
            'valor_global_a' => 0,
        ]);

    app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);
})->throws(DomainException::class, 'Preencha escopo e fornecedor antes de criar a AS.');

it('nao envia autorizacao que ainda nao foi criada', function () {
    $user = UserFactory::new()->active()->create();
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'status' => AsStatus::RASCUNHO,
    ]);

    app(AutorizacaoServicoFluxoService::class)
        ->enviar($autorizacaoServico, $user);
})->throws(DomainException::class, 'A AS só pode ser enviada depois de criada.');

it('nao abre orcamento depois da as ser enviada', function () {
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'status' => AsStatus::ENVIADA,
    ]);

    app(AutorizacaoServicoFluxoService::class)
        ->abrirOrcamento($autorizacaoServico);
})->throws(DomainException::class, 'A AS só pode ser enviada depois de criada.');

it('nao cancela autorizacao de servico com nota fiscal aprovada vinculada', function () {
    $user = UserFactory::new()->active()->create();
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Cancelamento',
        'cnpj' => '88.888.888/0001-88',
        'tipo' => 'CONSTRUTORA',
    ]);
    $obra = ObrasFactory::new()->create();
    $item = ControleNotaFiscalItemFactory::new()
        ->for(controleNotaFiscalParaObra($obra), 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => AsEscopoFactory::new()->create()->id,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 1000,
            'valor_global_a' => 1000,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);

    ControleNotaFiscalNotaFactory::new()
        ->create([
            'autorizacao_servico_id' => $autorizacaoServico->id,
            'autorizacao_servico_adicional_id' => null,
            'status' => StatusControleNotaFiscalNota::APROVADO->value,
        ]);

    app(AutorizacaoServicoFluxoService::class)
        ->cancelar($autorizacaoServico, 'Escopo cancelado pelo gestor.', $user);
})->throws(DomainException::class, 'Não é possível cancelar uma AS com nota fiscal aprovada.');

it('cancela autorizacao de servico com nota fiscal aprovada quando usuario tem permissao shield', function () {
    $user = UserFactory::new()->active()->create();
    Permission::findOrCreate('CancelApproved:AutorizacaoServico', 'web');
    $user->givePermissionTo('CancelApproved:AutorizacaoServico');

    $construtora = Construtora::create([
        'nome' => 'Fornecedor Cancelamento Permitido',
        'cnpj' => '77.777.777/0001-77',
        'tipo' => 'CONSTRUTORA',
    ]);
    $obra = ObrasFactory::new()->create();
    $item = ControleNotaFiscalItemFactory::new()
        ->for(controleNotaFiscalParaObra($obra), 'controleNotaFiscal')
        ->create([
            'as_escopo_id' => AsEscopoFactory::new()->create()->id,
            'empresa' => $construtora->nome,
            'valor_estimado_as' => 1000,
            'valor_global_a' => 1000,
        ]);

    $autorizacaoServico = app(AutorizacaoServicoFluxoService::class)
        ->criarParaItem($item, $user);

    ControleNotaFiscalNotaFactory::new()
        ->create([
            'autorizacao_servico_id' => $autorizacaoServico->id,
            'autorizacao_servico_adicional_id' => null,
            'status' => StatusControleNotaFiscalNota::APROVADO->value,
        ]);

    $cancelada = app(AutorizacaoServicoFluxoService::class)
        ->cancelar($autorizacaoServico, 'Cancelamento autorizado.', $user);

    expect($cancelada->status)->toBe(AsStatus::CANCELADA)
        ->and($cancelada->cancelado_por_id)->toBe($user->id);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function controleNotaFiscalParaObra(Obras $obra, array $attributes = []): ControleNotaFiscal
{
    $controle = $obra->controlesNotaFiscal()->latest('id')->first();

    if (! $controle instanceof ControleNotaFiscal) {
        $controle = $obra->controlesNotaFiscal()->create([
            'tipo_unidade' => TipoUnidade::EXPANSAO->value,
            'status' => ControleNotaFiscal::STATUS_ATIVO,
            'data_base' => now()->toDateString(),
            'unidade' => $obra->unidade,
            'sigla' => $obra->projeto?->sigla,
            'endereco' => $obra->endereco,
        ]);
    }

    if ($attributes !== []) {
        $controle->forceFill($attributes)->save();
    }

    return $controle->refresh();
}
