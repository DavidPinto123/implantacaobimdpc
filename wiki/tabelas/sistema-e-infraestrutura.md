# Tabelas: Sistema e Infraestrutura

## `jobs`

**Propósito**: Fila de jobs assíncronos do Laravel. Armazena jobs pendentes para processamento em background.
**Model**: *gerenciado pelo Laravel Queue*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| queue | string | não | — | Nome da fila (ex: `default`, `pdf`, `imports`) |
| payload | longText | não | — | Job serializado em JSON (classe + dados) |
| attempts | tinyInteger | não | — | Número de tentativas de execução |
| reserved_at | unsignedInteger | sim | — | Timestamp Unix de quando foi reservado para processamento |
| available_at | unsignedInteger | não | — | Timestamp Unix de quando o job fica disponível |
| created_at | unsignedInteger | não | — | Timestamp Unix de criação |

### Índices

- INDEX: `queue`

### Notas

- Jobs conhecidos: `GenerateVisitaTecnicaPdfJob`, `ProcessObraImportJob`
- Worker: `php artisan queue:work`

---

## `job_batches`

**Propósito**: Controle de batches (lotes) de jobs. Permite agrupar múltiplos jobs e rastrear progresso coletivo.
**Model**: *gerenciado pelo Laravel Bus*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | string PK | não | — | UUID do batch |
| name | string | não | — | Nome descritivo do batch |
| total_jobs | integer | não | — | Total de jobs no batch |
| pending_jobs | integer | não | — | Jobs ainda pendentes |
| failed_jobs | integer | não | — | Jobs com falha |
| failed_job_ids | longText | não | — | Array JSON dos IDs dos jobs falhos |
| options | mediumText | sim | — | Opções de configuração do batch |
| cancelled_at | integer | sim | — | Timestamp Unix de cancelamento |
| created_at | integer | não | — | Timestamp Unix de criação |
| finished_at | integer | sim | — | Timestamp Unix de conclusão |

---

## `failed_jobs`

**Propósito**: Registro de jobs que falharam após todas as tentativas. Permite reprocessamento manual.
**Model**: *gerenciado pelo Laravel Queue*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| uuid | string UNIQUE | não | — | UUID único do job falho |
| connection | text | não | — | Driver de conexão da fila |
| queue | text | não | — | Nome da fila |
| payload | longText | não | — | Job serializado |
| exception | longText | não | — | Stack trace da exceção |
| failed_at | timestamp | não | CURRENT_TIMESTAMP | Data/hora da falha |

### Índices

- UNIQUE: `uuid`

### Notas

- Reprocessar um job: `php artisan queue:retry {id}`
- Reprocessar todos: `php artisan queue:retry all`

---

## `cache`

**Propósito**: Cache de dados da aplicação (driver `database`).
**Model**: *gerenciado pelo Laravel Cache*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| key | string PK | não | — | Chave do cache |
| value | mediumText | não | — | Valor armazenado (serializado) |
| expiration | integer | não | — | Timestamp Unix de expiração |

---

## `cache_locks`

**Propósito**: Locks distribuídos para controle de concorrência (previne race conditions em operações críticas).
**Model**: *gerenciado pelo Laravel Cache*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| key | string PK | não | — | Chave do lock |
| owner | string | não | — | Identificador do processo que detém o lock |
| expiration | integer | não | — | Timestamp Unix de expiração do lock |

---

## `permissions`

**Propósito**: Permissões granulares do sistema (Spatie Permission). Geradas automaticamente pelo Filament Shield para cada Resource (view, create, edit, delete, etc.).
**Model**: `Spatie\Permission\Models\Permission`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| name | string | não | — | Nome da permissão (ex: `view_projeto`, `create_obra`, `delete_user`) |
| guard_name | string | não | — | Guard de autenticação (ex: `web`) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `(name, guard_name)`

### Notas

- Padrão Filament Shield: `{ação}_{resource_slug}` — ex: `view_projeto`, `create_projeto`
- Ações: `view_any`, `view`, `create`, `update`, `delete`, `delete_any`, `restore`, `restore_any`, `force_delete`, `force_delete_any`

---

## `roles`

**Propósito**: Roles (papéis) de usuário que agrupam permissions (Spatie Permission).
**Model**: `Spatie\Permission\Models\Role`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| name | string | não | — | Nome do role (ex: `admin`, `engenharia`, `comercial`) |
| guard_name | string | não | — | Guard de autenticação |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `(name, guard_name)`

---

## `model_has_permissions`

**Propósito**: Atribui permissions diretamente a modelos (ex: um User com permission específica sem precisar de role).
**Model**: *gerenciado pelo Spatie Permission*

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| permission_id | bigint FK | não | Permission |
| model_type | string | não | Tipo do modelo (ex: `App\Models\User`) |
| model_id | bigint | não | ID do modelo |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| permission_id | permissions.id | cascade |

### Índices

- PRIMARY composto: `(permission_id, model_id, model_type)`

---

## `model_has_roles`

**Propósito**: Atribui roles a modelos (usuários). Relação principal entre usuários e seus papéis no sistema.
**Model**: *gerenciado pelo Spatie Permission*

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| role_id | bigint FK | não | Role |
| model_type | string | não | Tipo do modelo (ex: `App\Models\User`) |
| model_id | bigint | não | ID do modelo |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| role_id | roles.id | cascade |

### Índices

- PRIMARY composto: `(role_id, model_id, model_type)`

---

## `role_has_permissions`

**Propósito**: Define quais permissions cada role possui. Configurado via painel Filament Shield.
**Model**: *gerenciado pelo Spatie Permission*

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| permission_id | bigint FK | não | Permission |
| role_id | bigint FK | não | Role |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| permission_id | permissions.id | cascade |
| role_id | roles.id | cascade |

### Índices

- PRIMARY composto: `(permission_id, role_id)`
