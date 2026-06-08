<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('CancelApproved:AutorizacaoServico', 'web');

        Role::query()
            ->whereIn('name', ['super_admin', 'Coordenador'])
            ->get()
            ->each(fn (Role $role): Role => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('name', 'CancelApproved:AutorizacaoServico')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            Role::query()
                ->whereIn('name', ['super_admin', 'Coordenador'])
                ->get()
                ->each(fn (Role $role): Role => $role->revokePermissionTo($permission));

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
