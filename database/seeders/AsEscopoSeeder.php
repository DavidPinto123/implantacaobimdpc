<?php

namespace Database\Seeders;

use App\Models\AsEscopo;
use Illuminate\Database\Seeder;

class AsEscopoSeeder extends Seeder
{
    public function run(): void
    {
        $dados = [
            ['grupo' => 'Civil', 'numero_as' => '01.1', 'escopo' => 'CIVIL - RECHEIO'],
            ['grupo' => 'Civil', 'numero_as' => '52.1', 'escopo' => 'HIDRAULICA'],
            ['grupo' => 'Ar Condicionado', 'numero_as' => '03.1', 'escopo' => 'AR COND. INSTALAÇÃO'],
            ['grupo' => 'Ar Condicionado', 'numero_as' => '04.1', 'escopo' => 'AR COND. MÁQUINAS'],
            ['grupo' => 'Elétrica', 'numero_as' => '05.1', 'escopo' => 'ELÉTRICA INSTALAÇÃO'],
            ['grupo' => 'Combate a Incêndio', 'numero_as' => '06.1', 'escopo' => 'COMBATE A INCÊNDIO'],
            ['grupo' => 'Homologados', 'numero_as' => '09.1', 'escopo' => 'DIVISÓRIAS SANITÁRIAS'],
            ['grupo' => 'Civil', 'numero_as' => '10.2', 'escopo' => 'PISO PORCELANATO'],
            ['grupo' => 'Homologados', 'numero_as' => '10.1', 'escopo' => 'PISO VINÍLICO + RODAPE'],
            ['grupo' => 'Homologados', 'numero_as' => '11.1', 'escopo' => 'PISO DE BORRACHA'],
            ['grupo' => 'Homologados', 'numero_as' => '12.1', 'escopo' => 'LUMINÁRIAS'],
            ['grupo' => 'Homologados', 'numero_as' => '13.1', 'escopo' => 'AQUECEDOR A GÁS'],
            ['grupo' => 'Homologados', 'numero_as' => '14.1', 'escopo' => 'MARCENARIA'],
            ['grupo' => 'Homologados', 'numero_as' => '15.1', 'escopo' => 'BALANÇA'],
            ['grupo' => 'Homologados', 'numero_as' => '16.1', 'escopo' => 'BEBEDOURO ACES.'],
            ['grupo' => 'Homologados', 'numero_as' => '17.1', 'escopo' => 'BEBEDOURO IND.'],
            ['grupo' => 'Homologados', 'numero_as' => '18.1', 'escopo' => 'ESPALDAR'],
            ['grupo' => 'Homologados', 'numero_as' => '51.1', 'escopo' => 'PISO DRENANTE'],
            ['grupo' => 'Homologados', 'numero_as' => '20.1', 'escopo' => "TV'S"],
            ['grupo' => 'Homologados', 'numero_as' => '21.1', 'escopo' => 'ELETRODOMÉSTICOS'],
            ['grupo' => 'Homologados', 'numero_as' => '23.1', 'escopo' => 'SECADORES'],
            ['grupo' => 'Homologados', 'numero_as' => '24.1', 'escopo' => 'DUCHAS'],
            ['grupo' => 'Homologados', 'numero_as' => '27.1', 'escopo' => 'RELÓGIO DIGITAL'],
            ['grupo' => 'Homologados', 'numero_as' => '29.1', 'escopo' => 'BICICLETÁRIO'],
            ['grupo' => 'Homologados', 'numero_as' => '30.1', 'escopo' => 'FACHADA'],
            ['grupo' => 'Homologados', 'numero_as' => '31.1', 'escopo' => 'PORTA AUTOMÁTICA'],
            ['grupo' => 'Homologados', 'numero_as' => '40.1', 'escopo' => 'COMUNICAÇÃO VISUAL'],
            ['grupo' => 'Homologados', 'numero_as' => '45.1', 'escopo' => 'KIT ENXOVAL'],
            ['grupo' => 'Homologados', 'numero_as' => '50.1', 'escopo' => 'DESFIBRILADOR'],
            ['grupo' => 'Shell', 'numero_as' => '33.1', 'escopo' => 'ESTRUTURA METALICA'],
            ['grupo' => 'Shell', 'numero_as' => '32.1', 'escopo' => 'PLATAFORMA PNE/ ELEVADOR'],
            ['grupo' => 'Shell', 'numero_as' => '36.1', 'escopo' => 'ENTRADA DE ENERGIA'],
        ];

        foreach ($dados as $dado) {
            AsEscopo::updateOrCreate(
                ['numero_as' => $dado['numero_as']],
                [
                    'grupo' => $dado['grupo'],
                    'escopo' => $dado['escopo'],
                    'is_active' => true,
                ]
            );
        }
    }
}
