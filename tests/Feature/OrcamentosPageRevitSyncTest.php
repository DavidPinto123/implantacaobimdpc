<?php

use App\Filament\Pages\OrcamentosPage;
use App\Models\Orcamento;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

afterEach(function () {
    DB::connection('revit')->table('orcamento_revit_itens')->where('codigo_obra', 'SF-TESTE-REVIT')->delete();
});

it('sincroniza itens do revit para um orcamento novo e depois atualiza sem duplicar', function () {
    $user = createResourceBaselineUser([
        'View:MenuOrcamentoObras',
    ]);

    $this->actingAs($user);

    $projeto = createProjetoRecord($user);

    DB::connection('revit')->table('orcamento_revit_itens')->insert([
        'codigo_obra' => 'SF-TESTE-REVIT',
        'categoria' => 'Paredes',
        'codigo' => '2.4.2',
        'descricao' => 'Parede em drywall 02 faces',
        'unidade' => 'm²',
        'quantidade' => 100,
        'valor_mat' => 89.39,
        'valor_mo' => 42.55,
        'ordem' => 1,
    ]);

    $component = Livewire::test(OrcamentosPage::class)
        ->call('novoOrcamento')
        ->set('formProjetoId', $projeto->id)
        ->set('formNome', 'Orçamento via Revit')
        ->set('formArquivoRevit', 'SF-TESTE-REVIT')
        ->set('formData', now()->format('Y-m-d'))
        ->call('sincronizarRevit');

    $formCategorias = $component->get('formCategorias');

    expect($formCategorias)->toHaveCount(1);
    expect($formCategorias[0]['nome'])->toBe('Paredes');
    expect($formCategorias[0]['itens'])->toHaveCount(1);
    expect($formCategorias[0]['itens'][0]['codigo'])->toBe('2.4.2');
    expect($formCategorias[0]['itens'][0]['quantidade'])->toBe('100.000');

    $component->call('salvar');

    $orcamento = Orcamento::where('nome', 'Orçamento via Revit')->firstOrFail();
    $this->assertDatabaseHas('orcamento_categorias', ['orcamento_id' => $orcamento->id, 'nome' => 'Paredes']);
    $this->assertDatabaseHas('orcamento_itens', ['codigo' => '2.4.2', 'quantidade' => 100]);

    // Revit envia um valor atualizado para o mesmo item + um item novo
    DB::connection('revit')->table('orcamento_revit_itens')
        ->where('codigo_obra', 'SF-TESTE-REVIT')->where('codigo', '2.4.2')
        ->update(['quantidade' => 150]);

    DB::connection('revit')->table('orcamento_revit_itens')->insert([
        'codigo_obra' => 'SF-TESTE-REVIT',
        'categoria' => 'Paredes',
        'codigo' => '2.4.4',
        'descricao' => 'Parede em drywall 02 faces - RU',
        'unidade' => 'm²',
        'quantidade' => 10,
        'valor_mat' => 111.36,
        'valor_mo' => 42.55,
        'ordem' => 2,
    ]);

    $component2 = Livewire::test(OrcamentosPage::class)
        ->call('editarOrcamento', $orcamento->id)
        ->call('sincronizarRevit');

    $formCategorias2 = $component2->get('formCategorias');

    expect($formCategorias2)->toHaveCount(1);
    expect($formCategorias2[0]['itens'])->toHaveCount(2);

    $itemAtualizado = collect($formCategorias2[0]['itens'])->firstWhere('codigo', '2.4.2');
    expect($itemAtualizado['quantidade'])->toBe('150.000');
    expect($itemAtualizado['id'])->not->toBeNull();

    $component2->call('salvar');

    $this->assertDatabaseHas('orcamento_itens', ['codigo' => '2.4.2', 'quantidade' => 150]);
    $this->assertDatabaseHas('orcamento_itens', ['codigo' => '2.4.4', 'quantidade' => 10]);
    expect(Orcamento::find($orcamento->id)->categorias()->withCount('itens')->get()->sum('itens_count'))->toBe(2);
});
