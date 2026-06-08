<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Mail\EnviarPdfMail;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalNota;
use App\Models\Obras;
use App\Services\AsaService;
use App\Services\ControleNotaFiscal\ControleNotaFiscalNotaService;
use Database\Factories\AsaFactory;
use Database\Factories\AutorizacaoServicoFactory;
use Database\Factories\ConstrutoraFactory;
use Database\Factories\ControleNotaFiscalAuxiliarFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

it('imports NF for AS using autorizacao_servico_id', function (): void {
    [$as, $user] = asDisponivelParaImportacao();

    $nota = app(ControleNotaFiscalNotaService::class)->importarParaAs(notaImportacaoData(250), $as, $user);

    expect($nota->autorizacao_servico_id)->toBe($as->id)
        ->and($nota->autorizacao_servico_adicional_id)->toBeNull();
});

it('imports NF for ASA using autorizacao_servico_adicional_id', function (): void {
    [$asa, $user] = asaDisponivelParaImportacao();

    $nota = app(ControleNotaFiscalNotaService::class)->importarParaAsa(notaImportacaoData(200), $asa, $user);

    expect($nota->autorizacao_servico_adicional_id)->toBe($asa->id)
        ->and($nota->autorizacao_servico_id)->toBeNull();
});

it('emails active supplier users when supplier imports NF for AS', function (): void {
    Mail::fake();

    [$as, $user, $controle] = asDisponivelParaImportacao();
    $contatoFornecedor = UserFactory::new()->active()->create([
        'construtoras_id' => $as->construtora_id,
        'email' => 'contato.fornecedor.as@example.test',
    ]);
    $usuarioFornecedorInativo = UserFactory::new()->create([
        'construtoras_id' => $as->construtora_id,
        'email' => 'fornecedor.inativo.as@example.test',
        'is_active' => false,
    ]);
    $orcamentista = UserFactory::new()->active()->create([
        'email' => 'orcamentista.nf@example.test',
    ]);
    $controle->obra->projeto->update(['user_id' => $orcamentista->id]);

    $data = notaImportacaoData(250);
    $data['numero_nf'] = '123456';

    app(ControleNotaFiscalNotaService::class)->importarParaAs($data, $as, $user);

    Mail::assertSent(EnviarPdfMail::class, function (EnviarPdfMail $mail) use ($contatoFornecedor, $orcamentista, $user, $usuarioFornecedorInativo): bool {
        return $mail->hasTo($user->email)
            && $mail->hasTo($contatoFornecedor->email)
            && ! $mail->hasTo($orcamentista->email)
            && ! $mail->hasTo($usuarioFornecedorInativo->email)
            && $mail->assunto === 'Nova nota fiscal para aprovação 123456'
            && str_contains($mail->mensagemEmail, 'aguarda aprovação');
    });
});

it('emails active supplier users when supplier imports NF for ASA', function (): void {
    Mail::fake();

    [$asa, $user, $controle] = asaDisponivelParaImportacao();
    $contatoFornecedor = UserFactory::new()->active()->create([
        'construtoras_id' => $user->construtoras_id,
        'email' => 'contato.fornecedor.asa@example.test',
    ]);
    $usuarioFornecedorInativo = UserFactory::new()->create([
        'construtoras_id' => $user->construtoras_id,
        'email' => 'fornecedor.inativo.asa@example.test',
        'is_active' => false,
    ]);
    $orcamentista = UserFactory::new()->active()->create([
        'email' => 'orcamentista.asa.nf@example.test',
    ]);
    $controle->obra->projeto->update(['user_id' => $orcamentista->id]);

    $data = notaImportacaoData(200);
    $data['numero_nf'] = 'ASA123';

    app(ControleNotaFiscalNotaService::class)->importarParaAsa($data, $asa, $user);

    Mail::assertSent(EnviarPdfMail::class, function (EnviarPdfMail $mail) use ($contatoFornecedor, $orcamentista, $user, $usuarioFornecedorInativo): bool {
        return $mail->hasTo($user->email)
            && $mail->hasTo($contatoFornecedor->email)
            && ! $mail->hasTo($orcamentista->email)
            && ! $mail->hasTo($usuarioFornecedorInativo->email)
            && $mail->assunto === 'Nova nota fiscal para aprovação ASA123'
            && str_contains($mail->mensagemEmail, 'aguarda aprovação');
    });
});

it('blocks importacao above committed saldo', function (): void {
    [$as, $user] = asDisponivelParaImportacao(valor: 300);

    ControleNotaFiscalNota::query()->create([
        'autorizacao_servico_id' => $as->id,
        ...notaImportacaoData(250),
    ]);

    expect(fn () => app(ControleNotaFiscalNotaService::class)->importarParaAs(notaImportacaoData(100), $as, $user))
        ->toThrow(ValidationException::class);
});

it('allows importacao against remaining saldo after partial approved measurement', function (): void {
    [$as, $user, $controle, $item] = asDisponivelParaImportacao(valor: 1000);

    ControleNotaFiscalNota::query()->create([
        'autorizacao_servico_id' => $as->id,
        ...notaImportacaoData(250),
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);

    $item->updateQuietly([
        'total_medicao_a_menos_b' => 250,
        'valor_acumulado_medido' => 250,
        'saldo' => 750,
    ]);

    $nota = app(ControleNotaFiscalNotaService::class)->importarParaAs(notaImportacaoData(700), $as, $user);

    expect($nota->autorizacao_servico_id)->toBe($as->id)
        ->and($nota->autorizacaoServico?->controleNotaFiscalItem?->controleNotaFiscal?->is($controle))->toBeTrue();
});

function asDisponivelParaImportacao(float $valor = 1000): array
{
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $construtora = ConstrutoraFactory::new()->create();
    $user = UserFactory::new()->active()->create(['construtoras_id' => $construtora->id]);
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'empresa' => $construtora->nome,
        'valor_global_a' => $valor,
        'total_medicao_a_menos_b' => $valor,
        'saldo' => $valor,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $as = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'valor' => $valor,
    ]);

    return [$as, $user, $controle, $item];
}

function asaDisponivelParaImportacao(float $valor = 1000): array
{
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();

    $construtora = ConstrutoraFactory::new()->create(['nome' => 'Fornecedor Importação ASA']);
    $user = UserFactory::new()->active()->create(['construtoras_id' => $construtora->id]);
    $auxiliar = ControleNotaFiscalAuxiliarFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'empresa' => $construtora->nome,
        'valor_global_a' => $valor,
        'total_medicao_a_menos_b' => $valor,
        'saldo' => $valor,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $asa = AsaFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => 'aprovado',
        'solicitante' => $construtora->nome,
        'valor_total' => $valor,
    ]);

    app(AsaService::class)->sincronizarItemAuxiliarFiscal($asa);

    return [$asa->refresh(), $user, $controle, $auxiliar];
}

function notaImportacaoData(float $valor): array
{
    return [
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => 'Fornecedor Teste',
        'cnpj_fornecedor' => '12345678000195',
        'numero_nf' => fake()->numerify('NF######'),
        'cnpj_faturamento' => '12345678000195',
        'valor_acumulado_medido_nf' => $valor,
        'status' => 'pendente',
        'sort_order' => 1,
    ];
}
