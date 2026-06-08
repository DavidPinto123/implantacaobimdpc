# Eventos, Listeners & Observers

## Arquitetura Event-Driven

Usada principalmente no módulo **Pós Obra**. O `PendenciaObserver` detecta mudanças no model `Pendencia` e dispara eventos de domínio. Listeners processam esses eventos e enviam notificações via WhatsApp.

```
Pendencia (model)
     ↓ mudança detectada
PendenciaObserver
     ↓ dispara evento
Event (PosObra)
     ↓ processado por
Listener (PosObra)
     ↓ executa
WhatsAppService (notificação)
```

## Eventos (app/Events/PosObra/)

| Evento | Disparado quando |
|--------|----------------|
| `PendenciaRegistrada` | Nova pendência é criada |
| `PrazoInformado` | Construtora informa prazo de resolução |
| `ExecucaoIniciada` | Construtora inicia execução da correção |
| `FinalizacaoSolicitada` | Construtora solicita aprovação da finalização |
| `PendenciaAprovada` | Gestor aprova a finalização |
| `PendenciaRejeitada` | Gestor rejeita a finalização |
| `SlaVencido` | Prazo SLA da pendência vence |

## Listeners (app/Listeners/PosObra/)

| Listener | Evento | Ação |
|---------|--------|------|
| `NotificarConstrutoraNovasPendencias` | `PendenciaRegistrada` | Envia WhatsApp para construtora com detalhes |
| `NotificarLiderPrazoInformado` | `PrazoInformado` | Notifica líder de obra |
| `NotificarLiderExecucaoIniciada` | `ExecucaoIniciada` | Notifica líder de obra |
| `NotificarFinalizacaoSolicitada` | `FinalizacaoSolicitada` | Notifica gestor para aprovação |
| `NotificarPendenciaAprovada` | `PendenciaAprovada` | Notifica construtora da aprovação |
| `NotificarPendenciaRejeitada` | `PendenciaRejeitada` | Notifica construtora da rejeição com motivo |
| `NotificarSlaVencido` | `SlaVencido` | Alerta gestor/lider sobre vencimento |

## Observers (app/Observers/)

### ObrasObserver
- **Arquivo**: `app/Observers/ObrasObserver.php`
- Observa: `Obras`, `AtualizacaoObra`
- Registrado em: `AppServiceProvider` ou `AdminPanelProvider`
- Side-effects em mudanças de obras (ex: notificações, cálculos de progresso)

### ControlePedidoObserver
- **Arquivo**: `app/Observers/ControlePedidoObserver.php`
- Observa: `ControlePedido`
- Processa mudanças em pedidos/compras

### RelatorioVisitaTecnicaObserver
- **Arquivo**: `app/Observers/RelatorioVisitaTecnicaObserver.php`
- Observa: `RelatorioVisitaTecnica`
- Side-effects em relatórios de visita (ex: disparo de tarefas, e-mails)

### PendenciaObserver
- **Arquivo**: `app/Observers/PosObra/PendenciaObserver.php`
- Observa: `Pendencia`
- **Ponto central** do módulo Pós Obra
- Detecta transições de `status` e dispara eventos correspondentes
- Registrado via `PosObraServiceProvider`

## Registro dos Eventos (PosObraServiceProvider)

```php
// app/Providers/PosObraServiceProvider.php
protected $listen = [
    PendenciaRegistrada::class    => [NotificarConstrutoraNovasPendencias::class],
    PrazoInformado::class         => [NotificarLiderPrazoInformado::class],
    ExecucaoIniciada::class       => [NotificarLiderExecucaoIniciada::class],
    FinalizacaoSolicitada::class  => [NotificarFinalizacaoSolicitada::class],
    PendenciaAprovada::class      => [NotificarPendenciaAprovada::class],
    PendenciaRejeitada::class     => [NotificarPendenciaRejeitada::class],
    SlaVencido::class             => [NotificarSlaVencido::class],
];
```
