<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AtualizacaoObraPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ViewAny:AtualizacaoObra',
            'View:AtualizacaoObra',
            'Create:AtualizacaoObra',
            'Update:AtualizacaoObra',
            'Delete:AtualizacaoObra',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
