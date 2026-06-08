# Módulo: Pós Obra

Módulo event-driven para gerenciamento de pendências pós-construção, com integração WhatsApp para comunicação com construtoras.

## Visão Geral

```
Pendência registrada
      ↓
Notificação WhatsApp → Construtora
      ↓
Prazo informado → Notificação ao Líder
      ↓
Execução iniciada → Notificação ao Líder
      ↓
Finalização solicitada → Aprovação/Rejeição
      ↓
SLA monitorado → Alerta se vencido
```

## Models (app/Models/PosObra/)

### Pendencia
- **Arquivo**: `app/Models/PosObra/Pendencia.php`
- **Tabela**: `po_pendencias`
- **Observer**: `PendenciaObserver` (dispara eventos)
- **Casts**: `urgencia` → `UrgenciaPendencia`, `status` → `StatusPendencia`

#### Relacionamentos

| Relacionamento | Tipo | Destino |
|--------------|------|---------|
| obra | BelongsTo | `Obras` |
| construtora | BelongsTo | `Construtora` |
| liderObra | BelongsTo | `User` |
| gestor | BelongsTo | `User` |
| disciplina | BelongsTo | `DisciplinaConfig` |
| anexos | HasMany | `AnexoPendencia` |
| atualizacoesStatus | HasMany | `AtualizacaoStatus` |
| conversas_whatsapp | HasMany | `ConversaWhatsapp` |

#### Status (Enum: StatusPendencia)
- Pendente
- Prazo Informado
- Em Execução
- Finalização Solicitada
- Aprovada
- Rejeitada

#### Urgência (Enum: UrgenciaPendencia)
- Baixa
- Média
- Alta
- Crítica

### Outros Models

| Model | Descrição |
|-------|-----------|
| `AnexoPendencia` | Anexos/fotos das pendências |
| `AtualizacaoStatus` | Histórico de mudanças de status |
| `ConfiguracaoSla` | Configuração de SLAs por tipo |
| `DisciplinaConfig` | Disciplinas (elétrica, hidráulica, etc.) |
| `ConversaWhatsapp` | Conversa WhatsApp por pendência |
| `MensagemWhatsapp` | Mensagens individuais |
| `WhatsappBotMensagem` | Templates do bot |
| `WhatsappConfig` | Configuração global do WhatsApp |
| `AprovacaoFinalizacao` | Registro de aprovações |

## Eventos & Listeners

### Eventos (app/Events/PosObra/)

| Evento | Quando é disparado |
|--------|-------------------|
| `PendenciaRegistrada` | Nova pendência criada |
| `PrazoInformado` | Construtora informa prazo |
| `ExecucaoIniciada` | Construtora inicia execução |
| `FinalizacaoSolicitada` | Construtora solicita finalização |
| `PendenciaAprovada` | Gestor aprova finalização |
| `PendenciaRejeitada` | Gestor rejeita finalização |
| `SlaVencido` | SLA da pendência venceu |

### Listeners (app/Listeners/PosObra/)

| Listener | Evento | Ação |
|---------|--------|------|
| `NotificarConstrutoraNovasPendencias` | `PendenciaRegistrada` | Notifica construtora via WhatsApp |
| `NotificarLiderPrazoInformado` | `PrazoInformado` | Notifica líder |
| `NotificarLiderExecucaoIniciada` | `ExecucaoIniciada` | Notifica líder |
| `NotificarFinalizacaoSolicitada` | `FinalizacaoSolicitada` | Notifica gestor |
| `NotificarPendenciaAprovada` | `PendenciaAprovada` | Notifica construtora |
| `NotificarPendenciaRejeitada` | `PendenciaRejeitada` | Notifica construtora |
| `NotificarSlaVencido` | `SlaVencido` | Alerta sobre vencimento |

## Services

### WhatsAppService (`app/Services/PosObra/WhatsAppService.php`)
- Integração com WhatsApp Cloud API
- Envio de mensagens de texto e templates
- Registro de conversas e mensagens

### WhatsAppBotService (`app/Services/PosObra/WhatsAppBotService.php`)
- Processamento de mensagens recebidas
- Fluxo automatizado de atualização de pendências
- Página de configuração: `FluxoBotPage`

### PendenciaService (`app/Services/PosObra/PendenciaService.php`)
- Regras de negócio para pendências
- Controle de fluxo de status
- Verificação de SLA

## Filament Resources

### PendenciaResource
- **Arquivo**: `app/Filament/Resources/PosObra/PendenciaResource.php`
- **RelationManagers**: `AnexosRelationManager`, `HistoricoStatusRelationManager`

### ConfiguracaoSlaResource
- **Arquivo**: `app/Filament/Resources/PosObra/ConfiguracaoSlaResource.php`
- Configuração de prazos SLA por tipo de disciplina

### DisciplinaConfigResource
- **Arquivo**: `app/Filament/Resources/PosObra/DisciplinaConfigResource.php`
- Gestão de disciplinas (elétrica, hidráulica, civil, etc.)

## Controller

### WhatsAppWebhookController
- **Arquivo**: `app/Http/Controllers/PosObra/WhatsAppWebhookController.php`
- `GET /webhook/whatsapp` — verificação do webhook (handshake com Meta)
- `POST /webhook/whatsapp` — recebe mensagens do WhatsApp

## Service Provider

### PosObraServiceProvider
- **Arquivo**: `app/Providers/PosObraServiceProvider.php`
- Registra singletons: `WhatsAppService`, `PendenciaService`, `WhatsAppBotService`
- Observa model `Pendencia`
- Mapeia eventos → listeners

## Observer

### PendenciaObserver
- **Arquivo**: `app/Observers/PosObra/PendenciaObserver.php`
- Detecta mudanças de status
- Dispara os eventos de domínio correspondentes

## Configuração do WhatsApp

- **Page Filament**: `WhatsAppConfigPage`
- Token de acesso, número de telefone, verify token
- Configuração de templates de mensagem
