<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HomologacaoPmoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View:HistoricoProjetos',
            'View:AprovacaoMudancaPosse',
            'View:ImportarMkt',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Preserva o acesso que existia antes via hasAnyRole nas pages.
        // super_admin já intercepta o gate (config filament-shield.super_admin).
        $rolesComAcesso = ['PMO', 'Planejamento Estratégico'];

        foreach ($rolesComAcesso as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permissions);
        }
    }
}
