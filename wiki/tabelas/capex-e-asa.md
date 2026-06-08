# Tabelas: CAPEX e ASA

## `capex_disciplinas`

**Propósito**: Catálogo de disciplinas de custo para simulação de CAPEX. Cada disciplina tem um modelo de cálculo (por área, fixo ou percentual).
**Model**: `App\Models\CapexDisciplina`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome da disciplina (ex: "Civil", "Elétrica", "HVAC") |
| tipo_calculo | enum | não | — | Modelo de cálculo: `area` (valor × m²), `fixo` (valor fixo), `percentual` (% do total) |
| valor_base | decimal(15,2) | não | — | Valor base para o cálculo (R$/m², R$ fixo, ou % dependendo do tipo) |
| usa_fator_correcao | boolean | não | true | Aplica fator de correção regional/temporal ao valor base? |
| ativo | boolean | não | true | Disciplina disponível para simulações? |
| parent_id | bigint FK | sim | — | Disciplina pai (para hierarquia de categorias) |
| consideracoes | text | sim | — | Notas e considerações sobre a disciplina |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| parent_id | capex_disciplinas.id | set null |

---

## `capex_simulacoes`

**Propósito**: Cabeçalho de uma simulação de CAPEX para um projeto. Armazena área, fator de correção e custo total estimado.
**Model**: `App\Models\CapexSimulacao`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto da simulação |
| area | decimal(10,2) | sim | — | Área da unidade (m²) — base para cálculos por área |
| fator_correcao | decimal(10,2) | não | 1 | Fator de correção (1 = sem correção) |
| valor_total | decimal(15,2) | não | — | Custo total estimado da simulação (R$) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | — |

---

## `capex_simulacao_itens`

**Propósito**: Itens individuais de uma simulação de CAPEX, um por escopo/disciplina com valores calculados.
**Model**: `App\Models\CapexSimulacaoItem`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| capex_simulacao_id | bigint FK | não | — | Simulação pai |
| as_escopo_id | bigint FK | sim | — | Escopo ASA vinculado |
| tipo | string | sim | — | Tipo do item (automático ou manual) |
| incluir | boolean | sim | — | Incluir este item no total? |
| ordem | integer | sim | — | Ordem de exibição |
| nome_escopo | string | sim | — | Nome do escopo para exibição |
| valor_base_m2 | decimal(15,2) | sim | — | Valor base por m² (R$/m²) |
| area | decimal(10,2) | sim | — | Área aplicada ao item (m²) |
| fator_correcao | decimal(10,2) | sim | — | Fator de correção do item |
| custo_estimado | decimal(15,2) | sim | — | Custo estimado calculado (R$) |
| percentual | decimal | sim | — | Percentual do total (%) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| capex_simulacao_id | capex_simulacoes.id | cascade |
| as_escopo_id | as_escopos.id | — |

---

## `as_escopos`

**Propósito**: Escopos de serviço padronizados para ASA (Assessments). Cada escopo tem um número AS único e pode ser vinculado a faixas de área com valores por m².
**Model**: `App\Models\AsEscopo`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| grupo | string | não | — | Grupo/categoria do escopo (ex: "Civil", "Elétrica") |
| numero_as | string(20) UNIQUE | não | — | Número identificador do escopo AS (ex: AS-001) |
| escopo | string UNIQUE | não | — | Descrição do escopo de serviço |
| is_active | boolean | não | true | Escopo ativo para uso em ASAs? |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `numero_as`
- UNIQUE: `escopo`

---

## `as_faixa_areas`

**Propósito**: Faixas de área para precificação de escopos ASA. Cada faixa define um intervalo de m² (ex: 0-500m², 500-1000m²) com valor R$/m² por escopo.
**Model**: `App\Models\AsFaixaArea`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome descritivo da faixa (ex: "Pequeno", "Médio", "Grande") |
| area_min | decimal(10,2) | não | — | Área mínima da faixa (m²) |
| area_max | decimal(10,2) | sim | — | Área máxima da faixa (m²) — null = sem limite superior |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `as_escopo_faixa_area`

**Propósito**: Pivot muitos-para-muitos entre escopos e faixas de área. Armazena o valor R$/m² de cada escopo para cada faixa de área.
**Model**: *gerenciado via `BelongsToMany` com `withPivot`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| as_escopo_id | bigint FK | não | — | Escopo ASA |
| as_faixa_area_id | bigint FK | não | — | Faixa de área |
| valor_m2 | decimal(15,2) | sim | — | Valor por m² (R$/m²) para esta combinação |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| as_escopo_id | as_escopos.id | — |
| as_faixa_area_id | as_faixa_areas.id | — |

---

## `asas`

