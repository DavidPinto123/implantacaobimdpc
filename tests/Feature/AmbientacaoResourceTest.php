<?php

use App\Filament\Resources\AmbientacaoResource;
use App\Filament\Resources\AmbientacaoResource\Pages\CreateAmbientacao;
use App\Filament\Resources\AmbientacaoResource\Pages\ListAmbientacaos;
use App\Filament\Resources\AmbientacaoResource\Pages\SelecionarAngulo;
use App\Models\Ambientacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    setupAdminPanelForTests();
});

it('lista ambientacoes exibindo preview e feed de comentarios sem erro', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Ambientacao',
        'View:Ambientacao',
        'Create:Ambientacao',
        'Update:Ambientacao',
        'Delete:Ambientacao',
    ]);

    $this->actingAs($user);

    $ambientacao = Ambientacao::create([
        'pavimento' => 'Térreo',
        'ambiente' => 'Recepção',
        'bloco_torre' => 'Bloco 4',
        'departamento' => 'Administração',
        'codigo' => '003',
        'link_render' => 'https://pano.autodesk.com/pano.html?teste',
    ]);

    $imagem = $ambientacao->imagens()->create([
        'arquivo' => 'ambientacoes/teste/imagens/foo.jpg',
        'legenda' => 'Imagem de teste',
        'origem' => 'upload',
        'uploaded_by' => $user->id,
    ]);

    $imagem->comentarios()->create([
        'comentario' => 'Comentário de teste',
        'user_id' => $user->id,
    ]);

    $this->get(AmbientacaoResource::getUrl('index'))->assertOk();

    Livewire::test(ListAmbientacaos::class)->assertCanSeeTableRecords([$ambientacao]);
});

it('formulario de criacao nao exibe identificacao/localizacao e cria sem codigo', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Ambientacao',
        'Create:Ambientacao',
    ]);

    $this->actingAs($user);

    $this->get(AmbientacaoResource::getUrl('create'))->assertOk();

    Livewire::test(CreateAmbientacao::class)
        ->assertFormFieldDoesNotExist('codigo')
        ->assertFormFieldDoesNotExist('pais_id')
        ->fillForm([
            'pavimento' => 'Térreo',
            'ambiente' => 'Sala de Musculação',
            'link_render' => 'https://pano.autodesk.com/pano.html?teste2',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('ambientacoes', [
        'ambiente' => 'Sala de Musculação',
        'codigo' => null,
    ]);
});

it('pagina de selecionar angulo exige imagem equirretangular previamente enviada', function () {
    $user = createResourceBaselineUser([
        'ViewAny:Ambientacao',
        'Update:Ambientacao',
    ]);

    $this->actingAs($user);

    $ambientacao = Ambientacao::create([
        'pavimento' => 'Térreo',
        'ambiente' => 'Recepção',
        'link_render' => 'https://pano.autodesk.com/pano.html?teste3',
    ]);

    $this->get(SelecionarAngulo::getUrl(['record' => $ambientacao]))->assertNotFound();

    $ambientacao->update(['pano_equirretangular' => 'ambientacoes/teste/pano/equirect.jpg']);

    $this->get(SelecionarAngulo::getUrl(['record' => $ambientacao]))->assertOk();
});
