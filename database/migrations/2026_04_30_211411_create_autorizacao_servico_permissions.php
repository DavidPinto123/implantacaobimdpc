<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'ViewAny:AutorizacaoServico',
        'View:AutorizacaoServico',
        'Create:AutorizacaoServico',
        'Update:AutorizacaoServico',
        'Delete:AutorizacaoServico',
        'Restore:AutorizacaoServico',
        'ForceDelete:AutorizacaoServico',
        'ForceDeleteAny:AutorizacaoServico',
        'RestoreAny:AutorizacaoServico',
        'Replicate:AutorizacaoServico',
        'Reorder:AutorizacaoServico',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->permissions)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
