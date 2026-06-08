# Tabelas: Financeiro

## `controle_pedidos`

**Propósito**: Controle de pedidos/contratos de compra por projeto. Registra fornecedores, valores, status e categorias de serviços contratados.
**Model**: `App\Models\ControlePedido` (Observer: `ControlePedidoObserver`)

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto ao qual o pedido pertence |
| construtora_id | bigint FK | sim | — | Construtora/fornecedor vinculado |
| elaboracao_contrato | date | sim | — | Data de elaboração do contrato |
| cnpj | string | sim | — | CNPJ do fornecedor |
| status | string | sim | — | Status do pedido |
| contratacao | date | sim | — | Data de contratação |
| observacoes | text | sim | — | Observações gerais |
| instal_ar | string | sim | — | Empresa de instalação de ar condicionado |
| luminarias | string | sim | — | Empresa de luminárias |
| instal_aquecedores | string | sim | — | Empresa de instalação de aquecedores |
| fachada | string | sim | — | Empresa de fachada |
| marcenaria | string | sim | — | Empresa de marcenaria |
| construtora_sugestao | string | sim | — | Construtora sugerida |
| divisorias | string | sim | — | Empresa de divisórias |
| contr_ar | string | sim | — | Contrato de ar condicionado |
| ginastica | string | sim | — | Empresa de equipamentos de ginástica |
| valor_oi | decimal(15,2) | sim | — | Valor da Ordem de Investimento (R$) |
| valor_realizado | decimal(15,2) | sim | — | Valor realizado/executado (R$) |
| realizado_nf | decimal(15,2) | sim | — | Valor realizado com NF emitida (R$) |
| saldo | decimal(15,2) | sim | — | Saldo disponível (OI - realizado) |
| situacao | string | sim | — | Situação atual do pedido |
| responsavel_orc | bigint FK | sim | — | Usuário responsável pelo orçamento |
| gestor_obra | bigint FK | sim | — | Usuário gestor da obra |
| tamanho | string | sim | — | Tamanho/porte do pedido |
| numero | integer | sim | — | Número do pedido |
| pedidos | json | sim | — | Array de pedidos adicionais |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| construtora_id | construtoras.id | set null |
| responsavel_orc | users.id | — |
| gestor_obra | users.id | — |

---

## `controle_pedido_itens`

**Propósito**: Itens individuais de um pedido/contrato (lista de serviços/materiais com valores).
**Model**: `App\Models\ControlePedidoItem`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| controle_pedido_id | bigint FK | não | — | Pedido pai |
| codigo | string | não | — | Código do item |
| nome | string | não | — | Descrição do item |
| contratado | boolean | não | false | Item foi efetivamente contratado? |
| valor | decimal(15,2) | não | 0 | Valor do item (R$) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| controle_pedido_id | controle_pedidos.id | cascade |

---

## `gestao_obras`

**Propósito**: Gestão financeira consolidada de obras. Controla orçamento, realizado, comprometido e PDP (Previsão de Desembolso e Pagamento).
**Model**: `App\Models\GestaoObra`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| codigo | string UNIQUE | não | — | Código identificador da obra |
| nome | string | não | — | Nome da obra |
| orcamento_inicial | decimal(15,2) | não | — | Orçamento inicial aprovado (R$) |
| realizado | decimal(15,2) | não | 0 | Valor total realizado (R$) |
| comprometido | decimal(15,2) | não | 0 | Valor comprometido/contratado ainda não pago (R$) |
| pdp | decimal(15,2) | não | 0 | Previsão de Desembolso e Pagamento (R$) |
| construtora_id | bigint FK | sim | — | Construtora principal |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| construtora_id | construtoras.id | cascade |

### Índices

- UNIQUE: `codigo`

---

## `nota_fiscals`

**Propósito**: Notas fiscais emitidas contra as obras. Controle de status, datas de emissão, recebimento e envio.
**Model**: `App\Models\NotaFiscal` *(Resource desativado com prefixo `.`)*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| numero | string UNIQUE | não | — | Número da NF |
| fornecedor | string | não | — | Nome do fornecedor emitente |
| cnpj | string(20) | sim | — | CNPJ do fornecedor |
| valor | decimal(15,2) | não | — | Valor total da NF (R$) |
| data_emissao | date | sim | — | Data de emissão da NF |
| data_recebimento | date | sim | — | Data de recebimento físico/digital |
| data_envio | date | sim | — | Data de envio para processamento |
| status | enum | não | 'pendente' | Status: `pendente`, `paga`, `cancelada` |
| arquivo | string | sim | — | Caminho do arquivo da NF no storage |
| observacoes | text | sim | — | Observações |
| tipos_faturamento | json | sim | — | Tipos de faturamento (cache denormalizado) |
| obra_id | bigint FK | não | — | Obra à qual a NF pertence |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| obra_id | gestao_obras.id | cascade |

### Índices

- UNIQUE: `numero`

---

## `tipo_faturamentos`

**Propósito**: Tipos/categorias de faturamento para classificação de NFs (ex: Material, Mão de Obra, Equipamento).
**Model**: `App\Models\TipoFaturamento`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string UNIQUE | não | — | Nome do tipo de faturamento |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `nome`

---

