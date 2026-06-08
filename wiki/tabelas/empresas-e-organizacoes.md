# Tabelas: Empresas e Organizações

## `empresas`

**Propósito**: Empresas do grupo Smart Fit ou parceiras. Usadas para vincular projetos e operações a entidades jurídicas.
**Model**: `App\Models\Empresa\Empresas`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Razão social |
| nome_fantasia | string | não | — | Nome fantasia |
| responsavel | string | não | — | Nome do responsável da empresa |
| email | string | não | — | E-mail de contato |
| contato | string | não | — | Telefone de contato |
| cnpj | string UNIQUE | não | — | CNPJ da empresa |
| tipo | string | não | — | Tipo da empresa (ex: matriz, filial, parceira) |
| status | boolean | não | true | Empresa ativa ou inativa |
| cidade_id | bigint FK | não | — | Cidade da empresa |
| estado_id | bigint FK | não | — | Estado da empresa |
| pais_id | bigint FK | não | — | País da empresa |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| cidade_id | cidades.id | cascade |
| estado_id | estados.id | cascade |
| pais_id | pais.id | cascade |

### Índices

- UNIQUE: `cnpj`

---

## `construtoras`

**Propósito**: Empresas construtoras que executam obras da Smart Fit. Vinculadas a obras e ao módulo Pós Obra para comunicação via WhatsApp.
**Model**: `App\Models\Construtora`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome da construtora |
| cnpj | string UNIQUE | não | — | CNPJ |
| telefone | string | sim | — | Telefone geral |
| email | string | sim | — | E-mail de contato |
| tipo | string | não | 'CONSTRUTORA' | Tipo: CONSTRUTORA, FORNECEDOR, etc. (Enum: `TipoConstrutora`) |
| telefone_whatsapp | string | sim | — | Número WhatsApp para notificações do Pós Obra (formato: 5511999999999) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `cnpj`

### Notas

- `telefone_whatsapp` é o número usado pelo `WhatsAppService` para enviar notificações automáticas de pendências
- Vinculada a `po_pendencias` (construtora responsável pela pendência)
- Vinculada a `po_disciplinas_config` via `construtora_disciplina` (quais disciplinas cada construtora atende)

---

## `setores`

**Propósito**: Setores internos da Smart Fit (ex: Engenharia, Arquitetura, PMO, Comercial). Controla quais dados cada usuário pode visualizar.
**Model**: `App\Models\Setor`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome do setor |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Notas

- Setor é a principal dimensão de filtro de visibilidade: um usuário só vê registros dos seus setores

---

## `setor_user`

**Propósito**: Tabela pivot. Relaciona usuários com seus setores (muitos-para-muitos).
**Model**: *gerenciado via `BelongsToMany` em `User` e `Setor`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| setor_id | bigint FK | não | — | Setor |
| user_id | bigint FK | não | — | Usuário |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| setor_id | setores.id | cascade |
| user_id | users.id | cascade |

---

## `departamentos`

**Propósito**: Dados de departamentos extraídos de sistemas externos (BIM/planilhas). Usados para referência e relatórios de layout.
**Model**: `App\Models\Departamentos`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nova_sigla | string | não | — | Sigla atualizada da unidade |
| unidade | string | não | — | Nome da unidade |
| departamento | string | não | — | Nome do departamento |
| area | string | não | — | Área em m² |
| data_extracao | date | não | — | Data de extração dos dados do sistema de origem |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `marcas`

**Propósito**: Marcas/bandeiras das academias do grupo (Smart Fit, Bio Ritmo, etc.). Usadas para classificar projetos e relatórios.
**Model**: `App\Models\Marca`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome da marca (ex: "Smart Fit", "Bio Ritmo") |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `pipes`

**Propósito**: Estágios do pipeline de vendas/prospecção. Define os stages do funil comercial.
**Model**: `App\Models\Pipe`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| pipeline | string | não | — | Nome do estágio do pipeline (ex: "Prospecção", "Negociação", "Assinatura") |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |
