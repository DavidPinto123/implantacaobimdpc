<?php

use App\Filament\Resources\AmbientesResource;
use App\Filament\Resources\Asas\AsaResource;
use App\Filament\Resources\AsEscopos\AsEscopoResource;
use App\Filament\Resources\AsFaixaAreas\AsFaixaAreaResource;
use App\Filament\Resources\AutorizacaoServicos\AutorizacaoServicoResource;
use App\Filament\Resources\CapexSimulacaos\CapexSimulacaoResource;
use App\Filament\Resources\CidadeResource;
use App\Filament\Resources\ConstrutoraResource;
use App\Filament\Resources\ControleNotaFiscals\ControleNotaFiscalResource;
use App\Filament\Resources\ControlePedidos\ControlePedidoResource;
use App\Filament\Resources\DadosResource;
use App\Filament\Resources\DepartamentosResource;
use App\Filament\Resources\ElaboracaoAditivos\ElaboracaoAditivoResource;
use App\Filament\Resources\EmpresasResource;
use App\Filament\Resources\EstadoResource;
use App\Filament\Resources\EtapaResource;
use App\Filament\Resources\ImportacaoNotaFiscals\ImportacaoNotaFiscalResource;
use App\Filament\Resources\ListaEmails\ListaEmailResource;
use App\Filament\Resources\MarcaResource;
use App\Filament\Resources\MatterportResource;
use App\Filament\Resources\ObraDocumentos\ObraDocumentoResource;
use App\Filament\Resources\ObraRecebimentos\ObraRecebimentoResource;
use App\Filament\Resources\Obras\ObrasResource;
use App\Filament\Resources\PaisResource;
use App\Filament\Resources\PipeResource;
use App\Filament\Resources\PosObra\ConfiguracaoSlaResource;
use App\Filament\Resources\PosObra\DisciplinaConfigResource;
use App\Filament\Resources\PosObra\PendenciaResource;
use App\Filament\Resources\ProjetoResource;
use App\Filament\Resources\RelatorioFotograficos\RelatorioFotograficoResource;
use App\Filament\Resources\RelatorioVisitaTecnicaResource;
use App\Filament\Resources\SetorResource;
use App\Filament\Resources\StatusContratacaoResource;
use App\Filament\Resources\TaskCategories\TaskCategoryResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\UserResource;
use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function setupFilamentResourceCoverageForTests($testCase = null): void
{
    setupAdminPanelForTests();
    ensureDefaultRoles();

    foreach (['colaborador_orcamento', 'engenharia'] as $roleName) {
        Role::findOrCreate($roleName, 'web');
    }

    $models = collect(activeFilamentResourceManifest())
        ->map(fn (string $resourceClass): string => class_basename($resourceClass::getModel()))
        ->merge(['ConfiguracaoSla', 'DisciplinaConfig', 'Pendencia'])
        ->unique()
        ->values()
        ->all();

    $actions = ['ViewAny', 'Create', 'View', 'Update', 'Delete'];
    $permissions = [];

    foreach ($models as $model) {
        foreach ($actions as $action) {
            $permissions[] = "{$action}:{$model}";
        }
    }

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $admin = createActiveUserWithPermissions($permissions);
    $admin->assignRole(Role::findOrCreate('super_admin', 'web'));

    /** @var Authenticatable $authenticatableAdmin */
    $authenticatableAdmin = $admin;

    if ($testCase !== null) {
        $testCase->actingAs($authenticatableAdmin);

        return;
    }

    test()->actingAs($authenticatableAdmin);
}

function activeFilamentResourceManifest(): array
{
    return [
        AutorizacaoServicoResource::class,
        ProjetoResource::class,
        ImportacaoNotaFiscalResource::class,
        ControleNotaFiscalResource::class,
        RelatorioVisitaTecnicaResource::class,
        UserResource::class,
        ObraDocumentoResource::class,
        ObraRecebimentoResource::class,
        MatterportResource::class,
        PendenciaResource::class,
        EmpresasResource::class,
        ConstrutoraResource::class,
        CapexSimulacaoResource::class,
        AsaResource::class,
        TaskResource::class,
        SetorResource::class,
        TaskCategoryResource::class,
        RelatorioFotograficoResource::class,
        ConfiguracaoSlaResource::class,
        DisciplinaConfigResource::class,
        PaisResource::class,
        ObrasResource::class,
        PipeResource::class,
        ListaEmailResource::class,
        EtapaResource::class,
        MarcaResource::class,
        DepartamentosResource::class,
        EstadoResource::class,
        ElaboracaoAditivoResource::class,
        ControlePedidoResource::class,
        DadosResource::class,
        CidadeResource::class,
        StatusContratacaoResource::class,
        AsFaixaAreaResource::class,
        AsEscopoResource::class,
        AmbientesResource::class,
    ];
}

function expectedButMissingFilamentResources(): array
{
    return [];
}

function discoveredActiveFilamentResources(): array
{
    $directory = new RecursiveDirectoryIterator(base_path('app/Filament/Resources'));
    $iterator = new RecursiveIteratorIterator($directory);

    $files = [];

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        if (! str_ends_with($file->getFilename(), 'Resource.php')) {
            continue;
        }

        if (str_starts_with($file->getBasename(), '.')) {
            continue;
        }

        $files[] = $file->getPathname();
    }

    return collect($files)
        ->map(function (string $path): string {
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $class = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);

            return 'App\\'.str($class)->after('app\\')->toString();
        })
        ->sort()
        ->values()
        ->all();
}
