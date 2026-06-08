# Tabelas: Pós Obra

## `po_disciplinas_config`

**Propósito**: Disciplinas técnicas para categorização de pendências pós-obra (ex: Elétrica, Hidráulica, Civil, HVAC). Define quais disciplinas estão disponíveis e em qual ordem aparecem.
**Model**: `App\Models\PosObra\DisciplinaConfig`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| codigo | string UNIQUE | não | — | Código único da disciplina (ex: `ELE`, `HID`, `CIV`) |
| label | string | não | — | Nome legível da disciplina (ex: "Elétrica") |
| ativo | boolean | não | true | Disciplina ativa para uso em novas pendências? |
| ordem | smallInteger | não | 0 | Ordem de exibição na listagem |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `codigo`

---

## `construtora_disciplina`

**Propósito**: Pivot muitos-para-muitos. Define quais disciplinas cada construtora atende no módulo Pós Obra.
**Model**: *gerenciado via `BelongsToMany` em `DisciplinaConfig` e `Construtora`*

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| construtora_id | bigint FK | não | — | Construtora |
| disciplina_config_id | bigint FK | não | — | Disciplina que a construtora atende |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| construtora_id | construtoras.id | cascade |
| disciplina_config_id | po_disciplinas_config.id | cascade |

### Índices

- UNIQUE: `(construtora_id, disciplina_config_id)`

---

## `po_configuracoes_sla`

**Propósito**: Configuração de SLA (Service Level Agreement) por nível de urgência. Define prazo máximo em horas para resolução de pendências de cada urgência.
**Model**: `App\Models\PosObra\ConfiguracaoSla`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| urgencia | string | não | — | Nível de urgência: `P1`, `P2`, `P3` (Enum: `UrgenciaPendencia`) |
| prazo_horas | smallInteger | não | — | Prazo máximo de resolução em horas |
| ativo | boolean | não | true | Configuração ativa? |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Notas

- P1 = Crítico (menor prazo), P2 = Urgente, P3 = Normal (maior prazo)
- O `PendenciaService` consulta esta tabela para calcular `data_termino` das pendências

---

## `po_pendencias`

**Propósito**: Tabela central do módulo Pós Obra. Representa uma pendência/problema identificado em uma obra após a entrega. Controla todo o ciclo de vida: registro → prazo → execução → aprovação/rejeição.
**Model**: `App\Models\PosObra\Pendencia` (Observer: `PendenciaObserver`)

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| codigo | string UNIQUE | não | — | Código único gerado (formato: `PO-YYYY-XXXX`) |
| obras_id | bigint FK | não | — | Obra onde a pendência foi identificada |
| construtora_id | bigint FK | sim | — | Construtora responsável pela resolução |
| lider_obra_id | bigint FK | sim | — | Líder de obra vinculado (usuário `is_lider_obra = true`) |
| gestor_id | bigint FK | não | — | Gestor Smart Fit responsável pela aprovação |
| disciplina_config_id | bigint FK | sim | — | Disciplina técnica da pendência |
| ticket | string | sim | — | Número do ticket no sistema de suporte (opcional) |
| descricao | text | não | — | Descrição detalhada da pendência |
| observacoes | text | sim | — | Observações adicionais |
| urgencia | string | não | — | Urgência: `P1`, `P2`, `P3` (Enum: `UrgenciaPendencia`) |
| status | string | não | 'REGISTRADA' | Status atual (Enum: `StatusPendencia`) |
| data_inicio | date | sim | — | Data de início prevista para execução |
| data_termino | date | sim | — | Prazo limite para resolução (calculado pelo SLA) |
| data_conclusao | dateTime | sim | — | Data/hora real de conclusão |
| impacto_operacao | boolean | não | false | A pendência impacta a operação da academia? |
| local_especifico | string | sim | — | Local específico dentro da unidade (ex: "Vestiário Masculino") |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Status (Enum: `StatusPendencia`)

