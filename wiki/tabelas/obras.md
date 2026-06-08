# Tabelas: Obras

## `obras`

**Propósito**: Acompanhamento da execução física de cada unidade Smart Fit. Registra progresso por disciplina (civil, elétrica, hidráulica, etc.), datas, status, fotos e dados operacionais.
**Model**: `App\Models\Obras` (Observer: `ObrasObserver`)

### Identificação e Vinculação

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| projeto_id | bigint FK | não | — | Projeto ao qual esta obra pertence |
| codigo | string | sim | — | Código da obra |
| sigla | string | sim | — | Sigla da unidade |
| nova_sigla | string | sim | — | Sigla atualizada |
| unidade | string | sim | — | Nome da unidade |
| marca | string | sim | — | Marca/bandeira |
| constructin_project_id | unsignedInteger | sim | — | ID do projeto no sistema ConstructIn (integração externa) |

### Status e Progresso

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| status | string | sim | — | Status geral da obra |
| percentual_obra | string | sim | — | Percentual de conclusão geral (%) |
| percentual_obra_executado | decimal | sim | — | Percentual executado real (%) |
| desvio | decimal | sim | — | Desvio entre planejado e realizado (dias ou %) |
| dias_obra_inicio_pmo | integer | sim | — | Dias decorridos desde início para PMO |
| status_visita | string | sim | — | Status da visita técnica |
| status_proj_exec | string | sim | — | Status do projeto executivo |
| engenharia | string | sim | — | Status da engenharia |
| comercial | string | sim | — | Status comercial |
| relatorio_fotografico | string | sim | — | Status do relatório fotográfico |
| termo_de_posse | string | sim | — | Status do termo de posse |

### Disciplinas de Obra

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| civil | string | sim | — | Status/progresso da disciplina civil |
| hidraulica | string | sim | — | Status/progresso da hidráulica |
| eletrica | string | sim | — | Status/progresso da elétrica |
| incendio | string | sim | — | Status/progresso do sistema de incêndio |
| instalacao_ar_condicionado | string | sim | — | Status da instalação de ar condicionado |
| maquinas_ar_condicionado | string | sim | — | Status das máquinas de ar condicionado |
| homologados_em_atraso | string | sim | — | Disciplinas homologadas com atraso |
| arquitetura | string | sim | — | Status da arquitetura |

### Datas

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| status_data_posse | date | sim | — | Data de posse do imóvel |
| inicio | date | sim | — | Data de início da obra |
| inicio_real | date | sim | — | Data real de início (pode diferir do planejado) |
| fim | date | sim | — | Data de término previsto |
| inicio_imp | date | sim | — | Início da implantação |
| fim_imp | date | sim | — | Fim da implantação |
| inauguracao | date | sim | — | Data de inauguração da unidade |
| data_assinatura_contrato | date | sim | — | Data de assinatura do contrato |
| data_envio_relatorio_fotografico | date | sim | — | Data de envio do relatório fotográfico |
| data_atualizacao_comentario | date | sim | — | Data da última atualização de comentários |
| data_solicitacao_vt | date | sim | — | Data de solicitação da visita técnica |
| data_agendamento_vt | date | sim | — | Data de agendamento da visita técnica |
| fachada_data_instalacao | date | sim | — | Data de instalação da fachada |
| inicio_prev_pendencias | date | sim | — | Início previsto para resolução de pendências |
| termino_prev_pendencias | date | sim | — | Término previsto para resolução de pendências |
| data_check_list | date | sim | — | Data do checklist de manutenção |
| previsao_ligacao_energia | date | sim | — | Previsão de ligação de energia |

### Prazos

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| prazo_planejado | integer | sim | — | Prazo planejado da obra em dias |
| prazo_realizado | integer | sim | — | Prazo realizado em dias |
| imp_prazo_planej | integer | sim | — | Prazo planejado da implantação em dias |
| imp_prazo_realiz | integer | sim | — | Prazo realizado da implantação em dias |
| entrada_ponto | date | sim | — | Data de entrada no ponto (posse) |
| entrada_ponto_ate_inauguracao | integer | sim | — | Dias da entrada até a inauguração |
| assinatura_ate_inauguracao | integer | sim | — | Dias da assinatura até a inauguração |
| dias_para_inauguracao | string | sim | — | Contagem regressiva para inauguração |

