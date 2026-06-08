<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ElaboracaoAditivo;
use App\Services\AsaFluxoService;
use App\Services\AsaPdfService;
use Database\Factories\ObrasFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    config(['filesystems.media_disk' => 'r2']);
    Storage::fake('r2');
});

function criarAsaComAuxiliar(array $asaOverrides = [], array $auxiliarOverrides = []): array
{
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor ASA Fluxo '.uniqid(),
        'cnpj' => '00.000.000/0001-99',
        'tipo' => 'CONSTRUTORA',
    ]);

    $obra = ObrasFactory::new()->create(['unidade' => 'Unidade Fluxo ASA']);

    $controle = ControleNotaFiscal::query()->firstOrCreate(
        ['obra_id' => $obra->id, 'tipo_unidade' => 'EXPANSAO'],
        [
            'status' => ControleNotaFiscal::STATUS_ATIVO,
            'data_base' => now()->toDateString(),
            'sigla' => 'TST',
            'unidade' => 'Unidade Fluxo ASA',
        ]
    );

    $user = UserFactory::new()->active()->create();

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $fornecedor->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'em_aprovacao_orcamento',
        'aprovado_gestor_por_id' => $user->id,
        'aprovado_gestor_em' => now(),
        'foto_antes' => [],
        'foto_depois' => [],
        'projeto_orcado' => [],
        'projeto_revisado' => [],
        'escopo_contratado' => [],
        'escopo_real' => [],
    ]);

    $asa = Asa::create(array_merge([
        'numero_asa' => 'ASA-TST-'.uniqid(),
        'projeto_id' => $obra->projeto_id,
        'sigla' => 'TST',
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'objeto' => 'ASA teste fluxo',
        'descricao' => 'ASA teste fluxo',
        'valor_bruto' => 5000,
        'desconto' => 0,
        'valor_total' => 5000,
        'solicitante' => $fornecedor->nome,
        'elaboracao_aditivo_id' => $aditivo->id,
    ], $asaOverrides));

    $auxiliar = ControleNotaFiscalAuxiliar::create(array_merge([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Solicitação Cliente',
        'numero_as' => strtoupper(substr('ASA-'.str_pad((string) $asa->id, 6, '0', STR_PAD_LEFT), 0, 20)),
        'escopo' => 'Serviço adicional teste',
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 5000,
        'total_medicao_a_menos_b' => 5000,
        'valor_acumulado_medido' => 0,
        'saldo' => 5000,
    ], $auxiliarOverrides));

    $asa->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();

    return compact('asa', 'auxiliar', 'aditivo', 'fornecedor', 'obra', 'user');
}

it('gerar pdf salva campos no modelo asa e atualiza status para criada', function (): void {
    ['asa' => $asa, 'user' => $user] = criarAsaComAuxiliar();

    $datas = [
        'as_data_inicio' => '2026-06-01',
        'as_data_termino' => '2026-08-30',
        'as_data_entrega' => null,
        'as_desconto' => 500.0,
        'as_descricao_pdf' => 'Execução de obra adicional',
        'as_itens_descricao_pdf' => [],
        'as_anexos' => [],
    ];
    $parcelamento = [['parcela' => 'Parcela 01', 'percentual' => 100.0, 'valor' => 4500.0, 'observacao' => '']];

    $result = app(AsaFluxoService::class)->gerarPdf($asa, $user, $datas, $parcelamento);

    expect($result->status)->toBe(AsStatus::CRIADA)
        ->and($result->as_data_inicio)->not->toBeNull()
        ->and($result->as_desconto)->toBe('500.00')
        ->and($result->as_descricao_pdf)->toBe('Execução de obra adicional')
        ->and($result->as_criada_por_id)->toBe($user->id)
        ->and($result->as_criada_em)->not->toBeNull()
        ->and($result->as_pdf)->not->toBeNull();
});

it('gerar pdf atualiza status_fluxo do elaboracao_aditivo para aprovado', function (): void {
    ['asa' => $asa, 'aditivo' => $aditivo, 'user' => $user] = criarAsaComAuxiliar();

    expect($aditivo->status_fluxo)->toBe('em_aprovacao_orcamento');

    app(AsaFluxoService::class)->gerarPdf($asa, $user, [], [
        ['parcela' => 'Parcela 01', 'percentual' => 100.0, 'valor' => 5000.0, 'observacao' => ''],
    ]);

    $aditivo->refresh();

    expect($aditivo->status_fluxo)->toBe('aprovado')
        ->and($aditivo->aprovado_orcamento_por_id)->toBe($user->id)
        ->and($aditivo->aprovado_orcamento_em)->not->toBeNull();
});

it('gerar pdf pode ser chamado novamente para reeditar o pdf existente', function (): void {
    ['asa' => $asa, 'user' => $user] = criarAsaComAuxiliar(['status' => AsStatus::CRIADA]);

    $parcelamento = [['parcela' => 'Parcela 01', 'percentual' => 100.0, 'valor' => 5000.0, 'observacao' => '']];

    $result = app(AsaFluxoService::class)->gerarPdf($asa, $user, [
        'as_descricao_pdf' => 'Descrição revisada',
    ], $parcelamento);

    expect($result->status)->toBe(AsStatus::CRIADA)
        ->and($result->as_descricao_pdf)->toBe('Descrição revisada');
});

