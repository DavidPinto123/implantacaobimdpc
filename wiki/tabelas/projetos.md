# Tabelas: Projetos

## `projetos`

**Propósito**: Tabela central do sistema. Representa uma unidade Smart Fit em algum estágio do ciclo de expansão (prospecção → inauguração). Contém 200+ colunas cobrindo dados do imóvel, financeiros, datas de planejamento vs realizado, documentos e responsáveis.
**Model**: `App\Models\Projeto` (SoftDeletes)

### Identificação

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome do projeto/unidade |
| sigla | string | sim | — | Sigla original da unidade |
| nova_sigla | string | sim | — | Sigla atualizada |
| numero | string(20) | sim | — | Número do projeto |
| codigo | string UNIQUE | sim | — | Código único gerado automaticamente |
| numero_loja | string | sim | — | Número da loja no sistema interno |
| marca | string | sim | — | Marca/bandeira (Smart Fit, Bio Ritmo, etc.) |
| complemento | longText | sim | — | Observações complementares de identificação |

### Localização

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| rua | string | sim | — | Logradouro |
| bairro | string | sim | — | Bairro |
| cep | string | sim | — | CEP |
| endereco | longText | sim | — | Endereço completo formatado |
| cidade_id | bigint FK | sim | — | Cidade |
| estado_id | bigint FK | sim | — | Estado |
| pais_id | bigint FK | sim | — | País |
| pin_google | longText | sim | — | URL do pin no Google Maps |
| imagem_ponto | json | sim | — | Imagens do ponto/local |

### Status e Workflow

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| user_id | bigint FK | não | — | Responsável principal pelo projeto |
| etapa_id | bigint FK | sim | — | Etapa atual do workflow (deprecated, usar `etapas` BelongsToMany) |
| status | string | sim | — | Status geral do projeto |
| pipeline | string | sim | — | Estágio no pipeline comercial |
| tipo | string | sim | — | Tipo do projeto (expansão, retrofit, etc.) |
| tipo_entrada | string | sim | — | Como o ponto entrou no radar |
| status_comite | string | sim | — | Status da aprovação em comitê |
| status_imovel | string | sim | — | Status do imóvel (disponível, em negociação, etc.) |
| status_contrato | string | sim | — | Status do contrato |
| evtl_status | string | sim | — | Status do EVTL (Estudo de Viabilidade Técnica e Legal) |
| evtl_recebido_em | date | sim | — | Data de recebimento do EVTL |
| crono_revisado | string | sim | — | Indicador se cronograma foi revisado |

### Dados do Imóvel

| Coluna             | Tipo          | Nullable | Default | Descrição                                           |
| ------------------ | ------------- | -------- | ------- | --------------------------------------------------- |
| tipo_de_loja       | string        | sim      | —       | Tipo da loja (piso térreo, shopping, etc.)          |
| tipo_imovel        | string        | sim      | —       | Tipo do imóvel (sala, galpão, laje, etc.)           |
| empreendimento     | string        | sim      | —       | Nome do empreendimento/shopping                     |
| locacao            | enum          | sim      | —       | Tipo de locação: `Mono usuário` ou `Multiusuário`   |
| area_academia      | decimal(10,2) | sim      | —       | Área da academia em m²                              |
| area_terreno       | decimal(10,2) | sim      | —       | Área total do terreno em m²                         |
| area_locada        | decimal(10,2) | sim      | —       | Área efetivamente locada em m²                      |
| n_pisos            | integer       | sim      | —       | Número de pisos                                     |
| pe_direito         | decimal(10,2) | sim      | —       | Pé-direito em metros                                |
| n_vagas_livres     | integer       | sim      | —       | Número de vagas de estacionamento livres            |
| modelo_entrega_p   | string        | sim      | —       | Modelo de entrega do imóvel pelo proprietário       |
| imovel_pronto      | boolean       | sim      | false   | Imóvel já está pronto para uso                      |
| relocation         | boolean       | sim      | false   | Projeto é de relocalização de unidade existente     |
| data_entrega_shell | date          | sim      | —       | Data prevista de entrega do shell pelo proprietário |
| observacoes_ponto  | text          | sim      | —       | Observações sobre o ponto                           |

### Dados Financeiros do Imóvel

