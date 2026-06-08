<?php

use App\Enums\AsStatus;
use App\Enums\StatusControleNotaFiscalNota;
use App\Enums\TipoUnidade;
use App\Filament\Pages\ConstrutoraControlesNotaFiscalPage;
use App\Filament\Resources\ImportacaoNotaFiscals\ImportacaoNotaFiscalResource;
use App\Models\Asa;
use App\Models\AsEscopo;
use App\Models\AutorizacaoServico;
use App\Models\Construtora;
use App\Models\ControleNotaFiscal;
use App\Models\ControleNotaFiscalAuxiliar;
use App\Models\ControleNotaFiscalItem;
use App\Models\ControleNotaFiscalNota;
use App\Models\ElaboracaoAditivo;
use App\Models\Obras;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    ensureDefaultRoles();
    Role::findOrCreate('Fornecedor', 'web');
});

function createConstrutoraUserWithPagePermission(Construtora $construtora, array $permissions = []): User
{
    $user = createActiveUserWithPermissions($permissions, [
        'construtoras_id' => $construtora->id,
    ]);

    $user->assignRole('Fornecedor');

    return $user;
}

function vincularEscopoEnviadoParaConstrutora(
    ControleNotaFiscalItem $item,
    Obras $obra,
    Construtora $construtora,
    ?string $numeroAs = null,
): AutorizacaoServico {
    if (! filled($item->as_escopo_id)) {
        $asEscopo = AsEscopo::create([
            'grupo' => $item->grupo ?: 'Grupo',
            'numero_as' => $item->numero_as ?: 'AS-'.$item->id,
            'escopo' => $item->escopo ?: 'Escopo',
            'is_active' => true,
        ]);

        $item->update(['as_escopo_id' => $asEscopo->id]);
    }

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'as_escopo_id' => $item->as_escopo_id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => $numeroAs ?? '2026-SF-EXP-'.$item->id,
        'numero_complemento' => null,
        'valor' => $item->valor_global_a,
        'observacoes' => null,
    ]);

    return $autorizacao;
}

it('exibe o botão da página espelho na importação de notas para fornecedor com permissão shield', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor Alfa',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora, [
        'ViewAny:ControleNotaFiscalNota',
        'View:ConstrutoraControlesNotaFiscalPage',
    ]);

    $this->actingAs($user)
        ->get(ImportacaoNotaFiscalResource::getUrl('index'))
        ->assertRedirect(ConstrutoraControlesNotaFiscalPage::getUrl());
});

it('bloqueia o acesso à página espelho sem permissão shield', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor Alfa',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora);

    $this->actingAs($user)
        ->get(ConstrutoraControlesNotaFiscalPage::getUrl())
        ->assertForbidden();
});