it('enviar atualiza liberado_para_fornecedor_at no auxiliar e status para enviada', function (): void {
    Mail::fake();

    ['asa' => $asa, 'auxiliar' => $auxiliar, 'user' => $user] = criarAsaComAuxiliar([
        'status' => AsStatus::CRIADA,
        'as_pdf' => 'asa/1/pdf/ASA.pdf',
    ]);

    Storage::disk('r2')->put('asa/1/pdf/ASA.pdf', '%PDF-1.4 fake');

    app(AsaFluxoService::class)->enviar($asa, $user, ['destino@test.com'], [], [], 'existente');

    $asa->refresh();
    $auxiliar->refresh();

    expect($asa->status)->toBe(AsStatus::ENVIADA)
        ->and($asa->as_enviada_por_id)->toBe($user->id)
        ->and($asa->as_enviada_em)->not->toBeNull()
        ->and($auxiliar->liberado_para_fornecedor_at)->not->toBeNull();
});

it('enviar envia e-mail para destinatarios informados', function (): void {
    Mail::fake();

    ['asa' => $asa, 'user' => $user] = criarAsaComAuxiliar([
        'status' => AsStatus::CRIADA,
        'as_pdf' => 'asa/2/pdf/ASA2.pdf',
    ]);

    Storage::disk('r2')->put('asa/2/pdf/ASA2.pdf', '%PDF-1.4 fake');

    app(AsaFluxoService::class)->enviar($asa, $user, ['envio@test.com'], [], [], 'existente');

    Mail::assertSent(EnviarPdfMail::class, fn (EnviarPdfMail $mail) => $mail->hasTo('envio@test.com'));
});

it('enviar lanca excecao quando asa ja foi enviada', function (): void {
    ['asa' => $asa, 'user' => $user] = criarAsaComAuxiliar(
        ['status' => AsStatus::ENVIADA, 'as_pdf' => 'asa/3/pdf/ASA3.pdf'],
        ['liberado_para_fornecedor_at' => now()]
    );

    Storage::disk('r2')->put('asa/3/pdf/ASA3.pdf', '%PDF-1.4 fake');

    expect(fn () => app(AsaFluxoService::class)->enviar($asa, $user, ['x@x.com'], [], [], 'existente'))
        ->toThrow(DomainException::class);
});

it('asa pdf service gera path de armazenamento com id e nome do arquivo', function (): void {
    ['asa' => $asa] = criarAsaComAuxiliar();

    $path = app(AsaPdfService::class)->storagePath($asa);

    expect($path)->toContain('autorizacao-servico-adicional/')
        ->and($path)->toContain('/pdf/')
        ->and($path)->toEndWith('.pdf');
});

it('cancelar marca ASA como cancelada e grava motivo, autor e timestamp', function (): void {
    ['asa' => $asa, 'user' => $user] = criarAsaComAuxiliar(['status' => AsStatus::CRIADA]);

    $resultado = app(AsaFluxoService::class)->cancelar($asa, 'Cancelamento manual.', $user);

    expect($resultado->status)->toBe(AsStatus::CANCELADA)
        ->and($resultado->as_motivo_cancelamento)->toBe('Cancelamento manual.')
        ->and($resultado->as_cancelada_por_id)->toBe($user->id)
        ->and($resultado->as_cancelada_em)->not->toBeNull();
});

it('cancelar é no-op quando ASA já está cancelada', function (): void {
    ['asa' => $asa, 'user' => $user] = criarAsaComAuxiliar([
        'status' => AsStatus::CANCELADA,
    ]);

    $original = $asa->as_cancelada_em;

    $resultado = app(AsaFluxoService::class)->cancelar($asa, 'Novo motivo.', $user);

    expect($resultado->status)->toBe(AsStatus::CANCELADA)
        ->and($resultado->as_motivo_cancelamento)->toBe($original); // não sobrescreveu nada
});

it('cancelar atualiza o status_fluxo do elaboracao_aditivo para cancelado', function (): void {
    ['asa' => $asa, 'aditivo' => $aditivo, 'user' => $user] = criarAsaComAuxiliar(['status' => AsStatus::CRIADA]);

    app(AsaFluxoService::class)->cancelar($asa, 'Cancelamento.', $user);

    $aditivo->refresh();

    expect($aditivo->status_fluxo)->toBe('cancelado');
});

it('cancelar lança DomainException quando há NF aprovada e usuário sem permissão CancelApproved', function (): void {
    ['asa' => $asa, 'auxiliar' => $auxiliar, 'user' => $user] = criarAsaComAuxiliar(['status' => AsStatus::ENVIADA]);

    $asa->notasFiscais()->create([
        'controle_nota_fiscal_id' => $auxiliar->controle_nota_fiscal_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'autorizacao_servico_adicional_id' => $asa->id,
        'numero_nota_fiscal' => 'NF-001',
        'cnpj_fornecedor' => '00.000.000/0001-99',
        'cnpj_faturamento' => '00.000.000/0001-99',
        'empresa' => 'Empresa Teste',
        'valor_medido' => 1000,
        'data_emissao' => now()->toDateString(),
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'tipo_medicao' => 'cliente',
        'importado_por' => $user->id,
        'arquivo_nota_fiscal' => 'fake.pdf',
    ]);

    expect(fn () => app(AsaFluxoService::class)->cancelar($asa, 'Tentativa.', $user))
        ->toThrow(DomainException::class, 'Não é possível cancelar uma ASA com nota fiscal aprovada.');
});