| Valor | Descrição |
|-------|-----------|
| `REGISTRADA` | Pendência registrada pelo gestor |
| `PRAZO_INFORMADO` | Construtora informou prazo de resolução |
| `EM_EXECUCAO` | Construtora iniciou a execução |
| `FINALIZACAO_SOLICITADA` | Construtora solicitou aprovação |
| `APROVADA` | Gestor aprovou a conclusão |
| `REJEITADA` | Gestor rejeitou a conclusão |

### Urgência (Enum: `UrgenciaPendencia`)

| Valor | Descrição |
|-------|-----------|
| `P1` | Crítico — maior prioridade, menor prazo SLA |
| `P2` | Urgente |
| `P3` | Normal — menor prioridade, maior prazo SLA |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| obras_id | obras.id | cascade |
| construtora_id | construtoras.id | set null |
| lider_obra_id | users.id | set null |
| gestor_id | users.id | cascade |
| disciplina_config_id | po_disciplinas_config.id | set null |

### Índices

- UNIQUE: `codigo`

---

## `po_anexos_pendencias`

**Propósito**: Fotos e documentos anexados a uma pendência (foto inicial do problema, evidências de resolução).
**Model**: `App\Models\PosObra\AnexoPendencia`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| pendencia_id | bigint FK | não | — | Pendência à qual o anexo pertence |
| tipo | string | não | — | Tipo do anexo: `FOTO_INICIAL` ou `EVIDENCIA` (Enum: `TipoAnexo`) |
| url | string | não | — | URL do arquivo no storage (R2) |
| nome_arquivo | string | sim | — | Nome original do arquivo |
| uploaded_by | bigint FK | não | — | Usuário que fez o upload |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| pendencia_id | po_pendencias.id | cascade |
| uploaded_by | users.id | cascade |

---

## `po_atualizacoes_status`

**Propósito**: Histórico de todas as mudanças de status de uma pendência. Audit trail completo do ciclo de vida.
**Model**: `App\Models\PosObra\AtualizacaoStatus`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| pendencia_id | bigint FK | não | — | Pendência que teve o status alterado |
| status_anterior | string | sim | — | Status antes da mudança (Enum: `StatusPendencia`) |
| status_novo | string | não | — | Novo status (Enum: `StatusPendencia`) |
| comentario | text | sim | — | Comentário sobre a mudança |
| atualizado_por | string | não | — | Identificação de quem atualizou (pode ser "Bot WhatsApp", nome do usuário, etc.) |
| created_at | timestamp | sim | — | Data/hora da mudança |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| pendencia_id | po_pendencias.id | cascade |

### Notas

- `atualizado_por` é texto livre — permite identificar atualizações feitas pelo bot WhatsApp

---

## `po_aprovacoes_finalizacao`

**Propósito**: Registro formal de solicitação e decisão de aprovação/rejeição de finalização de uma pendência.
**Model**: `App\Models\PosObra\AprovacaoFinalizacao`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| pendencia_id | bigint FK | não | — | Pendência em análise |
| solicitado_por | bigint FK | não | — | Usuário que solicitou a finalização (geralmente da construtora) |
| aprovado_por | bigint FK | sim | — | Gestor que tomou a decisão (null = pendente) |
| status | string | não | 'PENDENTE' | Status: `PENDENTE`, `APROVADA`, `REJEITADA` |
| motivo_rejeicao | text | sim | — | Motivo da rejeição (obrigatório quando rejeitado) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| pendencia_id | po_pendencias.id | cascade |
| solicitado_por | users.id | cascade |
| aprovado_por | users.id | set null |

---

## `po_mensagens_whatsapp`

**Propósito**: Mensagens individuais enviadas e recebidas via WhatsApp Cloud API, vinculadas a pendências.
**Model**: `App\Models\PosObra\MensagemWhatsapp`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| pendencia_id | bigint FK | sim | — | Pendência relacionada (null = mensagem fora de contexto) |
| telefone | string | não | — | Número de telefone (formato: 5511999999999) |
| direcao | string | não | — | Direção: `RECEBIDA` (chegou) ou `ENVIADA` (saiu) |
| mensagem | text | sim | — | Conteúdo de texto da mensagem |
| tipo | string | não | 'TEXTO' | Tipo: `TEXTO`, `IMAGEM`, `DOCUMENTO`, `AUDIO` |
| midia_url | string | sim | — | URL da mídia quando tipo ≠ TEXTO |
| status_entrega | string | sim | — | Status de entrega: `ENVIADA`, `ENTREGUE`, `LIDA`, `FALHA` |
| wamid | string | sim | — | ID da mensagem na API do Meta (WhatsApp Message ID) |
| created_at | timestamp | sim | — | Data/hora da mensagem |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| pendencia_id | po_pendencias.id | set null |