it('lista apenas controles elegíveis do fornecedor e filtra por unidade', function () {
    $construtoraAlfa = createConstrutoraRecord([
        'nome' => 'Fornecedor Alfa',
        'cnpj' => '11.111.111/0001-11',
    ]);
    $construtoraBeta = createConstrutoraRecord([
        'nome' => 'Fornecedor Beta',
        'cnpj' => '22.222.222/0001-22',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtoraAlfa, [
        'View:ConstrutoraControlesNotaFiscalPage',
        'ViewAny:ControleNotaFiscalNota',
    ]);

    $obraAlpha = Obras::factory()->create([
        'codigo' => 'OBRA-ALFA',
        'unidade' => 'Obra Alfa',
    ]);
    $obraBeta = Obras::factory()->create([
        'codigo' => 'OBRA-BETA',
        'unidade' => 'Obra Beta',
    ]);

    $controleAlpha = ControleNotaFiscal::create([
        'obra_id' => $obraAlpha->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'ALF',
        'unidade' => 'Obra Alfa',
    ]);
    $controleBeta = ControleNotaFiscal::create([
        'obra_id' => $obraBeta->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'BET',
        'unidade' => 'Obra Beta',
    ]);
    $controleNaoElegivel = ControleNotaFiscal::create([
        'obra_id' => $obraAlpha->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'NGE',
        'unidade' => 'Obra Alfa',
    ]);

    $escopoAlfa = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleAlpha->id,
        'grupo' => 'Grupo Alfa',
        'numero_as' => 'AS-ALFA',
        'escopo' => 'Escopo Alfa',
        'empresa' => $construtoraAlfa->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 400,
        'valor_acumulado_medido' => 400,
        'saldo' => 600,
        'liberado_para_fornecedor_at' => now(),
    ]);
    $escopoOutro = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleAlpha->id,
        'grupo' => 'Grupo Beta',
        'numero_as' => 'AS-BETA',
        'escopo' => 'Escopo Beta',
        'empresa' => $construtoraBeta->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 2000,
        'total_medicao_a_menos_b' => 800,
        'valor_acumulado_medido' => 800,
        'saldo' => 1200,
    ]);
    $escopoBeta = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleBeta->id,
        'grupo' => 'Grupo Beta Dois',
        'numero_as' => 'AS-BETA-2',
        'escopo' => 'Escopo Beta Dois',
        'empresa' => $construtoraAlfa->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 250,
        'valor_acumulado_medido' => 250,
        'saldo' => 250,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $autorizacaoAlfa = vincularEscopoEnviadoParaConstrutora($escopoAlfa, $obraAlpha, $construtoraAlfa, '2026-SF-EXP-ALFA');
    $autorizacaoBeta = vincularEscopoEnviadoParaConstrutora($escopoBeta, $obraBeta, $construtoraAlfa, '2026-SF-EXP-BETA');
    $autorizacaoOutro = vincularEscopoEnviadoParaConstrutora($escopoOutro, $obraAlpha, $construtoraBeta, '2026-SF-EXP-OUTRO');

    ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleNaoElegivel->id,
        'grupo' => 'Grupo Oculto',
        'numero_as' => 'AS-OCULTO',
        'escopo' => 'Escopo Oculto',
        'empresa' => $construtoraBeta->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 250,
        'valor_acumulado_medido' => 250,
        'saldo' => 250,
    ]);

    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoAlfa->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtoraAlfa->nome,
        'cnpj_fornecedor' => '11111111000111',
        'numero_nf' => '1001',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'valor_acumulado_medido_nf' => 300,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoAlfa->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtoraAlfa->nome,
        'cnpj_fornecedor' => '11111111000111',
        'numero_nf' => '1003',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::REPROVADO->value,
        'valor_acumulado_medido_nf' => 900,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoAlfa->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtoraAlfa->nome,
        'cnpj_fornecedor' => '11111111000111',
        'numero_nf' => '1002',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::PENDENTE->value,
        'valor_acumulado_medido_nf' => 100,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoOutro->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtoraBeta->nome,
        'cnpj_fornecedor' => '22222222000122',
        'numero_nf' => '2001',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'valor_acumulado_medido_nf' => 200,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);
    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoBeta->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtoraAlfa->nome,
        'cnpj_fornecedor' => '11111111000111',
        'numero_nf' => '3001',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::EM_ANALISE->value,
        'valor_acumulado_medido_nf' => 50,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);

    $this->actingAs($user);

    $controleAlphaLabel = 'OBRA-ALFA - Obra Alfa - Sigla ALF';

    Livewire::test(ConstrutoraControlesNotaFiscalPage::class)
        ->assertSee('Unidade')
        ->assertDontSee('Controles disponíveis')
        ->set('selectedObraId', (string) $obraAlpha->id)
        ->assertSee($controleAlphaLabel)
        ->assertSee('Itens contratuais')
        ->assertSee('Escopo Alfa')
        ->assertSee('1001')
        ->assertSee('1002')
        ->assertSee('1003')
        ->assertSee('R$ 600,00')
        ->assertDontSee('Escopo Beta')
        ->assertDontSee('2001')
        ->assertDontSee('Grupo Beta Dois');
});

it('considera diferenças de caixa e espaços ao comparar o campo empresa do escopo', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor Alfa',
        'cnpj' => '33.333.333/0001-33',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora, [
        'View:ConstrutoraControlesNotaFiscalPage',
    ]);

    $obra = Obras::factory()->create([
        'codigo' => 'OBRA-NORM',
        'unidade' => 'Obra Normalizada',
    ]);

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'NRM',
        'unidade' => 'Obra Normalizada',
    ]);

    $escopoNormalizado = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Grupo Normalizado',
        'numero_as' => 'AS-NORM',
        'escopo' => 'Escopo Normalizado',
        'empresa' => '  CONSTRUTORA ALFA  ',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 100,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 100,
        'liberado_para_fornecedor_at' => now(),
    ]);

    vincularEscopoEnviadoParaConstrutora($escopoNormalizado, $obra, $construtora, '2026-SF-EXP-NORM');

    $this->actingAs($user);

    Livewire::test(ConstrutoraControlesNotaFiscalPage::class)
        ->set('selectedObraId', (string) $obra->id)
        ->assertSee('OBRA-NORM - Obra Normalizada - Sigla NRM')
        ->assertSee('Escopo Normalizado');
});

