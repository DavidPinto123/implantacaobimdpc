<?php

use App\Enums\CategoriaAtualizacaoObra;
use App\Models\AtualizacaoObra;
use App\Models\Obras;
use App\Models\User;
use Database\Factories\ControlePedidoFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('gera atualizações para obras do projeto quando controle de pedido é criado', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);

    $obra = Obras::factory()->create();

    $controlePedido = ControlePedidoFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
    ]);

    $this->assertDatabaseHas('atualizacoes_obra', [
        'obra_id' => $obra->id,
        'usuario_id' => $user->id,
        'categoria' => CategoriaAtualizacaoObra::CONTRATACAO->value,
        'titulo' => 'Controle de Contratações criado',
        'automatico' => true,
    ]);

    expect($controlePedido->exists)->toBeTrue();
});

it('gera atualização quando campo rastreado é alterado', function () {
    $user = User::factory()->active()->create();
    $this->actingAs($user);

    $obra = Obras::factory()->create();

    $controlePedido = ControlePedidoFactory::new()->create([
        'projeto_id' => $obra->projeto_id,
        'status' => 'pendente',
    ]);

    AtualizacaoObra::query()->delete();

    $controlePedido->update([
        'status' => 'aprovado',
    ]);

    $this->assertDatabaseHas('atualizacoes_obra', [
        'obra_id' => $obra->id,
        'usuario_id' => $user->id,
        'categoria' => CategoriaAtualizacaoObra::CONTRATACAO->value,
        'campo_alterado' => 'status',
        'valor_anterior' => 'pendente',
        'valor_novo' => 'aprovado',
        'automatico' => true,
    ]);
});
