<?php

namespace Database\Seeders;

use App\Imports\ProjetosImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class ImportImoveisToProjetosSeeder extends Seeder
{
    public function run(): void
    {
        Excel::import(new ProjetosImport, public_path('planilha_importacao_projetos31.xlsx'));
    }
}