it('exibe na importação apenas notas vinculadas a escopos liberados para fornecedor', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor Alfa',
        'cnpj' => '44.444.444/0001-44',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora, [
        'ViewAny:ControleNotaFiscalNota',
        'Create:ControleNotaFiscalNota',
    ]);

    $obra = Obras::factory()->create([
        'codigo' => 'OBRA-IMP',
        'unidade' => 'Obra Importação',
    ]);

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'IMP',
        'unidade' => 'Obra Importação',
    ]);

    $escopoLiberado = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Grupo Liberado',
        'numero_as' => 'AS-LIB',
        'escopo' => 'Escopo Liberado',
        'empresa' => $construtora->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $escopoNaoLiberado = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Grupo Bloqueado',
        'numero_as' => 'AS-BLOQ',
        'escopo' => 'Escopo Bloqueado',
        'empresa' => $construtora->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 500,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 500,
    ]);

    $autorizacaoLiberada = vincularEscopoEnviadoParaConstrutora($escopoLiberado, $obra, $construtora, 'AS-LIB');
    $autorizacaoNaoLiberada = vincularEscopoEnviadoParaConstrutora($escopoNaoLiberado, $obra, $construtora, 'AS-BLOQ');

    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoLiberada->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtora->nome,
        'cnpj_fornecedor' => '44444444000144',
        'numero_nf' => '9001',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'valor_acumulado_medido_nf' => 100,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);

    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacaoNaoLiberada->id,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtora->nome,
        'cnpj_fornecedor' => '44444444000144',
        'numero_nf' => '9002',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::APROVADO->value,
        'valor_acumulado_medido_nf' => 100,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(ImportacaoNotaFiscalResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee('9001')
        ->assertDontSee('9002');
});

it('exibe atalho para a importação do escopo quando existe AS compatível', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor Alfa',
        'cnpj' => '55.555.555/0001-55',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora, [
        'View:ConstrutoraControlesNotaFiscalPage',
        'ViewAny:ControleNotaFiscalNota',
        'Create:ControleNotaFiscalNota',
    ]);

    $obra = Obras::factory()->create([
        'codigo' => 'OBRA-ATALHO',
        'unidade' => 'Obra Atalho',
    ]);

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'ATH',
        'unidade' => 'Obra Atalho',
    ]);

    $asEscopo = AsEscopo::create([
        'grupo' => 'Grupo Atalho',
        'numero_as' => 'AS-ATALHO',
        'escopo' => 'Escopo Atalho',
        'is_active' => true,
    ]);

    $escopo = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'as_escopo_id' => $asEscopo->id,
        'grupo' => 'Grupo Atalho',
        'numero_as' => 'AS-ATALHO',
        'escopo' => 'Escopo Atalho',
        'empresa' => $construtora->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $escopo->id,
        'as_escopo_id' => $escopo->as_escopo_id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => '2026-SF-EXP-001',
        'numero_complemento' => null,
        'valor' => 1000,
        'observacoes' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ConstrutoraControlesNotaFiscalPage::class)
        ->set('selectedObraId', (string) $obra->id)
        ->assertSee('Importar nota fiscal')
        ->assertSee('obra_id_lookup='.$obra->id)
        ->assertSee('asa_id_lookup='.$autorizacao->id);
});

it('exibe atalho para a importação do adicional quando existe ASA compatível', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor Alfa',
        'cnpj' => '55.555.555/0001-55',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora, [
        'View:ConstrutoraControlesNotaFiscalPage',
        'ViewAny:ControleNotaFiscalNota',
        'Create:ControleNotaFiscalNota',
    ]);

    $obra = Obras::factory()->create([
        'codigo' => 'OBRA-ATALHO-ASA',
        'unidade' => 'Obra Atalho ASA',
    ]);

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'ATH',
        'unidade' => 'Obra Atalho ASA',
    ]);

    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Grupo Adicional',
        'numero_as' => '2026-SF-EXP-ASA',
        'escopo' => 'Escopo Adicional',
        'empresa' => $construtora->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $asa = Asa::create([
        'numero_asa' => 'ASA-ATALHO-001',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'contrato' => 'Projeto',
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => 'aprovado',
        'codigo_as_emitida' => '2026-SF-EXP-ASA',
        'data_solicitacao' => now()->toDateString(),
        'data_aprovacao' => now()->toDateString(),
        'objeto' => 'ASA atalho',
        'descricao' => 'Escopo Adicional',
        'valor_bruto' => 1000,
        'desconto' => 0,
        'valor_total' => 1000,
        'solicitante' => $construtora->nome,
    ]);

    $this->actingAs($user);

    Livewire::test(ConstrutoraControlesNotaFiscalPage::class)
        ->set('selectedObraId', (string) $obra->id)
        ->assertSee('Importar nota fiscal')
        ->assertSee('obra_id_lookup='.$obra->id)
        ->assertSee('asa_id_lookup=asa%3A'.$asa->id);
});

