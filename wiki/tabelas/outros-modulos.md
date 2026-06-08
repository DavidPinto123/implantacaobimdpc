# Tabelas: Outros Módulos

## `matterports`

**Propósito**: Catálogo de tours 3D Matterport de unidades Smart Fit. Cada registro armazena os links de scan e dados de localização da unidade.
**Model**: `App\Models\Matterport`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| codigo | string | não | — | Código da unidade |
| nome | string | sim | — | Nome da unidade |
| sigla | string | sim | — | Sigla original |
| nova_sigla | string | sim | — | Sigla atualizada |
| pais | string | sim | — | Nome do país (desnormalizado) |
| estado | string | sim | — | Nome do estado (desnormalizado) |
| cidade | string | sim | — | Nome da cidade (desnormalizado) |
| endereco | string | sim | — | Endereço completo |
| pais_id | bigint FK | sim | — | FK para `pais` |
| estado_id | bigint FK | sim | — | FK para `estados` |
| cidade_id | bigint FK | sim | — | FK para `cidades` |
| link_matterport1 | longText | sim | — | Link do scan Matterport principal |
| link_matterport2 | longText | sim | — | Link do scan Matterport secundário |
| link_matterport3 | longText | sim | — | Link do scan Matterport terciário |
| link_drone | longText | sim | — | Link do vídeo de drone |
| link_google_maps | longText | sim | — | Link do ponto no Google Maps |
| imagem | string | sim | — | Imagem de capa (path no storage) |
| documentoPDF | string | sim | — | PDF relacionado (path no storage) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Notas

- QR Code gerado via rota `GET /projetos/{projeto}/matterport-qrcode`
- Os campos `pais`, `estado`, `cidade` são cópias desnormalizadas para performance

---

## `dados`

**Propósito**: Dados extraídos de sistemas BIM/planilhas sobre configuração de equipamentos e itens das unidades (quantitativos de projetos).
**Model**: `App\Models\Dados`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nova_sigla | string | sim | — | Sigla da unidade |
| unidade | string | sim | — | Nome da unidade |
| marca | string | sim | — | Marca/bandeira |
| bloco_tipo | string | sim | — | Bloco ou tipo do item |
| categoria | string | sim | — | Categoria do item |
| descricao | string | sim | — | Descrição do item |
| quantidade | string | sim | — | Quantidade |
| un | string | sim | — | Unidade de medida |
| pavimento | string | sim | — | Pavimento onde está o item |
| status | string | sim | — | Status do item |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `ambientes`

**Propósito**: Dados de ambientes/salas das unidades extraídos de sistemas de planejamento. Cada linha representa um ambiente com sua área e pavimento.
**Model**: `App\Models\Ambientes`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nova_sigla | string | não | — | Sigla da unidade |
| unidade | string | não | — | Nome da unidade |
| marca | string | não | — | Marca/bandeira |
| departamento | string | sim | — | Departamento do ambiente |
| ambiente | string | sim | — | Nome do ambiente (ex: "Área de Treino", "Vestiário") |
| area | string | sim | — | Área em m² (armazenado como string — importado de planilha) |
| pavimento | string | sim | — | Pavimento |
| data_extracao | string | sim | — | Data de extração dos dados |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `planejamento_estrategicos`

**Propósito**: Tabela de acompanhamento estratégico consolidado. Importada de planilhas de gestão e usada em dashboards executivos. Contém 160+ campos rastreando todas as fases do projeto com datas planejadas, realizadas, prazos e status.
**Model**: `App\Models\PlanejamentoEstrategico`

| Grupos de colunas | Descrição |
|-------------------|-----------|
| Identificação | `sigla`, `nova_sigla`, `crono_revisado`, `unidade`, `marca`, `escopo`, `pipe_land`, `status`, `comercial`, `arquitetura`, `engenharia` |
| Comitê / Contrato | `status_comite`, `status_contrato_do_comercial`, `data_assinatura_contrato_do_comercial` |
| Fase Cadastral | Datas planejadas/reais/prazo/status do cadastramento |
| Fase Visita Técnica | Datas planejadas/reais/prazo/status da visita técnica |
| Fase Briefing/Layout | Datas planejadas/reais/prazo/status do briefing |
| Fase OI (Ordem de Investimento) | Datas planejadas/reais/prazo/status + data aprovação |
| Fase PE (Planejamento Estratégico) | Datas de reunião + planejadas/reais/prazo |
| Fase Orçamento | Kickoff + planejadas/reais/prazo |
| Legal e Posse | Datas, status, documentação |
| Execução | Datas e prazos planejados vs realizados |
| Dados do Imóvel | Localização, área, equipamentos, métricas financeiras |
| Pré-vendas | Dados de marketing e vendas |
| Contatos | Diretoria e corretor |

### Notas

- Tabela ampla (wide table) para dashboards executivos — não é normalizada
- Atualizada via importação de planilhas Excel

---

## `importacao_templates`

**Propósito**: Templates salvos de mapeamento de colunas para importação de planilhas. Evita reconfigurar o mapeamento a cada importação.
**Model**: `App\Models\ImportacaoTemplate`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome do template |
| modulo | string | não | 'obras' | Módulo de destino da importação |
| mapeamento | json | não | — | Mapa de colunas: `{coluna_planilha: campo_banco}` |
| user_id | bigint FK | não | — | Usuário que criou o template |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| user_id | users.id | cascade |

---

## `importacao_logs`

**Propósito**: Registro de execuções de importação (sucesso, erros, linhas processadas). Auditoria das importações via planilha.
**Model**: `App\Models\ImportacaoLog`

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| id | bigint PK | não | Identificador |
| (campos de log) | variados | sim | Módulo, arquivo, usuário, linhas importadas, erros, status |
| created_at | timestamp | sim | Data da importação |
| updated_at | timestamp | sim | Última atualização |