### Localização

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| endereco | text | sim | — | Endereço completo |
| cidade | string | sim | — | Nome da cidade |
| uf | string | sim | — | UF do estado |
| empreendimento | string | sim | — | Nome do empreendimento |
| locacao | string | sim | — | Tipo de locação |
| tipo_imovel | string | sim | — | Tipo do imóvel |
| contato_corretor | text | sim | — | Dados do corretor |

### Utilidades / Infraestrutura

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| energia | text | sim | — | Status/observações sobre energia |
| agua | text | sim | — | Status/observações sobre água |
| gas | text | sim | — | Status/observações sobre gás |
| energia_observacoes | text | sim | — | Observações adicionais de energia |
| agua_observacoes | text | sim | — | Observações adicionais de água |
| gas_observacoes | text | sim | — | Observações adicionais de gás |
| camera_unidade | string | sim | — | Status das câmeras da unidade |
| gerador_contratual | string | sim | — | Status do gerador contratual |
| elevador | string | sim | — | Status do elevador |

### Fachada

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| fachada_status | string | sim | — | Status da fachada |
| fachada_observacao | string | sim | — | Observações sobre a fachada |

### Informações Comerciais e Período

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| mes | integer | sim | — | Mês de referência |
| ano | integer | sim | — | Ano de referência |
| pipe_land | string | sim | — | Estágio no pipeline |
| status_contrato | string | sim | — | Status do contrato |
| link | longText | sim | — | Links relevantes |

### Itens Críticos

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| itens_criticos | text | sim | — | Lista de itens críticos pendentes |
| descricao_itens_criticos | longText | sim | — | Descrição detalhada dos itens críticos |

### Equipamentos e Configuração

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| set_equipamentos | string | sim | — | Set de equipamentos da unidade |
| piso | string | sim | — | Tipo/status do piso |
| alteracao_spa_addons | string | sim | — | Alterações em spa e add-ons |
| gestor_pos_obra | string | sim | — | Nome do gestor pós-obra |

### Comunicações e Checklist

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| email_solicitacao_cl | string | sim | — | E-mail de solicitação do checklist |
| envio_qrcod | string | sim | — | Status de envio do QR Code |
| checklist_manutencao | string | sim | — | Status do checklist de manutenção |

### Cronogramas e Observações

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| cronograma_implantacao | string | sim | — | Link/referência do cronograma de implantação |
| cronograma_visi | string | sim | — | Link/referência do cronograma de visitas |
| ponto_atencao | longText | sim | — | Pontos de atenção |
| observacao | longText | sim | — | Observações gerais da obra |
| comentarios | longText | sim | — | Comentários da equipe |
| comentario | text | sim | — | Comentário adicional |
| comentarios_adicionais | longText | sim | — | Comentários adicionais sobre pendências |
| observacao_implantacao | string | sim | — | Observação específica da implantação |

### Fotos e Mídia

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| foto_perfil | string | sim | — | Foto de perfil da obra (path no storage) |
| foto_capa | string | sim | — | Foto de capa (path no storage) |
| fotos | json | sim | — | Array de fotos adicionais |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| projeto_id | projetos.id | cascade |

---

## `obra_construtora`

**Propósito**: Pivot muitos-para-muitos. Construtoras que executam cada obra.
**Model**: *gerenciado via `BelongsToMany` em `Obras`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| obra_id | bigint FK | não | — | Obra |
| construtora_id | bigint FK | não | — | Construtora executora |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `(obra_id, construtora_id)`

---

## `obra_user`

**Propósito**: Pivot muitos-para-muitos. Usuários (líderes, gestores) vinculados a cada obra.
**Model**: *gerenciado via `BelongsToMany` em `Obras` e `User`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| obra_id | bigint FK | não | — | Obra |
| user_id | bigint FK | não | — | Usuário vinculado |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

---

## `obra_documentos`