it('exibe atalho para importar nota fiscal de asa enviada sem autorizacao servico', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor ASA Legada',
        'cnpj' => '66.666.666/0001-66',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora, [
        'View:ConstrutoraControlesNotaFiscalPage',
        'ViewAny:ControleNotaFiscalNota',
        'Create:ControleNotaFiscalNota',
    ]);

    $obra = Obras::factory()->create([
        'codigo' => 'OBRA-ASA-LEGADA',
        'unidade' => 'Obra ASA Legada',
    ]);

    $controle = ControleNotaFiscal::create([
        'obra_id' => $obra->id,
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'ASL',
        'unidade' => 'Obra ASA Legada',
    ]);

    $aditivo = ElaboracaoAditivo::create([
        'user_id' => $user->id,
        'obra_id' => $obra->id,
        'construtora_id' => $construtora->id,
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

    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-LEGADA-ENV',
        'escopo' => 'Projeto executivo legado',
        'empresa' => $construtora->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 0,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $asa = Asa::create([
        'numero_asa' => 'ASA-LEGADA-001',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'contrato' => 'Projeto',
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => AsStatus::APROVADO,
        'codigo_as_emitida' => 'ASA-LEGADA-ENV',
        'data_solicitacao' => now()->toDateString(),
        'data_aprovacao' => now()->toDateString(),
        'objeto' => 'ASA legada',
        'descricao' => 'Projeto executivo legado',
        'valor_bruto' => 1000,
        'desconto' => 0,
        'valor_total' => 1000,
        'solicitante' => $construtora->nome,
        'elaboracao_aditivo_id' => $aditivo->id,
    ]);

    $this->actingAs($user);

    Livewire::test(ConstrutoraControlesNotaFiscalPage::class)
        ->set('selectedObraId', (string) $obra->id)
        ->assertSee('Projeto executivo legado')
        ->assertSee('Importar nota fiscal')
        ->assertSee('obra_id_lookup='.$obra->id)
        ->assertSee('asa_id_lookup=asa%3A'.$asa->id);
});

