<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Filament\Resources\AutorizacaoServicos\AutorizacaoServicoResource;
use App\Filament\Resources\AutorizacaoServicos\Pages\ControleAutorizacoesServico;
use App\Filament\Resources\AutorizacaoServicos\Pages\EditAutorizacaoServico;
use App\Mail\AutorizacaoServicoMail;
use App\Mail\EnviarPdfMail;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\CapexSimulacao;
use App\Models\CapexSimulacaoItem;
use App\Models\Construtora;
use App\Models\ControleAutorizacaoServicoResumo;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Models\Projeto;
use App\Models\Setor;
use App\Models\User;
use App\Services\AutorizacaoServicoFluxoService;
use App\Services\AutorizacaoServicoPdfService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config(['filesystems.media_disk' => 'r2']);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ensureDefaultRoles();
});

function autorizacaoFiscalParaItem(ControleNotaFiscalItem $item): AutorizacaoServico
{
    if (blank($item->as_escopo_id)) {
        $asEscopo = AsEscopo::create([
            'grupo' => $item->grupo ?: 'Grupo NF',
            'numero_as' => $item->numero_as ?: 'AS-NF-'.$item->id,
            'escopo' => $item->escopo ?: 'Escopo NF',
            'is_active' => true,
        ]);

        $item->forceFill(['as_escopo_id' => $asEscopo->id])->save();
    }

    return AutorizacaoServico::query()->firstOrCreate([
        'controle_nota_fiscal_item_id' => $item->id,
    ], [
        'obra_id' => $item->controleNotaFiscal?->obra_id,
        'as_escopo_id' => $item->as_escopo_id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => $item->numero_as ?: 'AS-NF-'.$item->id,
        'valor' => $item->valor_global_a ?: 0,
        'valor_estimado' => $item->valor_global_a ?: 0,
    ]);
}

function asaFiscalParaAuxiliar(ControleNotaFiscalAuxiliar $auxiliar): Asa
{
    return Asa::query()->firstOrCreate([
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
    ], [
        'numero_asa' => 'ASA-NF-'.$auxiliar->id,
        'projeto_id' => $auxiliar->controleNotaFiscal?->obra?->projeto_id,
        'status' => 'aprovado',
        'codigo_as_emitida' => $auxiliar->numero_as ?: 'ASA-NF-'.$auxiliar->id,
        'data_solicitacao' => now()->toDateString(),
        'data_aprovacao' => now()->toDateString(),
        'objeto' => $auxiliar->grupo ?: 'Adicional',
        'descricao' => $auxiliar->escopo ?: 'Adicional',
        'valor_bruto' => $auxiliar->valor_global_a ?: 0,
        'desconto' => 0,
        'valor_total' => $auxiliar->valor_global_a ?: 0,
        'solicitante' => $auxiliar->empresa,
    ]);
}

it('renderiza a tela de controle de as para usuarios autorizados', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create([
        'codigo' => 'OBR-AS-001',
        'unidade' => 'Unidade AS',
    ]);
    ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => 'Unidade AS',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee('Controle de AS')
        ->assertSee($obra->codigo)
        ->assertSee('Unidade AS');

    $this->get(AutorizacaoServicoResource::getUrl('index'))
        ->assertOk()
        ->assertSee('--cpr-as-zoom', false)
        ->assertSee('__cprAsZoomHookRegistered', false)
        ->assertSee('stickyHeight', false)
        ->assertSee('offsetHeight', false)
        ->assertSee('.dark .cpr-scroll .cpr-thead-main > tr > th', false)
        ->assertSee('background-color: #18181b', false)
        ->assertSee('background-color: #27272a', false)
        ->assertSee('gs-controle-page-size-select', false)
        ->assertSee('w-5 h-5 rounded text-gray-400 group-hover:text-primary-600', false)
        ->assertDontSee('style="width: 44px;"', false)
        ->assertSee('bg-gray-50 dark:bg-gray-900', false)
        ->assertSee('border-l-2 border-gray-300 dark:border-white/20', false)
        ->assertDontSee('background-color: #ffffff !important', false)
        ->assertDontSee('background-color: #000000 !important', false);
});

it('exibe a sigla original no controle de as quando a obra tambem possui nova sigla', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create([
        'projeto_id' => Projeto::factory()->create([
            'sigla' => 'SIG-AS',
            'nova_sigla' => 'NOVA-AS',
        ]),
        'codigo' => 'OBR-AS-SIGLA',
        'unidade' => 'Unidade Sigla AS',
    ]);

    ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'SIG-AS',
        'unidade' => 'Unidade Sigla AS',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee('SIG-AS')
        ->assertDontSee('NOVA-AS');
});

it('cria controle obrigatorio ativo com escopos padrao ao criar obra', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $escopoPadraoCivil = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => 'AS-PADRAO-CIVIL',
        'escopo' => 'Escopo padrao civil',
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $escopoPadraoEletrica = AsEscopo::create([
        'grupo' => 'Eletrica',
        'numero_as' => 'AS-PADRAO-ELETRICA',
        'escopo' => 'Escopo padrao eletrica',
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $escopoPersonalizado = AsEscopo::create([
        'grupo' => 'Personalizado',
        'numero_as' => 'AS-PERSONALIZADO',
        'escopo' => 'Escopo personalizado',
        'is_active' => true,
        'is_personalizado' => true,
    ]);
    $obra = Obras::factory()->create([
        'codigo' => 'OBR-AS-RASC',
        'unidade' => 'Unidade Rascunho AS',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee('Unidade Rascunho AS');

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->first();

    expect($controle)->not->toBeNull()
        ->and($controle->status)->toBe(ControleNotaFiscal::STATUS_ATIVO)
        ->and($controle->itens()->count())->toBe(2)
        ->and($controle->itens()->where('as_escopo_id', $escopoPadraoCivil->id)->exists())->toBeTrue()
        ->and($controle->itens()->where('as_escopo_id', $escopoPadraoEletrica->id)->exists())->toBeTrue()
        ->and($controle->itens()->where('as_escopo_id', $escopoPersonalizado->id)->exists())->toBeFalse();
});

it('importa percentuais padrao do escopo ao criar controle obrigatorio de as', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $escopoPadrao = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => 'AS-PADRAO-PERCENTUAL',
        'escopo' => 'Escopo padrao percentual',
        'percentual_faturamento_mao_obra_default' => 72.5,
        'percentual_faturamento_material_default' => 27.5,
        'is_active' => true,
        'is_personalizado' => false,
    ]);
    $obra = Obras::factory()->create([
        'codigo' => 'OBR-AS-PERCENTUAL',
        'unidade' => 'Unidade Percentual AS',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee('Unidade Percentual AS');

    $item = ControleNotaFiscalItem::query()
        ->whereHas('controleNotaFiscal', fn ($query) => $query->where('obra_id', $obra->id))
        ->where('as_escopo_id', $escopoPadrao->id)
        ->firstOrFail();

    expect($item->percentual_faturamento_mao_obra)->toBe('72.50')
        ->and($item->percentual_faturamento_material)->toBe('27.50');
});

it('permite configurar percentuais de mao de obra e material na linha do controle de as', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => 'PERC-AS',
        'unidade' => $obra->unidade,
    ]);
    $item = $controle->itens()->create([
        'grupo' => 'Civil',
        'numero_as' => 'AS-PERC',
        'escopo' => 'Escopo Percentual',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $item->id, [
            'percentual_faturamento_mao_obra' => '55.555',
            'percentual_faturamento_material' => '99',
        ]);

    expect($item->refresh()->percentual_faturamento_mao_obra)->toBe('55.56')
        ->and($item->percentual_faturamento_material)->toBe('44.44');
});

it('limita percentuais da linha do controle de as entre zero e cem com duas casas decimais', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => 'PERC-AS-LIMITE',
        'unidade' => $obra->unidade,
    ]);
    $item = $controle->itens()->create([
        'grupo' => 'Civil',
        'numero_as' => 'AS-PERC-LIMITE',
        'escopo' => 'Escopo Percentual Limite',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $item->id, [
            'percentual_faturamento_mao_obra' => '150,999',
            'percentual_faturamento_material' => '-10',
        ]);

    expect($item->refresh()->percentual_faturamento_mao_obra)->toBe('100.00')
        ->and($item->percentual_faturamento_material)->toBe('0.00');
});

it('aplica percentuais padrao ao selecionar novo escopo no controle de as', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => 'PERC-AS-ESCOPO',
        'unidade' => $obra->unidade,
    ]);
    $item = $controle->itens()->create([
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => 'AS-PERC-ESCOPO',
        'escopo' => 'Escopo Percentual Selecionado',
        'percentual_faturamento_mao_obra_default' => 72.5,
        'percentual_faturamento_material_default' => 27.5,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'percentual_faturamento_mao_obra' => '60',
            'percentual_faturamento_material' => '40',
        ]);

    expect($item->refresh()->as_escopo_id)->toBe($escopo->id)
        ->and($item->percentual_faturamento_mao_obra)->toBe('72.50')
        ->and($item->percentual_faturamento_material)->toBe('27.50');
});

it('exibe faturado pelo valor das notas aprovadas no controle de as', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create([
        'codigo' => 'OBR-AS-FAT',
        'unidade' => 'Unidade Faturado AS',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'ASFAT',
        'unidade' => 'Unidade Faturado AS',
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Shell',
        'numero_as' => '01',
        'escopo' => 'Escopo faturado',
        'valor_global_a' => 1000,
        'valor_acumulado_medido' => 888,
        'saldo' => 112,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);

    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => autorizacaoFiscalParaItem($item)->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'numero_nf' => 'NF-AS-APROVADA',
        'valor_acumulado_medido_nf' => 250.75,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => autorizacaoFiscalParaItem($item)->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'numero_nf' => 'NF-AS-PENDENTE',
        'valor_acumulado_medido_nf' => 900,
        'status' => StatusControleNotaFiscalNota::PENDENTE->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->set("itens.{$item->id}.faturado", 999)
        ->assertSee('250,75')
        ->assertSee('749,25')
        ->assertDontSee('888,00')
        ->assertDontSee('900,00')
        ->assertDontSee('999,00');
});

it('aplica filtros equivalentes ao controle de nota fiscal no controle de as', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Filtro AS',
        'cnpj' => '88.888.888/0001-88',
        'tipo' => 'CONSTRUTORA',
    ]);
    $obraElegivel = Obras::factory()->create([
        'projeto_id' => Projeto::factory()->create([
            'capex_aprovado_diretoria_valor' => 500000,
        ]),
        'codigo' => 'OBR-FILTRO-AS-OK',
        'unidade' => 'Unidade Filtro AS OK',
        'status' => 'Obras',
        'inicio' => '2026-05-10',
        'fim' => '2026-06-10',
    ]);
    $obraForaFiltro = Obras::factory()->create([
        'projeto_id' => Projeto::factory()->create([
            'capex_aprovado_diretoria_valor' => 100000,
        ]),
        'codigo' => 'OBR-FILTRO-AS-FORA',
        'unidade' => 'Unidade Filtro AS Fora',
        'status' => 'Em processo',
        'inicio' => '2026-07-10',
        'fim' => '2026-08-10',
    ]);
    $controleElegivel = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obraElegivel->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'aprovado',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-OK',
        'unidade' => $obraElegivel->unidade,
    ]);
    $controleForaFiltro = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obraForaFiltro->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-FORA',
        'unidade' => $obraForaFiltro->unidade,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleElegivel->id,
        'empresa' => $fornecedor->nome,
        'valor_global_a' => 1500,
        'percentual_total' => 100,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleForaFiltro->id,
        'empresa' => 'Outro Fornecedor',
        'valor_global_a' => 100,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee('Filtros')
        ->set('filtroStatus', ['aprovado'])
        ->set('filtroStatusUnidade', ['Obras'])
        ->set('filtroFornecedor', [$fornecedor->nome])
        ->set('filtroInicioDe', '2026-05-01')
        ->set('filtroInicioAte', '2026-05-31')
        ->set('filtroFimDe', '2026-06-01')
        ->set('filtroFimAte', '2026-06-30')
        ->set('filtroCapexMin', '400000')
        ->set('filtroValorMin', '1000')
        ->assertSee('Unidade Filtro AS OK')
        ->assertDontSee('Unidade Filtro AS Fora')
        ->assertSee('Filtros ativos')
        ->assertSee('Status do controle:')
        ->assertSee('Valor fechado:')
        ->call('removerFiltro', 'filtroFornecedor')
        ->assertSet('filtroFornecedor', [])
        ->call('limparFiltros')
        ->assertSet('filtroStatus', [])
        ->assertSet('filtroValorMin', null);
});

