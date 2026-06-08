<?php

namespace Database\Factories;

use App\Models\Projeto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Projeto>
 */
class ProjetoFactory extends Factory
{
    protected $model = Projeto::class;

    public function create($attributes = [], ?Model $parent = null)
    {
        $previousUser = Auth::user();

        if (! $previousUser) {
            Auth::setUser(UserFactory::new()->active()->create());
        }

        try {
            return parent::create($attributes, $parent);
        } finally {
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::guard()->logout();
            }
        }
    }

    public function definition(): array
    {
        $responsavel = Auth::user() ?? UserFactory::new()->active()->create();

        $cidade = CidadeFactory::new()->create()->load('estado.pais');

        return [
            'nome' => fake()->company().' '.fake()->word(),
            'sigla' => strtoupper(fake()->bothify('??###')),
            'user_id' => $responsavel->id,
            'etapa_id' => EtapaFactory::new(),
            'cidade_id' => $cidade->id,
            'estado_id' => $cidade->estado_id,
            'pais_id' => $cidade->estado->pais_id,
            'status' => 'Em processo',
            'rua' => fake()->streetName(),
            'bairro' => fake()->citySuffix(),
            'cep' => fake()->postcode(),
            'numero' => (string) fake()->numberBetween(1, 9999),
        ];
    }
}
