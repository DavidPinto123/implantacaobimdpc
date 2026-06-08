# Autorização & Permissões

## Stack de autorização

```
Spatie Permission (roles/permissions)
         +
Filament Shield (CRUD automático)
         +
Gate Policies (controle granular)
         +
Filtro por setor (visibilidade de dados)
```

## Spatie Permission

- **Pacote**: `spatie/laravel-permission ^6.21`
- Roles e permissions armazenados no banco
- Aplicado via trait `HasRoles` no model `User`
- Configuração em `config/permission.php`

### Como verificar permissão

```php
// No código
$user->can('view_projeto');
$user->hasRole('admin');

// No Filament Resource
public static function canView(Model $record): bool
{
    return auth()->user()->can('view_projeto');
}
```

## Filament Shield

- **Pacote**: `bezhansalleh/filament-shield ^4.0`
- Gera automaticamente permissions CRUD para cada Resource
- Padrão de nomes gerado: `view_projeto`, `create_projeto`, `edit_projeto`, `delete_projeto`
- Configuração em `config/filament-shield.php`

### Gerar permissions

```bash
php artisan shield:generate --all
php artisan shield:generate --resource=ProjetoResource
```

## Gate Policies

- **34+ Policies** em `app/Policies/`
- Uma policy por model
- Registradas em `AppServiceProvider` (especialmente Pós Obra):
  - `PendenciaPolicy`
  - `DisciplinaConfigPolicy`
  - `ConfiguracaoSlaPolicy`

### Policies por domínio

**Projetos & Obras**
- `ProjetoPolicy`, `ObrasPolicy`, `EtapaPolicy`
- `RelatorioVisitaTecnicaPolicy`, `RelatorioFotograficoPolicy`

**Usuários & Organizações**
- `UserPolicy`, `EmpresasPolicy`, `ConstrutoraPolicy`
- `SetorPolicy`, `DepartamentosPolicy`

**Comercial**
- `PipePolicy`, `MarcaPolicy`, `AcompanhamentoPolicy`
- `ReuniaoPolicy`

**Localização**
- `CidadePolicy`, `EstadoPolicy`, `PaisPolicy`
- `RegiaoInteressePolicy`

**Financeiro**
- `ControlePedidoPolicy`, `ListaEmailPolicy`

**CAPEX & ASA**
- `CapexSimulacaoPolicy`, `ElaboracaoAditivoPolicy`
- `AsaPolicy`, `AsEscopoPolicy`, `AsFaixaAreaPolicy`

**Tarefas**
- `TaskPolicy`, `TaskCategoryPolicy`

**Outros**
- `DadosPolicy`, `AmbientesPolicy`, `MatterportPolicy`
- `AtualizacaoObraPolicy`, `RolePolicy`

**Pós Obra**
- `PendenciaPolicy`, `ConfiguracaoSlaPolicy`, `DisciplinaConfigPolicy`

## Filtro por Setor

Usuários visualizam **apenas dados dos seus setores**. Aplicado nas queries dos Resources como escopo global ou filtro de tabela.

```php
// Exemplo de filtro por setor em um Resource
->modifyQueryUsing(fn (Builder $query) => 
    $query->whereHas('setores', fn ($q) => 
        $q->whereIn('id', auth()->user()->setores->pluck('id'))
    )
)
```

## Permissões Pós Obra

Seeders específicos para as permissões do módulo:

```bash
php artisan db:seed --class=PosObraPermissionsSeeder
php artisan db:seed --class=AtualizacaoObraPermissionSeeder
```

## Autenticação

- **Página de login customizada**: `app/Filament/Pages/Auth/Login.php`
  - Branding Smart Fit (amarelo `#fbba00`)
- **Edição de perfil**: `app/Filament/Pages/Auth/EditProfile.php`
- **Avatar**: implementado via interface `HasAvatar` no `User`
- **Fotos de perfil**: armazenadas em `fotos-perfil/` no R2/S3