it('exibe os valores da obra apenas para leitura', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    ControleAutorizacaoServicoResumo::create([
        'obra_id' => $obra->id,
        'oi_shell' => 1250.50,
        'valor_final_adicional_smart' => 350.25,
    ]);
    $controle = $obra->controlesNotaFiscal()->first();
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'escopo' => 'Projeto',
        'valor_global_a' => 350.25,
        'percentual_total' => 100,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_adicional_id' => asaFiscalParaAuxiliar($auxiliar)->id,
        'numero_nf' => 'NF-ADICIONAL-VALORES',
        'valor_acumulado_medido_nf' => 350.25,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee('1.250,50')
        ->assertSee('350,25')
        ->assertDontSee('Salvar valores da obra');
});

it('exibe os adicionais da asa abaixo da acao de adicionar linha somente para leitura', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create([
        'codigo' => 'OBR-ASA-ADICIONAL',
        'unidade' => 'Unidade ASA Adicional',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => 'ASA-ADD',
        'unidade' => $obra->unidade,
    ]);
    $controle->itens()->create([
        'grupo' => 'Civil',
        'numero_as' => 'AS-BASE',
        'escopo' => 'Escopo base',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
    ]);
    ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-ADICIONAL-01',
        'escopo' => 'Projeto executivo adicional',
        'empresa' => 'Fornecedor adicional',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 70,
        'percentual_faturamento_material' => 30,
        'valor_global_a' => 350.25,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 350.25,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSeeInOrder([
            'Adicionar linha',
            'Adicionais somados na linha da obra',
            'ASA-ADICIONAL-01',
            'Projeto executivo adicional',
            'Fornecedor adicional',
            '350,25',
        ])
        ->assertSee('Adicional')
        ->assertDontSee('Salvar valores da obra');

    expect($component->html())->toMatch('/>\s*2\s*<\/span>/');
});

it('envia asa pelo controle de as liberando linha adicional para fornecedor', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ], [
        'email' => 'gestor.envio.asa@example.com',
    ]);
    $gestorProjeto = User::factory()->active()->create([
        'email' => 'gestor.projeto.envio.asa@example.com',
    ]);

    Mail::fake();
    Storage::fake('r2');

    $obra = Obras::factory()->create([
        'codigo' => 'OBR-ENVIA-ASA',
        'unidade' => 'Unidade Envio ASA',
    ]);
    $obra->projeto()->update(['resp_eng' => $gestorProjeto->id]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Envio ASA',
        'cnpj' => '77.777.777/0001-77',
        'email' => 'fornecedor.envio.asa@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $usuarioFornecedor = User::factory()->active()->create([
        'email' => 'contato.fornecedor.envio.asa@example.com',
        'construtoras_id' => $fornecedor->id,
    ]);
    $usuarioFornecedorNaoSelecionado = User::factory()->active()->create([
        'email' => 'fornecedor.nao.selecionado.asa@example.com',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => 'ASA-ENV',
        'unidade' => $obra->unidade,
    ]);
    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $fornecedor->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovado',
        'aprovado_orcamento_por_id' => $user->id,
        'aprovado_orcamento_em' => now(),
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
    Storage::disk('r2')->put('autorizacao-servico-adicional/asa-envio/pdf/ASA-ENVIO-APROVADA.pdf', '%PDF-1.4 fake pdf content');
    $asaAprovada = Asa::create([
        'numero_asa' => 'ASA-ENVIO-APROVADA',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->projeto?->sigla,
        'endereco' => $obra->endereco,
        'contrato' => 'Projeto',
        'status' => 'criada',
        'codigo_as_emitida' => 'ASA-ENVIO-01',
        'data_solicitacao' => now()->toDateString(),
        'data_aprovacao' => now()->toDateString(),
        'objeto' => 'ASA aprovada',
        'descricao' => 'Projeto executivo para envio',
        'valor_bruto' => 350.25,
        'desconto' => 0,
        'valor_total' => 350.25,
        'gestor_id' => $user->id,
        'solicitante' => $fornecedor->nome,
        'elaboracao_aditivo_id' => $aditivo->id,
        'as_pdf' => 'autorizacao-servico-adicional/asa-envio/pdf/ASA-ENVIO-APROVADA.pdf',
    ]);
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-ENVIO-01',
        'escopo' => 'Projeto executivo para envio',
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 70,
        'percentual_faturamento_material' => 30,
        'valor_global_a' => 350.25,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 350.25,
    ]);
    $asaAprovada->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Enviar AS')
        ->callAction('enviarAsa', [
            'para' => ['contato.fornecedor.envio.asa@example.com'],
            'cc' => [$gestorProjeto->email],
            'cco' => [],
            'modo_excel_asa' => 'gerar',
        ], [
            'auxiliarId' => $auxiliar->id,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($auxiliar->refresh()->liberado_para_fornecedor_at)->not->toBeNull();

    expect($usuarioFornecedor->notifications()
        ->where('data->title', 'Item liberado para fornecedor')
        ->exists())->toBeTrue()
        ->and($usuarioFornecedorNaoSelecionado->notifications()
            ->where('data->title', 'Item liberado para fornecedor')
            ->exists())->toBeFalse();

    Mail::assertSent(EnviarPdfMail::class, function (EnviarPdfMail $mail) use ($gestorProjeto, $user): bool {
        return $mail->hasTo('contato.fornecedor.envio.asa@example.com')
            && ! $mail->hasTo('fornecedor.envio.asa@example.com')
            && $mail->hasCc($gestorProjeto->email)
            && $mail->hasBcc($user->email)
            && $mail->assunto === 'AS liberada ASA-ENVIO-APROVADA'
            && count($mail->anexos) === 1
            && str_ends_with((string) $mail->anexos[0]['nome'], '.xlsx')
            && $mail->anexos[0]['mime'] === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            && filled($mail->anexos[0]['conteudo']);
    });
});

it('nao permite enviar asa sem aprovacao do orcamentista', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);

    Mail::fake();

    $obra = Obras::factory()->create([
        'codigo' => 'OBR-ASA-PENDENTE',
        'unidade' => 'Unidade ASA Pendente',
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor ASA Pendente',
        'cnpj' => '88.888.888/0001-88',
        'email' => 'fornecedor.asa.pendente@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => 'ASA-PEND',
        'unidade' => $obra->unidade,
    ]);
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
    $asaValores = Asa::create([
        'numero_asa' => 'ASA-PENDENTE-ORCAMENTO',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->projeto?->sigla,
        'endereco' => $obra->endereco,
        'contrato' => 'Projeto',
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'codigo_as_emitida' => 'ASA-PENDENTE-01',
        'data_solicitacao' => now()->toDateString(),
        'objeto' => 'ASA pendente',
        'descricao' => 'Projeto executivo pendente',
        'valor_bruto' => 350.25,
        'desconto' => 0,
        'valor_total' => 350.25,
        'gestor_id' => $user->id,
        'solicitante' => $fornecedor->nome,
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-PENDENTE-01',
        'escopo' => 'Projeto executivo pendente',
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 70,
        'percentual_faturamento_material' => 30,
        'valor_global_a' => 350.25,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 350.25,
    ]);
    $asaValores->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Criar AS')
        ->assertDontSee('Enviar AS')
        ->callAction('enviarAsa', [
            'para' => ['fornecedor.asa.pendente@example.com'],
            'cc' => [],
            'cco' => [],
            'modo_excel_asa' => 'gerar',
        ], [
            'auxiliarId' => $auxiliar->id,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($auxiliar->refresh()->liberado_para_fornecedor_at)->toBeNull();
    Mail::assertNothingSent();
});

it('aprova asa no controle de as antes de permitir envio ao fornecedor', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);

    $obra = Obras::factory()->create([
        'codigo' => 'OBR-ASA-APROVAR',
        'unidade' => 'Unidade ASA Aprovar',
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor ASA Aprovar',
        'cnpj' => '99.999.999/0001-99',
        'email' => 'fornecedor.asa.aprovar@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => 'ASA-APR',
        'unidade' => $obra->unidade,
    ]);
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
    $asa = Asa::create([
        'numero_asa' => 'ASA-APROVAR-ORCAMENTO',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->projeto?->sigla,
        'endereco' => $obra->endereco,
        'contrato' => 'Projeto',
        'status' => AsStatus::EM_APROVACAO_ORCAMENTO,
        'codigo_as_emitida' => 'ASA-APROVAR-01',
        'data_solicitacao' => now()->toDateString(),
        'objeto' => 'ASA para aprovar',
        'descricao' => 'Projeto executivo para aprovar',
        'valor_bruto' => 350.25,
        'desconto' => 0,
        'valor_total' => 350.25,
        'gestor_id' => $user->id,
        'solicitante' => $fornecedor->nome,
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-APROVAR-01',
        'escopo' => 'Projeto executivo para aprovar',
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 70,
        'percentual_faturamento_material' => 30,
        'valor_global_a' => 350.25,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 350.25,
    ]);
    $asa->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();

    config(['filesystems.media_disk' => 'r2']);
    Storage::fake('r2');

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Criar AS')
        ->assertDontSee('Enviar AS')
        ->call('abrirModalGerarAsAsa', $auxiliar->id)
        ->assertSet('gerarAsModalAuxiliarId', $auxiliar->id)
        ->assertSet('gerarAsValorFechado', 350.25)
        ->call('confirmarGeracaoAs', [['parcela' => 'Parcela 01', 'percentual' => '100,00', 'valor' => '350,25', 'observacao' => '']])
        ->assertNotified();

    expect($asa->refresh()->status)->toBe(AsStatus::CRIADA)
        ->and($aditivo->refresh()->status_fluxo)->toBe('aprovado')
        ->and($aditivo->aprovado_orcamento_por_id)->toBe($user->id)
        ->and($aditivo->aprovado_orcamento_em)->not->toBeNull()
        ->and($auxiliar->refresh()->liberado_para_fornecedor_at)->toBeNull();
});

it('calcula valores da obra pelo somatorio das linhas de escopo', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopoShell = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '33.1',
        'escopo' => 'ESTRUTURA METALICA',
        'is_active' => true,
    ]);
    $escopoRecheio = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoShell->id,
        'grupo' => $escopoShell->grupo,
        'numero_as' => $escopoShell->numero_as,
        'escopo' => $escopoShell->escopo,
        'valor_estimado_as' => 1000,
        'valor_global_a' => 800,
        'percentual_total' => 100,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoRecheio->id,
        'grupo' => $escopoRecheio->grupo,
        'numero_as' => $escopoRecheio->numero_as,
        'escopo' => $escopoRecheio->escopo,
        'valor_estimado_as' => 250,
        'valor_global_a' => 175,
        'percentual_total' => 100,
    ]);
    ControleAutorizacaoServicoResumo::create([
        'obra_id' => $obra->id,
        'oi_shell' => 99999,
        'oi_recheio' => 99999,
        'valor_inicial_shell' => 99999,
        'valor_inicial_recheio' => 99999,
        'valor_final_shell' => 99999,
        'valor_final_recheio' => 99999,
        'valor_final_adicional_hp' => 99999,
        'valor_final_adicional_smart' => 25,
    ]);
    $auxiliarProjeto = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'escopo' => 'Projeto',
        'valor_global_a' => 50,
        'percentual_total' => 100,
    ]);
    $auxiliarLegalizacao = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Legalização',
        'escopo' => 'Legalização',
        'valor_global_a' => 30,
        'percentual_total' => 100,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_adicional_id' => asaFiscalParaAuxiliar($auxiliarProjeto)->id,
        'numero_nf' => 'NF-ADICIONAL-PROJETO',
        'valor_acumulado_medido_nf' => 50,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_adicional_id' => asaFiscalParaAuxiliar($auxiliarLegalizacao)->id,
        'numero_nf' => 'NF-ADICIONAL-LEGALIZACAO',
        'valor_acumulado_medido_nf' => 30,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee('Adicional')
        ->assertDontSee('Adicional PP')
        ->assertDontSee('Adicional Smart')
        ->assertDontSee('Adicional HP')
        ->assertSee('Desvio')
        ->assertSee('% Desvio')
        ->assertSee('% Shell')
        ->assertSee('% Adicional')
        ->assertSee('border-l-2 border-gray-300 dark:border-white/20', false)
        ->assertSee('1.000,00')
        ->assertSee('250,00')
        ->assertSee('1.250,00')
        ->assertSee('800,00')
        ->assertSee('175,00')
        ->assertSee('975,00')
        ->assertSee('80,00')
        ->assertDontSee('1.080,00')
        ->assertDontSee('25,00')
        ->assertSee('1.055,00')
        ->assertSee('-195,00')
        ->assertSee('-18,48%')
        ->assertSee('75,83%')
        ->assertSee('7,58%')
        ->assertDontSee('99.999,00');
});

