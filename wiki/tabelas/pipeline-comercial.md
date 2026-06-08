# Tabelas: Pipeline Comercial

## `prospeccoes`

**Propósito**: Dados de prospecção de um ponto. Primeiro estágio formal de análise antes de virar um projeto. Captura dados técnicos, financeiros e de localização do imóvel prospectado.
**Model**: `App\Models\Prospeccao`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto vinculado |
| etapa_id | bigint FK | sim | — | Etapa da prospecção |
| nome | string | sim | — | Nome do ponto |
| sigla | string | sim | — | Sigla do ponto |
| status | string | sim | — | Status da prospecção |
| tipo_entrada | string | sim | — | Como o ponto entrou no radar |
| nome_contato | string | sim | — | Nome do contato (proprietário/corretor) |
| contato | string | sim | — | Telefone/e-mail do contato |
| pin_google | longText | sim | — | URL do pin no Google Maps |
| tipo_de_loja | string | sim | — | Tipo da loja |
| n_vagas_livres | integer | sim | — | Número de vagas de estacionamento |
| area_academia | decimal(10,2) | sim | — | Área da academia (m²) |
| area_terreno | decimal(10,2) | sim | — | Área do terreno (m²) |
| n_pisos | integer | sim | — | Número de pisos |
| pe_direito | decimal(10,2) | sim | — | Pé-direito (metros) |
| modelo_entrega_pp | string | sim | — | Modelo de entrega do proprietário |
| aluguel_cto | decimal(15,2) | sim | — | Valor do aluguel (R$) |
| luvas | decimal(15,2) | sim | — | Luvas pagas ao proprietário (R$) |
| iptu | decimal(15,2) | sim | — | IPTU anual (R$) |
| condominio | decimal(15,2) | sim | — | Condomínio mensal (R$) |
| configuracao_academia | string | sim | — | Configuração/layout |
| dados_engenharia | text | sim | — | Dados técnicos de engenharia |
| prazo_inicio | date | sim | — | Prazo estimado de início |
| projeto_croqui | string | sim | — | Se há croqui do projeto |
| potencial_alunos | integer | sim | — | Estimativa de alunos |
| link_estudo_projecao_alunos | string | sim | — | Link para estudo de projeção |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| etapa_id | etapas.id | cascade |

---

## `acompanhamentos`

**Propósito**: Follow-up de oportunidades comerciais em andamento. Tabela de rastreamento com 70+ campos cobrindo status, datas, equipamentos e dados de localização.
**Model**: `App\Models\Acompanhamento`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| inicio_projeto | date | sim | — | Data de início do acompanhamento |
| (70+ campos) | variados | sim | — | Status por disciplina, datas planejadas e realizadas, dados da unidade, métricas financeiras, observações |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Notas

- Tabela de dados históricos importados de planilhas Excel
- Usada para dashboards e relatórios comerciais

---

## `reuniaos`

**Propósito**: Reuniões (comerciais, técnicas, de comitê) vinculadas a projetos.
**Model**: `App\Models\Reuniao`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| titulo | string | não | — | Título/assunto da reunião |
| data | date | não | — | Data da reunião |
| hora | time | não | — | Hora da reunião |
| tipo | enum | não | — | Modalidade: `online` ou `presencial` |
| convidados | string | sim | — | Lista de convidados |
| link_video | string | sim | — | Link da videoconferência (Meet, Teams, Zoom) |
| local | string | sim | — | Local físico (para reuniões presenciais) |
| descricao | text | sim | — | Pauta e descrição da reunião |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `reuniao_projeto`

**Propósito**: Pivot muitos-para-muitos. Relaciona reuniões com projetos, com dados adicionais de status e corretor.
**Model**: *gerenciado via `BelongsToMany` com `withPivot`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| reuniao_id | bigint FK | não | — | Reunião |
| projeto_id | bigint FK | não | — | Projeto discutido na reunião |
| status | string | sim | — | Status do projeto nesta reunião |
| corretor | string | sim | — | Corretor envolvido no negócio |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| reuniao_id | reuniaos.id | cascade |
| projeto_id | projetos.id | cascade |

---

## `reuniao_comites`

**Propósito**: Reuniões de comitê para aprovação de viabilidade de projetos. Registra a pré-condição documental para apresentação.
**Model**: `App\Models\ReuniaoComite`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto em pauta |
| estado_id | bigint FK | não | — | Estado/regional do comitê |
| unidade | string | sim | — | Nome da unidade |
| status_reuniao_comite | string | sim | — | Status da reunião de comitê |
| relatorio_visita | boolean | não | false | Relatório de visita técnica entregue? |
| estudo_massa | boolean | não | false | Estudo de massa entregue? |
| levantamento_cadastral | boolean | não | false | Levantamento cadastral entregue? |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| estado_id | estados.id | cascade |

---

## `aprovacao_reuniao_comite`

**Propósito**: Registro de votos de aprovação/reprovação de cada membro do comitê para um projeto.
**Model**: `App\Models\AprovacaoReuniaoComite`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto em votação |
| user_id | bigint FK | não | — | Membro do comitê votante |
| role | string | não | — | Papel/cargo do votante |
| aprovacao | enum | não | — | Resultado: `aprovado`, `aprovado_com_ressalva`, `reprovado` |
| approved_at | timestamp | sim | — | Data e hora da aprovação |
| comentarios_gerais | text | sim | — | Comentários gerais do votante |
| observacoes_ressalva | text | sim | — | Observações quando aprovado com ressalva |
| anexos_ressalva | json | sim | — | Anexos das ressalvas |
| pmo_cronograma | boolean | não | false | Documento PMO Cronograma verificado |
| pmo_termo_abertura | boolean | não | false | Termo de abertura verificado |
| comercial_proposta | boolean | não | false | Proposta comercial verificada |
| comercial_contrato | boolean | não | false | Contrato comercial verificado |
| planejamento_plano | boolean | não | false | Plano de planejamento verificado |
| planejamento_estudo | boolean | não | false | Estudo de viabilidade verificado |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| user_id | users.id | cascade |

### Índices

- INDEX: `approved_at`

---

## `aprovacao_viabilidades`

**Propósito**: Aprovação formal de viabilidade do ponto antes de avançar para a fase de projeto. Votação individual de membros do time.
**Model**: `App\Models\AprovacaoViabilidade`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto em análise |
| user_id | bigint FK | não | — | Usuário avaliador |
| role | string | sim | — | Papel/cargo do avaliador |
| aprovacao | enum | não | 'pendente' | Status: `pendente`, `aprovado`, `reprovado` |
| comentarios_gerais | longText | sim | — | Comentários da avaliação |
| consulta_previa | tinyInt | não | 0 | Documento de consulta prévia verificado |
| estudoviabilidade | tinyInt | não | 0 | Estudo de viabilidade verificado |
| visita_tecnica | tinyInt | não | 0 | Relatório de visita técnica verificado |
| projetos_adicionais | tinyInt | não | 0 | Projetos adicionais verificados |
| anexo_consulta_previa | json | sim | — | Anexo da consulta prévia |
| anexo_estudoviabilidade | json | sim | — | Anexo do estudo de viabilidade |
| anexo_visita_tecnica | json | sim | — | Anexo do relatório de visita |
| anexo_projetos_adicionais | json | sim | — | Anexo de projetos adicionais |
| observacoes_ressalva | longText | sim | — | Observações de ressalva |
| anexos_ressalva | json | sim | — | Anexos das ressalvas |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| user_id | users.id | cascade |
