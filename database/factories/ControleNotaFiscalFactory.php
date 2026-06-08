<?php

namespace Database\Factories;

use App\Enums\TipoUnidade;
use App\Models\ControleNotaFiscal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControleNotaFiscal>
 */
class ControleNotaFiscalFactory extends Factory
{
    protected $model = ControleNotaFiscal::class;

    public function definition(): array
    {
        return [
            'obra_id' => ObrasFactory::new(),
            'construtora_id' => ConstrutoraFactory::new(),
            'tipo_unidade' => TipoUnidade::EXPANSAO->value,
            'status' => ControleNotaFiscal::STATUS_ATIVO,
            'data_base' => fake()->date(),
            'unidade' => fake()->bothify('UN-##'),
            'sigla' => strtoupper(fake()->bothify('??#')),
            'endereco' => fake()->streetAddress(),
        ];
    }
}
