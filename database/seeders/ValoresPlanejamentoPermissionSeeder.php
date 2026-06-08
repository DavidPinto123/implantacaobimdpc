<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ValoresPlanejamentoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name'       => 'ver_valores_planejamento',
            'guard_name' => 'web',
        ]);

        // Concede a permissão a todos os roles que já têm acesso ao planejamento
        $rolesComAcesso = ['super_admin', 'admin', 'Gestor', 'PMO', 'Planejamento Estratégico'];

        foreach ($rolesComAcesso as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
