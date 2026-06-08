<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PosObraPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permissões de Resources (formato Shield: Action:Model)
        $models = ['Pendencia', 'DisciplinaConfig', 'ConfiguracaoSla'];
        $actions = [
            'ViewAny', 'View', 'Create', 'Update', 'Delete',
            'Restore', 'ForceDelete', 'ForceDeleteAny', 'RestoreAny',
            'Replicate', 'Reorder',
        ];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "$action:$model",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Permissões de Pages (formato Shield: View:NomeDaPagina)
        $pages = [
            'DashboardPosObra',
            'KanbanPendencias',
            'AprovacoesPage',
            'WhatsAppConfigPage',
            'FluxoBotPage',
            'LideresObraPage',
            'ConstrutorasPage',
        ];

        foreach ($pages as $page) {
            Permission::firstOrCreate([
                'name' => "View:$page",
                'guard_name' => 'web',
            ]);
        }

        // Super admin recebe todas as permissões
        $superAdmin = Role::findByName('super_admin');
        foreach ($models as $model) {
            $perms = Permission::where('name', 'like', "%:$model")->pluck('name');
            $superAdmin->givePermissionTo($perms);
        }
        foreach ($pages as $page) {
            $superAdmin->givePermissionTo("View:$page");
        }

        // Gestor recebe permissões de pendências + pages do Pós Obra
        $gestorRole = Role::findByName('Gestor');
        if ($gestorRole) {
            $gestorRole->givePermissionTo([
                'ViewAny:Pendencia', 'View:Pendencia',
                'Create:Pendencia', 'Update:Pendencia',
                'Delete:Pendencia',
                'View:DashboardPosObra',
                'View:KanbanPendencias',
                'View:AprovacoesPage',
            ]);
        }

        app()['cache']->forget('spatie.permission.cache');

        $this->command->info('Permissões do Pós Obra geradas e atribuídas.');
    }
}