| Coluna                         | Tipo          | Nullable | Default | Descrição                                   |
| ------------------------------ | ------------- | -------- | ------- | ------------------------------------------- |
| aluguel_cto                    | decimal(15,2) | sim      | —       | Valor do aluguel contratual (R$)            |
| luvas                          | decimal(15,2) | sim      | —       | Valor das luvas pagas ao proprietário (R$)  |
| iptu                           | decimal(15,2) | sim      | —       | Valor anual do IPTU (R$)                    |
| condominio                     | decimal(15,2) | sim      | —       | Valor mensal do condomínio (R$)             |
| carencia                       | integer       | sim      | —       | Período de carência em meses                |
| multa_contrato                 | decimal(15,2) | sim      | —       | Multa contratual (R$)                       |
| metro_contrato                 | decimal       | sim      | —       | Metragem contratada                         |
| metro_layout_util              | decimal       | sim      | —       | Metragem útil do layout                     |
| obs_aluguel                    | text          | sim      | —       | Observações sobre o aluguel                 |
| cash_on_cash                   | decimal(5,2)  | sim      | —       | Indicador de retorno Cash on Cash (%)       |
| capex_aprovado_diretoria_valor | decimal       | sim      | —       | Valor do CAPEX aprovado pela diretoria (R$) |
| capex_aprovado_diretoria       | boolean       | sim      | false   | CAPEX aprovado pela diretoria?              |
| coc_aprovado                   | boolean       | sim      | false   | CoC aprovado?                               |

### Datas do Ciclo de Vida

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| data_ass_contrato | date | sim | — | Data de assinatura do contrato |
| prazo_inicio | date | sim | — | Prazo planejado de início das obras |
| entrega_projeto | date | sim | — | Data de entrega do projeto de arquitetura |
| inicio_obra | date | sim | — | Data de início das obras |
| entrega_obra | date | sim | — | Data de entrega das obras |
| inauguracao | date | sim | — | Data de inauguração da unidade |
| ano_inauguracao | integer | sim | — | Ano de inauguração (derivado de `inauguracao`) |
| data_posse | date | sim | — | Data de posse do imóvel |
| mes_posse | string | sim | — | Mês de posse (formato texto) |
| posse_data_posse | date | sim | — | Data real de posse (campo alternativo) |

### Fases do PMO — Cadastramento (cad_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| cad_plan_inicio | date | sim | Início planejado do cadastramento |
| cad_plan_fim | date | sim | Fim planejado do cadastramento |
| cad_plan_dias | integer | sim | Dias planejados para cadastramento |
| cad_rea_inicio | date | sim | Início realizado do cadastramento |
| cad_rea_fim | date | sim | Fim realizado do cadastramento |
| cad_prazo | date | sim | Prazo limite do cadastramento |
| cad_status | string | sim | Status do cadastramento |

### Fases do PMO — Visita Técnica (vis_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| vis_plan_inicio | date | sim | Início planejado da visita técnica |
| vis_plan_fim | date | sim | Fim planejado |
| vis_plan_dias | integer | sim | Dias planejados |
| vis_rea_inicio | date | sim | Início realizado |
| vis_rea_fim | date | sim | Fim realizado |
| vis_prazo | date | sim | Prazo limite |
| vis_status | string | sim | Status |

### Fases do PMO — Briefing/Layout (brief_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| brief_plan | date | sim | Data planejada do briefing |
| brief_plan_lay_inicio | date | sim | Início planejado do layout |
| brief_plan_lay_fim | date | sim | Fim planejado do layout |
| brief_plan_dias | integer | sim | Dias planejados |
| brief_real | date | sim | Data real do briefing |
| brief_real_lay_inicio | date | sim | Início real do layout |
| brief_real_lay_fim | date | sim | Fim real do layout |
| brief_prazo | integer | sim | Prazo em dias |
| brief_status | string | sim | Status |

### Fases do PMO — Ordem de Investimento (ordem_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| ordem_planej_ini | date | sim | Início planejado |
| ordem_planej_fim | date | sim | Fim planejado |
| ordem_planejado | date | sim | Data planejada da OI |
| ordem_realizado | date | sim | Data real da OI |
| ordem_realizado_fim | date | sim | Fim real |
| ordem_prazo | date | sim | Prazo limite |
| ordem_status | string | sim | Status |
| ordem_data_aprov | date | sim | Data de aprovação da OI |
| ordem_status_aprov | string | sim | Status da aprovação |

### Fases do PMO — Projeto (proj_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| proj_planej_reuniao_start | date | sim | Data planejada da reunião de kickoff do projeto |
| proj_real_reuniao_start | date | sim | Data real da reunião de kickoff |
| proj_plan_ini | date | sim | Início planejado |
| proj_plan_fim | date | sim | Fim planejado |
| proj_plan | integer | sim | Dias planejados |
| proj_real_ini | date | sim | Início real |
| proj_real_fim | date | sim | Fim real |
| proj_prazo | date | sim | Prazo limite |
| proj_status | string | sim | Status |