it('calcula adicional da obra pelo valor das notas adicionais aprovadas', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-ADICIONAL-NF',
        'unidade' => $obra->unidade,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Shell',
        'valor_estimado_as' => 1000,
        'valor_global_a' => 1000,
        'percentual_total' => 100,
    ]);
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-ADICIONAL-NF',
        'escopo' => 'Adicional aprovado',
        'valor_global_a' => 777.77,
        'percentual_total' => 100,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_adicional_id' => asaFiscalParaAuxiliar($auxiliar)->id,
        'numero_nf' => 'NF-AD-APROVADA',
        'cnpj_fornecedor' => '11.111.111/0001-11',
        'valor_acumulado_medido_nf' => 123.45,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_adicional_id' => asaFiscalParaAuxiliar($auxiliar)->id,
        'numero_nf' => 'NF-AD-PENDENTE',
        'cnpj_fornecedor' => '22.222.222/0001-22',
        'valor_acumulado_medido_nf' => 456.78,
        'status' => StatusControleNotaFiscalNota::PENDENTE->value,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_adicional_id' => asaFiscalParaAuxiliar($auxiliar)->id,
        'numero_nf' => 'NF-AD-REPROVADA',
        'cnpj_fornecedor' => '33.333.333/0001-33',
        'valor_acumulado_medido_nf' => 98.76,
        'status' => StatusControleNotaFiscalNota::REPROVADO->value,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('123,45')
        ->assertSee('1.123,45')
        ->assertDontSee('456,78')
        ->assertDontSee('98,76')
        ->assertDontSee('1.777,77');

    expect(substr_count($component->html(), '777,77'))->toBe(1);
});

it('exibe valores de adicional asa sem estimado e com valor fechado pos desconto', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => $obra->projeto?->sigla,
        'unidade' => $obra->unidade,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Valores ASA',
        'cnpj' => '44.444.444/0001-44',
        'email' => 'fornecedor.valores.asa@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $fornecedor->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovado',
        'aprovado_orcamento_por_id' => $user->id,
        'aprovado_orcamento_em' => now(),
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
    $asaValores = Asa::create([
        'numero_asa' => 'ASA-VALORES-CONTROLE',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->projeto?->sigla,
        'endereco' => $obra->endereco,
        'contrato' => 'Projeto',
        'status' => AsStatus::APROVADO,
        'codigo_as_emitida' => 'ASA-VALORES-CONTROLE',
        'data_solicitacao' => now()->toDateString(),
        'data_aprovacao' => now()->toDateString(),
        'objeto' => 'ASA valores controle',
        'descricao' => 'Projeto valores ASA',
        'valor_bruto' => 1000,
        'desconto' => 250,
        'valor_total' => 750,
        'gestor_id' => $user->id,
        'solicitante' => $fornecedor->nome,
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-VALORES-CONTROLE',
        'escopo' => 'Projeto valores ASA',
        'empresa' => 'Fornecedor antigo ASA',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 70,
        'percentual_faturamento_material' => 30,
        'valor_global_a' => 9999,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 9999,
    ]);
    $asaValores->forceFill(['controle_nota_fiscal_auxiliar_id' => $auxiliar->id])->save();
    ControleNotaFiscalNota::create([
        'autorizacao_servico_adicional_id' => $asaValores->id,
        'numero_nf' => 'NF-ASA-FATURADA',
        'valor_acumulado_medido_nf' => 300,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('ASA-VALORES-CONTROLE')
        ->assertSee('Fornecedor Valores ASA')
        ->assertSee('750,00')
        ->assertSee('650,00')
        ->assertSee('65,00%')
        ->assertSee('350,00')
        ->assertSee('35,00%')
        ->assertSee('300,00')
        ->assertSee('450,00')
        ->assertDontSee('9.999,00')
        ->assertDontSee('1.000,00')
        ->assertDontSee('Fornecedor antigo ASA');

    expect($component->html())->toContain('<span class="text-gray-400">-</span>');
});

it('prioriza asa vinculada diretamente ao auxiliar fiscal', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $fornecedorVinculado = Construtora::create([
        'nome' => 'Fornecedor ASA Vinculada',
        'cnpj' => '87.111.111/0001-00',
        'tipo' => 'CONSTRUTORA',
    ]);
    $fornecedorSolto = Construtora::create([
        'nome' => 'Fornecedor ASA Solta',
        'cnpj' => '87.111.111/0001-01',
        'tipo' => 'CONSTRUTORA',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => ControleNotaFiscal::STATUS_ATIVO,
        'data_base' => now()->toDateString(),
        'sigla' => $obra->projeto?->sigla,
        'unidade' => $obra->unidade,
    ]);
    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-DIRETA-CONTROLE',
        'escopo' => 'Escopo solto',
        'empresa' => $fornecedorSolto->nome,
        'percentual_total' => 100,
        'valor_global_a' => 999,
    ]);
    $aditivoVinculado = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $fornecedorVinculado->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovado',
        'aprovado_orcamento_por_id' => $user->id,
        'aprovado_orcamento_em' => now(),
    ]);
    $aditivoSolto = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $fornecedorSolto->id,
        'data' => now()->toDateString(),
        'status_fluxo' => 'aprovado',
        'aprovado_orcamento_por_id' => $user->id,
        'aprovado_orcamento_em' => now(),
    ]);

    Asa::create([
        'numero_asa' => 'ASA-VINCULADA',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->projeto?->sigla,
        'contrato' => 'Projeto',
        'status' => 'aprovado',
        'codigo_as_emitida' => 'OUTRA-ASA',
        'objeto' => 'ASA vinculada',
        'descricao' => 'Escopo vinculado',
        'valor_total' => 321,
        'solicitante' => $fornecedorVinculado->nome,
        'elaboracao_aditivo_id' => $aditivoVinculado->id,
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
    ]);
    Asa::create([
        'numero_asa' => 'ASA-SOLTA',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->projeto?->sigla,
        'contrato' => 'Projeto',
        'status' => 'aprovado',
        'codigo_as_emitida' => 'ASA-DIRETA-CONTROLE',
        'objeto' => 'ASA solta',
        'descricao' => 'Escopo solto',
        'valor_total' => 654,
        'solicitante' => $fornecedorSolto->nome,
        'elaboracao_aditivo_id' => $aditivoSolto->id,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class);

    expect($component->instance()->valorFechadoAuxiliar($auxiliar->refresh()))->toBe(321.0)
        ->and($component->instance()->fornecedorAuxiliar($auxiliar->refresh()))->toBe($fornecedorVinculado->nome);
});

it('carrega apenas notas aprovadas para calcular faturado no controle de as', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-NOTAS',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'valor_global_a' => 1000,
        'percentual_total' => 100,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => autorizacaoFiscalParaItem($item)->id,
        'numero_nf' => '1001',
        'cnpj_fornecedor' => '11.111.111/0001-11',
        'valor_acumulado_medido_nf' => 250,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => autorizacaoFiscalParaItem($item)->id,
        'numero_nf' => '1002',
        'cnpj_fornecedor' => '22.222.222/0001-22',
        'valor_acumulado_medido_nf' => 999,
        'status' => StatusControleNotaFiscalNota::REPROVADO->value,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('250,00')
        ->assertDontSee('999,00');

    $itemCarregado = $component->instance()
        ->getObrasProperty()
        ->first()
        ->controlesNotaFiscal
        ->first()
        ->itens
        ->first();

    expect($itemCarregado->relationLoaded('notasFiscais'))->toBeTrue()
        ->and($itemCarregado->notasFiscais)->toHaveCount(1)
        ->and($itemCarregado->notasFiscais->first()->status)->toBe(StatusControleNotaFiscalNota::APROVADO->value);
});

it('nao carrega notas dos itens quando a obra esta fechada no controle de as', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-FECHADA',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'valor_global_a' => 1000,
        'percentual_total' => 100,
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => autorizacaoFiscalParaItem($item)->id,
        'numero_nf' => '2001',
        'cnpj_fornecedor' => '33.333.333/0001-33',
        'valor_acumulado_medido_nf' => 250,
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->assertSee($obra->unidade);

    $itemCarregado = $component->instance()
        ->getObrasProperty()
        ->first()
        ->controlesNotaFiscal
        ->first()
        ->itens
        ->first();

    expect($itemCarregado->relationLoaded('notas'))->toBeFalse();
});

it('nao renderiza acao para salvar valores da obra', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create();
    ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->assertDontSee('Salvar valores da obra');
});

it('nao renderiza acoes de mutacao dos escopos para usuario apenas leitura', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertDontSee('Salvar linha')
        ->assertDontSee('Criar AS')
        ->assertDontSee('Enviar AS')
        ->assertDontSee('Orçamento')
        ->assertDontSee('Cancelar AS')
        ->assertDontSee('Adicionar linha');
});

it('nao renderiza acoes de mutacao apenas por papel super admin sem permissoes shield', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $user->assignRole(Role::findOrCreate('super_admin', 'web'));
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertDontSee('Salvar linha')
        ->assertDontSee('Criar AS')
        ->assertDontSee('Enviar AS')
        ->assertDontSee('Orçamento')
        ->assertDontSee('Cancelar AS')
        ->assertDontSee('Adicionar linha');
});

it('exibe as acoes dos escopos ao final com texto por extenso', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    $html = Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Salvar linha')
        ->assertSee('Criar AS')
        ->assertDontSee('Enviar AS')
        ->assertDontSee('Orçamento')
        ->assertDontSee('Cancelar AS')
        ->assertSee('flex items-center gap-1.5 whitespace-nowrap', false)
        ->assertSee('dark:text-white dark:hover:bg-primary-500/10 dark:hover:text-primary-300', false)
        ->assertDontSee('bg-primary-50/70', false)
        ->html();

    expect(strpos($html, '>Status<'))->toBeLessThan(strrpos($html, '>Ações<'));
});

it('exibe as acoes quando as permissoes shield de fluxo estao selecionadas', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Salvar linha')
        ->assertSee('Criar AS')
        ->assertDontSee('Enviar AS')
        ->assertDontSee('Orçamento')
        ->assertDontSee('Cancelar AS')
        ->assertSee('Adicionar linha');
});

it('exibe visualizar as e oculta acoes indisponiveis quando a as ja foi criada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '1.01',
        'escopo' => 'Escopo criado',
        'is_active' => true,
    ]);
    $construtora = Construtora::create([
        'nome' => 'Fornecedor Visualizar AS',
        'cnpj' => '55.555.555/0001-55',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'percentual_total' => 100,
        'valor_global_a' => 1500,
    ]);
    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $construtora->id,
        'numero_as' => 'AS-VIEW-001',
        'valor' => 1500,
        'status' => AsStatus::CRIADA,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Visualizar AS')
        ->assertSee(EditAutorizacaoServico::getUrl(['record' => $autorizacao]), false)
        ->assertDontSee('Criar AS')
        ->assertSee('Salvar linha')
        ->assertDontSee('Orçamento')
        ->assertSee('Enviar AS')
        ->assertSee('dark:text-white dark:hover:bg-primary-500/10 dark:hover:text-primary-300', false)
        ->assertSee('dark:text-white dark:hover:bg-danger-500/10 dark:hover:text-danger-300', false)
        ->assertDontSee('bg-sky-50/70', false)
        ->assertDontSee('bg-danger-50/70', false)
        ->assertSee('Cancelar AS');
});

it('autoriza criacao de as pela permissao configurada no shield', function () {
    $user = createActiveUserWithPermissions([
        'Create:AutorizacaoServico',
    ]);

    expect($user->can('create', AutorizacaoServico::class))->toBeTrue();
});

