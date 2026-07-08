<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View:MenuChecklist',
            'View:MenuDownloads',
            'View:MenuTreinamentos',
            'View:MenuUpload',
            'View:MenuAtas',
            'View:MenuProjetosPiloto',
            'View:MenuWhatsApp',
            'View:MenuOrcamentoObras',
        ];

        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Atribuir ao super_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Criadas ' . count($permissions) . ' permissões de menu.');
    }
}
