<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Filament\Resources\Asas\AsaResource;
use App\Filament\Resources\AutorizacaoServicos\AutorizacaoServicoResource;
use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Filament\Resources\ImportacaoNotaFiscals\ImportacaoNotaFiscalResource;
use App\Models\AsaItem;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalNota;
use App\Models\ControleNotaFiscalNotaBaixa;
use App\Models\Obras;
use Database\Factories\AsaFactory;
use Database\Factories\AutorizacaoServicoFactory;
use Database\Factories\ControleNotaFiscalItemFactory;
use Database\Factories\ControleNotaFiscalNotaFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupFilamentResourceCoverageForTests($this);
});

it('mantem controles de nota fiscal e filhos ao excluir a obra com soft delete', function () {
    $obra = Obras::factory()->create();
    $controle = controleExpansaoDaObra($obra);

    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);

    $auxiliar = criarAuxiliarControleNotaFiscal($controle);

    $obra->delete();

    $this->assertSoftDeleted('obras', ['id' => $obra->id]);
    $this->assertDatabaseHas('controle_nota_fiscals', ['id' => $controle->id]);
    $this->assertDatabaseHas('controle_nota_fiscal_items', ['id' => $item->id]);
    $this->assertDatabaseHas('controle_nota_fiscal_auxiliares', ['id' => $auxiliar->id]);
});

it('oculta controles de nota fiscal de obras soft deletadas na consulta operacional', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);
    $this->actingAs($user);

    $obraDeletada = Obras::factory()->create();
    $controleOculto = controleExpansaoDaObra($obraDeletada);
    $obraAtiva = Obras::factory()->create();
    $controleVisivel = controleExpansaoDaObra($obraAtiva);

    $obraDeletada->delete();

    expect(ControleNotaFiscalResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($controleVisivel->id)
        ->not->toContain($controleOculto->id);

    $obraDeletada->restore();

    expect(ControleNotaFiscalResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($controleOculto->id);
});

it('oculta AS ASA e notas fiscais de obras soft deletadas nas consultas operacionais', function () {
    $user = createFinanceiroUserWithPermissions([
        'ViewAny:ControleNotaFiscal',
        'View:ControleNotaFiscal',
    ], asSuperAdmin: true);
    $this->actingAs($user);

    $obraDeletada = Obras::factory()->create();
    $controleDeletado = controleExpansaoDaObra($obraDeletada);
    $itemDeletado = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controleDeletado->id,
    ]);
    $auxiliarDeletado = criarAuxiliarControleNotaFiscal($controleDeletado);
    $asOculta = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obraDeletada->id,
        'controle_nota_fiscal_item_id' => $itemDeletado->id,
    ]);
    $asaOculta = AsaFactory::new()->create([
        'projeto_id' => $obraDeletada->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliarDeletado->id,
    ]);
    $notaOculta = ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $asOculta->id,
        'autorizacao_servico_adicional_id' => null,
    ]);

    $obraAtiva = Obras::factory()->create();
    $controleAtivo = controleExpansaoDaObra($obraAtiva);
    $itemAtivo = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controleAtivo->id,
    ]);
    $auxiliarAtivo = criarAuxiliarControleNotaFiscal($controleAtivo);
    $asVisivel = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obraAtiva->id,
        'controle_nota_fiscal_item_id' => $itemAtivo->id,
    ]);
    $asaVisivel = AsaFactory::new()->create([
        'projeto_id' => $obraAtiva->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliarAtivo->id,
    ]);
    $notaVisivel = ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $asVisivel->id,
        'autorizacao_servico_adicional_id' => null,
    ]);

    $obraDeletada->delete();

    expect(AutorizacaoServicoResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($asVisivel->id)
        ->not->toContain($asOculta->id)
        ->and(AsaResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($asaVisivel->id)
        ->not->toContain($asaOculta->id)
        ->and(ImportacaoNotaFiscalResource::getEloquentQuery()->pluck('id')->all())
        ->toContain($notaVisivel->id)
        ->not->toContain($notaOculta->id);
});

it('mantem a obra e seus vinculos fiscais ao excluir com soft delete quando o controle possui AS vinculada', function () {
    $obra = Obras::factory()->create();
    $controle = controleExpansaoDaObra($obra);
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);

    AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
    ]);

    $obra->delete();

    $this->assertSoftDeleted('obras', ['id' => $obra->id]);
    $this->assertDatabaseHas('controle_nota_fiscals', ['id' => $controle->id]);
});