it('exibe o escopo em uma unica coluna com select e salva grupo e as apenas ao salvar', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->assertSet("asEscopoOptions.{$escopo->id}", 'AR COND. INSTALAÇÃO')
        ->set('obrasExpandidas', [$obra->id])
        ->set("itens.{$item->id}.as_escopo_id", $escopo->id)
        ->assertSet("itens.{$item->id}.as_escopo_id", $escopo->id)
        ->assertSee('Ar Condicionado')
        ->assertSee('03.1')
        ->assertSee('AR COND. INSTALAÇÃO')
        ->assertSee('Selecionar escopo')
        ->assertSee('fornecedorAlterado', false)
        ->assertSee('cpr-row-unsaved', false);

    expect(substr_count($component->html(), '>Selecionar escopo</th>'))->toBe(0);

    $item->refresh();

    expect($item->as_escopo_id)->toBeNull()
        ->and($item->grupo)->toBeNull()
        ->and($item->numero_as)->toBeNull()
        ->and($item->escopo)->toBeNull();

    $component
        ->call('salvarItemComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
        ])
        ->assertNotified();

    $item->refresh();

    expect($item->as_escopo_id)->toBe($escopo->id)
        ->and($item->grupo)->toBe('Ar Condicionado')
        ->and($item->numero_as)->toBe('03.1')
        ->and($item->escopo)->toBe('AR COND. INSTALAÇÃO');
});

it('cria as com permissao de criacao sem exigir permissao de update', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    Mail::fake();
    Storage::fake('r2');

    $obra = Obras::factory()->create([
        'engenharia' => $user->name,
        'inicio' => '2026-05-10',
        'fim' => '2026-06-10',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'data_entrega' => '2026-05-25',
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Criacao',
        'cnpj' => '55.555.555/0001-55',
        'email' => 'fornecedor.criacao@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '1.500,00',
            'valor_fechado' => '1.250,00',
            'percentual_faturamento_mao_obra' => '55,56',
            'percentual_faturamento_material' => '44,44',
        ])
        ->assertSet('gerarAsModalItemId', $item->id)
        ->assertSet("itens.{$item->id}.as_escopo_id", $escopo->id)
        ->assertSet("itens.{$item->id}.construtora_id", $fornecedor->id)
        ->assertSet('gerarAsParcelas.0.percentual', '100,00')
        ->assertSet('gerarAsParcelas.0.valor', '1.500,00')
        ->assertSet('gerarAsParcelas.0.parcela', 'Parcela 1')
        ->assertSee('gs-as-create-modal', false)
        ->assertFormSet([
            'parcelamento' => [[
                'parcela' => 'Parcela 1',
                'percentual' => '100,00',
                'valor' => '1.500,00',
                'observacao' => '>> FATURAR SOMENTE COM AUTORIZAÇÃO DO(A) GESTOR(A) SMART FIT',
            ]],
        ], 'gerarAsValoresParcelamentoForm')
        ->assertDontSee('Planilha Excel opcional');

    expect($item->refresh()->autorizacaoServico()->exists())->toBeFalse();

    $component
        ->set('gerarAsModalDados', [])
        ->call(
            'confirmarGeracaoAs',
            [
                [
                    'parcela' => 'Parcela 01',
                    'percentual' => '40,00',
                    'valor' => '500,00',
                    'observacao' => 'Entrada',
                ],
                [
                    'parcela' => 'Parcela 02',
                    'percentual' => '60,00',
                    'valor' => '750,00',
                    'observacao' => 'Final',
                ],
            ],
            [
                'data_inicio_servico' => '2026-05-11',
                'data_termino_servico' => '2026-06-11',
                'data_entrega_material' => '2026-05-26',
                'desconto_autorizacao_servico' => '250,00',
                'descricao_servico_pdf' => 'Materiais e complementos conforme lista anexada',
            ],
        )
        ->assertNotified()
        ->assertSet('gerarAsModalItemId', null);

    $item->refresh();

    $autorizacaoServico = $item->autorizacaoServico;

    expect($autorizacaoServico)->not->toBeNull()
        ->and($item->as_escopo_id)->toBe($escopo->id)
        ->and($item->empresa)->toBe('Fornecedor Criacao')
        ->and($item->percentual_faturamento_mao_obra)->toBe('55.56')
        ->and($item->percentual_faturamento_material)->toBe('44.44');

    $pdfData = app(AutorizacaoServicoPdfService::class)->getViewData($autorizacaoServico);

    expect($autorizacaoServico)
        ->status->toBe(AsStatus::CRIADA)
        ->valor_estimado->toBe('1500.00')
        ->valor->toBe('1250.00')
        ->parcelamento_autorizacao_servico->toMatchArray([
            [
                'parcela' => 'Parcela 01',
                'percentual' => 40,
                'valor' => 500,
                'observacao' => 'Entrada',
            ],
            [
                'parcela' => 'Parcela 02',
                'percentual' => 60,
                'valor' => 750,
                'observacao' => 'Final',
            ],
        ])
        ->tipo_contratacao->toBeNull()
        ->descricao_servico_pdf->toContain('Materiais e complementos conforme lista anexada')
        ->created_by_id->toBe($user->id)
        ->enviado_por_id->toBeNull();

    expect($pdfData['percentualFaturamentoMaoObra'])->toBe(55.56)
        ->and($pdfData['percentualFaturamentoMaterial'])->toBe(44.44);

    expect($autorizacaoServico->data_inicio_servico?->toDateString())->toBe('2026-05-11')
        ->and($autorizacaoServico->data_termino_servico?->toDateString())->toBe('2026-06-11')
        ->and($autorizacaoServico->data_entrega_material?->toDateString())->toBe('2026-05-26');

    expect($item->liberado_para_fornecedor_at)->toBeNull();
    expect($item->valor_global_a)->toBe('1250.00');

    expect($item->refresh()->autorizacaoServico?->anexo_autorizacao_servico)
        ->not->toBeEmpty();

    Mail::assertNothingSent();
});

it('permite abrir criacao de as com valor fechado em branco quando valor estimado esta preenchido', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    Storage::fake('r2');

    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-BRANCO',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'Serviços civis',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Valor Branco',
        'cnpj' => '77.777.777/0001-77',
        'email' => 'fornecedor.valor.branco@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '1.500,00',
            'valor_fechado' => '',
        ])
        ->assertSet('gerarAsModalItemId', $item->id)
        ->assertSet('criacaoAsPendente', []);

    expect($item->refresh()->autorizacaoServico()->exists())->toBeFalse();
});

it('nao permite gerar as com valor estimado em branco mesmo quando valor fechado esta preenchido', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-EST-ZERO',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.3',
        'escopo' => 'Serviços civis com estimado zero',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Estimado Zero',
        'cnpj' => '99.999.999/0001-99',
        'email' => 'fornecedor.estimado.zero@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '',
            'valor_fechado' => '1.250,00',
        ])
        ->assertNotified()
        ->assertSet('criacaoAsPendente', [])
        ->assertSet('gerarAsModalItemId', null);

    $autorizacaoServico = $item->refresh()->autorizacaoServico;

    expect($autorizacaoServico)->toBeNull();
});

it('exige percentual nas parcelas do modal de geracao da as', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    Storage::fake('r2');

    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-PERCENTUAL',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.2',
        'escopo' => 'Serviços civis adicionais',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Percentual',
        'cnpj' => '88.888.888/0001-88',
        'email' => 'fornecedor.percentual@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '1.500,00',
            'valor_fechado' => '1.250,00',
        ])
        ->call(
            'confirmarGeracaoAs',
            [[
                'parcela' => 'Parcela 1',
                'percentual' => null,
                'valor' => '0,00',
                'observacao' => '',
            ]],
            [
                'data_inicio_servico' => null,
                'data_termino_servico' => null,
                'data_entrega_material' => null,
                'desconto_autorizacao_servico' => '0,00',
            ],
        )
        ->assertSet('gerarAsParcelas.0.percentual', '0,00')
        ->assertHasErrors(['gerarAsParcelas']);

    expect($item->refresh()->autorizacaoServico()->exists())->toBeFalse();
});

it('adiciona parcelas no modal usando o proximo numero disponivel', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-PARCELAS',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.4',
        'escopo' => 'Serviços civis parcelados',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Parcelas',
        'cnpj' => '11.111.111/0001-11',
        'email' => 'fornecedor.parcelas@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '1.500,00',
            'valor_fechado' => '1.250,00',
        ])
        ->callFormComponentAction('parcelamento', 'add', formName: 'gerarAsValoresParcelamentoForm')
        ->assertSet('gerarAsPdfFormData.parcelamento.1.parcela', 'Parcela 2');

    $parcelas = $component->get('gerarAsPdfFormData')['parcelamento'] ?? [];
    unset($parcelas[1]);

    $component
        ->set('gerarAsPdfFormData.parcelamento', array_values($parcelas))
        ->callFormComponentAction('parcelamento', 'add', formName: 'gerarAsValoresParcelamentoForm')
        ->assertSet('gerarAsPdfFormData.parcelamento.1.parcela', 'Parcela 2');
});

it('salva descricao rica e anexos informados na criacao da as pelo controle', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    Storage::fake('r2');

    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-RICH',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'ESCOPO PADRÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Rich',
        'cnpj' => '66.666.666/0001-66',
        'email' => 'fornecedor.rich@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    Storage::disk('r2')->put('autorizacao-servico/tmp/anexos/anexo-rich.png', 'anexo');

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '1.500,00',
            'valor_fechado' => '1.250,00',
        ])
        ->assertSee('Dados para Gerar AS')
        ->assertSee('Condições de pagamento')
        ->assertSee('Fechar')
        ->assertDontSee('Cancelar</button>', false)
        ->assertDontSee('Valores e Parcelamento')
        ->assertDontSee('Descrição do serviço')
        ->assertDontSee('Parcelamento da AS')
        ->assertDontSee('Tipo de contratação');

    $descricaoComponents = $component->instance()
        ->getSchema('gerarAsDescricaoForm')
        ?->getFlatComponents(withHidden: true);

    expect($descricaoComponents)->toHaveKeys(['descricao_servico_pdf', 'descricao_arquivo']);
    expect($descricaoComponents['descricao_arquivo']->getAcceptedFileTypes())->toBe([
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'image/avif',
    ]);
    expect($descricaoComponents['descricao_arquivo']->getMaxFiles())->toBe(1);

    $component
        ->fillForm([
            'desconto_autorizacao_servico' => '0,00',
            'parcelamento' => [[
                'parcela' => 'Parcela 01',
                'percentual' => '100,00',
                'valor' => '1.500,00',
                'observacao' => 'Parcela única',
            ]],
        ], 'gerarAsValoresParcelamentoForm')
        ->fillForm([
            'descricao_servico_pdf' => 'Descrição única da AS',
            'descricao_arquivo' => ['autorizacao-servico/tmp/anexos/anexo-rich.png'],
        ], 'gerarAsDescricaoForm')
        ->fillForm([
            'anexos_autorizacao_servico' => ['autorizacao-servico/tmp/anexos/anexo-rich.png'],
        ], 'gerarAsAnexosForm')
        ->call('confirmarGeracaoAs')
        ->assertNotified();

    $autorizacaoServico = AutorizacaoServico::query()->firstWhere('construtora_id', $fornecedor->id);

    expect($autorizacaoServico)
        ->not->toBeNull()
        ->tipo_contratacao->toBeNull()
        ->itens_descricao_servico_pdf->toMatchArray([[
            'descricao_tipo' => 'arquivo',
            'descricao' => 'Descrição única da AS',
            'descricao_arquivo' => ['autorizacao-servico/tmp/anexos/anexo-rich.png'],
        ]])
        ->anexos_autorizacao_servico->toBe(['autorizacao-servico/tmp/anexos/anexo-rich.png']);
});

