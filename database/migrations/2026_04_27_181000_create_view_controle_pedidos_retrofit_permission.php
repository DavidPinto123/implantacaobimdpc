<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! App::environment('local')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('View:ControlePedidosRetrofit', 'web');

        Role::query()
            ->whereIn('name', [
                'super_admin',
                'Gestor',
                'Coordenador',
                'Comercial',
                'PMO',
                'gestor_obra',
            ])
            ->get()
            ->each(function (Role $role) use ($permission): void {
                $role->givePermissionTo($permission);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! App::environment('local')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('name', 'View:ControlePedidosRetrofit')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            Role::query()
                ->whereIn('name', [
                    'super_admin',
                    'Gestor',
                    'Coordenador',
                    'Comercial',
                    'PMO',
                    'gestor_obra',
                ])
                ->get()
                ->each(function (Role $role) use ($permission): void {
                    $role->revokePermissionTo($permission);
                });

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
