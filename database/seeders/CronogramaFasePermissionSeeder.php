<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CronogramaFasePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ViewAny:CronogramaFase',
            'View:CronogramaFase',
            'Create:CronogramaFase',
            'Update:CronogramaFase',
            'Delete:CronogramaFase',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