**Propósito**: ASAs (Assessments/Aditivos de Serviço). Solicitações formais de serviços adicionais fora do contrato principal, com fluxo de aprovação e controle financeiro.
**Model**: `App\Models\Asa`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| numero_asa | string(50) UNIQUE | não | — | Número único da ASA (gerado automaticamente) |
| projeto_id | bigint FK | não | — | Projeto ao qual pertence |
| elaboracao_aditivo_id | bigint FK | sim | — | Elaboração de aditivo vinculada |
| sigla | string | sim | — | Sigla da unidade |
| endereco | string | sim | — | Endereço da unidade |
| contrato | string | sim | — | Número do contrato base |
| subgrupo | string | sim | — | Subgrupo/categoria da ASA |
| status | string | sim | — | Status atual da ASA |
| codigo_as_emitida | string | sim | — | Código da AS quando emitida formalmente |
| data_solicitacao | date | sim | — | Data da solicitação |
| data_aprovacao | date | sim | — | Data de aprovação |
| objeto | text | não | — | Objeto/escopo da ASA |
| justificativa | longText | sim | — | Justificativa técnica |
| altera_prazo | string | sim | — | Indica se altera o prazo do contrato |
| dias_prazo | integer | sim | — | Dias de alteração de prazo |
| valor_bruto | decimal(15,2) | não | 0 | Valor bruto (R$) |
| desconto | decimal(15,2) | não | 0 | Desconto aplicado (R$) |
| valor_total | decimal(15,2) | não | 0 | Valor total líquido (R$) |
| gestor_id | bigint FK | sim | — | Gestor responsável |
| solicitante | string | sim | — | Nome do solicitante |
| planilha_apresentada | json | sim | — | Planilha de orçamento apresentada |
| foto_antes | json | sim | — | Fotos do estado antes do serviço |
| foto_depois | json | sim | — | Fotos do estado depois do serviço |
| projeto_orcado | json | sim | — | Projeto orçado (arquivos) |
| projeto_revisado | json | sim | — | Projeto revisado (arquivos) |
| escopo_contratado | json | sim | — | Escopo contratado (arquivos) |
| escopo_real | json | sim | — | Escopo real executado (arquivos) |
| descricao | longText | sim | — | Descrição detalhada |
| evidencias | json | sim | — | Evidências do serviço |
| observacoes | longText | sim | — | Observações gerais |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | restrict |
| elaboracao_aditivo_id | elaboracao_aditivos.id | set null |
| gestor_id | users.id | set null |

### Índices

- UNIQUE: `numero_asa`

---

## `asa_items`

**Propósito**: Itens de medição de uma ASA (lista de serviços com quantidades e valores unitários/totais).
**Model**: `App\Models\AsaItem`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| asa_id | bigint FK | não | — | ASA pai |
| item | string(50) | sim | — | Código/número do item |
| descricao | string | não | — | Descrição do serviço |
| unidade | string(50) | sim | — | Unidade de medida (m², un, m, etc.) |
| quantidade | decimal(15,2) | não | 1 | Quantidade |
| valor_unitario | decimal(15,2) | não | 0 | Valor unitário (R$) |
| valor_total | decimal(15,2) | não | 0 | Valor total do item = quantidade × valor_unitario (R$) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| asa_id | asas.id | cascade |

---

## `elaboracao_aditivos`

**Propósito**: Elaboração de aditivos contratuais com fluxo de aprovação em dois níveis (gestor → orçamento). Precede a emissão formal de uma ASA.
**Model**: `App\Models\ElaboracaoAditivo`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| user_id | bigint FK | não | — | Usuário que elaborou o aditivo |
| construtora_id | bigint FK | sim | — | Construtora responsável pela execução |
| gestor_id | bigint FK | sim | — | Gestor que aprova o aditivo |
| as_escopo_id | bigint FK | sim | — | Escopo ASA de referência |
| obra_id | bigint FK | sim | — | Obra à qual o aditivo se refere |
| data | date | sim | — | Data da elaboração |
| ref_servico | string | sim | — | Referência do serviço |
| justificativa | text | sim | — | Justificativa técnica do aditivo |
| anexos | json | sim | — | Documentos de suporte |
| foto_antes | json | sim | — | Fotos antes |
| foto_depois | json | sim | — | Fotos depois |
| projeto_orcado | json | sim | — | Projeto orçado (arquivos) |
| projeto_revisado | json | sim | — | Projeto revisado |
| escopo_contratado | json | sim | — | Escopo contratado |
| escopo_real | json | sim | — | Escopo real |
| status_fluxo | string | não | 'elaboracao' | Status do fluxo: `elaboracao`, `aguardando_gestor`, `aprovado_gestor`, `aguardando_orcamento`, `aprovado`, `reprovado` |
| justificativa_reprovacao_gestor | text | sim | — | Justificativa do gestor ao reprovar |
| justificativa_reprovacao_orcamento | text | sim | — | Justificativa do orçamento ao reprovar |
| aprovado_gestor_por_id | bigint FK | sim | — | Usuário que aprovou como gestor |
| aprovado_gestor_em | timestamp | sim | — | Data/hora da aprovação do gestor |
| aprovado_orcamento_por_id | bigint FK | sim | — | Usuário que aprovou pelo orçamento |
| aprovado_orcamento_em | timestamp | sim | — | Data/hora da aprovação do orçamento |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| user_id | users.id | cascade |
| construtora_id | construtoras.id | set null |
| gestor_id | users.id | set null |
| as_escopo_id | as_escopos.id | set null |
| obra_id | obras.id | set null |
| aprovado_gestor_por_id | users.id | set null |
| aprovado_orcamento_por_id | users.id | set null |

---

## `elaboracao_aditivo_items`

**Propósito**: Itens de medição de um aditivo em elaboração (lista de serviços com material, mão de obra e totais).
**Model**: `App\Models\ElaboracaoAditivoItem`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| elaboracao_aditivo_id | bigint FK | não | — | Aditivo pai |
| item | string | sim | — | Código/número do item |
| descricao_servico | text | não | — | Descrição detalhada do serviço |
| quantidade | decimal(15,2) | não | 0 | Quantidade |
| unidade | string(50) | sim | — | Unidade de medida |
| valor_material_unitario | decimal(15,2) | não | 0 | Valor do material por unidade (R$) |
| valor_mao_obra_unitario | decimal(15,2) | não | 0 | Valor da mão de obra por unidade (R$) |
| total_unitario | decimal(15,2) | não | 0 | Total unitário = material + mão de obra (R$) |
| valor_total_geral | decimal(15,2) | não | 0 | Valor total = quantidade × total_unitario (R$) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| elaboracao_aditivo_id | elaboracao_aditivos.id | cascade |