**Propósito**: Documentos necessários para a obra (habite-se, AVCB, etc.) com controle de status de entrega.
**Model**: `App\Models\ObraDocumento`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| obra_id | bigint FK | não | — | Obra à qual pertence |
| nome | string | não | — | Nome/tipo do documento |
| status | string | não | 'pendente' | Status: `pendente`, `enviado`, `nao_aplicavel` |
| usuario_id | bigint FK | sim | — | Usuário que fez a última atualização |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| obra_id | obras.id | cascade |
| usuario_id | users.id | set null |

---

## `obra_recebimentos`

**Propósito**: Itens a serem recebidos na entrega da obra (equipamentos, materiais) com controle de recebimento.
**Model**: `App\Models\ObraRecebimento`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| obra_id | bigint FK | não | — | Obra |
| nome | string | não | — | Nome/descrição do item a receber |
| status | string | não | 'pendente' | Status: `pendente`, `recebido`, `nao_aplicavel` |
| usuario_id | bigint FK | sim | — | Usuário que registrou o recebimento |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| obra_id | obras.id | cascade |
| usuario_id | users.id | set null |

---

## `atualizacoes_obra`

**Propósito**: Feed de atualizações e comentários de uma obra. Suporta respostas (parent_id), menções a usuários e registros automáticos de mudanças de campo. É o "histórico vivo" da obra.
**Model**: `App\Models\AtualizacaoObra` (Observer: `ObrasObserver`)

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| obra_id | bigint FK | não | — | Obra a que pertence |
| usuario_id | bigint FK | não | — | Usuário autor da atualização |
| parent_id | bigint FK | sim | — | ID da atualização pai (para respostas/threads) |
| categoria | string | não | — | Categoria da atualização (Enum: `CategoriaAtualizacaoObra`) |
| titulo | string | não | — | Título/resumo da atualização |
| conteudo | text | sim | — | Corpo detalhado da atualização |
| mencoes | json | sim | — | Array de user_ids mencionados |
| campo_alterado | string | sim | — | Campo que foi alterado (para updates automáticos) |
| valor_anterior | text | sim | — | Valor anterior do campo |
| valor_novo | text | sim | — | Novo valor do campo |
| fixado | boolean | não | false | Atualização fixada no topo do feed |
| automatico | boolean | não | true | Gerada automaticamente pelo sistema (vs. manual) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| obra_id | obras.id | cascade |
| usuario_id | users.id | cascade |
| parent_id | atualizacoes_obra.id | cascade |

### Notas

- `automatico = true`: gerada pelo `ObrasObserver` ao detectar mudanças em campos relevantes
- `automatico = false`: postada manualmente por um usuário
- `fixado = true`: aparece sempre no topo do feed da obra
- `mencoes`: array de IDs de usuários — pode ser usado para notificações

---

## `midias`

**Propósito**: Tabela polimórfica para armazenar arquivos (imagens, vídeos, documentos) vinculados a qualquer model da aplicação. Substitui o campo JSON `obras.fotos` para imagens de obras.
**Model**: `App\Models\Midia`

### Colunas

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| mediavel_type | string | não | — | Namespace do model dono (polimórfico) |
| mediavel_id | bigint | não | — | ID do model dono (polimórfico) |
| path | string | não | — | Caminho do arquivo no storage |
| disk | string(20) | não | `r2` | Disco de storage (ex: `r2`, `public`) |
| categoria | string | não | `obra` | Categoria/agrupamento da mídia (ex: `obra`, `perfil`, nome de categoria customizada) |
| tipo | string(30) | não | `imagem` | Tipo do arquivo: `imagem`, `video`, `documento`, `arquivo` |
| nome_original | string | sim | — | Nome original do arquivo no upload |
| ordem | unsignedInteger | não | 0 | Posição de exibição |
| metadata | json | sim | — | Dados extras livres (dimensões, duração, etc.) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

| Índice | Colunas |
|--------|---------|
| Primary | `id` |
| Composite | `mediavel_type`, `mediavel_id`, `categoria` |

### Notas

- Chave polimórfica: `mediavel_type` + `mediavel_id` (padrão Laravel `morphs()`)
- Dados migrados de `obras.fotos` (JSON) para `midias` durante a migration `2026_04_16_200000_create_midias_table`
- Scopes disponíveis no model: `scopeImagens()`, `scopeCategoria($categoria)`
- Atributo computado `url` retorna a URL pública via `Storage::disk($this->disk)->url($this->path)`
