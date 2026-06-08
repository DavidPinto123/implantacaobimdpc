<?php

namespace Database\Seeders;

use App\Models\PosObra\ConfiguracaoSla;
use App\Models\PosObra\DisciplinaConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PosObraSeeder extends Seeder
{
    public function run(): void
    {
        // 24 disciplinas da planilha
        $disciplinas = [
            'Civil', 'Elétrica', 'Hidráulica', 'Vazamento', 'Ar Condicionado',
            'Exaustor', 'Elevador', 'Porta', 'Marcenaria', 'Estrutura',
            'Pintura', 'Impermeabilização', 'Serralheria', 'Gás', 'Energia',
            'Piso', 'Forro', 'Fachada', 'Sinalização', 'CFTV',
            'Incêndio', 'Automação', 'Limpeza', 'Outros',
        ];

        foreach ($disciplinas as $index => $nome) {
            DisciplinaConfig::firstOrCreate(
                ['codigo' => strtoupper(Str::slug($nome, '_'))],
                ['label' => $nome, 'ordem' => $index + 1, 'ativo' => true]
            );
        }

        // SLAs padrão (P1=24h, P2=12h, P3=6h)
        $slas = [
            ['urgencia' => 'P1', 'prazo_horas' => 24],
            ['urgencia' => 'P2', 'prazo_horas' => 12],
            ['urgencia' => 'P3', 'prazo_horas' => 6],
        ];

        foreach ($slas as $sla) {
            ConfiguracaoSla::firstOrCreate(
                ['urgencia' => $sla['urgencia']],
                ['prazo_horas' => $sla['prazo_horas'], 'ativo' => true]
            );
        }

        $this->command->info('Disciplinas e SLAs do Pós Obra criados.');
    }
}