it('congela registros fiscais filhos enquanto a obra esta soft deletada', function () {
    $obra = Obras::factory()->create();
    $controle = controleExpansaoDaObra($obra);
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);
    $auxiliar = criarAuxiliarControleNotaFiscal($controle);
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
    ]);
    $asa = AsaFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
    ]);
    $asaItem = AsaItem::query()->create([
        'autorizacao_servico_adicional_id' => $asa->id,
        'item' => 'Item fiscal',
        'descricao' => 'Subitem fiscal',
        'unidade' => 'UN',
        'quantidade' => 1,
        'valor_unitario' => 100,
        'valor_total' => 100,
    ]);
    $nota = ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $autorizacaoServico->id,
        'autorizacao_servico_adicional_id' => null,
    ]);
    $baixa = ControleNotaFiscalNotaBaixa::query()->create([
        'controle_nota_fiscal_nota_id' => $nota->id,
        'user_id' => UserFactory::new()->active()->create()->id,
        'baixado_em' => now(),
    ]);

    $obra->delete();

    expect($controle->fresh()->obraEstaSoftDelete())->toBeTrue()
        ->and(fn () => $controle->fresh()->update(['status' => ControleNotaFiscal::STATUS_ENCERRADO]))->toThrow(ValidationException::class)
        ->and(fn () => $item->fresh()->update(['escopo' => 'Escopo bloqueado']))->toThrow(ValidationException::class)
        ->and(fn () => $auxiliar->fresh()->update(['escopo' => 'Auxiliar bloqueado']))->toThrow(ValidationException::class)
        ->and(fn () => $autorizacaoServico->fresh()->update(['observacoes' => 'AS bloqueada']))->toThrow(ValidationException::class)
        ->and(fn () => $asa->fresh()->update(['observacoes' => 'ASA bloqueada']))->toThrow(ValidationException::class)
        ->and(fn () => $asaItem->fresh()->update(['descricao' => 'Subitem bloqueado']))->toThrow(ValidationException::class)
        ->and(fn () => $nota->fresh()->update(['observacoes' => 'Nota bloqueada']))->toThrow(ValidationException::class)
        ->and(fn () => $baixa->fresh()->update(['baixado_em' => now()->addDay()]))->toThrow(ValidationException::class);

    $obra->restore();

    expect($controle->fresh()->obraEstaSoftDelete())->toBeFalse();
    expect($controle->fresh()->update(['status' => ControleNotaFiscal::STATUS_ENCERRADO]))->toBeTrue();
});

