<?php

use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Models\ControlePedidoItem;
use Database\Factories\ControlePedidoFactory;
use Database\Factories\ControlePedidoItemFactory;
use Database\Factories\OrdemInvestimentoFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

it('recria itens do controle de pedido conforme mapa e pedidos', function () {
    $controlePedido = ControlePedidoFactory::new()->create([
        'pedidos' => [
            '1_1' => true,
            '2_1' => false,
        ],
    ]);

    ControlePedidoItemFactory::new()->create([
        'controle_pedido_id' => $controlePedido->id,
        'codigo' => 'X.0',
        'nome' => 'Item antigo',
        'contratado' => true,
        'valor' => 999,
    ]);

    OrdemInvestimentoFactory::new()->create([
        'projeto_id' => $controlePedido->projeto_id,
        'estrutura' => [
            [
                'nome' => 'EXECUÇÃO DE OBRA CIVIL - RECHEIO',
                'padrao' => 100,
                'ad' => 25,
            ],
        ],
    ]);

    ControlePedidoResource::afterSave($controlePedido);

    $mapCount = count(ControlePedidoResource::pedidosMap());

    expect(ControlePedidoItem::query()->where('controle_pedido_id', $controlePedido->id)->count())->toBe($mapCount);

    $itemRecheio = ControlePedidoItem::query()
        ->where('controle_pedido_id', $controlePedido->id)
        ->where('codigo', '1.1')
        ->firstOrFail();

    $itemShell = ControlePedidoItem::query()
        ->where('controle_pedido_id', $controlePedido->id)
        ->where('codigo', '2.1')
        ->firstOrFail();

    expect($itemRecheio->contratado)->toBeTrue()
        ->and((float) $itemRecheio->valor)->toBe(125.0)
        ->and($itemShell->contratado)->toBeFalse()
        ->and((float) $itemShell->valor)->toBe(0.0);
});
