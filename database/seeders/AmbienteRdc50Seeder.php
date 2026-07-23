<?php

namespace Database\Seeders;

use App\Models\AmbienteRdc50;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class AmbienteRdc50Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/RDC50_ambientes_R2.xlsx');

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $columns = [
            'A' => 'unidade_funcional',
            'B' => 'subgrupo',
            'C' => 'tipo',
            'D' => 'num_atividade',
            'E' => 'ambiente',
            'F' => 'nome_fiorentini',
            'G' => 'obrigatoriedade',
            'H' => 'quantificacao_minima',
            'I' => 'pe_direito_minimo',
            'J' => 'area_dimensao_minima',
            'K' => 'instalacoes',
            'L' => 'rev_piso',
            'M' => 'rev_parede',
            'N' => 'rev_forro',
            'O' => 'rev_rodape',
            'P' => 'rev_rodameio',
        ];

        $records = [];

        foreach ($rows as $index => $row) {
            if ($index === 1) {
                continue;
            }

            if (blank($row['E'] ?? null)) {
                continue;
            }

            $record = [];
            foreach ($columns as $col => $field) {
                $value = trim((string) ($row[$col] ?? ''));
                $record[$field] = $value === '' ? null : $value;
            }
            $record['created_at'] = now();
            $record['updated_at'] = now();

            $records[] = $record;
        }

        AmbienteRdc50::truncate();

        foreach (array_chunk($records, 200) as $chunk) {
            AmbienteRdc50::insert($chunk);
        }

        $this->command?->info(count($records).' ambientes da RDC50 importados.');
    }
}