it('lista destinos derivados de as e asa liberados e calcula saldo comprometido', function () {
    $construtora = createConstrutoraRecord([
        'nome' => 'Fornecedor Derivado',
        'cnpj' => '77.777.777/0001-77',
    ]);

    $user = createConstrutoraUserWithPagePermission($construtora, [
        'View:ConstrutoraControlesNotaFiscalPage',
        'ViewAny:ControleNotaFiscalNota',
    ]);

    $obra = Obras::factory()->create([
        'codigo' => 'OBRA-DER',
        'unidade' => 'Obra Derivada',
    ]);

    $controle = ControleNotaFiscal::query()
        ->where('obra_id', $obra->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();
    $controle->update([
        'status' => ControleNotaFiscal::STATUS_AGUARDANDO_CONSTRUTORA,
        'data_base' => now()->toDateString(),
        'sigla' => 'DER',
        'unidade' => 'Obra Derivada',
    ]);

    $item = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Grupo AS Derivada',
        'numero_as' => 'AS-DER',
        'escopo' => 'Escopo AS Derivada',
        'empresa' => 'Nome legado divergente',
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 1000,
        'total_medicao_a_menos_b' => 1000,
        'valor_acumulado_medido' => 0,
        'saldo' => 1000,
        'liberado_para_fornecedor_at' => now(),
    ]);

    $asEscopo = AsEscopo::create([
        'grupo' => 'Grupo AS Derivada',
        'numero_as' => 'AS-DER',
        'escopo' => 'Escopo AS Derivada',
        'is_active' => true,
    ]);

    $autorizacao = AutorizacaoServico::create([
        'obra_id' => $obra->id,
        'controle_nota_fiscal_item_id' => $item->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-DER',
        'valor' => 1000,
        'valor_estimado' => 1000,
    ]);

    $auxiliar = ControleNotaFiscalAuxiliar::create([
        'controle_nota_fiscal_id' => $controle->id,
        'grupo' => 'Projeto',
        'numero_as' => 'ASA-DER',
        'escopo' => 'Escopo ASA Derivada',
        'empresa' => $construtora->nome,
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
        'numero_asa' => 'ASA-DERIVADA-001',
        'projeto_id' => $obra->projeto_id,
        'sigla' => $obra->sigla,
        'endereco' => $obra->endereco,
        'contrato' => 'Projeto',
        'controle_nota_fiscal_auxiliar_id' => $auxiliar->id,
        'status' => 'aprovado',
        'codigo_as_emitida' => 'ASA-CODIGO-DERIVADO',
        'data_solicitacao' => now()->toDateString(),
        'data_aprovacao' => now()->toDateString(),
        'objeto' => 'ASA derivada',
        'descricao' => 'Escopo ASA Derivada',
        'valor_bruto' => 500,
        'desconto' => 0,
        'valor_total' => 500,
        'solicitante' => $construtora->nome,
    ]);

    ControleNotaFiscalNota::create([
        'autorizacao_servico_id' => $autorizacao->id,
        'autorizacao_servico_adicional_id' => null,
        'importado_por_id' => $user->id,
        'tipo_medicao' => ControleNotaFiscalNota::TIPO_MEDICAO_MAO_OBRA,
        'empresa' => $construtora->nome,
        'cnpj_fornecedor' => '77777777000177',
        'numero_nf' => 'NF-AS-APROVADA',
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
        'empresa' => $construtora->nome,
        'cnpj_fornecedor' => '77777777000177',
        'numero_nf' => 'NF-AS-PENDENTE',
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
        'empresa' => $construtora->nome,
        'cnpj_fornecedor' => '77777777000177',
        'numero_nf' => 'NF-ASA-PENDENTE',
        'cnpj_faturamento' => '99999999000199',
        'instrucoes_pagamento' => 'pix',
        'status' => StatusControleNotaFiscalNota::PENDENTE->value,
        'valor_acumulado_medido_nf' => 100,
        'emissao' => now()->toDateString(),
        'envio' => now()->toDateString(),
    ]);

    $obraEncerrada = Obras::factory()->create([
        'codigo' => 'OBRA-DER-FEC',
        'unidade' => 'Obra Derivada Fechada',
    ]);
    $controleEncerrado = ControleNotaFiscal::query()
        ->where('obra_id', $obraEncerrada->id)
        ->where('tipo_unidade', TipoUnidade::EXPANSAO->value)
        ->firstOrFail();
    $controleEncerrado->update([
        'data_base' => now()->toDateString(),
        'sigla' => 'FEC',
        'unidade' => 'Obra Derivada Fechada',
    ]);
    $itemEncerrado = ControleNotaFiscalItem::create([
        'controle_nota_fiscal_id' => $controleEncerrado->id,
        'grupo' => 'Grupo Encerrado',
        'numero_as' => 'AS-FECHADA',
        'escopo' => 'Escopo encerrado oculto',
        'empresa' => $construtora->nome,
        'percentual_total' => 100,
        'percentual_faturamento_mao_obra' => 60,
        'percentual_faturamento_material' => 40,
        'valor_global_a' => 100,
        'total_medicao_a_menos_b' => 100,
        'valor_acumulado_medido' => 0,
        'saldo' => 100,
        'liberado_para_fornecedor_at' => now(),
    ]);
    AutorizacaoServico::create([
        'obra_id' => $obraEncerrada->id,
        'controle_nota_fiscal_item_id' => $itemEncerrado->id,
        'as_escopo_id' => $asEscopo->id,
        'construtora_id' => $construtora->id,
        'status' => AsStatus::ENVIADA,
        'numero_as' => 'AS-FECHADA',
        'valor' => 100,
        'valor_estimado' => 100,
    ]);
    $controleEncerrado->forceFill(['status' => ControleNotaFiscal::STATUS_ENCERRADO])->save();

    $this->actingAs($user);

    Livewire::test(ConstrutoraControlesNotaFiscalPage::class)
        ->set('selectedObraId', (string) $obra->id)
        ->assertSee('Escopo AS Derivada')
        ->assertSee('Escopo ASA Derivada')
        ->assertSee('NF-AS-APROVADA')
        ->assertSee('NF-AS-PENDENTE')
        ->assertSee('NF-ASA-PENDENTE')
        ->assertSee('R$ 1.500,00')
        ->assertSee('R$ 900,00')
        ->assertDontSee('Escopo encerrado oculto');
});
