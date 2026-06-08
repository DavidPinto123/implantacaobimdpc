<?php

namespace Database\Seeders;

use App\Models\Pais;
use Illuminate\Database\Seeder;

class PaisesAmericasSeeder extends Seeder
{
    public function run(): void
    {
        $paises = [
            'AR' => 'Argentina',
            'BO' => 'Bolívia',
            'BR' => 'Brasil',
            'CL' => 'Chile',
            'CO' => 'Colômbia',
            'EC' => 'Equador',
            'GY' => 'Guiana',
            'PY' => 'Paraguai',
            'PE' => 'Peru',
            'SR' => 'Suriname',
            'UY' => 'Uruguai',
            'VE' => 'Venezuela',
            'BZ' => 'Belize',
            'CR' => 'Costa Rica',
            'SV' => 'El Salvador',
            'GT' => 'Guatemala',
            'HN' => 'Honduras',
            'MX' => 'México',
            'NI' => 'Nicarágua',
            'PA' => 'Panamá',
            'BS' => 'Bahamas',
            'BB' => 'Barbados',
            'CU' => 'Cuba',
            'DO' => 'República Dominicana',
            'HT' => 'Haiti',
            'JM' => 'Jamaica',
            'PR' => 'Porto Rico',
            'TT' => 'Trinidad e Tobago',
        ];

        foreach ($paises as $iso => $nome) {
            // Atualiza por iso quando já existir, senão por nome (para reaproveitar registros legados sem iso).
            $existente = Pais::where('iso', $iso)->orWhere('nome', $nome)->first();

            if ($existente) {
                $existente->fill(['nome' => $nome, 'iso' => $iso])->save();
            } else {
                Pais::create(['nome' => $nome, 'iso' => $iso]);
            }
        }
    }
}