it('envia as criada pelo controle gerando pdf antes do email', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $gestorProjeto = User::factory()->active()->create([
        'email' => 'gestor.projeto.envio.as@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    Mail::fake();
    Storage::fake('r2');

    $obra = Obras::factory()->create([
        'engenharia' => $user->name,
        'inicio' => '2026-05-10',
        'fim' => '2026-06-10',
    ]);
    $obra->projeto()->update(['resp_eng' => $gestorProjeto->id]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Envio Controle',
        'cnpj' => '55.555.555/0001-56',
        'email' => 'fornecedor.envio.controle@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    User::factory()->active()->create([
        'email' => 'contato.fornecedor.envio.controle@example.com',
        'construtoras_id' => $fornecedor->id,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-CONTROLE-ENVIO',
        'valor' => 1250,
        'valor_estimado' => 1500,
        'created_by_id' => $user->id,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => null,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_estimado_as' => 1500,
        'valor_global_a' => 1250,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->callAction('enviarAs', [], [
            'itemId' => $item->id,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $autorizacaoServico->refresh();

    expect($autorizacaoServico->status)->toBe(AsStatus::ENVIADA)
        ->and($autorizacaoServico->anexo_autorizacao_servico)->not->toBeEmpty()
        ->and($item->refresh()->liberado_para_fornecedor_at)->not->toBeNull()
        ->and($item->empresa)->toBe($fornecedor->nome);

    Storage::disk('r2')->assertExists($autorizacaoServico->anexo_autorizacao_servico);
    Mail::assertSent(AutorizacaoServicoMail::class, function (AutorizacaoServicoMail $mail) use ($gestorProjeto, $user): bool {
        return $mail->hasTo('contato.fornecedor.envio.controle@example.com')
            && ! $mail->hasTo('fornecedor.envio.controle@example.com')
            && $mail->hasCc($gestorProjeto->email)
            && $mail->hasBcc($user->email);
    });
});

it('cancela as pelo controle notificando por email', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ], [
        'email' => 'gestor.cancelamento.as@example.com',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    Mail::fake();

    $obra = Obras::factory()->create([
        'engenharia' => $user->name,
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Cancelamento Controle',
        'cnpj' => '66.666.666/0001-66',
        'email' => 'fornecedor.cancelamento.controle@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-CONTROLE-CANCELA',
        'valor' => 1250,
        'valor_estimado' => 1500,
        'created_by_id' => $user->id,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_estimado_as' => 1500,
        'valor_global_a' => 1250,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->callAction('cancelarAs', [
            'para' => ['fornecedor.cancelamento.controle@example.com'],
            'cc' => [$user->email],
            'cco' => [],
        ], [
            'itemId' => $item->id,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $autorizacaoServico->refresh();

    expect($autorizacaoServico->status)->toBe(AsStatus::CANCELADA)
        ->and($autorizacaoServico->cancelado_por_id)->toBe($user->id)
        ->and($autorizacaoServico->motivo_cancelamento)->toBe('Cancelamento manual pelo controle de AS.');

    Mail::assertSent(EnviarPdfMail::class, function (EnviarPdfMail $mail) use ($user): bool {
        return $mail->hasTo('fornecedor.cancelamento.controle@example.com')
            && $mail->hasCc($user->email)
            && $mail->assunto === 'AS cancelada AS-CONTROLE-CANCELA';
    });
});

it('bloqueia abertura do modal de as com valor fechado zero', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    Mail::fake();
    Storage::fake('r2');

    $obra = Obras::factory()->create([
        'engenharia' => $user->name,
        'inicio' => '2026-05-10',
        'fim' => '2026-06-10',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'data_entrega' => '2026-05-25',
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Valor Zero',
        'cnpj' => '66.666.666/0001-66',
        'email' => 'fornecedor.zero@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '0,00',
            'valor_fechado' => '0,00',
        ])
        ->assertNotified()
        ->assertSet('criacaoAsPendente', [])
        ->assertSet('gerarAsModalItemId', null);

    expect($item->refresh()->autorizacaoServico()->exists())->toBeFalse();
});

it('bloqueia desconto maior que o valor estimado no modal de geracao da as', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS-DESCONTO',
        'unidade' => $obra->unidade,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.7',
        'escopo' => 'Serviços com desconto',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Desconto Maior',
        'cnpj' => '12.345.678/0001-90',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '1.000,00',
        ])
        ->fillForm([
            'desconto_autorizacao_servico' => '1.000,01',
            'parcelamento' => [[
                'parcela' => 'Parcela 1',
                'percentual' => '100,00',
                'valor' => '0,00',
                'observacao' => '',
            ]],
        ], 'gerarAsValoresParcelamentoForm')
        ->call('confirmarGeracaoAs')
        ->assertHasErrors(['gerarAsDesconto'])
        ->assertSee('O desconto não pode ser maior que o valor inicial.');

    expect($item->refresh()->autorizacao_servico_id)->toBeNull();
});

it('bloqueia criacao de as sem escopo ou empresa mesmo com valores zerados', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Create:AutorizacaoServico',
    ]);
    $user->assignRole(Role::findOrCreate('Gestor', 'web'));
    $setorObras = Setor::firstOrCreate(['setor' => 'Obras']);
    $user->setores()->syncWithoutDetaching([$setorObras->id]);

    $obra = Obras::factory()->create([
        'engenharia' => $user->name,
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $itemSemEscopo = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $itemSemEmpresa = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Obrigatorio',
        'cnpj' => '77.777.777/0001-77',
        'email' => 'fornecedor.obrigatorio@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $itemSemEscopo->id, [
            'construtora_id' => $fornecedor->id,
            'valor_estimado' => '0,00',
            'valor_fechado' => '0,00',
        ])
        ->assertNotified()
        ->assertSet('criacaoAsPendente', [])
        ->assertSet('gerarAsModalItemId', null);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('criarAsComDados', $itemSemEmpresa->id, [
            'as_escopo_id' => $escopo->id,
            'valor_estimado' => '0,00',
            'valor_fechado' => '0,00',
        ])
        ->assertNotified()
        ->assertSet('criacaoAsPendente', [])
        ->assertSet('gerarAsModalItemId', null);

    expect($itemSemEscopo->refresh()->autorizacaoServico()->exists())->toBeFalse()
        ->and($itemSemEmpresa->refresh()->autorizacaoServico()->exists())->toBeFalse()
        ->and(AutorizacaoServico::query()->count())->toBe(0);
});

it('permite regerar o pdf pela linha do controle as quando status esta criada', function () {
    setupFilamentResourceCoverageForTests($this);

    Storage::fake('r2');

    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create([
        'unidade' => 'Unidade Regerar PDF',
        'inicio' => '2026-05-10',
        'fim' => '2026-06-10',
    ]);
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'OBRA CIVIL',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Regerar',
        'cnpj' => '77.777.777/0001-77',
        'email' => 'fornecedor.regerar@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-REGERAR-001',
        'valor' => 1000,
        'valor_estimado' => 1200,
        'anexo_autorizacao_servico' => 'autorizacao-servico/regerar/antigo.pdf',
        'created_by_id' => $user->id,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_estimado_as' => 1200,
        'valor_global_a' => 1000,
        'data_entrega' => '2026-05-25',
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    $this->actingAs($user);

    app(AutorizacaoServicoFluxoService::class)->gerar(
        $autorizacaoServico,
        parcelamento: [
            [
                'parcela' => 'Parcela 01',
                'percentual' => 100,
                'valor' => 1000,
                'observacao' => 'Teste',
            ],
        ],
        datas: [
            'data_inicio_servico' => '2026-05-12',
            'data_termino_servico' => '2026-06-12',
            'data_entrega_material' => '2026-05-26',
            'desconto_autorizacao_servico' => 100,
        ],
    );

    $autorizacaoServico->refresh();

    expect($autorizacaoServico->anexo_autorizacao_servico)->toBe('autorizacao-servico/'.$autorizacaoServico->id.'/pdf/AS-REGERAR-001.pdf')
        ->and($autorizacaoServico->data_inicio_servico?->toDateString())->toBe('2026-05-12')
        ->and($autorizacaoServico->data_termino_servico?->toDateString())->toBe('2026-06-12')
        ->and($autorizacaoServico->data_entrega_material?->toDateString())->toBe('2026-05-26')
        ->and($autorizacaoServico->desconto_autorizacao_servico)->toBe('100.00')
        ->and((float) $autorizacaoServico->parcelamento_autorizacao_servico[0]['valor'])->toBe(1000.0);

    Storage::disk('r2')->assertExists($autorizacaoServico->anexo_autorizacao_servico);
});

it('mantem apenas o escopo bloqueado depois que a as foi criada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopoOriginal = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $novoEscopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '04.1',
        'escopo' => 'PISO',
        'is_active' => true,
    ]);
    $fornecedorOriginal = Construtora::create([
        'nome' => 'Fornecedor Original',
        'cnpj' => '11.111.111/0001-11',
        'tipo' => 'CONSTRUTORA',
    ]);
    $novoFornecedor = Construtora::create([
        'nome' => 'Novo Fornecedor',
        'cnpj' => '22.222.222/0001-22',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopoOriginal->id,
        'construtora_id' => $fornecedorOriginal->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-GERADA-001',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoOriginal->id,
        'grupo' => $escopoOriginal->grupo,
        'numero_as' => $escopoOriginal->numero_as,
        'escopo' => $escopoOriginal->escopo,
        'empresa' => $fornecedorOriginal->nome,
        'valor_global_a' => 100,
        'valor_acumulado_medido' => 20,
        'saldo' => 80,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $item->id, [
            'as_escopo_id' => $novoEscopo->id,
            'construtora_id' => $novoFornecedor->id,
            'valor_estimado' => '350,00',
            'valor_fechado' => '250,00',
        ])
        ->assertNotified();

    $item->refresh();
    $autorizacaoServico->refresh();

    expect($item->as_escopo_id)->toBe($escopoOriginal->id)
        ->and($item->numero_as)->toBe('03.1')
        ->and($item->empresa)->toBe('Novo Fornecedor')
        ->and($item->valor_estimado_as)->toBe('350.00')
        ->and($item->valor_global_a)->toBe('100.00')
        ->and($autorizacaoServico->as_escopo_id)->toBe($escopoOriginal->id)
        ->and($autorizacaoServico->construtora_id)->toBe($novoFornecedor->id)
        ->and($autorizacaoServico->numero_as)->toBe('AS-GERADA-001')
        ->and($autorizacaoServico->valor_estimado)->toBe('350.00')
        ->and($autorizacaoServico->valor)->toBe('100.00');
});

it('exibe complemento da as na linha do escopo', function () {
    $user = createActiveUserWithPermissions(['ViewAny:AutorizacaoServico']);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-GERADA-C1',
        'numero_complemento' => 'C1',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);

    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'grupo' => $escopo->grupo,
        'numero_as' => $escopo->numero_as,
        'numero_complemento' => 'C1',
        'escopo' => $escopo->escopo,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Complemento')
        ->assertSee('C1');
});

it('salva escopo complementar da linha com complemento', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'grupo' => $escopo->grupo,
        'numero_as' => $escopo->numero_as,
        'numero_complemento' => 'C1',
        'escopo' => $escopo->escopo,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Escopo Complementar')
        ->assertSee('x-model="asEscopoId"', false)
        ->call('salvarItemComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'numero_complemento' => 'C1',
            'escopo_complementar' => 'Adição de evaporadora',
        ])
        ->assertNotified();

    expect($item->refresh()->escopo_complementar)->toBe('Adição de evaporadora');
});

it('bloqueia edicao da linha quando a as esta cancelada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Cancelado',
        'cnpj' => '33.333.333/0001-33',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CANCELADA,
        'numero_as' => 'AS-GERADA-CANCELADA',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'grupo' => $escopo->grupo,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => $fornecedor->nome,
        'valor_global_a' => 100,
        'valor_acumulado_medido' => 20,
        'saldo' => 80,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $item->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('AS enviada ou cancelada não pode ser editada')
        ->call('salvarItemComDados', $item->id, [
            'valor_fechado' => '250,00',
        ])
        ->assertNotified();

    $item->refresh();
    $autorizacaoServico->refresh();

    expect($item->valor_global_a)->toBe('100.00')
        ->and($item->valor_acumulado_medido)->toBe('20.00')
        ->and($item->saldo)->toBe('80.00')
        ->and($autorizacaoServico->valor)->toBe('100.00')
        ->and($autorizacaoServico->valor_estimado)->toBe('100.00');
});

