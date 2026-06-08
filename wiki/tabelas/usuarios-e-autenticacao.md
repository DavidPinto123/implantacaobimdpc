# Tabelas: Usuários e Autenticação

## `users`

**Propósito**: Usuários do sistema. Autenticação, perfil e vínculo com setores, obras e construtoras.
**Model**: `App\Models\User`

| Coluna               | Tipo          | Nullable | Default | Descrição                                                      |
| -------------------- | ------------- | -------- | ------- | -------------------------------------------------------------- |
| id                   | bigint PK     | não      | auto    | Identificador                                                  |
| name                 | string        | não      | —       | Nome completo                                                  |
| email                | string UNIQUE | não      | —       | E-mail de login                                                |
| phone                | string        | sim      | —       | Telefone de contato                                            |
| email_verified_at    | timestamp     | sim      | —       | Data de verificação do e-mail                                  |
| password             | string        | não      | —       | Senha (hashed bcrypt)                                          |
| foto_perfil          | string        | sim      | —       | Caminho da foto no storage R2 (`fotos-perfil/`)                |
| is_active            | boolean       | não      | true    | Se o usuário está ativo no sistema                             |
| is_fornecedor        | boolean       | não      | false   | Usuário representa uma empresa fornecedora                     |
| is_lider_obra        | boolean       | não      | false   | Usuário atua como líder de obra (acesso Pós Obra)              |
| construtoras_id      | bigint FK     | sim      | —       | Construtora vinculada (para usuários do tipo fornecedor/líder) |
| must_change_password | boolean       | não      | true    | Força troca de senha no próximo login                          |
| pais_id              | bigint FK     | sim      | —       | País do usuário                                                |
| estado_id            | bigint FK     | sim      | —       | Estado do usuário                                              |
| cidade_id            | bigint FK     | sim      | —       | Cidade do usuário                                              |
| remember_token       | string        | sim      | —       | Token "lembrar-me" para sessão persistente                     |
| created_at           | timestamp     | sim      | —       | Data de criação                                                |
| updated_at           | timestamp     | sim      | —       | Última atualização                                             |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| construtoras_id | construtoras.id | set null |
| pais_id | pais.id | set null |
| estado_id | estados.id | set null |
| cidade_id | cidades.id | set null |

### Índices

- UNIQUE: `email`
- INDEX: `is_active`

### Notas

- Usa `HasRoles` (Spatie Permission) para controle de roles/permissions
- Implementa `FilamentUser` (acesso ao painel) e `HasAvatar`
- Senha tem cast `hashed` (auto-hash ao setar)
- Filtro por setor: usuários veem apenas dados dos setores aos quais pertencem (via `setor_user`)
- `is_lider_obra = true` → acesso ao módulo Pós Obra como líder de obra (vinculado a uma construtora)

---

## `password_reset_tokens`

**Propósito**: Tokens temporários para redefinição de senha via e-mail.
**Model**: *sem model Eloquent dedicado (gerenciado pelo Laravel)*

| Coluna     | Tipo      | Nullable | Default | Descrição                     |
| ---------- | --------- | -------- | ------- | ----------------------------- |
| email      | string PK | não      | —       | E-mail do usuário solicitante |
| token      | string    | não      | —       | Token de redefinição (hashed) |
| created_at | timestamp | sim      | —       | Data de criação do token      |

### Notas

- Token expira por tempo configurado em `config/auth.php`
- Invalidado após uso bem-sucedido

---

## `sessions`

**Propósito**: Sessões de usuário ativas (driver de sessão = `database`).
**Model**: *sem model Eloquent dedicado*

| Coluna        | Tipo       | Nullable | Default | Descrição                                                 |
| ------------- | ---------- | -------- | ------- | --------------------------------------------------------- |
| id            | string PK  | não      | —       | ID único da sessão                                        |
| user_id       | bigint FK  | sim      | —       | Usuário autenticado (null = sessão anônima)               |
| ip_address    | string(45) | sim      | —       | IP de origem da sessão                                    |
| user_agent    | text       | sim      | —       | User-Agent do navegador                                   |
| payload       | longText   | não      | —       | Dados da sessão serializados                              |
| last_activity | integer    | não      | —       | Timestamp Unix da última atividade (usado para expiração) |

### Índices

- INDEX: `user_id`
- INDEX: `last_activity`

---

## `notifications`

**Propósito**: Notificações do sistema usando o sistema de Database Notifications do Laravel.
**Model**: *gerenciado pelo Laravel via `Notifiable` trait*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | uuid PK | não | — | Identificador UUID da notificação |
| type | string | não | — | Classe da notificação (ex: `App\Notifications\UserUpdatedNotification`) |
| notifiable_type | string | não | — | Tipo do modelo notificável (ex: `App\Models\User`) |
| notifiable_id | bigint | não | — | ID do modelo notificável |
| data | text | não | — | Payload JSON com os dados da notificação |
| read_at | timestamp | sim | — | Quando foi lida (null = não lida) |
| created_at | timestamp | sim | — | Data de envio |
| updated_at | timestamp | sim | — | Última atualização |

### Notas

- Notificação conhecida: `UserUpdatedNotification` — dispara quando um usuário é atualizado
