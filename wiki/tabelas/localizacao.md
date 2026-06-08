# Tabelas: Localização

## `pais`

**Propósito**: Países cadastrados para vincular projetos, usuários e empresas geograficamente.
**Model**: `App\Models\Pais`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome do país (ex: "Brasil") |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `estados`

**Propósito**: Estados/UFs vinculados a um país. Usados em projetos, usuários, empresas e mapas geográficos.
**Model**: `App\Models\Estado`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| pais_id | bigint FK | não | — | País ao qual pertence |
| nome | string | não | — | Nome do estado (ex: "São Paulo") |
| uf | string(2) | sim | — | Sigla da UF (ex: "SP") — usada na rota `/projetos-por-estado/{sigla}` |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| pais_id | pais.id | cascade |

### Notas

- `uf` é usado pela rota `/projetos-por-estado/{sigla}` para filtrar projetos nos mapas geográficos

---

## `cidades`

**Propósito**: Cidades vinculadas a estados. Usadas em projetos, usuários, empresas e regiões de interesse.
**Model**: `App\Models\Cidade`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| estado_id | bigint FK | não | — | Estado ao qual pertence |
| nome | string | não | — | Nome da cidade |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| estado_id | estados.id | cascade |

---

## `regiao_interesses`

**Propósito**: Regiões de interesse para expansão da Smart Fit. Pontos geográficos identificados como candidatos a novas unidades antes de virar um projeto formal.
**Model**: `App\Models\RegiaoInteresse`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| pais_id | bigint FK | não | — | País da região |
| estado_id | bigint FK | não | — | Estado da região |
| cidade_id | bigint FK | não | — | Cidade da região |
| nome | string | não | — | Nome identificador da região/ponto |
| endereco | string | não | — | Endereço completo |
| bairro | string | não | — | Bairro |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| pais_id | pais.id | cascade |
| estado_id | estados.id | cascade |
| cidade_id | cidades.id | cascade |
