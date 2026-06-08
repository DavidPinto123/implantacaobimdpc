<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ZerarDatasPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => 'ZerarDatas:Cronograma',
            'guard_name' => 'web',
        ]);
    }
}