it('congela registros fiscais filhos quando o controle de nota fiscal esta encerrado', function () {
    $obra = Obras::factory()->create();
    $controle = controleExpansaoDaObra($obra);
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);
    $itemSemAs = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);
    $auxiliar = criarAuxiliarControleNotaFiscal($controle);
    $auxiliarSemAsa = criarAuxiliarControleNotaFiscal($controle);
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
    ]);
    $asa = AsaFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
    ]);
    $asaItem = AsaItem::query()->create([
        'autorizacao_servico_adicional_id' => $asa->id,
        'item' => 'Item fiscal',
        'descricao' => 'Subitem fiscal',
        'unidade' => 'UN',
        'quantidade' => 1,
        'valor_unitario' => 100,
        'valor_total' => 100,
    ]);
    $nota = ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $autorizacaoServico->id,
        'autorizacao_servico_adicional_id' => null,
        'status' => StatusControleNotaFiscalNota::EM_ANALISE->value,
    ]);
    $baixa = ControleNotaFiscalNotaBaixa::query()->create([
        'controle_nota_fiscal_nota_id' => $nota->id,
        'user_id' => UserFactory::new()->active()->create()->id,
        'baixado_em' => now(),
    ]);

    expect($controle->update(['status' => ControleNotaFiscal::STATUS_ENCERRADO]))->toBeTrue()
        ->and(fn () => ControleNotaFiscalItemFactory::new()->create([
            'controle_nota_fiscal_id' => $controle->id,
        ]))->toThrow(ValidationException::class)
        ->and(fn () => AutorizacaoServicoFactory::new()->create([
            'obra_id' => $obra->id,
            'controle_nota_fiscal_item_id' => $itemSemAs->id,
        ]))->toThrow(ValidationException::class)
        ->and(fn () => AsaFactory::new()->create([
            'projeto_id' => $obra->projeto_id,
            'controle_nota_fiscal_auxiliar_id' => $auxiliarSemAsa->id,
        ]))->toThrow(ValidationException::class)
        ->and(fn () => ControleNotaFiscalNota::query()->create([
            'autorizacao_servico_id' => $autorizacaoServico->id,
            'autorizacao_servico_adicional_id' => null,
            'importado_por_id' => UserFactory::new()->active()->create()->id,
            'tipo_medicao' => 'mao_obra',
            'empresa' => 'Fornecedor fechado',
            'cnpj_fornecedor' => '11222333000181',
            'numero_nf' => '9001',
            'cnpj_faturamento' => '11222333000181',
            'valor_acumulado_medido_nf' => 100,
            'emissao' => now()->toDateString(),
            'envio' => now()->toDateString(),
            'status' => StatusControleNotaFiscalNota::EM_ANALISE->value,
            'sort_order' => 2,
        ]))->toThrow(ValidationException::class)
        ->and(fn () => $nota->fresh()->update([
            'status' => StatusControleNotaFiscalNota::APROVADO->value,
        ]))->toThrow(ValidationException::class)
        ->and(fn () => $autorizacaoServico->fresh()->update([
            'status' => 'enviada',
        ]))->toThrow(ValidationException::class)
        ->and(fn () => $asa->fresh()->update([
            'status' => AsStatus::APROVADO,
        ]))->toThrow(ValidationException::class)
        ->and(fn () => $asaItem->fresh()->update([
            'descricao' => 'Subitem bloqueado',
        ]))->toThrow(ValidationException::class)
        ->and(fn () => $baixa->fresh()->update([
            'baixado_em' => now()->addDay(),
        ]))->toThrow(ValidationException::class);
});

it('bloqueia exclusao definitiva da obra quando o controle possui ASA vinculada', function () {
    $obra = Obras::factory()->create();
    $controle = controleExpansaoDaObra($obra);
    $auxiliar = criarAuxiliarControleNotaFiscal($controle);

    AsaFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
    ]);

    expect(fn () => $obra->forceDelete())->toThrow(ValidationException::class);

    $this->assertNotSoftDeleted('obras', ['id' => $obra->id]);
    $this->assertDatabaseHas('controle_nota_fiscals', ['id' => $controle->id]);
});

it('bloqueia exclusao definitiva da obra quando o controle possui nota fiscal importada', function () {
    $obra = Obras::factory()->create();
    $controle = controleExpansaoDaObra($obra);
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);
    $autorizacaoServico = AutorizacaoServicoFactory::new()->create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
    ]);

    ControleNotaFiscalNotaFactory::new()->create([
        'autorizacao_servico_id' => $autorizacaoServico->id,
        'autorizacao_servico_adicional_id' => null,
    ]);

    expect(fn () => $obra->forceDelete())->toThrow(ValidationException::class);

    $this->assertNotSoftDeleted('obras', ['id' => $obra->id]);
    $this->assertDatabaseHas('controle_nota_fiscals', ['id' => $controle->id]);
});

it('remove controles sem vinculos fiscais ao excluir definitivamente a obra', function () {
    $obra = Obras::factory()->create();
    $controle = controleExpansaoDaObra($obra);
    $item = ControleNotaFiscalItemFactory::new()->create([
        'controle_nota_fiscal_id' => $controle->id,
    ]);
    $auxiliar = criarAuxiliarControleNotaFiscal($controle);

    $obra->forceDelete();

    $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
    $this->assertDatabaseMissing('controle_nota_fiscals', ['id' => $controle->id]);
    $this->assertDatabaseMissing('controle_nota_fiscal_items', ['id' => $item->id]);
    $this->assertDatabaseMissing('controle_nota_fiscal_auxiliares', ['id' => $auxiliar->id]);
});

function controleExpansaoDaObra(Obras $obra): ControleNotaFiscal
{
    return ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();
}

function criarAuxiliarControleNotaFiscal(ControleNotaFiscal $controle): ControleNotaFiscalAuxiliar
{
    return ControleNotaFiscalAuxiliar::query()->create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'escopo' => 'Projeto',
        'empresa' => 'Empresa Teste',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 500,
        'valor_acumulado_medido' => 0,
        'saldo' => 500,
        'sort_order' => 0,
    ]);
}