### Fases do PMO — Orçamento (orca_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| orca_reuniao_kickoff | date | sim | Data do kickoff de orçamento |
| orca_planejado_ini | date | sim | Início planejado |
| orca_planejado_fim | date | sim | Fim planejado |
| orca_planejado | integer | sim | Dias planejados |
| orca_real_ini | date | sim | Início real |
| orca_real_fim | date | sim | Fim real |
| orca_prazo | date | sim | Prazo limite |
| orca_status | string | sim | Status |

### Fases do PMO — Legal (legal_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| legal_status_consulta_prev | string | sim | Status da consulta prévia |
| legal_doc_posse | string | sim | Status da documentação de posse |
| legal_plan_ini | date | sim | Início planejado |
| legal_plan_fim | date | sim | Fim planejado |
| legal_prazo_legal | date | sim | Prazo legal |
| legal_realizado_ini | date | sim | Início realizado |
| legal_realizado_fim | date | sim | Fim realizado |
| legal_prazo | date | sim | Prazo limite |
| legal_status | string | sim | Status |

### Fases do PMO — Execução/Implantação (exec_*, imp_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| exec_prazo_plan | date | sim | Prazo planejado de execução |
| exec_prazo_real | date | sim | Prazo real de execução |
| imp_inicio | date | sim | Início da implantação |
| imp_fim | date | sim | Fim da implantação |
| imp_prazo_planejado | integer | sim | Prazo planejado em dias |
| imp_prazo_realizado | integer | sim | Prazo realizado em dias |
| imp_mes | integer | sim | Mês da implantação |
| imp_ano | integer | sim | Ano da implantação |

### Posse (posse_*)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| posse_engenharia | string | sim | Status engenharia na posse |
| posse_legalizacao | string | sim | Status legalização na posse |
| posse_status | string | sim | Status geral da posse |
| posse_comentarios | text | sim | Comentários sobre a posse |

### Responsáveis

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| resp_com | bigint FK | sim | Responsável Comercial (→ users) |
| resp_arq | bigint FK | sim | Responsável Arquitetura (→ users) |
| resp_eng | bigint FK | sim | Responsável Engenharia (→ users) |
| resp_pmo | bigint FK | sim | Responsável PMO (→ users) |

### Dados Técnicos e Configuração

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| configuracao_academia | string | sim | Configuração/layout da academia |
| dados_engenharia | text | sim | Dados técnicos de engenharia |
| pontos_atencao | longText | sim | Pontos de atenção relevantes |
| projeto_croqui | boolean | sim | false | Projeto tem croqui |
| escopo | string | sim | — | Escopo do projeto |
| pavimento | string | sim | — | Pavimento(s) ocupado(s) |
| set_equipamentos | string | sim | — | Set de equipamentos previsto |
| tier | string | sim | — | Tier da unidade |
| renda | string | sim | — | Renda prevista |
| vendas_mkt | string | sim | — | Previsão de vendas/marketing |
| vendas_mkt_realizado | string | sim | — | Realizado de vendas/marketing |
| diretoria | string | sim | — | Diretoria responsável |
| contato_corretor | text | sim | — | Dados de contato do corretor |
| reuniao_ita | string | sim | — | Data/info da reunião ITA |

### Projeção de Alunos

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| potencial_alunos | integer | sim | Estimativa de alunos potenciais |
| link_estudo_projecao_alunos | longText | sim | Link para estudo de projeção |

### Risco

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| risco_obra | tinyInt | sim | Nível de risco da obra (0=baixo, …) |
| risco_obra_comentario | longText | sim | Justificativa do risco |

### Links externos

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| link_matterport | string | sim | URL do tour Matterport |
| link_docs | longText | sim | Link Google Drive ou similar |
| link_construct_in | longText | sim | Link da obra no ConstructIn |
| link_estudo_projecao_alunos | longText | sim | Link do estudo de projeção de alunos |

### Documentos/Anexos (JSON)

