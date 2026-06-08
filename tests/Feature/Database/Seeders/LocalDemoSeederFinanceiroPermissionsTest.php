<?php

use Database\Seeders\LocalDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(DatabaseTransactions::class);

it('nao concede permissão de exclusão do controle de nota fiscal aos perfis locais sincronizados', function () {
    $gestor = Role::findOrCreate('Gestor', 'web');
    Role::findOrCreate('coordenador_orcamento', 'web');
    Role::findOrCreate('Fornecedor', 'web');
    $superAdmin = Role::findOrCreate('super_admin', 'web');

    Permission::findOrCreate('Delete:ControleNotaFiscal', 'web');

    $gestor->givePermissionTo('Delete:ControleNotaFiscal');
    $superAdmin->givePermissionTo('Delete:ControleNotaFiscal');

    $seeder = app(LocalDemoSeeder::class);

    $syncMethod = new ReflectionMethod($seeder, 'syncLocalProfilePermissions');
    $syncMethod->setAccessible(true);
    $syncMethod->invoke($seeder);

    expect($gestor->fresh()->hasPermissionTo('Delete:ControleNotaFiscal'))->toBeFalse()
        ->and($superAdmin->fresh()->hasPermissionTo('Delete:ControleNotaFiscal'))->toBeTrue();
});
