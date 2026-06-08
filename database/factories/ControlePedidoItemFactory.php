<?php

namespace Database\Factories;

use App\Models\ControlePedidoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlePedidoItem>
 */
class ControlePedidoItemFactory extends Factory
{
    protected $model = ControlePedidoItem::class;

    public function definition(): array
    {
        return [
            'controle_pedido_id' => ControlePedidoFactory::new(),
            'codigo' => fake()->numerify('#.#'),
            'nome' => fake()->sentence(3),
            'contratado' => fake()->boolean(),
            'valor' => fake()->randomFloat(2, 0, 50000),
        ];
    }
}