| Coluna | Tipo | Nullable | Descrição |
|--------|------|----------|-----------|
| anexos | json | sim | Anexos gerais |
| imagem_ponto | json | sim | Fotos do ponto |
| oi_pdf | json | sim | PDF da Ordem de Investimento |
| anexo_evtl | json | sim | EVTL |
| anexo_proposta_comercial | json | sim | Proposta comercial |
| anexo_proposta_comercial_comentario | json | sim | Comentários da proposta |
| anexo_contrato_assinado | json | sim | Contrato assinado |
| anexo_contrato_assinado_comentario | json | sim | Comentários do contrato |
| anexo_pmo_cronograma | json | sim | Cronograma PMO |
| comentario_pmo_cronograma | json | sim | Comentários do cronograma |
| anexo_pmo_termo_abertura | json | sim | Termo de abertura do projeto |
| comentario_pmo_termo_abertura | json | sim | Comentários do termo |
| anexo_planejamento_plano | json | sim | Plano de planejamento estratégico |
| planejamento_plano_comentario | json | sim | Comentários do plano |
| anexo_planejamento_estudo | json | sim | Estudo de viabilidade |
| planejamento_estudo_comentario | json | sim | Comentários do estudo |
| anexo_consulta_previa | json | sim | Consulta prévia de legalização |
| anexo_consulta_previa_comentario | json | sim | Comentários |
| anexo_estudoviabilidade | json | sim | Estudo de viabilidade técnica |
| anexo_estudoviabilidade_comentario | json | sim | Comentários |
| anexo_visita_tecnica | json | sim | Relatório de visita técnica |
| anexo_visita_tecnica_comentario | json | sim | Comentários |
| anexo_projetos_adicionais | json | sim | Projetos adicionais |
| anexo_projetos_adicionais_comentario | json | sim | Comentários |
| anexo_matricula_iptu | json | sim | Matrícula e IPTU do imóvel |
| anexo_habite_se | json | sim | Habite-se |
| anexo_avcb | json | sim | AVCB (Auto de Vistoria do Corpo de Bombeiros) |
| anexo_projeto | json | sim | Projeto de arquitetura/engenharia |
| anexo_convencao_condominio | json | sim | Convenção de condomínio |
| anexo_regime_interno | json | sim | Regime interno do condomínio |
| anexo_normas_gerais | json | sim | Normas gerais do empreendimento |
| anexo_outros_documentos | json | sim | Outros documentos |
| dir_status_contrato | string | sim | — | Status do contrato pela diretoria |

### Outros

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| deleted_at | timestamp | sim | — | SoftDelete |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras (principais)

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| user_id | users.id | restrict |
| etapa_id | etapas.id | set null |
| cidade_id | cidades.id | set null |
| estado_id | estados.id | set null |
| pais_id | pais.id | set null |
| resp_com | users.id | set null |
| resp_arq | users.id | set null |
| resp_eng | users.id | set null |
| resp_pmo | users.id | set null |

---

## `etapas`

**Propósito**: Etapas/fases do workflow de expansão. Define as macrofases que um projeto pode estar.
**Model**: `App\Models\Etapa`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| nome | string | não | — | Nome da etapa (ex: "Prospecção", "Assinatura", "Obras", "Inaugurada") |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `etapa_projeto`

**Propósito**: Pivot muitos-para-muitos entre projetos e etapas. Representa o histórico de etapas de um projeto.
**Model**: *gerenciado via `BelongsToMany`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto |
| etapa_id | bigint FK | não | — | Etapa |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| etapa_id | etapas.id | cascade |

---

## `projeto_user`

**Propósito**: Pivot muitos-para-muitos. Usuários vinculados a um projeto (equipe do projeto).
**Model**: *gerenciado via `BelongsToMany`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto |
| user_id | bigint FK | não | — | Usuário membro da equipe |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| user_id | users.id | cascade |

---

## `projeto_setor`

**Propósito**: Pivot muitos-para-muitos. Setores responsáveis por um projeto.
**Model**: *gerenciado via `BelongsToMany`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto |
| setor_id | bigint FK | não | — | Setor responsável |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |
| setor_id | setores.id | cascade |

---

## `historico_projetos`

**Propósito**: Audit trail de mudanças de status e etapa nos projetos. Registra quem mudou o quê e quando.
**Model**: `App\Models\HistoricoProjeto`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto que foi alterado |
| usuario_id | bigint FK | não | — | Usuário que fez a alteração |
| setor | string | não | — | Setor do usuário no momento da alteração |
| status | string | não | — | Status no momento do registro |
| etapa | string | não | — | Etapa no momento do registro |
| status_antigo | string | sim | — | Status anterior à mudança |
| status_novo | string | sim | — | Novo status após a mudança |
| acao | string | sim | — | Ação realizada |
| fase_antiga | string | sim | — | Fase/etapa anterior |
| fase_nova | string | sim | — | Nova fase/etapa |
| fase | string | sim | — | Fase atual (snapshot) |
| created_at | timestamp | sim | — | Data do evento |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | restrict |
| usuario_id | users.id | restrict |
