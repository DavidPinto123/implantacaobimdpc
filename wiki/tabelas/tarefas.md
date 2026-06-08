# Tabelas: Tarefas

## `task_categories`

**Propósito**: Categorias de tarefas (ex: Arquitetura, Engenharia, PMO). Organiza tarefas por tipo ou área de responsabilidade.
**Model**: `App\Models\TaskCategory`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| name | string UNIQUE | não | — | Nome da categoria |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `name`

---

## `tasks`

**Propósito**: Tarefas atribuídas a usuários vinculadas a projetos e setores. Suporta cálculo de prazos em dias úteis ou corridos (via `DateCalc`).
**Model**: `App\Models\Task`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| title | string | não | — | Título da tarefa |
| description | text | sim | — | Descrição detalhada |
| task_category_id | bigint FK | não | — | Categoria da tarefa |
| sigla | string(20) | sim | — | Sigla da unidade associada |
| marca_id | bigint FK | não | — | Marca/bandeira da unidade |
| created_by | bigint FK | não | — | Usuário que criou a tarefa |
| assigned_to | bigint FK | não | — | Responsável principal pela entrega |
| setor_id | bigint FK | sim | — | Setor responsável |
| projeto_id | bigint FK | sim | — | Projeto relacionado |
| prazo | unsignedInteger | sim | — | Número de dias para conclusão |
| dias_corridos | boolean | não | false | `false` = dias úteis, `true` = dias corridos |
| inicio | date | sim | — | Data de início |
| termino_programado | date | sim | — | Data de término calculada (início + prazo) |
| data_entrega | dateTime | sim | — | Data/hora efetiva de entrega |
| status | string | sim | null | Status atual: `pendente`, `em_andamento`, `concluida`, etc. |
| priority | string | sim | — | Prioridade: `baixa`, `media`, `alta`, `urgente` |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| task_category_id | task_categories.id | cascade |
| marca_id | marcas.id | cascade |
| created_by | users.id | cascade |
| assigned_to | users.id | cascade |
| setor_id | setores.id | set null |
| projeto_id | projetos.id | set null |

### Índices

- INDEX composto: `(assigned_to, status)` — otimiza consultas de tarefas por responsável e status
- INDEX: `created_by`

### Notas

- `dias_corridos = false`: o `termino_programado` é calculado pulando fins de semana e feriados via `DateCalc`
- `dias_corridos = true`: cálculo simples (início + prazo)
- `assigned_to` é o responsável principal; colaboradores adicionais ficam em `task_user`

---

## `task_user`

**Propósito**: Pivot muitos-para-muitos. Usuários colaboradores de uma tarefa (além do responsável principal em `assigned_to`).
**Model**: *gerenciado via `BelongsToMany` em `User` (`tarefasTemporarias`)*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| task_id | bigint FK | não | — | Tarefa |
| user_id | bigint FK | não | — | Usuário colaborador |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| task_id | tasks.id | cascade |
| user_id | users.id | cascade |

### Índices

- UNIQUE: `(task_id, user_id)`