### Notas

- `wamid`: identificador único da Meta, usado para rastrear status de entrega e evitar duplicatas

---

## `po_conversas_whatsapp`

**Propósito**: Estado atual da conversa/sessão WhatsApp de um número de telefone com o bot. Controla em qual fase do fluxo o usuário está.
**Model**: `App\Models\PosObra\ConversaWhatsapp`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| telefone | string UNIQUE | não | — | Número de telefone (uma conversa por número) |
| pendencia_id | bigint FK | sim | — | Pendência ativa na conversa |
| perfil | string | não | — | Perfil do usuário: `LIDER`, `CONSTRUTORA`, `GESTOR` |
| fase | string | não | — | Fase atual no fluxo do bot (ex: `AGUARDANDO_PRAZO`, `AGUARDANDO_EVIDENCIAS`) |
| contexto | json | sim | — | Dados de contexto da sessão (ex: pendência em discussão, opções apresentadas) |
| ultima_mensagem_at | dateTime | sim | — | Timestamp da última mensagem recebida (para controle de timeout de sessão) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Chaves estrangeiras

| Coluna | Referencia | On Delete |
|--------|-----------|-----------|
| pendencia_id | po_pendencias.id | set null |

### Índices

- UNIQUE: `telefone`

### Notas

- `contexto`: objeto JSON livre que o `WhatsAppBotService` usa para manter o estado entre mensagens
- Quando o bot detecta inatividade (via `ultima_mensagem_at`), pode resetar o fluxo

---

## `po_whatsapp_config`

**Propósito**: Configuração global da integração WhatsApp Cloud API. Armazena credenciais de forma encriptada.
**Model**: `App\Models\PosObra\WhatsappConfig`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| phone_number_id | string | não | — | ID do número de telefone na API Meta |
| token | text | não | — | Token de acesso à API Meta (armazenado encriptado via Laravel `encrypted` cast) |
| verify_token | string | não | — | Token de verificação do webhook (configurado no painel Meta) |
| ativo | boolean | não | false | Integração ativa? |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Notas

- Apenas um registro deve existir (acessado via `WhatsappConfig::instancia()`)
- `token` é automaticamente encriptado/decriptado pelo Laravel
- `verify_token` é comparado com o valor enviado pelo Meta no handshake do webhook (`GET /webhook/whatsapp`)

---

## `po_whatsapp_bot_mensagens`

**Propósito**: Templates de mensagens do bot WhatsApp. Permite editar textos do bot via painel sem deploy.
**Model**: `App\Models\PosObra\WhatsappBotMensagem`

| Coluna | Tipo | Nullable | Default | Descrição |
|--------|------|----------|---------|-----------|
| id | bigint PK | não | auto | Identificador |
| chave | string(100) UNIQUE | não | — | Chave identificadora do template (ex: `fluxo.lider.saudacao`) |
| texto | text | não | — | Texto da mensagem (pode conter variáveis `{nome}`, `{codigo}`, etc.) |
| created_at | timestamp | sim | — | Data de criação |
| updated_at | timestamp | sim | — | Última atualização |

### Índices

- UNIQUE: `chave`

### Notas

- `WhatsappBotMensagem::get('chave')` busca o texto no banco; se não encontrado, usa constante hardcoded como fallback
- `WhatsappBotMensagem::formatar('chave', ['nome' => 'João'])` interpola variáveis no texto
- Categorias de mensagens: Fluxo Líder, Fluxo Construtora, Fluxo Gestor, Geral, SLA