it('edita as criada pelo controle e regera o pdf pelo mesmo modal', function () {
    Storage::fake('r2');
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'Serviços civis',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Editar AS',
        'cnpj' => '22.333.444/0001-55',
        'email' => 'editar.as@example.com',
        'tipo' => 'CONSTRUTORA',
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'grupo' => $escopo->grupo,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => $fornecedor->nome,
        'valor_estimado_as' => 1000,
        'valor_global_a' => 900,
        'saldo' => 900,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-EDITA-CONTROLE',
        'valor' => 900,
        'valor_estimado' => 1000,
        'desconto_autorizacao_servico' => 100,
        'controle_nota_fiscal_item_id' => $item->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('editarAsComDados', $item->id, [
            'valor_estimado' => '1.500,00',
        ])
        ->assertSet('gerarAsModalEdicao', true)
        ->assertSet('gerarAsModalItemId', $item->id)
        ->assertSet('gerarAsPdfFormData.valor_inicial', '150000')
        ->call('confirmarGeracaoAs', null, [
            'desconto_autorizacao_servico' => '250,00',
            'descricao_servico_pdf' => 'Escopo revisado',
        ])
        ->assertNotified()
        ->assertSet('gerarAsModalItemId', null);

    $item->refresh();
    $autorizacaoServico->refresh();

    expect($item->valor_estimado_as)->toBe('1500.00')
        ->and($item->valor_global_a)->toBe('1250.00')
        ->and($autorizacaoServico->valor_estimado)->toBe('1500.00')
        ->and($autorizacaoServico->valor)->toBe('1250.00')
        ->and($autorizacaoServico->desconto_autorizacao_servico)->toBe('250.00')
        ->and($autorizacaoServico->descricao_servico_pdf)->toBe('Escopo revisado')
        ->and($autorizacaoServico->anexo_autorizacao_servico)->not->toBeEmpty();
});

it('bloqueia edicao da linha quando a as esta enviada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'Serviços civis',
        'is_active' => true,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'valor_estimado_as' => 1000,
        'valor_global_a' => 900,
        'saldo' => 900,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-ENVIADA-IMUTAVEL',
        'valor' => 900,
        'valor_estimado' => 1000,
        'controle_nota_fiscal_item_id' => $item->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('AS enviada ou cancelada não pode ser editada')
        ->assertDontSee('Editar AS')
        ->call('salvarItemComDados', $item->id, [
            'valor_estimado' => '1.500,00',
        ])
        ->assertNotified()
        ->call('editarAsComDados', $item->id, [
            'valor_estimado' => '1.500,00',
        ])
        ->assertNotified();

    expect($item->refresh()->valor_estimado_as)->toBe('1000.00')
        ->and($autorizacaoServico->refresh()->valor_estimado)->toBe('1000.00');
});

it('permite remover manualmente linha em rascunho mesmo com dados salvos', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $linhaVazia = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 0,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 0,
    ]);
    $linhaPreenchida = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'escopo' => 'Escopo preenchido',
        'percentual_total' => 100,
        'valor_global_a' => 0,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 0,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Remover linha')
        ->assertDontSee('Remover linha vazia')
        ->call('removerLinhaVazia', $linhaPreenchida->id)
        ->assertNotified()
        ->call('removerLinhaVazia', $linhaVazia->id)
        ->assertNotified();

    expect(ControleNotaFiscalItem::query()->whereKey($linhaVazia->id)->exists())->toBeFalse()
        ->and(ControleNotaFiscalItem::query()->whereKey($linhaPreenchida->id)->exists())->toBeFalse();
});

it('permite apagar varias linhas rascunho da obra em lote', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $outraObra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $controleOutraObra = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $outraObra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS2',
        'unidade' => $outraObra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'Escopo Civil',
        'is_active' => true,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-BLOQ',
        'valor' => 100,
        'valor_estimado' => 100,
        'created_by_id' => $user->id,
    ]);
    $linhaRascunhoA = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'escopo' => 'Rascunho A',
        'percentual_total' => 100,
    ]);
    $linhaRascunhoB = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'escopo' => 'Rascunho B',
        'percentual_total' => 100,
    ]);
    $linhaComAs = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'escopo' => 'Linha com AS',
        'percentual_total' => 100,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $linhaComAs->id])->save();
    $linhaOutraObra = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleOutraObra->id,
        'escopo' => 'Outra obra',
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Apagar selecionadas')
        ->call('removerLinhasRascunhoObra', $obra->id, [
            $linhaRascunhoA->id,
            $linhaRascunhoB->id,
            $linhaComAs->id,
            $linhaOutraObra->id,
        ])
        ->assertNotified();

    expect(ControleNotaFiscalItem::query()->whereKey($linhaRascunhoA->id)->exists())->toBeFalse()
        ->and(ControleNotaFiscalItem::query()->whereKey($linhaRascunhoB->id)->exists())->toBeFalse()
        ->and(ControleNotaFiscalItem::query()->whereKey($linhaComAs->id)->exists())->toBeTrue()
        ->and(ControleNotaFiscalItem::query()->whereKey($linhaOutraObra->id)->exists())->toBeTrue();
});

it('mantem remover linha apos salvar linha em rascunho', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 0,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 0,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('Remover linha')
        ->assertDontSee('Remover linha vazia')
        ->call('salvarItemComDados', $linha->id, [
            'as_escopo_id' => $escopo->id,
        ])
        ->assertNotified()
        ->assertSee('Remover linha');

    expect($linha->refresh()->as_escopo_id)->toBe($escopo->id);
});

it('salva valor estimado da as sem alterar valor fechado da linha rascunho', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 0,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 0,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $linha->id, [
            'valor_estimado' => '123,45',
            'valor_fechado' => '',
        ])
        ->assertNotified();

    $linha->refresh();

    expect($linha->valor_estimado_as)->toBe('123.45')
        ->and($linha->valor_global_a)->toBe('0.00')
        ->and($linha->saldo)->toBe('0.00')
        ->and(ControleNotaFiscalItem::query()->whereKey($linha->id)->exists())->toBeTrue();
});

it('salva apenas a linha acionada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $primeiraLinha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);
    $segundaLinha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_estimado_as' => 321,
        'valor_global_a' => 123,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $primeiraLinha->id, [
            'as_escopo_id' => $escopo->id,
            'valor_estimado' => '1.000,00',
            'valor_fechado' => '800,00',
        ])
        ->assertNotified();

    $primeiraLinha->refresh();
    $segundaLinha->refresh();

    expect($primeiraLinha->as_escopo_id)->toBe($escopo->id)
        ->and($primeiraLinha->valor_estimado_as)->toBe('1000.00')
        ->and($primeiraLinha->valor_global_a)->toBe('0.00')
        ->and($segundaLinha->as_escopo_id)->toBeNull()
        ->and($segundaLinha->valor_estimado_as)->toBe('321.00')
        ->and($segundaLinha->valor_global_a)->toBe('123.00');
});

it('salva em lote as alteracoes das linhas da obra', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $primeiraLinha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);
    $segundaLinha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Lote',
        'cnpj' => '44.444.444/0001-44',
        'tipo' => 'CONSTRUTORA',
    ]);
    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->call('salvarItensObraComDados', $obra->id, [
            $primeiraLinha->id => [
                'as_escopo_id' => $escopo->id,
                'construtora_id' => $fornecedor->id,
                'valor_estimado' => '1.234,56',
                'valor_fechado' => '1.000,00',
                'faturado' => '999,00',
            ],
            $segundaLinha->id => [
                'valor_estimado' => '500,00',
            ],
        ])
        ->assertNotified();

    $primeiraLinha->refresh();
    $segundaLinha->refresh();

    expect($primeiraLinha->as_escopo_id)->toBe($escopo->id)
        ->and($primeiraLinha->grupo)->toBe('Ar Condicionado')
        ->and($primeiraLinha->numero_as)->toBe('03.1')
        ->and($primeiraLinha->escopo)->toBe('AR COND. INSTALAÇÃO')
        ->and($primeiraLinha->empresa)->toBe('Fornecedor Lote')
        ->and($primeiraLinha->valor_estimado_as)->toBe('1234.56')
        ->and($primeiraLinha->valor_global_a)->toBe('0.00')
        ->and($primeiraLinha->valor_acumulado_medido)->toBe('0.00')
        ->and($primeiraLinha->saldo)->toBe('0.00')
        ->and($segundaLinha->valor_estimado_as)->toBe('500.00')
        ->and($segundaLinha->valor_global_a)->toBe('0.00');
});

it('salva em lote apenas as linhas recebidas no payload atual', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $primeiraLinha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_estimado_as' => 100,
    ]);
    $segundaLinha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_estimado_as' => 200,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set("itens.{$segundaLinha->id}.valor_estimado", '999,00')
        ->call('salvarItensObraComDados', $obra->id, [
            $primeiraLinha->id => [
                'valor_estimado' => '300,00',
            ],
        ])
        ->assertNotified();

    expect($primeiraLinha->refresh()->valor_estimado_as)->toBe('300.00')
        ->and($segundaLinha->refresh()->valor_estimado_as)->toBe('200.00');
});

it('salva linhas vinculadas a as no salvamento em lote', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'numero_as' => 'AS-VINCULADA',
        'valor' => 0,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);
    $autorizacaoServico->forceFill(['controle_nota_fiscal_item_id' => $linha->id])->save();

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItensObraComDados', $obra->id, [
            $linha->id => [
                'valor_estimado' => '900,00',
            ],
        ])
        ->assertNotified('1 linha de AS salva');

    expect($linha->refresh()->valor_estimado_as)->toBe('900.00');
});

it('atribui complemento ao salvar multiplas linhas com o mesmo escopo na obra', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $linhas = collect(range(1, 3))
        ->map(fn (): ControleNotaFiscalItem => ControleNotaFiscalItem::create([
            'controle_nota_fiscal_id' => $controle->id,
            'percentual_total' => 100,
            'percentual_faturamento_mao_obra' => 60,
            'percentual_faturamento_material' => 40,
        ]));
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->call('salvarItensObraComDados', $obra->id, [
            $linhas[0]->id => [
                'as_escopo_id' => $escopo->id,
                'numero_complemento' => null,
            ],
            $linhas[1]->id => [
                'as_escopo_id' => $escopo->id,
                'numero_complemento' => 'C1',
                'escopo_complementar' => 'Primeiro complemento',
            ],
            $linhas[2]->id => [
                'as_escopo_id' => $escopo->id,
                'numero_complemento' => 'C2',
            ],
        ])
        ->assertNotified();

    expect($linhas[0]->refresh()->numero_complemento)->toBeNull()
        ->and($linhas[1]->refresh()->numero_complemento)->toBe('C1')
        ->and($linhas[1]->escopo_complementar)->toBe('Primeiro complemento')
        ->and($linhas[2]->refresh()->numero_complemento)->toBe('C2');
});

it('atribui complemento ao selecionar escopos iguais antes de salvar a obra', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $linhas = collect(range(1, 3))
        ->map(fn (): ControleNotaFiscalItem => ControleNotaFiscalItem::create([
            'controle_nota_fiscal_id' => $controle->id,
            'percentual_total' => 100,
            'percentual_faturamento_mao_obra' => 60,
            'percentual_faturamento_material' => 40,
        ]));
    $escopo = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('atualizarEscopoItemComComplemento', $linhas[0]->id, $escopo->id)
        ->assertReturned([
            'as_escopo_id' => $escopo->id,
            'numero_complemento' => '',
            'escopo_complementar' => '',
        ])
        ->call('atualizarEscopoItemComComplemento', $linhas[1]->id, $escopo->id)
        ->assertReturned([
            'as_escopo_id' => $escopo->id,
            'numero_complemento' => 'C1',
            'escopo_complementar' => '',
        ])
        ->call('atualizarEscopoItemComComplemento', $linhas[2]->id, $escopo->id)
        ->assertReturned([
            'as_escopo_id' => $escopo->id,
            'numero_complemento' => 'C2',
            'escopo_complementar' => '',
        ]);

    expect($linhas[0]->refresh()->numero_complemento)->toBeNull()
        ->and($linhas[1]->refresh()->numero_complemento)->toBe('C1')
        ->and($linhas[2]->refresh()->numero_complemento)->toBe('C2');
});

