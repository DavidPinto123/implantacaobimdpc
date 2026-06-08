<?php

namespace Database\Seeders;

use App\Models\CapexDisciplina;
use Illuminate\Database\Seeder;

class CapexEstruturaSeeder extends Seeder
{
    public function run(): void
    {
        CapexDisciplina::truncate();

        $estrutura = [

            'OBRA CIVIL' => [
                'EXECUÇÃO DE OBRA CIVIL - RECHEIO',
                'EXECUÇÃO DE OBRA CIVIL - SHELL',
                'FORN. E INSTAL. - ESTRUTURA METÁLICA',
            ],

            'AR CONDICIONADO' => [
                'INSTAL. AR CONDICIONADO',
                'MÁQ. AR CONDICIONADO',
            ],

            'ELÉTRICA/PCI' => [
                'INSTAL. ELÉTRICA',
                'INSTAL. COMBATE INCÊNDIO',
            ],

            'NO BREAK' => [
                'FORN. EQUIPAMENTO NO BREAK',
            ],

            'GERENCIAMENTO' => [
                'GERENCIAMENTO',
            ],

            'PISOS/DIVISÓRIAS' => [
                'FORN. E INSTAL. - DIVISÓRIAS BANHEIRO',
                'FORN. PISO VINILICO',
                'FORN. PISO DE BORRACHA',
            ],

            'LUMINÁRIAS' => [
                'FORN. LUMINÁRIAS',
            ],

            'AQUECEDORES' => [
                'FORN. E INSTAL. - AQUECEDORES',
            ],

            'MARCENARIA' => [
                'FORN. E INSTAL. - MARCENARIA',
            ],

            'BALANÇAS' => [
                'FORN. BALANÇA',
            ],

            'BEBEDOURO' => [
                'BEBEDOURO ACESSÍVEL',
                'BEBEDOURO INDUSTRIAL',
            ],

            'EQUIPAMENTOS' => [
                'FORN. E INSTAL. - ESPALDAR',
                'FORN. E INSTAL. - RACK FUNCIONAL',
            ],

            'ELETRO/ELETRÔNICOS' => [
                'FORN. TELEVISORES',
                'FORN. ELETRODOMÉSTICOS',
            ],

            'MASSAGEM' => [
                'FORN. POLTRONAS DE MASSAGEM',
            ],

            'BANHEIRO' => [
                'FORN. SECADOR DE MÃOS',
                'FORN. DUCHAS',
            ],

            'VENTILADORES' => [
                'VENTILADORES', // Item não contem no pedidosMap()
                'INSTAL. VENTILADORES', // Item não contem no pedidosMap()
            ],

            'RELÓGIO DE PAREDE' => [
                'RELÓGIO DE PAREDE',
            ],

            'LIMPEZA' => [
                'LIMPEZA FINA', // Item não contem no pedidosMap()
            ],

            'BICICLETÁRIO' => [
                'BICICLETÁRIO',
            ],

            'FACHADA' => [
                'FORN. E INSTAL. - FACHADA',
                'PELÍCULA DA FACHADA', // Item não contem no pedidosMap()
            ],

            'PORTA AUTOMÁTICA' => [
                'FORN. E INSTAL. - PORTA AUTOMÁTICA',
            ],

            'ELEVADOR/PLATAFORMA' => [
                'PLATAFORMA PNE',
                'FORN. E INSTAL. - ELEVADOR',
            ],

            'ENTRADA DE ENERGIA' => [
                'CONSULTORIA - ENTRADA DE ENERGIA',
                'FORN. E INSTAL. - ENTRADA DE ENERGIA',
            ],

            'CADEIRAS' => [
                'CADEIRAS', // Item não contem no pedidosMap()
                'CADEIRAS - OPERAÇÕES', // Item não contem no pedidosMap()
            ],

            'ACÚSTICAS' => [
                'FORN. E INSTAL. - ACÚSTICA',
            ],

            'GERADOR' => [
                'LOCAÇÃO DE GERADOR',
            ],

            'COMUNICAÇÃO' => [
                'COMUNICAÇÃO VISUAL INTERNA',
                'QUADRO ACRÍLICO - OPERAÇÕES', // Item não contem no pedidosMap()
            ],

            'CAPACHO' => [
                'CAPACHO DA ENTRADA - OPERAÇÕES', // Item não contem no pedidosMap()
            ],

            'LIXEIRA' => [
                'LIXEIRAS - OPERAÇÕES', // Item não contem no pedidosMap()
            ],

            'ENXOVAL' => [
                'ENXOVAL',
            ],

            'SEGURANÇA' => [
                'SERV. SEGURANÇA',
            ],

            'TI E SONORIZAÇÃO' => [
                'TI E SONORIZAÇÃO',
            ],

            'PRÉ-OBRA' => [
                'PRÉ - OBRA',
            ],

            'ADITIVO' => [
                'ADITIVO', // Item não contem no pedidosMap()
            ],

            'DESFIBRILADOR' => [
                'DESFIBRILADOR',
            ],

            'PISO DRENANTE' => [
                'PISO DRENANTE',
            ],

            'INSTAL. HIDRÁULICAS' => [
                'INSTAL. HIDRÁULICAS',
            ],
        ];

        foreach ($estrutura as $grupo => $servicos) {

            // Cria o grupo (pai)
            $pai = CapexDisciplina::create([
                'nome' => $grupo,
                'tipo_calculo' => 'fixo',
                'valor_base' => 0,
                'usa_fator_correcao' => true,
                'ativo' => true,
            ]);

            // Cria os filhos
            foreach ($servicos as $servico) {

                CapexDisciplina::create([
                    'nome' => $servico,
                    'parent_id' => $pai->id,
                    'tipo_calculo' => 'fixo',
                    'valor_base' => 0,
                    'usa_fator_correcao' => true,
                    'ativo' => true,
                ]);
            }
        }
    }
}
