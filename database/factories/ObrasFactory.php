<?php

namespace Database\Factories;

use App\Models\Obras;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Obras>
 */
class ObrasFactory extends Factory
{
    protected $model = Obras::class;

    public function definition(): array
    {
        return [
            'projeto_id' => ProjetoFactory::new(),
            'status' => 'Obras',
            'homologados_em_atraso' => 'nao',
            'relatorio_fotografico' => 'nao_enviado',
            'termo_de_posse' => 'nao',
            'cronograma_visi' => 'nao_enviado',
            'camera_unidade' => 'nao',
            'email_solicitacao_cl' => 'nao_enviado',
            'envio_qrcod' => 'nao_enviado',
            'checklist_manutencao' => 'nao_iniciado',
            'codigo' => strtoupper(fake()->bothify('OBR-??####')),
            'unidade' => fake()->company(),
            'comentarios' => fake()->sentence(),
            'comentario' => fake()->sentence(),
            'fotos' => [],
        ];
    }
}