it('nao renumera complementos de outras linhas quando o escopo de uma linha muda', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopoOriginal = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $escopoNovo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'OBRA CIVIL',
        'is_active' => true,
    ]);
    $linhaC1 = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoOriginal->id,
        'numero_complemento' => 'C1',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);
    $linhaC2 = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoOriginal->id,
        'numero_complemento' => 'C2',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->call('salvarItemComDados', $linhaC1->id, [
            'as_escopo_id' => $escopoNovo->id,
            'numero_complemento' => 'C1',
            'escopo_complementar' => 'Complemento antigo',
            'valor_estimado' => '100,00',
            'valor_fechado' => '100,00',
        ])
        ->assertNotified();

    expect($linhaC1->refresh()->as_escopo_id)->toBe($escopoNovo->id)
        ->and($linhaC1->grupo)->toBe('Civil')
        ->and($linhaC1->numero_as)->toBe('01.1')
        ->and($linhaC1->escopo)->toBe('OBRA CIVIL')
        ->and($linhaC1->numero_complemento)->toBeNull()
        ->and($linhaC1->escopo_complementar)->toBeNull()
        ->and($linhaC2->refresh()->as_escopo_id)->toBe($escopoOriginal->id)
        ->and($linhaC2->numero_complemento)->toBe('C2');
});

it('nao reutiliza complemento quando uma linha do mesmo escopo foi removida', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'OBRA CIVIL',
        'is_active' => true,
    ]);
    $linhaPrincipal = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);
    $linhaComplementar = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $linhaPrincipal->id, [
            'as_escopo_id' => $escopo->id,
        ])
        ->call('salvarItemComDados', $linhaComplementar->id, [
            'as_escopo_id' => $escopo->id,
        ])
        ->assertNotified();

    expect($linhaPrincipal->refresh()->numero_complemento)->toBeNull()
        ->and($linhaComplementar->refresh()->numero_complemento)->toBe('C1');

    $linhaComplementar->delete();

    $novaLinha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'percentual_total' => 100,
    ]);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('salvarItemComDados', $novaLinha->id, [
            'as_escopo_id' => $escopo->id,
        ])
        ->assertNotified();

    expect($novaLinha->refresh()->numero_complemento)->toBe('C2');
});

it('atribui novo complemento quando a linha muda para outro escopo ja usado na obra', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopoOriginal = AsEscopo::create([
        'grupo' => 'Ar Condicionado',
        'numero_as' => '03.1',
        'escopo' => 'AR COND. INSTALAÇÃO',
        'is_active' => true,
    ]);
    $escopoNovo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'OBRA CIVIL',
        'is_active' => true,
    ]);
    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoNovo->id,
        'numero_complemento' => null,
        'percentual_total' => 100,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoOriginal->id,
        'numero_complemento' => 'C1',
        'escopo_complementar' => 'Complemento antigo',
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('atualizarEscopoItemComComplemento', $linha->id, $escopoNovo->id)
        ->assertReturned([
            'as_escopo_id' => $escopoNovo->id,
            'numero_complemento' => 'C1',
            'escopo_complementar' => '',
        ]);

    expect($linha->refresh()->as_escopo_id)->toBe($escopoNovo->id)
        ->and($linha->grupo)->toBe('Civil')
        ->and($linha->numero_as)->toBe('01.1')
        ->and($linha->escopo)->toBe('OBRA CIVIL')
        ->and($linha->numero_complemento)->toBe('C1')
        ->and($linha->escopo_complementar)->toBeNull();
});

it('sincroniza valor estimado da linha pelo simulador oi selecionado no modal', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'SHELL',
        'is_active' => true,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulador OI Controle AS',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell principal',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 80,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => 'C1',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell C1',
        'valor_base_m2' => 2.5,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 250,
        'percentual' => 20,
    ]);
    $linhaPrincipal = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'percentual_total' => 100,
    ]);
    $linhaC1 = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => 'C1',
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->callAction('sincronizarSimuladorOiItem', [], [
            'itemId' => $linhaPrincipal->id,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified(
            FilamentNotification::make()
                ->title('Importação da OI concluída')
                ->body('1 linha atualizada com valor da OI.')
                ->success(),
        );

    expect($linhaPrincipal->refresh()->valor_estimado_as)->toBe('1000.00')
        ->and($linhaPrincipal->valor_estimado_as_simulador)->toBe('1000.00')
        ->and($linhaC1->refresh()->valor_estimado_as)->toBe('0.00')
        ->and($linhaC1->valor_estimado_as_simulador)->toBeNull();
});

it('atualiza o estado exibido ao sincronizar linha pelo simulador oi sem recarregar a pagina', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'SHELL',
        'is_active' => true,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulador OI Estado Linha',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell principal',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 80,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'valor_estimado_as' => 0,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSet("itens.{$linha->id}.valor_estimado", '0.00')
        ->callAction('sincronizarSimuladorOiItem', [], [
            'itemId' => $linha->id,
        ])
        ->assertHasNoActionErrors()
        ->assertSet("itens.{$linha->id}.valor_estimado", '1000.00')
        ->assertSet("itens.{$linha->id}.valor_estimado_as_simulador", '1000.00');
});

it('simulador oi sobrescreve valor estimado manual em linha com as criada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'SHELL',
        'is_active' => true,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulador OI AS Criada',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell principal',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 80,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'valor_estimado_as' => 1200,
        'valor_estimado_as_simulador' => 900,
        'valor_estimado_as_editado_manualmente' => true,
        'valor_global_a' => 800,
        'saldo' => 800,
        'percentual_total' => 100,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-SIM-OI-001',
        'valor' => 800,
        'valor_estimado' => 1200,
        'controle_nota_fiscal_item_id' => $linha->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSet("itens.{$linha->id}.valor_estimado", '1200.00')
        ->callAction('sincronizarSimuladorOiItem', [], [
            'itemId' => $linha->id,
        ])
        ->assertHasNoActionErrors()
        ->assertSet("itens.{$linha->id}.valor_estimado", '1000.00')
        ->assertSet("itens.{$linha->id}.valor_estimado_as_simulador", '1000.00');

    expect($linha->refresh()->valor_estimado_as)->toBe('1000.00')
        ->and($linha->valor_estimado_as_simulador)->toBe('1000.00')
        ->and($linha->valor_estimado_as_editado_manualmente)->toBeFalse()
        ->and($linha->valor_global_a)->toBe('800.00')
        ->and($autorizacaoServico->refresh()->valor_estimado)->toBe('1000.00')
        ->and($autorizacaoServico->valor)->toBe('800.00');
});

it('destaca valor estimado quando a linha nao usa mais o valor da oi', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'SHELL',
        'is_active' => true,
    ]);

    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'valor_estimado_as' => 1200,
        'valor_estimado_as_simulador' => 1000,
        'valor_estimado_as_editado_manualmente' => true,
        'valor_global_a' => 1200,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSee('data-oi-manual="true"', false)
        ->assertSee('cpr-row-oi-manual', false)
        ->assertSee('cpr-money-field--oi-manual', false)
        ->assertSee('Valor fora do Simulador OI')
        ->assertDontSee('Original Simulador OI')
        ->assertDontSee('Restaurar')
        ->assertDontSee('Original Simulador OI: R$ 1.000,00');
});

it('nao destaca a linha quando valor atual ainda e igual ao importado do simulador oi', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'SHELL',
        'is_active' => true,
    ]);

    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'valor_estimado_as' => 1000,
        'valor_estimado_as_simulador' => 1000,
        'valor_estimado_as_editado_manualmente' => true,
        'valor_global_a' => 1000,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertDontSee('data-oi-manual="true"', false)
        ->assertDontSee('Restaurar');
});

it('destaca valor estimado alterado em escopo manual vindo da oi', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '99.1',
        'escopo' => 'Escopo manual OI',
        'is_active' => true,
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => 'C1',
        'valor_estimado_as' => 500,
        'valor_estimado_as_simulador' => 500,
        'valor_estimado_as_editado_manualmente' => false,
        'valor_global_a' => 500,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->call('salvarItemComDados', $item->id, [
            'as_escopo_id' => $escopo->id,
            'numero_complemento' => 'C1',
            'valor_estimado' => '600,00',
            'valor_fechado' => '500,00',
        ])
        ->assertSee('data-oi-manual="true"', false)
        ->assertSee('cpr-money-field--oi-manual', false)
        ->assertSee('Valor fora do Simulador OI')
        ->assertDontSee('Restaurar');

    expect($item->refresh()->valor_estimado_as)->toBe('600.00')
        ->and($item->valor_estimado_as_simulador)->toBe('500.00')
        ->and($item->valor_estimado_as_editado_manualmente)->toBeTrue();
});

it('sincroniza todos os escopos da obra pelo simulador oi selecionado no modal geral', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopoShell = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'SHELL',
        'is_active' => true,
    ]);
    $escopoRecheio = AsEscopo::create([
        'grupo' => 'Recheio',
        'numero_as' => '02.1',
        'escopo' => 'RECHEIO',
        'is_active' => true,
    ]);
    $escopoNovo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '03.1',
        'escopo' => 'ESCOPO NOVO',
        'is_active' => true,
        'is_personalizado' => true,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulador OI Geral Controle AS',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopoShell->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell principal',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 40,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopoRecheio->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Recheio principal',
        'valor_base_m2' => 20,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 2000,
        'percentual' => 40,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopoNovo->id,
        'numero_complemento' => 'C1',
        'tipo' => 'manual',
        'incluir' => true,
        'nome_escopo' => 'Escopo novo',
        'valor_base_m2' => 500,
        'area' => null,
        'fator_correcao' => 1,
        'custo_estimado' => 500,
        'percentual' => 20,
    ]);
    $linhaShell = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoShell->id,
        'numero_complemento' => null,
        'percentual_total' => 100,
    ]);
    $linhaRecheio = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopoRecheio->id,
        'numero_complemento' => null,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->callAction('sincronizarSimuladorOiObra', [], [
            'obraId' => $obra->id,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified(
            FilamentNotification::make()
                ->title('Importação da OI concluída')
                ->body('3 linhas atualizadas com valor da OI; 1 linha criada no Controle AS.')
                ->success(),
        );

    $linhaCriada = $controle->itens()
        ->where('as_escopo_id', $escopoNovo->id)
        ->where('numero_complemento', 'C1')
        ->first();

    expect($linhaShell->refresh()->valor_estimado_as)->toBe('1000.00')
        ->and($linhaShell->valor_estimado_as_simulador)->toBe('1000.00')
        ->and($linhaRecheio->refresh()->valor_estimado_as)->toBe('2000.00')
        ->and($linhaRecheio->valor_estimado_as_simulador)->toBe('2000.00')
        ->and($linhaCriada)->not->toBeNull()
        ->and($linhaCriada->asEscopo?->is_personalizado)->toBeTrue()
        ->and($linhaCriada->valor_estimado_as)->toBe('500.00')
        ->and($linhaCriada->valor_estimado_as_simulador)->toBe('500.00');
});

it('atualiza o estado exibido ao sincronizar todos os escopos pelo simulador oi sem recarregar a pagina', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Shell',
        'numero_as' => '01.1',
        'escopo' => 'SHELL',
        'is_active' => true,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulador OI Estado Obra',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => '',
        'tipo' => 'auto',
        'incluir' => true,
        'nome_escopo' => 'Shell principal',
        'valor_base_m2' => 10,
        'area' => 100,
        'fator_correcao' => 1,
        'custo_estimado' => 1000,
        'percentual' => 100,
    ]);
    $linha = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_complemento' => null,
        'valor_estimado_as' => 0,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->assertSet("itens.{$linha->id}.valor_estimado", '0.00')
        ->callAction('sincronizarSimuladorOiObra', [], [
            'obraId' => $obra->id,
        ])
        ->assertHasNoActionErrors()
        ->assertSet("itens.{$linha->id}.valor_estimado", '1000.00')
        ->assertSet("itens.{$linha->id}.valor_estimado_as_simulador", '1000.00');
});