## `nota_fiscal_tipo_faturamento`

**Propósito**: Pivot muitos-para-muitos entre NFs e tipos de faturamento.
**Model**: *gerenciado via `BelongsToMany`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| nota_fiscal_id | bigint FK | não | — | Nota fiscal |
| tipo_faturamento_id | bigint FK | não | — | Tipo de faturamento |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| nota_fiscal_id | nota_fiscals.id | cascade |
| tipo_faturamento_id | tipo_faturamentos.id | cascade |

### Índices

- PRIMARY: `(nota_fiscal_id, tipo_faturamento_id)`

---

## `faturamentos`

**Propósito**: Registros de faturamento direto ou indireto vinculados a uma NF. Detalha empresa faturante, NF, valores acumulados e datas do processo.
**Model**: `App\Models\Faturamento`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nota_fiscal_id | bigint FK | não | — | NF vinculada |
| tipo | enum | não | — | Tipo: `direto` ou `indireto` |
| empresa | string | sim | — | Empresa faturante |
| numero_nf | string | sim | — | Número da NF desta linha |
| cnpj_faturamento_smart | string | sim | — | CNPJ Smart Fit para faturamento |
| valor_acumulado_medido_nf | decimal(15,2) | sim | — | Valor acumulado medido na NF |
| emissao | date | sim | — | Data de emissão |
| recebimento | date | sim | — | Data de recebimento |
| envio | date | sim | — | Data de envio |
| status | string | sim | — | Status do faturamento |
| observacoes | text | sim | — | Observações |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| nota_fiscal_id | nota_fiscals.id | cascade |

---

## `ordem_investimentos`

**Propósito**: Ordens de Investimento (OI) aprovadas para cada projeto. Documento formal que autoriza o investimento financeiro.
**Model**: `App\Models\OrdemInvestimento`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto da OI |
| valor_total | decimal(15,2) | não | — | Valor total aprovado (R$) |
| area | decimal(10,2) | sim | — | Área da unidade (m²) |
| custo_m2 | decimal(15,2) | sim | — | Custo por m² (R$) |
| estrutura | json | sim | — | Estrutura de custos detalhada por disciplina |
| status_oi | string | não | 'em_aprovacao' | Status: `em_aprovacao`, `aprovada`, `rejeitada` |
| pdf_path | string | sim | — | Caminho do PDF da OI no storage |
| user_id | bigint FK | sim | — | Usuário que criou a OI |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| user_id | users.id | — |

---

## `lista_emails`

**Propósito**: Listas de e-mail para comunicações em massa (notificações, avisos de obras, etc.).
**Model**: `App\Models\ListaEmail`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome da lista |
| descricao | text | sim | — | Descrição do propósito da lista |
| emails | json | sim | — | Array de endereços de e-mail |
| ativo | boolean | não | true | Lista ativa? |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `controle_nota_fiscal_notas`

**Propósito**: Notas fiscais importadas pela construtora, vinculadas a itens de medição (ASA/aditivos). Registra dados de emissão, pagamento, auditoria de importação e decisão de aprovação/reprovação.
**Model**: `App\Models\ControleNotaFiscalNota`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| controle_nota_fiscal_item_id | bigint FK | sim | — | Item de medição principal (ASA/aditivo) |
| controle_nota_fiscal_auxiliar_id | bigint FK | sim | — | Dados auxiliares da medição |
| importado_por_id | bigint FK | sim | — | Usuário que importou a nota |
| decidido_por_id | bigint FK | sim | — | Usuário que aprovou ou reprovou a nota |
| tipo_medicao | string(20) | não | 'direto' | Tipo de medição: `direto` ou `mao_obra`/`material` |
| empresa | string | sim | — | Empresa faturante |
| cnpj_fornecedor | string | sim | — | CNPJ do fornecedor emitente |
| numero_nf | string | sim | — | Número da nota fiscal |
| cnpj_faturamento | string | sim | — | CNPJ de faturamento Smart Fit |
| instrucoes_pagamento | string | sim | — | Instruções de pagamento |
| boleto_path | string | sim | — | Caminho do boleto no storage (R2) |
| valor_acumulado_medido_nf | decimal(12,2) | não | 0 | Valor acumulado medido na NF (R$) |
| emissao | date | sim | — | Data de emissão da NF |
| recebimento | date | sim | — | Data de recebimento |
| envio | date | sim | — | Data de envio para pagamento |
| status | string | sim | — | Status: `pendente`, `em_analise`, `aprovado`, `reprovado` |
| decidido_em | timestamp | sim | — | Data/hora da decisão de aprovação ou reprovação |
| arquivo_path | string | sim | — | Caminho do arquivo da NF no storage (R2) |
| observacoes | text | sim | — | Observações gerais |
| sort_order | unsignedInt | não | 0 | Ordem de exibição |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| controle_nota_fiscal_item_id | controle_nota_fiscal_items.id | cascade |
| controle_nota_fiscal_auxiliar_id | controle_nota_fiscal_auxiliares.id | set null |
| importado_por_id | users.id | set null |
| decidido_por_id | users.id | set null |

### Índices

- INDEX: `tipo_medicao`, `numero_nf`, `status`, `sort_order`, `importado_por_id`, `decidido_por_id`