it('hidrata o select de escopo ao importar escopo manual do simulador oi sem recarregar a pagina', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::query()->updateOrCreate([
        'obra_id' => $obra->id,
        'tipo_unidade' => TipoUnidade::EXPANSAO->value,
    ], [
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $simulacao = CapexSimulacao::create([
        'projeto_id' => $obra->projeto_id,
        'nome' => 'Simulador OI Escopo Manual',
        'area_unidade' => 100,
        'fator_correcao' => 1,
        'status' => 1,
    ]);
    CapexSimulacaoItem::create([
        'capex_simulacao_id' => $simulacao->id,
        'as_escopo_id' => null,
        'numero_complemento' => '',
        'tipo' => 'manual',
        'incluir' => true,
        'nome_escopo' => 'Escopo manual importado',
        'valor_base_m2' => 123,
        'area' => null,
        'fator_correcao' => 1,
        'custo_estimado' => 123,
        'percentual' => 100,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->set('obrasExpandidas', [$obra->id])
        ->callAction('sincronizarSimuladorOiObra', [], [
            'obraId' => $obra->id,
        ])
        ->assertHasNoActionErrors();

    $linhaCriada = $controle->itens()->firstOrFail();
    $escopoCriado = $linhaCriada->asEscopo()->firstOrFail();

    $component
        ->assertSet("asEscopoOptions.{$escopoCriado->id}", null)
        ->assertSet("asEscopoMetadados.{$escopoCriado->id}.escopo", 'Escopo manual importado')
        ->assertSet("itens.{$linhaCriada->id}.as_escopo_id", $escopoCriado->id)
        ->assertSet("itens.{$linhaCriada->id}.valor_estimado", '123.00')
        ->assertSee('Escopo manual importado');
});

it('exibe os novos campos da as apenas para visualizacao e download', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.1',
        'escopo' => 'Serviços civis',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Novos Campos AS',
        'cnpj' => '11.222.333/0001-44',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-EDIT-CAMPOS',
        'valor' => 1000,
        'valor_estimado' => 1200,
        'desconto_autorizacao_servico' => 0,
        'anexos_autorizacao_servico' => ['autorizacao-servico/anexos/antigo.pdf'],
        'anexo_autorizacao_servico' => 'autorizacao-servico/pdf/AS-EDIT-CAMPOS.pdf',
    ]);

    $this->actingAs($user);

    $this->get(AutorizacaoServicoResource::getUrl('edit', ['record' => $autorizacaoServico]))
        ->assertOk()
        ->assertSee('Visualizar AS')
        ->assertDontSee('Editar AS')
        ->assertDontSee('Tipo da contratação')
        ->assertSee('Data início')
        ->assertSee('Data término')
        ->assertSee('Data entrega')
        ->assertSee('Desconto')
        ->assertSee('Parcelamento')
        ->assertSee('Descrição no PDF')
        ->assertSee('PDF gerado')
        ->assertSee('Anexos adicionais')
        ->assertDontSee('Planilha Excel')
        ->assertDontSee('Remover arquivo')
        ->assertDontSee('Salvar')
        ->assertDontSee('Gerar PDF')
        ->assertDontSee('Regerar PDF')
        ->assertDontSee('Enviar AS')
        ->assertDontSee('Baixar PDF');
});

it('abre modal criar as com descricao por texto e imagem', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Create:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.2',
        'escopo' => 'Escopo modal AS',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Modal AS',
        'cnpj' => '33.333.333/0001-33',
        'tipo' => 'CONSTRUTORA',
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'empresa' => $fornecedor->nome,
        'valor_global_a' => 1500,
        'valor_estimado_as' => 1500,
        'percentual_total' => 100,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->call('abrirModalGerarAs', $item->id, [
            'as_escopo_id' => $escopo->id,
            'construtora_id' => $fornecedor->id,
            'valor_fechado' => '1.500,00',
        ]);

    $descricao = data_get($component->instance()->gerarAsPdfFormData, 'itens_descricao_servico_pdf.0');

    expect($descricao)
        ->toHaveKeys(['descricao_tipo', 'descricao', 'descricao_arquivo'])
        ->and(array_key_exists('item', $descricao))->toBeFalse()
        ->and(array_key_exists('unidade', $descricao))->toBeFalse()
        ->and(array_key_exists('quantidade', $descricao))->toBeFalse();
});

it('permite editar dados do pdf da as criada pelo modal da linha do controle as', function () {
    Storage::fake('r2');

    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.3',
        'escopo' => 'Escopo editavel AS',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Editavel AS',
        'cnpj' => '44.444.444/0001-44',
        'tipo' => 'CONSTRUTORA',
    ]);
    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-EDITAVEL-001',
        'valor' => 1000,
        'valor_estimado' => 1200,
        'descricao_servico_pdf' => 'Descricao antiga',
        'itens_descricao_servico_pdf' => [[
            'descricao_tipo' => 'arquivo',
            'descricao' => 'Descricao antiga',
            'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/antiga.png'],
        ]],
        'anexos_autorizacao_servico' => [
            'autorizacao-servico/tmp/anexos/contrato.pdf',
            'autorizacao-servico/tmp/anexos/medicao.xlsx',
        ],
        'parcelamento_autorizacao_servico' => [[
            'parcela' => 'Parcela 1',
            'percentual' => 100,
            'valor' => 1000,
            'observacao' => 'Original',
        ]],
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_estimado_as' => 1200,
        'valor_global_a' => 1000,
        'valor_acumulado_medido' => 200,
        'saldo' => 800,
    ]);
    $autorizacaoServico->update(['controle_nota_fiscal_item_id' => $item->id]);

    $this->actingAs($user);

    $pdfComponent = Livewire::test(ControleAutorizacoesServico::class)
        ->call('abrirModalEditarPdfAs', $item->id)
        ->assertSet('gerarAsModalModo', 'editar_pdf')
        ->assertSet('gerarAsModalItemId', $item->id);

    expect(array_values((array) data_get($pdfComponent->instance()->gerarAsPdfFormData, 'descricao_arquivo')))
        ->toBe(['autorizacao-servico/tmp/descricao/antiga.png'])
        ->and(array_values((array) data_get($pdfComponent->instance()->gerarAsPdfFormData, 'anexos_autorizacao_servico')))
        ->toBe([
            'autorizacao-servico/tmp/anexos/contrato.pdf',
            'autorizacao-servico/tmp/anexos/medicao.xlsx',
        ]);

    $component = Livewire::test(ControleAutorizacoesServico::class)
        ->call('editarAsComDados', $item->id, [
            'valor_fechado' => '1.200,00',
        ])
        ->assertSet('gerarAsModalItemId', $item->id);

    expect(array_values((array) data_get($component->instance()->gerarAsPdfFormData, 'descricao_arquivo')))
        ->toBe(['autorizacao-servico/tmp/descricao/antiga.png'])
        ->and(array_values((array) data_get($component->instance()->gerarAsPdfFormData, 'anexos_autorizacao_servico')))
        ->toBe([
            'autorizacao-servico/tmp/anexos/contrato.pdf',
            'autorizacao-servico/tmp/anexos/medicao.xlsx',
        ]);

    $pdfComponent
        ->fillForm([
            'desconto_autorizacao_servico' => '100,00',
            'parcelamento' => [[
                'parcela' => 'Parcela 1',
                'percentual' => '50,00',
                'valor' => '450,00',
                'observacao' => 'Entrada',
            ],
                [
                    'parcela' => 'Parcela 2',
                    'percentual' => '50,00',
                    'valor' => '450,00',
                    'observacao' => 'Final',
                ],
            ],
        ], 'gerarAsValoresParcelamentoForm')
        ->fillForm([
            'descricao_servico_pdf' => 'Descricao editada',
            'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/editada.png'],
        ], 'gerarAsDescricaoForm')
        ->call('confirmarGeracaoAs')
        ->assertNotified()
        ->assertHasNoErrors();

    expect($autorizacaoServico->refresh())
        ->numero_as->toBe('AS-EDITAVEL-001')
        ->numero_complemento->toBe('')
        ->valor->toBe('1100.00')
        ->valor_estimado->toBe('1200.00')
        ->desconto_autorizacao_servico->toBe('100.00')
        ->parcelamento_autorizacao_servico->toMatchArray([
            [
                'parcela' => 'Parcela 1',
                'percentual' => 50,
                'valor' => 550,
                'observacao' => 'Entrada',
            ],
            [
                'parcela' => 'Parcela 2',
                'percentual' => 50,
                'valor' => 550,
                'observacao' => 'Final',
            ],
        ])
        ->descricao_servico_pdf->toBe('Descricao editada')
        ->status->toBe(AsStatus::CRIADA);

    expect($autorizacaoServico->itens_descricao_servico_pdf)->toMatchArray([[
        'descricao_tipo' => 'arquivo',
        'descricao' => 'Descricao editada',
        'descricao_arquivo' => ['autorizacao-servico/tmp/descricao/editada.png'],
    ]]);

    expect($item->refresh())
        ->valor_estimado_as->toBe('1200.00')
        ->valor_global_a->toBe('1000.00')
        ->valor_acumulado_medido->toBe('200.00')
        ->saldo->toBe('800.00');

    Storage::disk('r2')->assertExists($autorizacaoServico->anexo_autorizacao_servico);
});

it('visualizacao da as criada permanece somente leitura', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => Obras::factory()->create()->id,
        'as_escopo_id' => AsEscopo::create([
            'grupo' => 'Civil',
            'numero_as' => '01.5',
            'escopo' => 'Escopo visualizar',
            'is_active' => true,
        ])->id,
        'construtora_id' => Construtora::create([
            'nome' => 'Fornecedor Visualizar AS',
            'cnpj' => '66.666.666/0001-66',
            'tipo' => 'CONSTRUTORA',
        ])->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-VISUALIZAR-SOMENTE-LEITURA',
        'valor' => 1000,
        'valor_estimado' => 1000,
    ]);

    $this->actingAs($user);

    $this->get(AutorizacaoServicoResource::getUrl('edit', ['record' => $autorizacaoServico]))
        ->assertOk()
        ->assertDontSee('Salvar')
        ->assertDontSee('Gerar PDF')
        ->assertDontSee('Regerar PDF');
});

it('bloqueia edicao do pdf da as quando status nao esta criada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $obra = Obras::factory()->create();
    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => 'rascunho',
        'data_base' => now()->toDateString(),
        'sigla' => 'AS1',
        'unidade' => $obra->unidade,
    ]);
    $escopo = AsEscopo::create([
        'grupo' => 'Civil',
        'numero_as' => '01.4',
        'escopo' => 'Escopo bloqueado AS',
        'is_active' => true,
    ]);
    $fornecedor = Construtora::create([
        'nome' => 'Fornecedor Bloqueado AS',
        'cnpj' => '55.555.555/0001-55',
        'tipo' => 'CONSTRUTORA',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'as_escopo_id' => $escopo->id,
        'construtora_id' => $fornecedor->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-BLOQUEADA-001',
        'valor' => 1000,
        'valor_estimado' => 1000,
        'descricao_servico_pdf' => 'Descricao original',
    ]);
    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $escopo->id,
        'numero_as' => $escopo->numero_as,
        'escopo' => $escopo->escopo,
        'empresa' => $fornecedor->nome,
        'percentual_total' => 100,
        'valor_estimado_as' => 1000,
        'valor_global_a' => 1000,
    ]);
    $autorizacaoServico->update(['controle_nota_fiscal_item_id' => $item->id]);

    $this->actingAs($user);

    Livewire::test(ControleAutorizacoesServico::class)
        ->call('abrirModalEditarPdfAs', $item->id)
        ->assertNotified()
        ->assertSet('gerarAsModalItemId', null);

    expect($autorizacaoServico->refresh())
        ->numero_as->toBe('AS-BLOQUEADA-001')
        ->descricao_servico_pdf->toBe('Descricao original');
});

it('nao exibe acao de salvar na visualizacao da as criada', function () {
    $user = createActiveUserWithPermissions([
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Update:AutorizacaoServico',
    ]);
    $autorizacaoServico = AutorizacaoServico::create([
        'obra_id' => Obras::factory()->create()->id,
        'as_escopo_id' => AsEscopo::create([
            'grupo' => 'Civil',
            'numero_as' => '01.6',
            'escopo' => 'Escopo visualizacao somente leitura',
            'is_active' => true,
        ])->id,
        'construtora_id' => Construtora::create([
            'nome' => 'Fornecedor Visualizacao Somente Leitura',
            'cnpj' => '66.666.666/0001-66',
            'tipo' => 'CONSTRUTORA',
        ])->id,
        'status' => AsStatus::CRIADA,
        'numero_as' => 'AS-SALVAR-OCULTO',
        'valor' => 1000,
        'valor_estimado' => 1000,
    ]);

    $this->actingAs($user);

    $this->get(AutorizacaoServicoResource::getUrl('edit', ['record' => $autorizacaoServico]))
        ->assertOk()
        ->assertDontSee('Salvar');
});
